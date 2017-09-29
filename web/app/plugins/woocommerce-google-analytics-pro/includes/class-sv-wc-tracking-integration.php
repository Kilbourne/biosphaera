<?php
/**
 * WooCommerce Google Analytics Pro
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Google Analytics Pro to newer
 * versions in the future. If you wish to customize WooCommerce Google Analytics Pro for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-google-analytics-pro/ for more information.
 *
 * @package     WC-Google-Analytics-Pro/Integration
 * @author      SkyVerge
 * @copyright   Copyright (c) 2015-2016, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Base Tracking Integration class
 *
 * Provides basic setup, form fields and hooks
 * for tracking plugins.
 *
 * The subclass should at least provide a constructor that sets the
 * integration id, method_title and method_description, and implement
 * a `get_plugin()` method, which should return the integration plugin
 * instance.
 *
 * @since 1.0.0
 */
class SV_WC_Tracking_Integration extends WC_Integration {


	/** @var \SV_WC_API_Base instance */
	protected $api;

	/** @var int Order ID that was fully refunded **/
	protected $order_fully_refunded = null;

	/** @var array of event names */
	public $event_name = array();

	/** @var array of property names */
	public $property_name = array();


	/**
	 * Constructor
	 *
	 * Should be overriden and called from whithin subclasses, so that
	 * they can set up the integration id, method title & description
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Integration ID
	 * @param string $title Integration title
	 * @param string $description Integration description
	 */
	public function __construct( $id, $title, $description ) {

		// setup integration
		$this->id                 = $id;
		$this->method_title       = $title;
		$this->method_description = $description;

		// load admin form
		$this->init_form_fields();

		// load settings
		$this->init_settings();

		// load event / property names
		foreach ( $this->settings as $key => $value ) {

			if ( strpos( $key, 'event_name' ) !== false ) {

				// event name setting, remove '_event_name' and use as key
				$key = str_replace( '_event_name', '', $key );
				$this->event_name[ $key ] = $value;

			} elseif ( strpos( $key, 'property_name' ) !== false ) {

				// property name setting, remove '_property_name' and use as key
				$key = str_replace( '_property_name', '', $key );
				$this->property_name[ $key ] = $value;
			}
		}


		// add hooks to record events - only add hook if event name is populated

		// pageviews
		add_action( 'wp_head', array( $this, 'pageview' ) );

		// viewed homepage
		if ( $this->event_name['viewed_homepage'] ) {
			add_action( 'wp_head', array( $this, 'viewed_homepage' ) );
		}

		// signed in
		if ( $this->event_name['signed_in'] ) {
			add_action( 'wp_login', array( $this, 'signed_in' ), 10, 2 );
		}

		// signed out
		if ( $this->event_name['signed_out'] ) {
			add_action( 'wp_logout', array( $this, 'signed_out' ) );
		}

		// viewed Signup page (on my account page, if enabled)
		if ( $this->event_name['viewed_signup'] ) {
			add_action( 'register_form', array( $this, 'viewed_signup' ) );
		}

		// signed up for new account (on my account page if enabled OR during checkout)
		if ( $this->event_name['signed_up'] ) {
			add_action( 'user_register', array( $this, 'signed_up' ) );
		}

		// viewed product (properties: Name)
		if ( $this->event_name['viewed_product'] ) {
			add_action( 'woocommerce_after_single_product_summary', array( $this, 'viewed_product' ), 1 );
		}

		// clicked product in listing
		if ( $this->event_name['clicked_product'] ) {
			add_action( 'woocommerce_before_shop_loop_item', array( $this, 'clicked_product' ) );
		}

		// added product to cart (properties: Product Name, Quantity)
		if ( $this->event_name['added_to_cart'] ) {

			// single product add to cart button
			add_action( 'woocommerce_add_to_cart', array( $this, 'added_to_cart' ), 10, 6 );

			// AJAX add to cart
			if ( is_ajax() ) {
				add_action( 'woocommerce_ajax_added_to_cart', array( $this, 'ajax_added_to_cart' ) );
			}
		}

		// removed product from cart (Properties: Product Name)
		if ( $this->event_name['removed_from_cart'] ) {
			add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'removed_from_cart' ) );
			add_action( 'woocommerce_remove_cart_item',               array( $this, 'removed_from_cart' ) );
		}

		// changed quantity of product in cart (properties: Product Name, Quantity )
		if ( $this->event_name['changed_cart_quantity'] ) {
			add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'changed_cart_quantity' ), 10, 2 );
		}

		// viewed cart
		if ( $this->event_name['viewed_cart'] ) {
			add_action( 'woocommerce_after_cart_contents', array( $this, 'viewed_cart' ) );
			add_action( 'woocommerce_cart_is_empty', array( $this, 'viewed_cart' ) );
		}

		// started checkout
		if ( $this->event_name['started_checkout'] ) {
			add_action( 'woocommerce_after_checkout_form', array( $this, 'started_checkout' ) );
		}

		// started payment (for gateways that direct post from payment page, eg: Braintree TR, Authorize.net AIM, etc
		if ( $this->event_name['started_payment'] ) {
			add_action( 'after_woocommerce_pay', array( $this, 'started_payment' ) );
		}

		// completed purchase
		if ( $this->event_name['completed_purchase'] ) {

			// most orders will call payment complete
			add_action( 'woocommerce_payment_complete', array( $this, 'completed_purchase' ) );

			// catch orders where the order is placed but not yet paid
			add_action( 'woocommerce_order_status_on-hold', array( $this, 'completed_purchase' ) );

			// catch orders where the payment previously failed and was manually changed by the admin
			add_action( 'woocommerce_order_status_failed_to_processing', array( $this, 'completed_purchase' ) );
			add_action( 'woocommerce_order_status_failed_to_completed',  array( $this, 'completed_purchase' ) );
		}

		// completed payment
		if ( $this->event_name['completed_payment'] ) {

			add_action( 'woocommerce_order_status_processing',            array( $this, 'completed_payment' ) );
			add_action( 'woocommerce_order_status_on-hold_to_completed',  array( $this, 'completed_payment' ) );
		}

		// wrote review or commented (properties: Product Name if review, Post Title if blog post)
		if ( $this->event_name['wrote_review'] || $this->event_name['commented'] ) {
			add_action( 'comment_post', array( $this, 'wrote_review_or_commented' ) );
		}

		// viewed account
		if ( $this->event_name['viewed_account'] ) {
			add_action( 'woocommerce_after_my_account', array( $this, 'viewed_account' ) );
		}

		// viewed order
		if ( $this->event_name['viewed_order'] ) {
			add_action( 'woocommerce_view_order', array( $this, 'viewed_order' ) );
		}

		// updated address
		if ( $this->event_name['updated_address'] ) {
			add_action( 'woocommerce_customer_save_address', array( $this, 'updated_address' ) );
		}

		// changed password
		if ( $this->event_name['changed_password'] && ! empty( $_POST['password_1'] ) ) {
			add_action( 'woocommerce_save_account_details', array( $this, 'changed_password' ) );
		}

		// applied coupon
		if ( $this->event_name['applied_coupon'] ) {
			add_action( 'woocommerce_applied_coupon', array( $this, 'applied_coupon' ) );
		}

		// removed coupon
		if ( $this->event_name['removed_coupon'] && ! empty( $_GET['remove_coupon'] ) ) {
			add_action( 'woocommerce_init', array( $this, 'removed_coupon' ) );
		}

		// tracked order
		if ( $this->event_name['tracked_order'] ) {
			add_action( 'woocommerce_track_order', array( $this, 'tracked_order' ) );
		}

		// estimated shipping
		if ( $this->event_name['estimated_shipping'] ) {
			add_action( 'woocommerce_calculated_shipping', array( $this, 'estimated_shipping' ) );
		}

		// cancelled Oorder
		if ( $this->event_name['cancelled_order'] ) {
			add_action( 'woocommerce_cancelled_order', array( $this, 'cancelled_order' ) );
		}

		// order refunded
		if ( $this->event_name['order_refunded'] ) {
			add_filter( 'woocommerce_order_fully_refunded_status', array( $this, 'track_full_refunds' ), 10, 3 );
			add_action( 'woocommerce_order_refunded',              array( $this, 'order_refunded' ), 10, 2 );
		}

		// reordered previous order
		if ( $this->event_name['reordered'] ) {
			add_action( 'woocommerce_ordered_again', array( $this, 'reordered' ) );
		}

		// save admin options
		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		// ensure Google AdWords doesn't mistake offsite gateways as the referrer
		add_filter( 'woocommerce_get_return_url', array( $this, 'adwords_referrer_remove_gateways' ) );
	}


	/** Helper methods ********************************************************/


	/**
	 * Returns true if the integration is enabled.
	 *
	 * @since 1.0.0
	 * @return bool true if integration is enabled, false otherwise.
	 */
	public function is_enabled() {

		return 'yes' === $this->get_option( 'enabled' );
	}


	/**
	 * Returns the category hierarchy up to 5 levels deep for the passed product
	 *
	 * @since 1.1.1
	 * @return string The category hierarchy or empty string
	 */
	public function get_category_hierarchy( $product ) {

		$categories = wc_get_product_terms( $product->id, 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );

		if ( ! is_array( $categories ) || empty( $categories ) ) {
			return '';
		}

		$child_term = $categories[0];

		return trim( $this->get_category_parents( $child_term->term_id ), '/' );

	}


	/**
	 * Builds the category hierarchy recursively
	 * Inspired by get_category_parents() in WordPress core
	 *
	 * @since 1.1.1
	 * @return string The category hierarchy
	 */
	private function get_category_parents( $term_id, $separator = '/', $visited = array() ) {

		$chain  = '';
		$parent = get_term( $term_id, 'product_cat' );

		if ( is_wp_error( $parent ) ) {
			return $parent;
		}

		$name = $parent->name;

		if ( $parent->parent && ( $parent->parent !== $parent->term_id ) && ! in_array( $parent->parent, $visited ) && count( $visited ) < 4 ) {

			$visited[] = $parent->parent;

			$chain .= $this->get_category_parents( $parent->parent, $separator, $visited );
		}

		$chain .= $name . $separator;

		return $chain;
	}


	/** General methods *******************************************************/


	/**
	 * Ensure Google Adwords doesn't mistake the offsite gateway as the referrer
	 * by adding the `utm_nooverride` parameter
	 *
	 * @param  string $return_url WooCommerce Return URL
	 *
	 * @return string URL
	 */
	public function adwords_referrer_remove_gateways( $return_url ) {

		$return_url = remove_query_arg( 'utm_nooverride', $return_url );

		$return_url = add_query_arg( 'utm_nooverride', '1', $return_url );

		return $return_url;
	}


	/** Settings **************************************************************/


	/**
	 * Initializes form fields in the format required by WC_Integration.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {

		// add fields for event names
		$this->form_fields = array(

			'additional_settings_section' => array(
				'title'       => esc_html__( 'Additional Settings', 'woocommerce-google-analytics-pro' ),
				'type'        => 'title',
			),

			'debug_mode' => array(
				'title'       => __( 'Debug Mode', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'This logs API requests/responses to the WooCommerce log. Please only enable this if you are having issues.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'select',
				'default'     => 'off',
				'options'     => array(
					'off' => __( 'Off', 'woocommerce-google-analytics-pro' ),
					'on'  => __( 'On', 'woocommerce-google-analytics-pro' ),
				),
			),

			'event_names_section' => array(
				'title'       => __( 'Event Names', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Customize the event names. Leave a field blank to disable tracking of that event.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'title',
			),

			'signed_in_event_name' => array(
				'title'       => __( 'Signed In', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer signs in.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'signed in'
			),

			'signed_out_event_name' => array(
				'title'       => __( 'Signed Out', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer signs out.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'signed out'
			),

			'viewed_signup_event_name' => array(
				'title'       => __( 'Viewed Signup', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views the registration form.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'viewed signup'
			),

			'signed_up_event_name' => array(
				'title'       => __( 'Signed Up', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer registers a new account.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'signed up'
			),

			'viewed_homepage_event_name' => array(
				'title'       => __( 'Viewed Homepage', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views the homepage.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'viewed homepage'
			),

			'viewed_product_event_name' => array(
				'title'       => __( 'Viewed Product', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views a single product', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'viewed product'
			),

			'clicked_product_event_name' => array(
				'title'       => __( 'Clicked Product', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer clicks a product in listing, such as search results or related products.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'clicked product'
			),

			'added_to_cart_event_name' => array(
				'title'       => __( 'Added to Cart', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer adds an item to the cart.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'added to cart'
			),

			'removed_from_cart_event_name' => array(
				'title'       => __( 'Removed from Cart', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer removes an item from the cart.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'removed from cart'
			),

			'changed_cart_quantity_event_name' => array(
				'title'       => __( 'Changed Cart Quantity', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer changes the quantity of an item in the cart.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'changed cart quantity'
			),

			'viewed_cart_event_name' => array(
				'title'       => __( 'Viewed Cart', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views the cart.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'viewed cart'
			),

			'applied_coupon_event_name' => array(
				'title'       => __( 'Applied Coupon', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer applies a coupon', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'applied coupon'
			),

			'removed_coupon_event_name' => array(
				'title'       => __( 'Removed Coupon', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer removes a coupon', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'removed coupon'
			),

			'started_checkout_event_name' => array(
				'title'       => __( 'Started Checkout', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer starts the checkout.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'started checkout'
			),

			'started_payment_event_name' => array(
				'title'       => __( 'Started Payment', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views the payment page (used with direct post payment gateways)', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'started payment'
			),

			'completed_purchase_event_name' => array(
				'title'       => __( 'Completed Purchase', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer completes a purchase.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'completed purchase'
			),

			'completed_payment_event_name' => array(
				'title'       => __( 'Completed Payment', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer completes payment for their purchase.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'completed payment',
			),

			'wrote_review_event_name' => array(
				'title'       => __( 'Wrote Review', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer writes a review.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'wrote review'
			),

			'commented_event_name' => array(
				'title'       => __( 'Commented', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer writes a comment.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'commented'
			),

			'viewed_account_event_name' => array(
				'title'       => __( 'Viewed Account', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views the My Account page.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'viewed account'
			),

			'viewed_order_event_name' => array(
				'title'       => __( 'Viewed Order', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer views an order', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'viewed order'
			),

			'updated_address_event_name' => array(
				'title'       => __( 'Updated Address', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer updates their address.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'updated address'
			),

			'changed_password_event_name' => array(
				'title'       => __( 'Changed Password', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer changes their password.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'changed password'
			),

			'estimated_shipping_event_name' => array(
				'title'       => __( 'Estimated Shipping', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer estimates shipping.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'estimated shipping'
			),

			'tracked_order_event_name' => array(
				'title'       => __( 'Tracked Order', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer tracks an order.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'tracked order'
			),

			'cancelled_order_event_name' => array(
				'title'       => __( 'Cancelled Order', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer cancels an order.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'cancelled order'
			),

			'order_refunded_event_name' => array(
				'title'       => __( 'Order Refunded', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when an order is refunded.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'order refunded'
			),

			'reordered_event_name' => array(
				'title'       => __( 'Reordered', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Triggered when a customer reorders a previous order.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => 'reordered'
			),
		);

		// add fields for property names
		if ( $this->supports_property_names() ) {

			$this->form_fields = array_merge( $this->form_fields, array(
				'property_names_section' => array(
					'title'       => __( 'Property Names', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Customize the property names. Leave a field blank to disable tracking of that property.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'section',
					'default'     => ''
				),

				'product_name_property_name' => array(
					'title'       => __( 'Product Name', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer views a product, adds / removes / changes quantities in the cart, or writes a review.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'product name'
				),

				'quantity_property_name' => array(
					'title'       => __( 'Product Quantity', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer adds a product to their cart or changes the quantity in their cart.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'quantity'
				),

				'category_property_name' => array(
					'title'       => __( 'Product Category', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer adds a product to their cart.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'category'
				),

				'coupon_code_property_name' => array(
					'title'       => __( 'Coupon Code', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer applies a coupon.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'coupon code'
				),

				'order_id_property_name' => array(
					'title'       => __( 'Order ID', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer completes their purchase.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'order id'
				),

				'order_total_property_name' => array(
					'title'       => __( 'Order Total', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer completes their purchase.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'order total'
				),

				'shipping_total_property_name' => array(
					'title'       => __( 'Shipping Total', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer completes their purchase.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'shipping total'
				),

				'total_quantity_property_name' => array(
					'title'       => __( 'Total Quantity', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer completes their purchase.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'total quantity'
				),

				'payment_method_property_name' => array(
					'title'       => __( 'Payment Method', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer completes their purchase.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'payment method'
				),

				'post_title_property_name' => array(
					'title'       => __( 'Post Title', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer leaves a comment on a post.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'post title'
				),

				'country_property_name' => array(
					'title'       => __( 'Shipping Country', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer estimates shipping.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'country'
				),

				'purchased_product_sku_property_name' => array(
					'title'       => __( 'Purchased SKU', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer purchases the product.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'purchased product sku'
				),

				'purchased_product_name_property_name' => array(
					'title'       => __( 'Purchased Product Name', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer purchases the product.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'purchased product name'
				),

				'purchased_product_category_property_name' => array(
					'title'       => __( 'Purchased Category', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer purchases the product.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'purchased product category'
				),

				'purchased_product_price_property_name' => array(
					'title'       => __( 'Purchased Price', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer purchases the product.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'purchased product price'
				),

				'purchased_product_qty_property_name' => array(
					'title'       => __( 'Purchased Quantity', 'woocommerce-google-analytics-pro' ),
					'description' => __( 'Tracked when a customer purchases the product.', 'woocommerce-google-analytics-pro' ),
					'type'        => 'text',
					'default'     => 'purchased product quantity'
				)

			) );
		}
	}


	/**
	 * Check if this tracking integration supports property names
	 *
	 * @since 1.0.0
	 * @return bool True, if supports property names, false otherwise
	 */
	protected function supports_property_names() {
		return true;
	}


	/**
	 * Track when an order was fully refunded
	 *
	 * @param string $ignore Ignore, pass-thru
	 * @param int $order_id
	 * @param int $refund_id
	 * @return string
	 */
	public function track_full_refunds( $ignore, $order_id, $refund_id ) {

		$this->order_fully_refunded = $order_id;

		return $ignore;
	}


	/**
	 * Returns true if debug mode is enabled
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function debug_mode_on() {

		return 'yes' === $this->get_option( 'debug_mode', 'no' );
	}


}
