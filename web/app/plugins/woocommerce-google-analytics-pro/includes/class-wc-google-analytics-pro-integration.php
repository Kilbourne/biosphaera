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
 * Google Analytics Pro Integration class
 *
 * Handles settings and provides common tracking functions
 * needed by enhanced eCommerce tracking
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro_Integration extends SV_WC_Tracking_Integration {


	/** @var string URL to Google Analytics Pro Authentication proxy */
	const PROXY_URL = 'https://wc-google-analytics-pro-proxy.herokuapp.com';

	/** @var string Yoast's GA tracking type, Universal or old 'ga.js'. Default is empty string, which means that Yoast tracking is inactive. */
	private $_yoast_ga_tracking_type = '';

	/** @var \WC_Google_Analytics_Pro_Email_Tracking instance **/
	public $email_tracking;

	/** @var array cache for user tracking status **/
	private $user_tracking_enabled = array();

	/** @var object Google Client instance **/
	private $ga_client;

	/** @var object Google_Service_Analytics instance **/
	private $analytics;

	/** @var array associative array of queued tracking JavaScript **/
	private $queued_js = array();

	/** @var array associative array of funnel steps **/
	private $funnel_steps = array();


	/**
	 * Setup settings page
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct(
			'google_analytics_pro',
			__( 'Google Analytics Pro', 'woocommerce-google-analytics-pro' ),
			__( 'Supercharge your Google Analytics tracking with enhanced eCommerce tracking, and custom event tracking', 'woocommerce-google-analytics-pro' )
		);

		/**
		 * Filters the Google Analytics tracking function name.
		 *
		 * @since 1.0.3
		 * @param string $ga_function_name The Google Analytics tracking function name, defaults to '__gaTracker'
		 */
		$this->ga_function_name = apply_filters( 'wc_google_analytics_pro_tracking_function_name', '__gaTracker' );

		$this->funnel_steps = $this->get_funnel_steps();

		// header/footer JavaScript code, only add if tracking ID is available
		if ( $this->get_tracking_id() ) {

			add_action( 'wp_head',  array( $this, 'ga_tracking_code' ), 9 );
			add_action( 'login_head', array( $this, 'ga_tracking_code' ), 9 );
		}

		// print tracking JavaScript
		add_action( 'wp_footer', array( $this, 'print_js' ) );

		// Enhanced Ecommerce impressions
		add_action( 'woocommerce_before_shop_loop_item', array( $this, 'product_impression' ) );
		add_action( 'woocommerce_before_single_product', array( $this, 'product_impression' ) );

		// save GA identity to each order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'store_ga_identity' ) );

		// two filters catching the event of Yoast doing tracking
		add_filter( 'yoast-ga-push-array-ga-js',     array( $this, 'set_yoast_ga_tracking_type_ga_js' ) );
		add_filter( 'yoast-ga-push-array-universal', array( $this, 'yoast_ga_push_array_universal' ) );

		// track emails
		$this->email_tracking = wc_google_analytics_pro()->load_class( '/includes/class-wc-google-analytics-pro-email-tracking.php', 'WC_Google_Analytics_Pro_Email_Tracking' );

		// handle Google Client API callbacks
		add_action( 'woocommerce_api_wc-google-analytics-pro/auth', array( $this, 'authenticate' ) );

		// load styles/scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		add_filter( 'woocommerce_settings_api_sanitized_fields_google_analytics_pro', array( $this, 'filter_admin_options' ) );
	}


	/**
	 * Load admin styles and scripts
	 *
	 * @since 1.0
	 * @param string $hook_suffix the current URL filename, ie edit.php, post.php, etc
	 */
	public function load_styles_scripts( $hook_suffix ) {

		if ( wc_google_analytics_pro()->is_plugin_settings() ) {

			wp_enqueue_script( 'wc-google-analytics-pro-admin', wc_google_analytics_pro()->get_plugin_url() . '/assets/js/admin/wc-google-analytics-pro-admin.min.js', array( 'jquery' ), WC_Google_Analytics_Pro::VERSION );

			wp_localize_script( 'wc-google-analytics-pro-admin', 'wc_google_analytics_pro', array(
				'ajax_url'            => admin_url('admin-ajax.php'),
				'auth_url'            => $this->get_auth_url(),
				'revoke_access_nonce' => wp_create_nonce( 'revoke-access' ),
				'i18n' => array(
					'ays_revoke' => esc_html__( 'Are you sure you wish to revoke access to your Google Account?', 'woocommerce-google-analytics-pro' ),
				),
			) );

			wp_enqueue_style( 'wc-google-analytics-pro-admin', wc_google_analytics_pro()->get_plugin_url() . '/assets/css/admin/wc-google-analytics-pro-admin.min.css', WC_Google_Analytics_Pro::VERSION );
		}
	}


	/**
	 * Enqueue tracking JavaScript
	 *
	 * Google Analytics is a bit picky about the order tacking JavaScript is output
	 * + Impressions -> Pageview -> Events
	 *
	 * This method queues tracking JavaScript so it can be later output in the correct order
	 *
	 * @since 1.0.3
	 * @param string $type The tracking type. One of 'impression', 'pageview', or 'event'
	 * @param string $javascript
	 */
	public function enqueue_js( $type, $javascript ) {

		if ( ! isset( $this->queued_js[ $type ] ) ) {
			$this->queued_js[ $type ] = array();
		}

		$this->queued_js[ $type ][] = $javascript;
	}


	/**
	 * Print tracking JavaScript
	 *
	 * Google Analytics is a bit picky about the order tacking JavaScript is output
	 * + Impressions -> Pageview -> Events
	 *
	 * This method prints the queued tracking JavaScript in the correct order
	 *
	 * @since 1.0.3
	 */
	public function print_js() {

		// define the correct order tracking types should be printed
		$types = array( 'impression', 'pageview', 'event' );

		$javascript = '';

		foreach ( $types as $type ) {

			if ( isset( $this->queued_js[ $type ] ) ) {

				foreach ( $this->queued_js[ $type ] as $code ) {
					$javascript .= "\n" . $code . "\n";
				}
			}
		}

		// enqueue the JavaScript
		wc_enqueue_js( $javascript );
	}


	/** Tracking methods ************************************************/


	/**
	 * Tracking code.
	 *
	 * Output GA tracking javascript in <head>.
	 *
	 * @since 1.0.0
	 */
	public function ga_tracking_code() {

		// bail if tracking is disabled
		if ( $this->do_not_track() ) {
			return;
		}

		// bail if Yoast is doing the basic tracking already
		if ( $this->is_yoast_ga_tracking_active() ) {
			return;
		}

		// no indentation on purpose
		?>
<!-- Start WooCommerce Google Analytics Pro -->
	<?php do_action( 'wc_google_analytics_pro_before_tracking_code' ); ?>
<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','<?php echo $this->ga_function_name; ?>');
	<?php echo $this->ga_function_name; ?>( 'create', '<?php echo esc_js( $this->get_tracking_id() ); ?>', 'auto' );
	<?php echo $this->ga_function_name; ?>( 'set', 'forceSSL', true );
<?php if ( 'yes' === $this->settings['track_user_id'] && is_user_logged_in() ) : ?>
	<?php echo $this->ga_function_name; ?>( 'set', 'userId', '<?php echo esc_js( get_current_user_id() ) ?>' );
<?php endif; ?>
<?php if ( 'yes' === $this->settings['anonymize_ip'] ) : ?>
	<?php echo $this->ga_function_name; ?>( 'set', 'anonymizeIp', true );
<?php endif; ?>
<?php if ( 'yes' === $this->settings['enable_displayfeatures'] ) : ?>
	<?php echo $this->ga_function_name; ?>( 'require', 'displayfeatures' );
<?php endif; ?>
	<?php echo $this->ga_function_name; ?>( 'require', 'ec' );
</script>
	<?php do_action( 'wc_google_analytics_pro_after_tracking_code' ); ?>
<!-- end WooCommerce Google Analytics Pro -->
		<?php
	}


	/**
	 * Output event tracking JavaScript
	 *
	 * @since 1.0.0
	 * @param string $event_name Name of Event to be set
	 * @param array/string $properties Properties to be set with event.
	 */
	private function js_record_event( $event_name, $properties = array() ) {

		// verify tracking status
		if ( $this->do_not_track() ) {
			return;
		}

		// Yoast is in non-universal mode, skip
		if ( $this->is_yoast_ga_tracking_active() && ! $this->is_yoast_ga_tracking_universal() ) {
			return;
		}

		if ( ! is_array( $properties ) ) {
			return;
		}

		$this->enqueue_js( 'event', $this->get_event_tracking_js( $event_name, $properties ) );
	}


	/**
	 * Get event tracking JS code
	 *
	 * @since 1.0.0
	 * @param string $event_name Name of Event to be set
	 * @param array/string $properties Properties to be set with event.
	 * @return string|null
	 */
	private function get_event_tracking_js( $event_name, $properties ) {

		if ( ! is_array( $properties ) ) {
			return;
		}

		$properties = array(
			'hitType'        => isset( $properties['hitType'] )        ? $properties['hitType']        : 'event',     // Required
			'eventCategory'  => isset( $properties['eventCategory'] )  ? $properties['eventCategory']  : 'page',      // Required
			'eventAction'    => isset( $properties['eventAction'] )    ? $properties['eventAction']    : $event_name, // Required
			'eventLabel'     => isset( $properties['eventLabel'] )     ? $properties['eventLabel']     : null,
			'eventValue'     => isset( $properties['eventValue'] )     ? $properties['eventValue']     : null,
			'nonInteraction' => isset( $properties['nonInteraction'] ) ? $properties['nonInteraction'] : false,
		);

		// remove blank properties
		unset( $properties[''] );

		$properties = json_encode( $properties );

		return "{$this->ga_function_name}( 'send', {$properties} );";
	}


	/**
	 * Record event via Measurement Protocol API
	 *
	 * @since 1.0.0
	 * @param string $event_name Name of Event to be set
	 * @param array $properties Properties to be set with event.
	 * @param array $ec Additional Enhanced Ecommerce data to be sent with the event
	 * @param array $identities Optional. Identities to use when tracking the event. If not provided, auto-detects from GA cookie and current user.
	 */
	private function api_record_event( $event_name, $properties = array(), $ec = array(), $identities = null, $admin_event = false ) {

		$user_id = is_array( $identities ) && isset( $identities['uid'] ) ? $identities['uid'] : null;

		// verify tracking status
		if ( $this->do_not_track( $admin_event, $user_id ) ) {
			return;
		}

		// remove blank properties/ec properties
		unset( $properties[''] );
		unset( $ec[''] );

		// auto-detect identities, if not provided
		if ( ! is_array( $identities ) || empty( $identities ) || empty( $identities['cid'] ) ) {
			$identities = $this->get_identities();
		}

		// remove user ID, unless user ID tracking is enabled,
		if ( 'yes' !== $this->get_option( 'track_user_id' ) && isset( $identities['uid'] ) ) {
			unset( $identities['uid'] );
		}

		// track the event via Measurement Protocol
		$this->get_api()->track_event( $event_name, $identities, $properties, $ec );
	}


	/**
	 * Get the code to add a product to the tracking code.
	 *
	 * @since 1.0.0
	 * @param int $product_id ID of the product to add.
	 * @param int $quantity Quantity to add to the code.
	 * @return string Code to use within a tracking code.
	 */
	private function get_ec_add_product_js( $product_id, $quantity = 1 ) {

		$product = wc_get_product( $product_id );
		$variant = ( 'variation' === $product->product_type ) ? implode( ',', array_values( $product->get_variation_attributes() ) ) : '';

		$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );

		/**
		 * Filters the product details data (productFieldObject)
		 *
		 * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#product-data
		 *
		 * @since 1.1.1
		 * @param array $product_details_data An associative array of product product details data
		 */
		$product_details_data = apply_filters( 'wc_google_analytics_pro_product_details_data', array(
			'id'       => $product_identifier,
			'name'     => $product->get_title(),
			'brand'    => '',
			'category' => $this->get_category_hierarchy( $product ),
			'variant'  => $variant,
			'price'    => $product->get_price(),
			'quantity' => $quantity,
			'position' => isset( $woocommerce_loop['loop'] ) ? $woocommerce_loop['loop'] : '',
		) );

		$js = sprintf(
			"%s( 'ec:addProduct', %s );",
			$this->ga_function_name,
			wp_json_encode( $product_details_data )
		);

		return $js;
	}


	/**
	 * Get unique identity of user
	 *
	 * @link http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
	 *
	 * @since 1.0.0
	 * @return string Visitor's ID from Google's cookie, or generated
	 */
	private function get_cid() {

		// get identity via GA cookie
		if ( isset( $_COOKIE['_ga'] ) ) {

			list( $version, $domainDepth, $cid1, $cid2 ) = preg_split( '[\.]', $_COOKIE['_ga'], 4 );
			$contents = array( 'version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1 . '.' . $cid2 );
			$identity = $contents['cid'];

		} else {

			// neither cookie set and named identity not passed, assign random identity
			// cookies are probably disabled for visitor
			if ( $this->debug_mode_on() ) {

				wc_google_analytics_pro()->log( 'No identity found. Cookies are probably disabled for visitor.' );
			}

			$identity = $this->generate_uuid();
		}

		return $identity;
	}


	/**
	 * Returns an array with 1 or 2 identities - the CID (GA unique ID from cookie) and
	 * current user ID, if available.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_identities() {

		$identities = array( 'cid' => $this->get_cid() );

		if ( is_user_logged_in() ) {
			$identities['uid'] = get_current_user_id();
		}

		return $identities;
	}


	/**
	 * Generate UUID v4 function - needed to generate a CID when one isn't available
	 *
	 * @link http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_uuid() {

		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}


	/**
	 * Check disable tracking.
	 *
	 * @TODO: this is an abysmal way of handling whether events should be tracked
	 * or not. It should be refactored before Max commits seppuku @MR 2016-01-25
	 *
	 * @since 1.0.0
	 * @param bool $admin_event Optional. Whether or not this is an admin event that should be tracked. Defaults to false.
	 * @param int $user_id Optional. User ID to check roles for
	 * @return bool true if tracking should be disabled, otherwise false
	 */
	private function do_not_track( $admin_event = false, $user_id = null ) {

		// do not track activity in the admin area, unless specified
		if ( ! $admin_event && ! is_ajax() && is_admin() ) {
			return true;
		}

		// track, unless disabled for the current user either by Yoast or by us.
		return ! $this->is_tracking_enabled_for_user_role( $user_id, $admin_event );
	}


	/**
	 * Check if tracking should be performed for the provided user, by the role.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID to check. Defaults to current user ID.
	 * @param bool $admin_event
	 * @return bool
	 */
	private function is_tracking_enabled_for_user_role( $user_id = null, $admin_event = false ) {

		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! isset( $this->user_tracking_enabled[ $user_id ] ) ) {

			// get user's info
			$user = get_user_by( 'id', $user_id );

			if ( ! $user || ! $user->ID || ! count( $user->roles ) ) {

				// OK to track not logged-in users or users with no role.
				$enabled = true;

			} elseif ( wc_google_analytics_pro()->is_yoast_ga_active() ) {

				// if Yoast GA is active, use their setting for disallowed roles, see Yoast_GA_Tracking::do_tracking
				$enabled = array_intersect( $user->roles, wc_google_analytics_pro()->get_yoast_ga_option( 'ignore_users' ) ) ? false : true;

			} elseif ( user_can( $user_id, 'manage_woocommerce' ) ) {

				// enable tracking of admins and shop managers only if checked in Settings
				// this also forces admin events performed by (duh) admins to be tracked, but has the side
				// effect of tracking purchases made by admins. This can't be helped until the do_not_track() method is refactored
				$enabled = $admin_event ? $admin_event : ( 'yes' === $this->settings['admin_tracking_enabled'] );

			} else {

				$enabled = true;
			}

			$this->user_tracking_enabled[ $user_id ] = $enabled;
		}

		return $this->user_tracking_enabled[ $user_id ];
	}


	/**
	 * Checks HTTP referrer to see if request was a page reload
	 * Prevents duplication of tracking events when user reloads page or submits a form
	 * e.g applying a coupon on the cart page
	 *
	 * @since 1.0.0
	 * @return bool true if not a page reload, false if page reload.
	 */
	private function not_page_reload() {

		// no referer..consider it's not a reload.
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return true;
		}

		// compare paths
		return ( parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_PATH ) !== parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
	}


	/** Yoast integration methods *************************************************/


	/**
	 * Invoked by a filter at the end of Yoast's tracking.
	 * If we came here then Yoast is going to print the GA init script.
	 *
	 * @see Yoast_GA_Universal::tracking
	 * @since 1.0.0
	 * @param mixed $data Tracking data
	 * @return mixed
	 */
	public function yoast_ga_push_array_universal( $data ) {

		$this->_yoast_ga_tracking_type = 'universal';

		// require Enhanced Ecommerce
		$data[] = "'require','ec'";

		// remove the pageview tracking, as we need to track it
		// in the footer instead (because of product impressions)
		if ( ! empty( $data ) ) {

			foreach ( $data as $key => $value ) {

				// check strpos rather than strict equal to account for search archives and 404 pages
				if ( strpos( $value, "'send','pageview'" ) !== false ) {
					unset( $data[ $key ] );
				}
			}
		}

		return $data;
	}


	/**
	 * Invoked by a filter at the end of Yoast's tracking.
	 * If we came here then Yoast is going to print the GA init script.
	 *
	 * @see Yoast_GA_JS::tracking
	 * @since 1.0.0
	 * @param mixed $ignore Ignored because we just need a trigger, not data.
	 * @return mixed
	 */
	public function set_yoast_ga_tracking_type_ga_js( $ignore ) {

		$this->_yoast_ga_tracking_type = 'ga-js';

		return $ignore;
	}


	/**
	 * Get Yoast's GA tracking type
	 *
	 * @since 1.0.0
	 * @return bool True if active.
	 */
	public function get_yoast_ga_tracking_type() {
		return $this->_yoast_ga_tracking_type;
	}


	/**
	 * Check if Yoast's tracking is active.
	 *
	 * @since 1.0.0
	 * @return bool True if active.
	 */
	public function is_yoast_ga_tracking_active() {
		return $this->get_yoast_ga_tracking_type() !== '';
	}


	/**
	 * Get Yoast's GA tracking type
	 *
	 * @since 1.0.0
	 * @return bool True if active.
	 */
	public function is_yoast_ga_tracking_universal() {
		return 'universal' === $this->get_yoast_ga_tracking_type();
	}


	/** Helper methods ********************************************************/


	/**
	 * Check if this tracking integration supports property names
	 *
	 * @since 1.0.0
	 * @return bool True, if supports property names, false otherwise
	 */
	protected function supports_property_names() {
		return false;
	}


	/**
	 * Get the plugin instance
	 *
	 * @since 1.0.0
	 * @return object
	 */
	protected function get_plugin() {
		return wc_google_analytics_pro();
	}


	/**
	 * Get the Google Analytics Tracking ID
	 *
	 * @since 1.0.0
	 * @return string The tracking ID
	 */
	public function get_tracking_id() {

		// Yoast's settings override ours
		if ( wc_google_analytics_pro()->is_yoast_ga_active() ) {
			return Yoast_GA_Options::instance()->get_tracking_code();
		}

		return $this->get_option( 'tracking_id' );
	}


	/**
	 * Get the Measurement Protocol API wrapper
	 *
	 * @since 1.0.0
	 * @return \WC_Google_Analytics_Pro_Measurement_Protocol_API
	 */
	public function get_api() {

		if ( is_object( $this->api ) ) {
			return $this->api;
		}

		// measurement protocol API wrapper
		require_once( wc_google_analytics_pro()->get_plugin_path() . '/includes/api/class-wc-google-analytics-pro-measurement-protocol-api.php' );

		// measurement protocol API request
		require_once( wc_google_analytics_pro()->get_plugin_path() . '/includes/api/class-wc-google-analytics-pro-measurement-protocol-api-request.php' );

		// measurement protocol API response
		require_once( wc_google_analytics_pro()->get_plugin_path() . '/includes/api/class-wc-google-analytics-pro-measurement-protocol-api-response.php' );

		return $this->api = new WC_Google_Analytics_Pro_Measurement_Protocol_API( $this->get_tracking_id() );
	}


	/**
	 * Get the list type of the current screen.
	 *
	 * @since 1.0.0
	 */
	public function get_list_type() {

		$list_type = '';

		if ( is_search() ) {

			$list_type = __( 'Search', 'woocommerce-google-analytics-pro' );

		} elseif ( is_product_category() ) {

			$list_type = __( 'Product category', 'woocommerce-google-analytics-pro' );

		} elseif ( is_product_tag() ) {

			$list_type = __( 'Product tag', 'woocommerce-google-analytics-pro' );

		} elseif ( is_archive() ) {

			$list_type = __( 'Archive', 'woocommerce-google-analytics-pro' );

		} elseif ( is_single() ) {

			$list_type = __( 'Related/Up sell', 'woocommerce-google-analytics-pro' );

		} elseif ( is_cart() ) {

			$list_type = __( 'Cross sell (cart)', 'woocommerce-google-analytics-pro' );
		}

		return apply_filters( 'wc_google_analytics_pro_list_type', $list_type );
	}


	/**
	 * Get the funnel steps
	 *
	 * @since 1.1.1
	 */
	private function get_funnel_steps() {

		/**
		 * Filters the funnel steps
		 *
		 * @since 1.1.1
		 * @param array $funnel_steps An associative array of event keys => funnel action and step
		 * @param \WC_Google_Analytics_Pro_Integration $this The instance of this class
		 */
		return apply_filters( 'wc_google_analytics_pro_product_funnel_steps', array(
			'clicked_product' => array(
				'action' => 'click',
				'step'   => 1,
			),
			'viewed_product' => array(
				'action' => 'detail',
				'step'   => 2,
			),
			'added_to_cart' => array(
				'action' => 'add',
				'step'   => 3,
			),
			'started_checkout' => array(
				'action' => 'checkout',
				'step'   => 4,
			),
			'completed_purchase' => array(
				'action' => 'purchase',
				'step'   => 5,
			),
		), $this );
	}


	/**
	 * Get the funnel (action) JavaScript of the provided event key if it exists
	 *
	 * @since 1.1.1
	 * @param string $event_key
	 * @param array $args Optional. An array of args to be encoded as the `actionFieldObject`
	 * @return string the JavaScript or an empty string
	 */
	private function get_funnel_js( $event_key, $args = array() ) {

		if ( ! isset( $this->funnel_steps[ $event_key ] ) ) {
			return '';
		}

		$funnel_js = '';

		$funnel_action = $this->get_funnel_action( $event_key );
		$funnel_step   = $this->get_funnel_step( $event_key );

		if ( ! empty( $funnel_action ) ) {

			if ( ! empty( $funnel_step ) ) {
				$args['step'] = $funnel_step;
			}

			$action_obj = wp_json_encode( $args );

			$funnel_js = "{$this->ga_function_name}( 'ec:setAction', '{$funnel_action}', {$action_obj} );";
		}

		return $funnel_js;
	}


	/**
	 * Get the funnel step of the provided event key if it exists
	 *
	 * @since 1.1.1
	 * @param string $event_key
	 * @return int|string the integer step or empty string
	 */
	private function get_funnel_step( $event_key ) {

		$step = '';

		if ( isset( $this->funnel_steps[ $event_key ] ) && isset( $this->funnel_steps[ $event_key ]['step'] ) ) {
			$step = $this->funnel_steps[ $event_key ]['step'];
		}

		return $step;
	}


	/**
	 * Get the funnel action of the provided event key if it exists
	 *
	 * @since 1.1.1
	 * @param string $event_key
	 * @return string the funnel action or empty string
	 */
	private function get_funnel_action( $event_key ) {

		$action = '';

		if ( isset( $this->funnel_steps[ $event_key ] ) && isset( $this->funnel_steps[ $event_key ]['action'] ) ) {
			$action = $this->funnel_steps[ $event_key ]['action'];
		}

		return $action;
	}


	/** Settings **************************************************************/


	/**
	 * Initializes form fields in the format required by WC_Integration.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {

		// initialize common fields
		parent::init_form_fields();

		$this->form_fields = array_merge( array(

			'tracking_settings_section' => array(
				'title' => __( 'Tracking Settings', 'woocommerce-google-analytics-pro' ),
				'type'  => 'title',
			),

			'enabled' => array(
				'title'   => __( 'Enable Google Analytics tracking', 'woocommerce-google-analytics-pro' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			),
		),

		$this->get_auth_fields(),

		array(

			'use_manual_tracking_id' => array(
				'label'       => __( 'Enter tracking ID manually (not recommended)', 'woocommerce-google-analytics-pro' ),
				'type'        => 'checkbox',
				'class'       => 'js-wc-google-analytics-toggle-manual-tracking-id',
				'default'     => 'no',
				'desc_tip'    => __( "We won't be able to display reports or configure your account automatically", 'woocommerce-google-analytics-pro' ),
			),

			'tracking_id' => array(
				'title'       => __( 'Google Analytics tracking ID', 'woocommerce-google-analytics-pro' ),
				'label'       => __( 'Google Analytics tracking ID', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'Go to your Google Analytics account to find your ID. e.g. <code>UA-XXXXX-X</code>', 'woocommerce-google-analytics-pro' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => 'UA-XXXXX-X',
			),

			'admin_tracking_enabled' => array(
				'title'       => __( 'Track Administrators?', 'woocommerce-google-analytics-pro' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'Check to enable tracking when logged in as Administrator or Shop Manager.', 'woocommerce-google-analytics-pro' ),
			),

			'enable_displayfeatures' => array(
				'title'         => __( 'Tracking Options', 'woocommerce-google-analytics-pro' ),
				'label'         => __( '"Display Advertising" Support', 'woocommerce-google-analytics-pro' ),
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => 'start',
				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				'description'   => sprintf( __( 'Set the Google Analytics code to support Display Advertising. %1$sRead more about Display Advertising%2$s.', 'woocommerce-google-analytics-pro' ), '<a href="https://support.google.com/analytics/answer/2700409" target="_blank">', '</a>' ),
			),

			'anonymize_ip'          => array(
				'label'         => __( 'Anonymize IP addresses', 'woocommerce-google-analytics-pro' ),
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => '',
				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				'description'   => sprintf( __( 'Enabling this option is mandatory in certain countries due to national privacy laws. %1$sRead more about IP Anonymization%2$s.', 'woocommerce-google-analytics-pro' ), '<a href="https://support.google.com/analytics/answer/2763052" target="_blank">', '</a>' ),
			),

			'track_user_id'         => array(
				'label'         => __( 'Track User ID', 'woocommerce-google-analytics-pro' ),
				'type'          => 'checkbox',
				'default'       => 'no',
				'checkboxgroup' => '',
				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				'description'   => sprintf( __( 'Enable User ID tracking. %1$sRead more about the User ID feature%2$s.', 'woocommerce-google-analytics-pro' ), '<a href="https://support.google.com/analytics/answer/3123662" target="_blank">', '</a>' ),
			),

			'track_product_impressions_on' => array(
				'title'       => __( 'Track product impressions on:', 'woocommerce-google-analytics-pro' ),
				'desc_tip'    => __( 'Control where product impressions are tracked.', 'woocommerce-google-analytics-pro' ),
				'description' => __( 'If you\'re running into issues, particularly if you see the "No HTTP response detected" error, try disabling product impressions on archive pages.', 'woocommerce-google-analytics-pro' ),
				'type'        => 'multiselect',
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'single_product_pages' => __( 'Single Product Pages', 'woocommerce-google-analytics-pro' ),
					'archive_pages'        => __( 'Archive Pages', 'woocommerce-google-analytics-pro' ),
				),
				'default'     => array( 'single_product_pages', 'archive_pages' ),
			),

		), $this->form_fields );
	}


	/**
	 * Returns the authentication fields, but only when on the plugin settings
	 * screen as this requires an API call to GA to get profile data
	 *
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_auth_fields() {

		if ( ! wc_google_analytics_pro()->is_plugin_settings() ) {
			return array();
		}

		$auth_fields = array();

		$ga_profiles      = $this->get_access_token() ? $this->get_ga_profiles() : null;
		$auth_button_text = $this->get_access_token() ? esc_html__( 'Re-authenticate with your Google account', 'woocommerce-google-analytics-pro' ) : esc_html__( 'Authenticate with your Google account', 'woocommerce-google-analytics-pro' );

		if ( ! empty( $ga_profiles ) ) {

			// add empty option so clearing the field is possible
			$ga_profiles = array_merge( array( '' => '' ), $ga_profiles );

			$auth_fields = array(
				'profile' => array(
					'title'    => __( 'Google Analytics Profile', 'woocommerce-google-analytics-pro' ),
					'type'     => 'deep_select',
					'default'  => '',
					'class'    => 'wc-enhanced-select-nostd',
					'options'  => $ga_profiles,
					'custom_attributes' => array(
						'data-placeholder' => __( 'Select a profile&hellip;', 'woocommerce-google-analytics-pro' ),
					),
					'desc_tip' => __( "Choose which Analytics profile you want to track", 'woocommerce-google-analytics-pro' ),
				),
			);
		}

		$auth_fields['oauth_button'] = array(
			'type'     => 'button',
			'default'  => $auth_button_text,
			'class'    => 'button',
			'desc_tip' => __( 'We need view & edit access to your Analytics account so we can display reports and automatically configure Analytics settings for you.', 'woocommerce-google-analytics-pro' ),
		);

		if ( empty( $ga_profiles ) ) {
			$auth_fields['oauth_button']['title'] = __( 'Google Analytics Profile', 'woocommerce-google-analytics-pro' );
		}

		if ( $this->get_access_token() ) {
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$auth_fields['oauth_button']['description'] = sprintf( __( 'or %1$srevoke authorization%2$s' ), '<a href="#" class="js-wc-google-analytics-pro-revoke-authorization">', '</a>' );
		}

		return $auth_fields;
	}


	/**
	 * Get the Google Client API authentication URL
	 *
	 * @since 1.0.0
	 * @return string url
	 */
	public function get_auth_url() {
		return self::PROXY_URL . '/auth?callback=' . urlencode( $this->get_callback_url() );
	}


	/**
	 * Get the refresh token
	 *
	 * @since 1.0.0
	 * @return string|null
	 */
	private function get_refresh_token() {
		return get_option( 'wc_google_analytics_pro_refresh_token' );
	}


	/**
	 * Get the Google Client API refresh access token URL, if a refresh token is available
	 *
	 * @since 1.0.0
	 * @return string|null
	 */
	public function get_access_token_refresh_url() {

		if ( $refresh_token = $this->get_refresh_token() ) {

			return self::PROXY_URL . '/auth/refresh?token=' . base64_encode( $refresh_token );
		}
	}


	/**
	 * Get the Google Client API revoke access token URL, if a token is available
	 *
	 * @since 1.0.0
	 * @return string|null
	 */
	public function get_access_token_revoke_url() {

		if ( $token = $this->get_access_token() ) {

			return self::PROXY_URL . '/auth/revoke?token=' . base64_encode( $token );
		}
	}


	/**
	 * Get the Google Client API callback URL
	 *
	 * @since 1.0.0
	 * @return string url
	 */
	public function get_callback_url() {

		return get_home_url( null, 'wc-api/wc-google-analytics-pro/auth' );
	}


	/** Event tracking methods ******************************/


	/**
	 * Track pageview.
	 *
	 * @since 1.0.0
	 */
	public function pageview() {

		if ( $this->do_not_track() ) {
			return;
		}

		// Yoast is in non-universal mode, skip
		if ( $this->is_yoast_ga_tracking_active() && ! $this->is_yoast_ga_tracking_universal() ) {
			return;
		}

		$this->enqueue_js( 'pageview', "{$this->ga_function_name}( 'send', 'pageview' );" );
	}


	/**
	 * Track viewed homepage
	 *
	 * @since 1.1.3
	 */
	public function viewed_homepage() {

		// bail if tracking is disabled
		if ( $this->do_not_track() ) {
			return;
		}

		if ( is_front_page() && $this->event_name['viewed_homepage'] ) {

			$properties = array(
				'eventCategory'  => 'Homepage',
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['viewed_homepage'], $properties );
		}
	}


	/**
	 * Track the log-in event.
	 *
	 * @since 1.0.0
	 * @param string $user_login Username
	 * @param \WP_User $user WP_User object of the logged-in user.
	 */
	public function signed_in( $user_login, $user ) {

		if ( in_array( $user->roles[0], apply_filters( 'wc_google_analytics_pro_signed_in_user_roles', array( 'subscriber', 'customer' ) ) ) ) {

			$properties = array(
				'eventCategory' => 'My Account',
				'eventLabel'    => $user_login,
			);

			$this->api_record_event( $this->event_name['signed_in'], $properties );

			// store GA identity in user meta
			update_user_meta( $user->ID, '_wc_google_analytics_pro_identity', $this->get_cid() );
		}
	}


	/**
	 * Track sign out
	 *
	 * @since 1.0.0
	 */
	public function signed_out() {

		$this->api_record_event( $this->event_name['signed_out'] );
	}


	/**
	 * Sign up view.
	 *
	 * Track the sign up page view (on my account page when enabled).
	 *
	 * @since 1.0.0
	 */
	public function viewed_signup() {

		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory'  => 'My Account',
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['viewed_signup'], $properties );
		}
	}


	/**
	 * Sign up event.
	 *
	 * Track the sign up event.
	 *
	 * @since 1.0.0
	 */
	public function signed_up() {

		$properties = array(
			'eventCategory' => 'My Account',
		);

		$this->api_record_event( $this->event_name['signed_up'], $properties );
	}


	/**
	 * View product.
	 *
	 * Track the viewing of a product.
	 *
	 * @since 1.0.0
	 */
	public function viewed_product() {

		// bail if tracking is disabled
		if ( $this->do_not_track() ) {
			return;
		}

		if ( $this->not_page_reload() ) {

			// add Enhanced Ecommerce tracking
			$product_id = get_the_ID();

			// JS add product
			$js = $this->get_ec_add_product_js( $product_id );

			// JS add action
			$js .= $this->get_funnel_js( 'viewed_product' );

			// enqueue JS
			$this->enqueue_js( 'event', $js );

			// set event properties - EC data will be sent with the event
			$properties = array(
				'eventCategory'  => 'Products',
				'eventLabel'     => esc_js( get_the_title() ),
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['viewed_product'], $properties );
		}
	}


	/**
	 * Click product.
	 *
	 * Track click on a product in a listing
	 *
	 * @since 1.0.0
	 */
	public function clicked_product() {

		if ( $this->do_not_track() ) {
			return;
		}

		// Yoast is in non-universal mode, skip
		if ( $this->is_yoast_ga_tracking_active() && ! $this->is_yoast_ga_tracking_universal() ) {
			return;
		}

		global $product;

		$list       = $this->get_list_type();
		$properties = array(
			'eventCategory' => 'Products',
			'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
		);

		$js =
			"$( '.products .post-" . esc_js( $product->id ) . " a' ).click( function() {
				if ( true === $(this).hasClass( 'add_to_cart_button' ) ) {
					return;
				}
				" . $this->get_ec_add_product_js( $product->id ) . $this->get_funnel_js( 'clicked_product', array( 'list' => $list ) ) . $this->get_event_tracking_js( $this->event_name['clicked_product'], $properties ) . "
			});";

		$this->enqueue_js( 'event', $js );
	}


	/**
	 * Add-to-cart event.
	 *
	 * Track the (non-ajax) add to cart button event.
	 *
	 * @since 1.0.0
	 * @param string $cart_item_key  Array key (unique ID) of the cart array.
	 * @param int    $product_id     Product ID.
	 * @param int    $quantity       Quantity added to the cart.
	 * @param int    $variation_id   Variation ID (optional)
	 * @param array  $variation      Variation data.
	 * @param array  $cart_item_data Cart item data.
	 */
	public function added_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		// don't track add to cart from AJAX POST here
		if ( isset( $_POST['action'] ) ) {
			return;
		}

		$product = wc_get_product( $product_id );

		$properties = array(
			'eventCategory' => 'Products',
			'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			'eventValue'    => (int) $quantity,
			'checkoutStep'  => $this->get_funnel_step( 'added_to_cart' ),
		);


		if ( ! empty( $variation ) ) {

			// added a variable product to cart, set attributes as properties
			// remove 'pa_' from keys to keep property names consistent
			$variation = array_flip( str_replace( 'attribute_', '', array_flip( $variation ) ) );

			$properties = array_merge( $properties, $variation );
		}

		$ec = array( 'add_to_cart' => array( $product, '1' ) );

		$this->api_record_event( $this->event_name['added_to_cart'], $properties, $ec );
	}


	/**
	 * Track the (ajax) add to cart button event.
	 *
	 * @since 1.0.0
	 * @param int $product_id
	 */
	public function ajax_added_to_cart( $product_id ) {

		$product = wc_get_product( $product_id );

		$properties = array(
			'eventCategory' => 'Products',
			'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			'eventValue'    => 1,
			'checkoutStep'  => $this->get_funnel_step( 'added_to_cart' ),
		);

		$ec = array( 'add_to_cart' => array( $product, '1' ) );

		$this->api_record_event( $this->event_name['added_to_cart'], $properties, $ec );
	}


	/**
	 * Track product removal from cart.
	 *
	 * @since 1.0.0
	 * @param string $cart_item_key
	 */
	public function removed_from_cart( $cart_item_key ) {

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			$item    = WC()->cart->cart_contents[ $cart_item_key ];
			$product = wc_get_product( $item['product_id'] );

			$properties = array(
				'eventCategory' => 'Cart',
				'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			);

			$ec = array( 'remove_from_cart' => $product );

			$this->api_record_event( $this->event_name['removed_from_cart'], $properties, $ec );
		}
	}


	/**
	 * Track change quantity event in the cart.
	 *
	 * @since 1.0.0
	 * @param string $cart_item_key Array key (unique ID) of the cart array.
	 * @param int $quantity Changed quantity.
	 */
	public function changed_cart_quantity( $cart_item_key, $quantity ) {;

		if ( isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {

			$item    = WC()->cart->cart_contents[ $cart_item_key ];
			$product = wc_get_product( $item['product_id'] );

			$properties = array(
				'eventCategory' => 'Cart',
				'eventLabel'    => htmlentities( $product->get_title(), ENT_QUOTES, 'UTF-8' ),
			);

			$this->api_record_event( $this->event_name['changed_cart_quantity'], $properties );
		}
	}


	/**
	 * Track cart page view as event.
	 *
	 * @since 1.0.0
	 */
	public function viewed_cart() {

		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory'  => 'Cart',
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['viewed_cart'], $properties );
		}
	}


	/**
	 * Track the start of checkout.
	 *
	 * @since 1.0.0
	 */
	public function started_checkout() {

		// bail if tracking is disabled
		if ( $this->do_not_track() ) {
			return;
		}

		if ( $this->not_page_reload() ) {

			// enhanced Ecommerce tracking
			$js = '';

			foreach ( WC()->cart->get_cart() as $item ) {

				// JS add product
				$js .= $this->get_ec_add_product_js( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'], $item['quantity'] );
			}

			// JS add action
			$js .= $this->get_funnel_js( 'started_checkout' );

			// enqueue JS
			$this->enqueue_js( 'event', $js );

			// set event properties
			$properties = array(
				'eventCategory'  => 'Checkout',
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['started_checkout'], $properties );
		}
	}


	/**
	 * Track the start of payment at checkout.
	 *
	 * @since 1.0.0
	 */
	public function started_payment() {

		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory'  => 'Checkout',
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['started_payment'], $properties );
		}
	}


	/**
	 * Track when someone is commenting. This can be a regular
	 * comment or an product review.
	 *
	 * @since 1.0.0
	 */

	public function wrote_review_or_commented() {

		// separate comments from review tracking
		$type = get_post_type();

		if ( 'product' === $type ) {

			$properties = array(
				'eventCategory' => 'Products',
				'eventLabel'    => get_the_title(),
			);

			if ( $this->event_name['wrote_review'] ) {
				$this->api_record_event( $this->event_name['wrote_review'], $properties );
			}

		} elseif ( 'post' === $type ) {

			$properties = array(
				'eventCategory' => 'Post',
				'eventLabel'    => get_the_title(),
			);

			if ( $this->event_name['commented'] ) {
				$this->api_record_event( $this->event_name['commented'], $properties );
			}
		}
	}


	/**
	 * Track completed purchase. This method uses the ecommerce.js
	 * plug-in to send data to GA.
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 */
	public function completed_purchase( $order_id ) {

		/**
		 * Filters whether the completed purchase event should be tracked or not.
		 *
		 * @since 1.1.5
		 * @param bool $do_not_track true to not track the event, false otherwise
		 * @param int $order_id the order ID
		 */
		if ( true === apply_filters( 'wc_google_analytics_pro_do_not_track_completed_purchase', false, $order_id ) ) {
			return;
		}

		// bail if tracking is disabled but not if the status is being manually changed by the admin
		if ( $this->do_not_track() && ! ( doing_action( 'woocommerce_order_status_failed_to_processing' ) || doing_action( 'woocommerce_order_status_failed_to_completed' ) ) ) {
			return;
		}

		// don't track order when its already tracked
		if ( 'yes' === get_post_meta( $order_id, '_wc_google_analytics_pro_tracked' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// record purchase event
		$properties = array(
			'eventCategory' => 'Checkout',
			'eventLabel'    => $order->get_order_number(),
			'eventValue'    => round( $order->get_total() * 100 ),
			'checkoutStep'  => $this->get_funnel_step( 'completed_purchase' ),
		);

		$ec = array( 'purchase' => $order );

		// set identities manually, as this event may also be triggered by an admin
		$cid = $this->get_order_ga_identity( $order_id );

		$identities = array(
			'cid' => $cid ? $cid : $this->get_cid(),
			'uid' => $order->get_user_id()
		);

		$this->api_record_event( $this->event_name['completed_purchase'], $properties, $ec, $identities, true );

		// mark order as tracked
		update_post_meta( $order->id, '_wc_google_analytics_pro_tracked', 'yes' );
	}


	/**
	 * Track completed payment for orders that were previously on-hold, like
	 * BACS or cheque orders
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 */
	public function completed_payment( $order_id ) {

		/**
		 * Filters whether the completed payment event should be tracked or not.
		 *
		 * @since 1.1.5
		 * @param bool $do_not_track true to not track the event, false otherwise
		 * @param int $order_id the order ID
		 */
		if ( true === apply_filters( 'wc_google_analytics_pro_do_not_track_completed_payment', false, $order_id ) ) {
			return;
		}

		// orders marked as paid will be tracked in completed_purchase()
		if ( metadata_exists( 'post', $order_id, '_paid_date' ) ) {
			return;
		}

		// don't track order when its already tracked - note that this uses a different meta key from completed purchase on purpose :)
		if ( 'yes' === get_post_meta( $order_id, '_wc_google_analytics_pro_tracked_completed_payment' ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$properties = array(
			'eventCategory' => 'Checkout',
			'eventLabel'    => $order->get_order_number(),
			'eventValue'    => round( $order->get_total() * 100 ),
		);

		$ec = array();

		// track purchase if it hasn't been tracked in completed_purchase()
		if ( 'yes' !== get_post_meta( $order_id, '_wc_google_analytics_pro_tracked' ) ) {
			$ec['purchase'] = $order;
		}

		// set identities manually, as this event may also be triggered by an admin
		$cid = $this->get_order_ga_identity( $order_id );

		$identities = array(
			'cid' => $cid ? $cid : $this->get_cid(),
			'uid' => $order->get_user_id()
		);

		$this->api_record_event( $this->event_name['completed_payment'], $properties, $ec, $identities, true );

		// mark order as tracked
		update_post_meta( $order->id, '_wc_google_analytics_pro_tracked_completed_payment', 'yes' );
	}


	/**
	 * Track the view of the account page.
	 *
	 * @since 1.0.0
	 */
	public function viewed_account() {

		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory'  => 'My Account',
				'nonInteraction' => true,
			);

			$this->js_record_event( $this->event_name['viewed_account'], $properties );
		}
	}


	/**
	 * Track the view of an order.
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 */
	public function viewed_order( $order_id ) {

		if ( $this->not_page_reload() ) {

			$order = wc_get_order( $order_id );

			$properties = array(
				'eventCategory'  => 'Orders',
				'eventLabel'     => $order->get_order_number(),
				'nonInteraction' => true,
			);

			$this->api_record_event( $this->event_name['viewed_order'], $properties );
		}
	}


	/**
	 * Track updating address.
	 *
	 * @since 1.0.0
	 */
	public function updated_address() {

		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory' => 'My Account',
			);

			$this->api_record_event( $this->event_name['updated_address'], $properties );
		}
	}


	/**
	 * Track changed password.
	 *
	 * @since 1.0.0
	 */
	public function changed_password() {

		if ( ! empty( $_POST['password_1'] ) && $this->not_page_reload() ) {

			$properties = array(
				'eventCategory' => 'My Account',
			);

			$this->api_record_event( $this->event_name['changed_password'], $properties );
		}
	}


	/**
	 * Track the applying of a coupon.
	 *
	 * @since 1.0.0
	 * @param string $coupon_code Coupon code that is being applied.
	 */
	public function applied_coupon( $coupon_code ) {

		$properties = array(
			'eventCategory' => 'Coupons',
			'eventLabel'    => $coupon_code,
		);

		$this->api_record_event( $this->event_name['applied_coupon'], $properties );
	}


	/**
	 * Track the removing of a coupon.
	 *
	 * @since 1.0.0
	 */
	public function removed_coupon() {

		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory' => 'Coupons',
				'eventLabel'    => $_GET['remove_coupon'],
			);

			$this->api_record_event( $this->event_name['removed_coupon'], $properties );
		}
	}


	/**
	 * Track the 'track order' event.
	 *
	 * @since 1.0.0
	 * @param int $order_id Id of the order being tracked.
	 */
	public function tracked_order( $order_id ) {

		if ( $this->not_page_reload() ) {

			$order = wc_get_order( $order_id );

			$properties = array(
				'eventCategory' => 'Orders',
				'eventLabel'    => $order->get_order_number(),
			);

			$this->api_record_event( $this->event_name['tracked_order'], $properties );
		}
	}


	/**
	 * Track the calculate shipping form usage.
	 *
	 * @since 1.0.0
	 */
	public function estimated_shipping() {

		// do not check for not_page_reload, because the event happens on the same 'cart' page
		if ( $this->not_page_reload() ) {

			$properties = array(
				'eventCategory' => 'Cart',
			);

			$this->api_record_event( $this->event_name['estimated_shipping'], $properties );

		}
	}


	/**
	 * Track the cancellation of an order.
	 *
	 * @param int $order_id
	 * @since 1.0.0
	 */
	public function cancelled_order( $order_id ) {

		$order = wc_get_order( $order_id );

		$properties = array(
			'eventCategory' => 'Orders',
			'eventLabel'    => $order->get_order_number(),
		);

		$this->api_record_event( $this->event_name['cancelled_order'], $properties );
	}


	/**
	 * Track when an order is refunded
	 *
	 * @since 1.0.0
	 * @param int $order_id Order ID
	 */
	public function order_refunded( $order_id, $refund_id ) {

		// don't track if the refund is already tracked
		if ( 'yes' === get_post_meta( $refund_id, '_wc_google_analytics_pro_tracked' ) ) {
			return;
		}

		$order          = wc_get_order( $order_id );
		$refund         = wc_get_order( $refund_id );
		$items          = $refund->get_items();
		$refunded_items = array();

		// get refunded items
		if ( ! empty( $items ) ) {

			foreach ( $items as $item_id => $item ) {

				// any item with a quantity and line total is refunded
				if ( $item['qty'] >= 1 && $refund->get_line_total( $item ) <= 0 ) {
					$refunded_items[ $item_id ] = $item;
				}
			}
		}

		$properties = array(
			'eventCategory' => 'Orders',
			'eventLabel'    => $order->get_order_number(),
			'eventValue'    => $refund->get_refund_amount(),
		);

		// Enhanced Ecommerce can only track full refunds and refunds for specific items
		if ( $order_id == $this->order_fully_refunded || ! empty( $refunded_items ) ) {
			$ec = array( 'refund' => array( $order, $refunded_items ) );
		} else {
			$ec = null;
		}

		// set identities manually, as this event may also be triggered by an admin
		$cid = $this->get_order_ga_identity( $order_id );

		$identities = array(
			'cid' => $cid ? $cid : $this->get_cid(),
			'uid' => $order->get_user_id()
		);

		$this->api_record_event( $this->event_name['order_refunded'], $properties, $ec, $identities, true );

		// mark order as tracked
		update_post_meta( $refund_id, '_wc_google_analytics_pro_tracked', 'yes' );
	}


	/**
	 * Track the event when someone uses the 'Order again' button.
	 *
	 * @since 1.0.0
	 */
	public function reordered( $order_id ) {

		if ( $this->not_page_reload() ) {

			$order = wc_get_order( $order_id );

			$properties = array(
				'eventCategory' => 'Orders',
				'eventLabel'    => $order->get_order_number(),
			);

			$this->api_record_event( $this->event_name['reordered'], $properties );
		}
	}


	/** Enhanced e-commerce specific methods **********************/


	/**
	 * Track the impression of a product. A impression is the listing
	 * of a product anywhere on the website.
	 * E.g. search/archive/category/related/cross sell.
	 *
	 * @since 1.0.0
	 */
	public function product_impression() {

		if ( $this->do_not_track() ) {
			return;
		}

		// Yoast is in non-universal mode, skip
		if ( $this->is_yoast_ga_tracking_active() && ! $this->is_yoast_ga_tracking_universal() ) {
			return;
		}

		$track_on = $this->get_option( 'track_product_impressions_on', array() );

		// bail if product impression tracking is disabled on product pages and we're on a prdouct page
		// note: this doesn't account for the [product_page] shortcode unfortunately
		if ( ! in_array( 'single_product_pages', $track_on, true ) && is_product() ) {
			return;
		}

		// bail if product impression tracking is disabled on product archive pages and we're on an archive page
		if ( ! in_array( 'archive_pages', $track_on, true ) && ( is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() ) ) {
			return;
		}

		global $product, $woocommerce_loop;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$attributes = ( 'variable' === $product->product_type ) ? $product->get_variation_default_attributes() : array();
		$variant    = ( 'variable' === $product->product_type ) ? implode( ', ', array_values( $attributes ) ) : '';

		$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );

		// set up impression data as associative array and merge attributes to be sent as custom dimensions
		$impression_data = array_merge( array(
			'id'       => $product_identifier,
			'name'     => $product->get_title(),
			'list'     => $this->get_list_type(),
			'brand'    => '',
			'category' => $this->get_category_hierarchy( $product ),
			'variant'  => $variant,
			'position' => isset( $woocommerce_loop['loop'] ) ? $woocommerce_loop['loop'] : 1,
			'price'    => $product->get_price(),
		), $attributes );

		/**
		 * Filters the product impression data (impressionFieldObject)
		 *
		 * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-ecommerce#impression-data
		 *
		 * @since 1.1.1
		 * @param array $impression_data An associative array of product impression data
		 */
		$impression_data = apply_filters( 'wc_google_analytics_pro_product_impression_data', $impression_data );

		// unset empty values to reduce request size
		foreach ( $impression_data as $key => $value ) {

			if ( empty( $value ) ) {
				unset( $impression_data[ $key ] );
			}
		}

		$this->enqueue_js( 'impression', sprintf(
			"%s( 'ec:addImpression', %s );",
			$this->ga_function_name,
			wp_json_encode( $impression_data )
		) );
	}


	/**
	 * Track custom event
	 *
	 * Contains excess checks to account for any kind of user input
	 *
	 * @since 1.0.0
	 * @param bool $event_name
	 * @param bool $properties
	 */
	public function custom_event( $event_name = false, $properties = false ) {

		if ( isset( $event_name ) && $event_name != '' && strlen( $event_name ) > 0 ) {

			// sanitize property names and values
			$prop_array = false;
			$props      = false;

			if ( isset( $properties ) && is_array( $properties ) && count( $properties ) > 0 ) {

				foreach ( $properties as $k => $v ) {

					$key   = $this->sanitize_event_string( $k );
					$value = $this->sanitize_event_string( $v );

					if ( $key && $value ) {
						$prop_array[$key] = $value;
					}
				}

				$props = false;

				if ( $prop_array && is_array( $prop_array ) && count( $prop_array ) > 0 ) {
					$props = $prop_array;
				}
			}

			// sanitize event name
			$event = $this->sanitize_event_string( $event_name );

			// if everything checks out then trigger event
			if ( $event ) {
				$this->api_record_event( $event, $props );
			}
		}
	}


	/**
	 * Sanitize string for custom events
	 *
	 * Contains excess checks to account for any kind of user input
	 *
	 * @since 1.0.0
	 * @param bool $str
	 * @return string|bool
	 */
	private function sanitize_event_string( $str = false ) {

		if ( isset( $str ) ) {
			// remove excess spaces
			$str = trim( $str );

			// remove characters that could break JSON or JS trigger
			$str = str_replace( array( '\'', '"', ',', ';', ':', '.', '{', '}' ), '', $str );

			// URL encode for safety
			$str = urlencode( $str );

			return $str;
		}

		return false;
	}


	/**
	 * Store GA Identity (CID) on the order
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 */
	public function store_ga_identity( $order_id ) {

		update_post_meta( $order_id, '_wc_google_analytics_pro_identity', $this->get_cid() );
	}


	/**
	 * Get the GA Identity associated with an order
	 *
	 * @since 1.0.0
	 * @param int $order_id
	 * @return string|null
	 */
	public function get_order_ga_identity( $order_id ) {

		return get_post_meta( $order_id, '_wc_google_analytics_pro_identity', true );
	}


	/**
	 * Authenticate with Google API
	 *
	 * @since 1.0.0
	 */
	public function authenticate() {

		// missing token
		if ( ! isset( $_REQUEST['token'] ) || ! $_REQUEST['token'] ) {
			return;
		}

		$json_token = base64_decode( $_REQUEST['token'] );
		$token      = json_decode( $json_token, true );

		// invalid token
		if ( ! $token ) {
			return;
		}

		// update access token
		update_option( 'wc_google_analytics_pro_access_token', $json_token );
		update_option( 'wc_google_analytics_pro_account_id', md5( $json_token ) );
		delete_transient( 'wc_google_analytics_pro_properties' );

		// update refresh token
		if ( isset( $token['refresh_token'] ) ) {
			update_option( 'wc_google_analytics_pro_refresh_token', $token['refresh_token'] );
		}

		echo '<script>window.opener.wc_google_analytics_pro.auth_callback(' . $json_token . ');</script>';
		exit();
	}


	/**
	 * Get the current access token
	 *
	 * @since 1.0.0
	 * @return string|null
	 */
	public function get_access_token() {
		return get_option( 'wc_google_analytics_pro_access_token' );
	}


	/**
	 * Get Google Client API instance
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public function get_ga_client() {

		if ( ! isset( $this->ga_client ) ) {

			$this->ga_client = new Google_Client();
			$this->ga_client->setAccessToken( $this->get_access_token() );
		}

		// refresh token if required
		if ( $this->ga_client->isAccessTokenExpired() ) {
			$this->refresh_access_token();
		}

		return $this->ga_client;
	}


	/**
	 * Get Google Client API Analytics Service instance
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public function get_analytics() {

		if ( ! isset( $this->analytics ) ) {
			$this->analytics = new Google_Service_Analytics( $this->get_ga_client() );
		}

		return $this->analytics;
	}


	/**
	 * Refresh the access token
	 *
	 * @since 1.0.0
	 * @return string|null JSON token, or null on failure
	 */
	private function refresh_access_token() {

		// bail out if no refresh token is available
		if ( ! $this->get_refresh_token() ) {

			wc_google_analytics_pro()->log( 'Could not refresh access token: refresh token not available' );
			return;
		}

		$response = wp_remote_get( $this->get_access_token_refresh_url(), array( 'timeout' => MINUTE_IN_SECONDS ) );

		// bail out if the request failed
		if ( is_wp_error( $response ) ) {

			wc_google_analytics_pro()->log( sprintf( 'Could not refresh access token: %s', json_encode( $response->errors ) ) );
			return;
		}

		// bail out if the response was empty
		if ( ! $response || ! $response['body'] ) {
			wc_google_analytics_pro()->log( 'Could not refresh access token: response was empty' );
			return;
		}

		// try to decode the token
		$json_token = base64_decode( $response['body'] );
		$token      = json_decode( $json_token, true );

		// bail out if the token was invalid
		if ( ! $token ) {
			wc_google_analytics_pro()->log( 'Could not refresh access token: returned token was invalid' );
			return;
		}

		// update access token
		update_option( 'wc_google_analytics_pro_access_token', $json_token );
		$this->ga_client->setAccessToken( $json_token );

		return $json_token;
	}


	/**
	 * Generate Select HTML.
	 *
	 * @param  mixed $key
	 * @param  mixed $data
	 * @since  1.0.0
	 * @return string
	 */
	public function generate_deep_select_html( $key, $data ) {

		$field    = $this->get_field_key( $key );
		$defaults = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array()
		);

		$data = wp_parse_args( $data, $defaults );

		$k = 0;
		$optgroup_open = false;

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<select class="select <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?>>

						<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>

							<?php if ( is_array( $option_value ) ) : ?>

								<optgroup label="<?php echo esc_attr( $option_key ); ?>">

								<?php foreach ( $option_value as $option_key => $option_value ) : ?>
									<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, esc_attr( $this->get_option( $key ) ) ); ?>><?php echo esc_attr( $option_value ); ?></option>
								<?php endforeach; ?>

							<?php else : ?>
								<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, esc_attr( $this->get_option( $key ) ) ); ?>><?php echo esc_attr( $option_value ); ?></option>
							<?php endif; ?>

						<?php endforeach; ?>

					</select>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}


	/**
	 * Get a list of Google Analytics profiles
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_ga_profiles() {

		if ( ! wc_google_analytics_pro()->is_plugin_settings() ) {
			return array();
		}

		// check if profiles transient exists
		if ( false === ( $ga_profiles = get_transient( 'wc_google_analytics_pro_profiles' ) ) ) {

			$ga_profiles = array();
			$analytics   = $this->get_analytics();

			// try to fetch analytics accounts
			try {

				// give ourselves an unlimited timeout if possible
				@set_time_limit( 0 );

				// get the account summaries in one API call
				$account_summaries = $analytics->management_accountSummaries->listManagementAccountSummaries();

				// loop over the account summaries to get available web properties
				foreach ( $account_summaries->getItems() as $account_summary ) {

					if ( ! $account_summary instanceof Google_Service_Analytics_AccountSummary ) {
						continue;
					}

					// loop over the properties to get available profiles
					foreach ( $account_summary->getWebProperties() as $property ) {

						if ( ! $property instanceof Google_Service_Analytics_WebPropertySummary ) {
							continue;
						}

						$optgroup = sprintf( '%s - %s', $account_summary->getName(), $property->getName() );
						$ga_profiles[ $optgroup ] = array();

						// loop over the profiles
						foreach ( $property->getProfiles() as $profile ) {

							if ( ! $profile instanceof Google_Service_Analytics_ProfileSummary ) {
								continue;
							}

							$ga_profiles[ $optgroup ][ $account_summary->getId() . '|' . $property->getId() . '|' . $profile->getId() ] = sprintf( '%s (%s)', $profile->getName(), $property->getId() );
						}

						// sort profiles naturally
						natcasesort( $ga_profiles[ $optgroup ] );
					}
				}
			}

			// catch service exception
			catch ( Google_Service_Exception $e ) {

				wc_google_analytics_pro()->log( $e->getMessage() );

				if ( is_admin() ) {
					wc_google_analytics_pro()->get_admin_notice_handler()->add_admin_notice(
						'<strong>' . wc_google_analytics_pro()->get_plugin_name() . ':</strong> ' .
						sprintf(
							/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
							__( 'The request to list the Google Analytics profiles for the currently authenticated Google account has timed out. Please try again in a few minutes or try re-authenticating with your Google account.', 'woocommerce-google-analytics-pro' ),
							'<a href="https://console.developers.google.com/" target="_blank">',
							'</a>'
 						),
						wc_google_analytics_pro()->get_id() . '-account-' . get_option( 'wc_google_analytics_pro_account_id' ) . '-no-analytics-access',
						array( 'dismissible' => true, 'always_show_on_settings' => false, 'notice_class' => 'error' )
					);
				}

				// return a blank array so select box is valid
				return array();
			}

			// catch general google exception
			catch ( Google_Exception $e ) {

				wc_google_analytics_pro()->log( $e->getMessage() );

				if ( is_admin() ) {
					wc_google_analytics_pro()->get_admin_notice_handler()->add_admin_notice(
						'<strong>' . wc_google_analytics_pro()->get_plugin_name() . ':</strong> ' .
						__( 'The currently authenticated Google account does not have access to any Analytics accounts. Please re-authenticate with an account that has access to Google Analytics.', 'woocommerce-google-analytics-pro' ),
						wc_google_analytics_pro()->get_id() . '-account-' . get_option( 'wc_google_analytics_pro_account_id' ) . '-no-analytics-access',
						array( 'dismissible' => true, 'always_show_on_settings' => false, 'notice_class' => 'error' )
					);
				}

				// return a blank array so select box is valid
				return array();
			}

			// sort properties in the United Kingdom... just kidding, sort by keys, by comparing them naturally
			uksort( $ga_profiles, 'strnatcasecmp' );

			// set 5 minute transient
			set_transient( 'wc_google_analytics_pro_profiles', $ga_profiles, 5 * MINUTE_IN_SECONDS );
		}

		return $ga_profiles;
	}


	/**
	 * Filter admin options before saving
	 *
	 * @since 1.0.0
	 * @param array $sanitized_fields
	 * @return array
	 */
	public function filter_admin_options( $sanitized_fields ) {

		// prevent button labels from being saved
		unset( $sanitized_fields['oauth_button'] );

		// unset web profile if manual tracking is being used
		if ( isset( $sanitized_fields['use_manual_tracking_id'] ) && 'yes' == $sanitized_fields['use_manual_tracking_id'] ) {
			$sanitized_fields['profile'] = '';
		}

		// get tracking ID from web profile, if using oAuth, and save it to the tracking ID option
		elseif ( ! empty( $sanitized_fields['profile'] ) ) {

			$parts = explode( '|', $sanitized_fields['profile'] );
			$sanitized_fields['tracking_id'] = $parts[1];
		}

		// manual tracking ID not configured, and no profile selected. Remove tracking ID.
		else {
			$sanitized_fields['tracking_id'] = '';
		}

		return $sanitized_fields;
	}


	/**
	 * Get the currently selected Google Analytics Account ID
	 *
	 * @since 1.0.0
	 * @return int|null
	 */
	public function get_ga_account_id() {
		return $this->get_ga_profile_part( 0 );
	}


	/**
	 * Get the currently selected Google Analytics property ID
	 *
	 * @since 1.0.0
	 * @return int|null
	 */
	public function get_ga_property_id() {
		return $this->get_ga_profile_part( 1 );
	}


	/**
	 * Get the currently selected Google Analytics profile ID
	 *
	 * @since 1.0.0
	 * @return int|null
	 */
	public function get_ga_profile_id() {
		return $this->get_ga_profile_part( 2 );
	}


	/**
	 * Get the given part from the profile option
	 *
	 * @since 1.0.0
	 * @param int $key
	 * @return mixed|null
	 */
	private function get_ga_profile_part( $key ) {

		$profile = $this->get_option( 'profile' );

		if ( ! $profile ) {
			return;
		}

		$pieces = explode( '|', $profile );

		if ( ! isset( $pieces[ $key ] ) ) {
			return;
		}

		return $pieces[ $key ];
	}


}
