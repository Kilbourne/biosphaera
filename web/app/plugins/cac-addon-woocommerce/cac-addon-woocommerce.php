<?php
/*
Plugin Name: 		Admin Columns - WooCommerce add-on
Version: 			1.4
Description: 		Enhance your product and order overviews with new columns, and edit products directly from the overview page. WooCommerce integration Add-on for Admin Columns Pro.
Author: 			Codepress
Author URI: 		https://admincolumns.com
Text Domain: 		codepress-admin-columns
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit when accessed directly
}

// Plugin information
define( 'CAC_WC_VERSION', '1.4' );
define( 'CAC_WC_FILE', __FILE__ );
define( 'CAC_WC_URL', plugin_dir_url( __FILE__ ) );
define( 'CAC_WC_DIR', plugin_dir_path( __FILE__ ) );

require CAC_WC_DIR . 'classes/helpers/orders.php';

/**
 * Main ACF Addon plugin class
 *
 * @since 1.0
 */
class CPAC_Addon_WC {

	/**
	 * Admin Columns main plugin class instance
	 *
	 * @since 1.0
	 * @var CPAC
	 */
	public $cpac;

	/**
	 * Main plugin directory
	 *
	 * @since 1.0
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * WooCommerce posttypes
	 *
	 * @since 1.2
	 * @var array
	 */
	private $post_types;

	/**
	 * WooCommerce taxonomies
	 *
	 * @since 1.2
	 * @var array
	 */
	private $taxonomies;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct() {

		$this->plugin_basename = plugin_basename( __FILE__ );

		// load translations from pro version
		if ( defined( 'CAC_PRO_URL' ) ) {
			load_plugin_textdomain( 'codepress-admin-columns', false, CAC_PRO_URL . 'languages/' );
		}

		// set wc post types
		$this->post_types = array( 'product', 'shop_order', 'shop_coupon' );
		$this->taxonomies = array( 'product_cat', 'product_tag', 'product_shipping_class' );

		// Admin Columns-dependent setup
		add_action( 'cac/loaded', array( $this, 'init' ) );

		// Hooks
		add_action( 'after_plugin_row_' . $this->plugin_basename, array( $this, 'display_plugin_row_notices' ), 11 );
		add_filter( 'cac/columns/custom', array( $this, 'add_columns' ), 10, 2 );
		add_filter( 'cac/storage_models', array( $this, 'set_menu_type' ) );
		add_filter( 'cac/grouped_columns', array( $this, 'grouped_columns_sort' ) );
		add_filter( 'cac/default_column_names', array( $this, 'default_column_names' ), 10, 2 );
	}

	/**
	 * Init
	 *
	 * @since 1.0
	 */
	public function init( $cpac ) {
		$this->cpac = $cpac;

		$this->maybe_upgrade();
		$this->after_setup();

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/**
	 * @since 1.3
	 */
	public function scripts() {
		if ( $this->cpac->is_columns_screen() ) {
			wp_enqueue_style( 'cac-wc-column', CAC_WC_URL . 'assets/css/column.css' );
		}
	}

	public function grouped_columns_sort( $grouped_columns ) {
		$label = __( 'WooCommerce', 'woocommerce' );

		if ( isset( $grouped_columns[ $label ] ) ) {
			$acf[ $label ] = $grouped_columns[ $label ];
			unset( $grouped_columns[ $label ] );
			$grouped_columns = $acf + $grouped_columns;
		}

		return $grouped_columns;
	}

	/**
	 * Set the menu type to woocommerce
	 *
	 * @since 1.2
	 */
	public function set_menu_type( $storage_models ) {
		if ( $this->is_woocommerce_active() ) {
			foreach ( $storage_models as $k => $storage_model ) {
				if ( in_array( $storage_model->get_post_type(), $this->post_types ) ) {
					$storage_models[ $k ] = $storage_model->set_menu_type( 'Woocommerce' );
				}
			}
		}

		return $storage_models;
	}

	/**
	 * @since 1.1
	 */
	public function maybe_upgrade() {

		$db_version = get_option( 'cac/woocommerce/version' );

		if ( version_compare( CAC_WC_VERSION, $db_version ) !== 0 ) {
			if ( version_compare( CAC_WC_VERSION, '1.1', '>=' ) && version_compare( $db_version, '1.1', '<' ) ) {
				$mappings = array(
					'column-wc-price' => 'price',
					'column-wc-sku'   => 'sku',
					'column-wc-stock' => 'is_in_stock'
				);

				foreach ( $this->post_types as $type ) {
					if ( $columns = get_option( 'cpac_options_' . $type ) ) {
						foreach ( $columns as $index => $column ) {
							if ( isset( $mappings[ $column['type'] ] ) ) {
								$columns[ $index ]['type'] = $mappings[ $column['type'] ];
							}

							if ( isset( $mappings[ $index ] ) ) {
								$columns = cpac_array_key_replace( $columns, $index, $mappings[ $index ] );
							}
						}

						update_option( 'cpac_options_' . $type, $columns );
					}
				}
			}

			update_option( 'cac/woocommerce/version', CAC_WC_VERSION );
		}
	}

	/**
	 * Fire callbacks for plugin setup completion
	 *
	 * @since 1.0
	 */
	public function after_setup() {

		/**
		 * Fires when the Admin Columns WooCommerce plugin is fully loaded
		 *
		 * @since 1.0
		 *
		 * @param CPAC_WC $cpac_wc_instance Main Admin Columns WooCommerce plugin class instance
		 */
		do_action( 'cpac-wc/loaded', $this );
	}

	/**
	 * Add custom columns
	 *
	 * @since 1.0
	 */
	public function add_columns( $columns, $storage_model ) {

		if ( $this->is_woocommerce_active() ) {

			require_once CAC_WC_DIR . 'classes/column/wc-column.php';
			require_once CAC_WC_DIR . 'classes/column/wc-column-default.php';

			// Product columns
			if ( 'product' == $storage_model->key ) {
				$columns['CPAC_WC_Column_Post_Attributes'] = CAC_WC_DIR . 'classes/column/product/attributes.php';
				$columns['CPAC_WC_Column_Post_Reviews_Enabled'] = CAC_WC_DIR . 'classes/column/product/reviews-enabled.php';
				$columns['CPAC_WC_Column_Post_Featured'] = CAC_WC_DIR . 'classes/column/product/featured.php';
				$columns['CPAC_WC_Column_Post_Weight'] = CAC_WC_DIR . 'classes/column/product/weight.php';
				$columns['CPAC_WC_Column_Post_Dimensions'] = CAC_WC_DIR . 'classes/column/product/dimensions.php';
				$columns['CPAC_WC_Column_Post_Backorders_Allowed'] = CAC_WC_DIR . 'classes/column/product/backorders-allowed.php';
				$columns['CPAC_WC_Column_Post_Order_Count'] = CAC_WC_DIR . 'classes/column/product/order-count.php';
				$columns['CPAC_WC_Column_Post_Order_Total'] = CAC_WC_DIR . 'classes/column/product/order-total.php';
				$columns['CPAC_WC_Column_Post_Parent'] = CAC_WC_DIR . 'classes/column/product/parent.php';
				$columns['CPAC_WC_Column_Post_Price'] = CAC_WC_DIR . 'classes/column/product/price.php';
				$columns['CPAC_WC_Column_Post_Shipping_Class'] = CAC_WC_DIR . 'classes/column/product/shipping-class.php';
				$columns['CPAC_WC_Column_Post_SKU'] = CAC_WC_DIR . 'classes/column/product/sku.php';
				$columns['CPAC_WC_Column_Post_Stock'] = CAC_WC_DIR . 'classes/column/product/stock.php';
				$columns['CPAC_WC_Column_Post_Stock_Status'] = CAC_WC_DIR . 'classes/column/product/stock-status.php';
				$columns['CPAC_WC_Column_Post_Upsells'] = CAC_WC_DIR . 'classes/column/product/upsells.php';
				$columns['CPAC_WC_Column_Post_Visibility'] = CAC_WC_DIR . 'classes/column/product/visibility.php';
				$columns['CPAC_WC_Column_Post_Tax_Status'] = CAC_WC_DIR . 'classes/column/product/tax-status.php';
				$columns['CPAC_WC_Column_Post_Thumb'] = CAC_WC_DIR . 'classes/column/product/thumb.php';
				$columns['CPAC_WC_Column_Post_Tax_Class'] = CAC_WC_DIR . 'classes/column/product/tax-class.php';
				$columns['CPAC_WC_Column_Post_Crosssells'] = CAC_WC_DIR . 'classes/column/product/crosssells.php';
				$columns['CPAC_WC_Column_Post_Product_Type'] = CAC_WC_DIR . 'classes/column/product/product-type.php';
				$columns['CPAC_WC_Column_Post_Variation'] = CAC_WC_DIR . 'classes/column/product/variation.php';
			}

			// Order columns
			if ( 'shop_order' == $storage_model->key ) {
				$columns['CPAC_WC_Column_Post_Order_Discount'] = CAC_WC_DIR . 'classes/column/shop_order/order-discount.php';
				$columns['CPAC_WC_Column_Post_Product_Thumbnails'] = CAC_WC_DIR . 'classes/column/shop_order/product-thumbnails.php';
				$columns['CPAC_WC_Column_Post_Product'] = CAC_WC_DIR . 'classes/column/shop_order/product.php';
				$columns['CPAC_WC_Column_Post_Product_Details'] = CAC_WC_DIR . 'classes/column/shop_order/product-details.php';
				$columns['CPAC_WC_Column_Post_Order_Coupons_Used'] = CAC_WC_DIR . 'classes/column/shop_order/order-coupons-used.php';
				$columns['CPAC_WC_Column_Post_Order_Status'] = CAC_WC_DIR . 'classes/column/shop_order/order-status.php';
				$columns['CPAC_WC_Column_Post_Transaction_ID'] = CAC_WC_DIR . 'classes/column/shop_order/transaction-id.php';
				$columns['CPAC_WC_Column_Post_Payment_Method'] = CAC_WC_DIR . 'classes/column/shop_order/payment-method.php';
				$columns['CPAC_WC_Column_Post_Order_Shipping_Method'] = CAC_WC_DIR . 'classes/column/shop_order/shipping-method.php';
			}

			// Coupon columns
			if ( 'shop_coupon' == $storage_model->key ) {
				$columns['CPAC_WC_Column_Post_Usage'] = CAC_WC_DIR . 'classes/column/shop_coupon/usage.php';
				$columns['CPAC_WC_Column_Post_Description'] = CAC_WC_DIR . 'classes/column/shop_coupon/description.php';
				$columns['CPAC_WC_Column_Post_Amount'] = CAC_WC_DIR . 'classes/column/shop_coupon/amount.php';
				$columns['CPAC_WC_Column_Post_Coupon_Code'] = CAC_WC_DIR . 'classes/column/shop_coupon/coupon-code.php';
				$columns['CPAC_WC_Column_Post_Type'] = CAC_WC_DIR . 'classes/column/shop_coupon/type.php';
				$columns['CPAC_WC_Column_Post_Expiry_Date'] = CAC_WC_DIR . 'classes/column/shop_coupon/expiry-date.php';
				$columns['CPAC_WC_Column_Post_Free_Shipping'] = CAC_WC_DIR . 'classes/column/shop_coupon/free-shipping.php';
				$columns['CPAC_WC_Column_Post_Apply_Before_Tax'] = CAC_WC_DIR . 'classes/column/shop_coupon/apply-before-tax.php';
				$columns['CPAC_WC_Column_Post_Include_Products'] = CAC_WC_DIR . 'classes/column/shop_coupon/include-products.php';
				$columns['CPAC_WC_Column_Post_Exclude_Products'] = CAC_WC_DIR . 'classes/column/shop_coupon/exclude-products.php';
				$columns['CPAC_WC_Column_Post_Minimum_Amount'] = CAC_WC_DIR . 'classes/column/shop_coupon/minimum-amount.php';
			}

			// Coupon columns
			if ( 'wp-users' == $storage_model->key ) {
				$columns['CPAC_WC_Column_User_Orders'] = CAC_WC_DIR . 'classes/column/user/orders.php';
				$columns['CPAC_WC_Column_User_Total_Sales'] = CAC_WC_DIR . 'classes/column/user/total-sales.php';
				$columns['CPAC_WC_Column_User_Order_Count'] = CAC_WC_DIR . 'classes/column/user/order-count.php';
				$columns['CPAC_WC_Column_User_Coupons_Used'] = CAC_WC_DIR . 'classes/column/user/coupons-used.php';
				//$columns['CPAC_WC_Column_User_Order_Custom_Field'] = CAC_WC_DIR . 'classes/column/user/order-custom-field.php';
			}

			// Remove WooCommerce placeholder column
			if ( isset( $columns['CPAC_Column_WC_Placeholder'] ) ) {
				unset( $columns['CPAC_Column_WC_Placeholder'] );
			}
		}

		return $columns;
	}

	/**
	 * Group default WooCommerce column types into the WooCommerce column type group
	 *
	 * @since 1.0
	 */
	public function default_column_names( $columns, $storage_model ) {
		switch ( $storage_model->get_post_type() ) {
			case 'product' :
				$columns = array( 'thumb', 'name', 'sku', 'price', 'product_cat', 'product_tag', 'featured', 'product_type', 'date' );
				break;
			case 'shop_order' :
				$columns = array( 'customer_message', 'order_notes', 'order_status', 'order_actions', 'order_date', 'order_title', 'order_items', 'shipping_address', 'order_total', 'billing_address' );
				break;
			case 'shop_coupon' :
				$columns = array( 'coupon_code', 'amount', 'type', 'description', 'expiry_date', 'products', 'usage' );
				break;
		}

		return $columns;
	}

	/**
	 * Whether the main plugin is active
	 *
	 * @since 1.0
	 *
	 * @return bool Returns true if the main Admin Columns plugin is active, false otherwise
	 */
	public function is_cpac_active() {
		return class_exists( 'CPAC', false );
	}

	/**
	 * Whether WooCommerce is active
	 *
	 * @since 1.0
	 *
	 * @return bool Returns true if WooCommerce is active, false otherwise
	 */
	public function is_woocommerce_active() {
		return class_exists( 'WooCommerce', false );
	}

	/**
	 * Shows a message below the plugin on the plugins page
	 *
	 * @since 1.0
	 */
	public function display_plugin_row_notices() {

		// Display notice for missing dependencies
		$missing_dependencies = array();

		if ( ! $this->is_cpac_active() ) {
			$missing_dependencies[] = '<a href="' . admin_url( 'plugin-install.php' ) . '?tab=search&s=Admin+Columns&plugin-search-input=Search+Plugins' . '" target="_blank">' . __( 'Admin Columns', 'codepress-admin-columns' ) . '</a>';
		}

		if ( ! $this->is_woocommerce_active() ) {
			$missing_dependencies[] = '<a href="' . admin_url( 'plugin-install.php' ) . '?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' . '" target="_blank">' . __( 'WooCommerce', 'codepress-admin-columns' ) . '</a>';
		}

		if ( ! empty( $missing_dependencies ) ) {
			if ( count( $missing_dependencies ) === 1 ) {
				$missing_list = $missing_dependencies[0];
			}
			else {
				$missing_list = implode( ', ', array_slice( $missing_dependencies, 0, - 1 ) );
				$missing_list = sprintf( __( '%s and %s', 'codepress-admin-columns' ), $missing_list, implode( '', array_slice( $missing_dependencies, - 1 ) ) );
			}

			?>
			<tr class="plugin-update-tr">
				<td colspan="3" class="plugin-update">
					<div class="update-message">
						<?php printf( __( 'The WooCommerce add-on is enabled but not effective. It requires %s in order to work.', 'codepress-admin-columns' ), $missing_list ); ?>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Get column mappings indicating default columns to be replaced by custom columns
	 *
	 * @since 1.0
	 *
	 * @return array Column mappings ([original type] => [replacement type])
	 */
	public function get_column_mappings() {

		return array(
			'price'       => 'column-wc-price',
			'sku'         => 'column-wc-sku',
			'is_in_stock' => 'column-wc-stock',
			'usage'       => 'column-wc-usage',
			'description' => 'column-wc-description',
			'amount'      => 'column-wc-amount',
			'type'        => 'column-wc-type',
			'coupon_code' => 'column-wc-coupon_code'
		);
	}
}

new CPAC_Addon_WC();