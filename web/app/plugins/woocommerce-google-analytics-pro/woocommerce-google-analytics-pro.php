<?php
/**
 * Plugin Name: WooCommerce Google Analytics Pro
 * Plugin URI: http://www.woothemes.com/products/woocommerce-google-analytics-pro/
 * Description: Supercharge your Google Analytics tracking with enhanced eCommerce tracking and custom event tracking
 * Author: WooThemes / SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.1.5
 * Text Domain: woocommerce-google-analytics-pro
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2015-2016 SkyVerge, Inc.
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Google-Analytics-Pro
 * @author    SkyVerge
 * @category  Integration
 * @copyright Copyright (c) 2015-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'd8aed8b7306b509eec1589e59abe319f', '1312497' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.4.1', 'WooCommerce Google Analytics Pro', __FILE__, 'init_woocommerce_google_analytics_pro', array(
	'minimum_wc_version'   => '2.4.13',
	'minimum_wp_version'   => '4.1',
	'backwards_compatible' => '4.4.0',
) );

function init_woocommerce_google_analytics_pro() {

/**
 * # WooCommerce Google Analytics Pro Main Plugin Class
 *
 * ## Plugin Overview
 *
 * This plugin adds Google Analytics tracking to many different WooCommerce events, like adding a product to the cart or completing
 * a purchase. Admins can control the name of the events and properties sent to Google Analytics in the integration settings section.
 *
 * ## Features
 *
 * + Provides basic Google Analytics tracking
 * + Provides basic eCommerce tracking
 * + Provides enhanced eCommerce tracking
 * + Provides event tracking using both analytics.js and the Measurement Protocol
 *
 * ## Admin Considerations
 *
 * + The plugin is added as an integration, so all settings exist inside the integrations section (WooCommerce > Settings > Integrations)
 *
 * ## Frontend Considerations
 *
 * + The Google Analytics tracking javascript is added to the <head> of every page load
 *
 * ## Database
 *
 * ### Global Settings
 *
 * + `woocommerce_google_analytics_pro_settings` - a serialized array of Google Analytics Pro integration settings, include API credentials and event/property names
 *
 * ### Options table
 *
 * + `wc_google_analytics_pro_version` - the current plugin version, set on install/upgrade
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro extends SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.1.5';

	/** @var WC_Google_Analytics_Pro single instance of this plugin */
	protected static $instance;

	/** plugin id */
	const PLUGIN_ID = 'google_analytics_pro';

	/** @var \WC_Google_Analytics_Pro_AJAX instance */
	protected $ajax;

	/** @var \WC_Google_Analytics_Pro_Integration instance */
	private $integration = null;

	/** @var bool whether we have run analytics profile checks */
	private $has_run_analytics_profile_checks = false;


	/**
	 * Construct.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct( self::PLUGIN_ID, self::VERSION );

		// load integration
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );
	}


	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// load the Google client API if it doesn't exist
		if ( ! ( function_exists( 'google_api_php_client_autoload' ) || class_exists( 'Google_Client' ) ) ) {
			require_once( $this->get_plugin_path() . '/lib/google/google-api-php-client/src/Google/autoload.php' );
		}

		// base integration
		require_once( $this->get_plugin_path() . '/includes/class-sv-wc-tracking-integration.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-google-analytics-pro-integration.php' );

		add_filter( 'woocommerce_integrations', array( $this, 'load_integration' ) );

		// AJAX includes
		if ( is_ajax() ) {
			$this->ajax_includes();
		}

		if ( is_admin() ) {

			// Check if free WooCommerce Google Analytics integration is activated and deactivate it
			if ( $this->is_plugin_active( 'woocommerce-google-analytics-integration.php' ) ) {
				$this->maybe_deactivate_free_integration();
			}
		}
	}


	/**
	 * Include required AJAX files
	 *
	 * @since 1.0.0
	 */
	private function ajax_includes() {

		$this->ajax = $this->load_class( '/includes/class-wc-google-analytics-pro-ajax.php', 'WC_Google_Analytics_Pro_AJAX' );
	}


	/**
	 * Add GA Pro as WC integration
	 *
	 * @since 1.0.0
	 * @param array $integrations
	 * @return array
	 */
	public function load_integration( $integrations ) {

		$integrations[] = 'WC_Google_Analytics_Pro_Integration';

		return $integrations;
	}


	/**
	 * Return ajax class instance
	 *
	 * @since 1.1.0
	 * @return \WC_Google_Analytics_Pro_AJAX
	 */
	public function get_ajax_instance() {
		return $this->ajax;
	}


	/**
	 * Handle localization, WPML compatible
	 *
	 * @since 1.0.0
	 */
	public function load_translation() {

		load_plugin_textdomain( 'woocommerce-google-analytics-pro', false, dirname( plugin_basename( $this->get_file() ) ) . '/i18n/languages' );
	}


	/** Helper methods ********************************************************/


	/**
	 * Main Google Analytics Pro Instance, ensures only one instance is/can be loaded
	 *
	 * @since 1.0.0
	 * @see wc_google_analytics_pro()
	 * @return WC_Google_Analytics_Pro
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Get the Ajax class instance
	 *
	 * @since 1.0.0
	 * @return \WC_Google_Analytics_Pro_AJAX instance
	 */
	public function ajax() {

		/* @deprecated since 1.1.0 */
		_deprecated_function( 'wc_google_analytics_pro()->ajax()', '1.1.0', 'wc_google_analytics_pro()->get_ajax_instance()' );
		return $this->get_ajax_instance();
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce Google Analytics Pro', 'woocommerce-google-analytics-pro' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}

	/**
	 * Gets the plugin documentation url, which for Customer/Order CSV Export is non-standard
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://docs.woothemes.com/document/woocommerce-google-analytics-pro/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {
		return 'http://support.woothemes.com/';
	}

	/**
	 * Gets the URL to the settings page
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @param string $_ unused
	 * @return string URL to the settings page
	 */
	public function get_settings_url( $_ = '' ) {

		return admin_url( 'admin.php?page=wc-settings&tab=integration&section=google_analytics_pro' );
	}


	/**
	 * Returns true if on the plugin settings page
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'] ) && 'wc-settings' === $_GET['page'] &&
		       isset( $_GET['tab'] ) && 'integration' === $_GET['tab'] &&
		       ( ! isset( $_GET['section'] ) || $this->get_id() === $_GET['section'] );
	}


	/**
	 * Maybe automatically log API requests/responses when debug mode is enabled
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::add_api_request_logging()
	 */
	public function add_api_request_logging() {

		$settings = get_option( 'woocommerce_google_analytics_pro_settings', array() );

		if ( ! isset( $settings['debug_mode'] ) || 'off' === $settings['debug_mode'] ) {
			return;
		}

		parent::add_api_request_logging();
	}


	/**
	 * Handle deactivating the free integration if needed
	 *
	 * @since 1.0.0
	 */
	public function maybe_deactivate_free_integration() {

		$settings = get_option( 'woocommerce_google_analytics_settings', array() );

		// if standard tracking is enabled, just disable purchase/add to cart tracking
		if ( ! empty( $settings['ga_id'] ) && 'yes' === $settings['ga_standard_tracking_enabled'] ) {

			// disable eCommerce tracking in free integration
			$settings['ga_ecommerce_tracking_enabled']          = 'no';
			$settings['ga_event_tracking_enabled']              = 'no';
			$settings['ga_enhanced_ecommerce_tracking_enabled'] = 'no';

			update_option( 'woocommerce_google_analytics_settings', $settings );

			/* translators: Placeholders: %1$s - plugin name, wrapped in <strong> tags, %2$s - anchor link tag, e.g. <a href=""></a> */
			$notice = sprintf( esc_html__( '%1$s: You are using the free WooCommerce Google Analytics integration for standard tracking. For best results, we recommend that you use a separate plugin for standard tracking, like %2$s.', 'woocommerce-google-analytics-pro' ),
					'<strong>' . $this->get_plugin_name() . '</strong>',
					'<a href="' . esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=google-analytics-for-wordpress' ) ) . '">Google Analytics by MonsterInsights</a>'
			);

		} else {

			// otherwise assume another GA tracking plugin (e.g. Yoast) is active and simply deactivate
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( 'woocommerce-google-analytics-integration/woocommerce-google-analytics-integration.php' );

			$notice = '<strong>' . $this->get_plugin_name() . ':</strong> ' .
				__( 'The free WooCommerce Google Analytics integration has been deactivated and is not needed when the Pro version is active.', 'woocommerce-google-analytics-pro' );

			// hide the free integration's connection notice, if it hasn't already been dismissed
			ob_start();

			?>$( 'a[href$="page=wc-settings&tab=integration&section=google_analytics"]' ).closest( 'div.updated' ).hide();<?php

			wc_enqueue_js( ob_get_clean() );
		}

		$this->get_admin_notice_handler()->add_admin_notice( $notice, 'free-integration', array( 'dismissible' => true, 'notice_class' => 'updated' ) );
	}


	/**
	 * Display various admin notices to assist with proper setup and configuration
	 *
	 * @since 1.0.0
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		// onboarding notice
		if ( ! $this->get_integration()->get_access_token() && 'yes' !== $this->get_integration()->get_option( 'use_manual_tracking_id' ) ) {

			if ( $this->is_plugin_settings() ) {

				// just show "read the docs" notice when on settings
				$notice = sprintf(
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
						__( '%1$sNeed help setting up WooCommerce Google Analytics Pro?%2$s %3$sRead the documentation%4$s.', 'woocommerce-google-analytics-pro' ),
						'<strong>',
						'</strong>',
						'<a target="_blank" href="' . esc_url( $this->get_documentation_url() ) . '">',
						'</a>'
				);

			} else {

				// show "Connect to GA" notice everywhere else
				$notice = sprintf(
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
						__( '%1$sWooCommerce Google Analytics Pro is almost ready!%2$s To get started, please ​%3$sconnect to Google Analytics%4$s.', 'woocommerce-google-analytics-pro' ),
						'<strong>',
						'</strong>',
						'<a href="' . esc_url( $this->get_settings_url() ) . '">',
						'</a>'
				);
			}

			$this->get_admin_notice_handler()->add_admin_notice( $notice, 'onboarding', array( 'dismissible' => true, 'notice_class' => 'updated', 'always_show_on_settings' => false ) );
		}

		// show Yoast-related notices
		if ( $this->is_yoast_ga_active() && $this->is_plugin_settings() ) {

			// warn about Yoast's settings taking over ours
			$this->get_admin_notice_handler()->add_admin_notice(
				'<strong>' . $this->get_plugin_name() . ':</strong> ' .
				__( 'Google Analytics by MonsterInsights plugin is active. Its settings will take precedence over the values set in the "Tracking Settings" section.', 'woocommerce-google-analytics-pro' ),
				'yoast-active',
				array( 'dismissible' => true, 'always_show_on_settings' => false )
			);

			// warn about Yoast in debug mode
			if ( $this->get_yoast_ga_option( 'debug_mode' ) ) {

				$this->get_admin_notice_handler()->add_admin_notice(
					'<strong>' . $this->get_plugin_name() . ':</strong> ' .
					__( 'Google Analytics by MonsterInsights is set to Debug Mode. Please disable debug mode so WooCommerce Google Analytics Pro can function properly.', 'woocommerce-google-analytics-pro' ),
					'yoast-in-debug'
				);

			}

			// warn about Yoast not having universal tracking enabled
			if ( ! $this->get_yoast_ga_option( 'enable_universal' ) ) {

				$this->get_admin_notice_handler()->add_admin_notice(
					'<strong>' . $this->get_plugin_name() . ':</strong> ' .
					__( 'WooCommerce Google Analytics Pro requires Universal Analytics. Please enable Universal Analytics in Google Analytics by MonsterInsights.', 'woocommerce-google-analytics-pro' ),
					'yoast-in-non-universal'
				);
			}
		}
	}


	/**
	 * Display admin notices on the Integration page if Analytics profile settings are not correct
	 *
	 * @since 1.0.0
	 */
	public function add_delayed_admin_notices() {

		$this->check_analytics_profile_settings();
	}


	/**
	 * Check Google Analytics profile for correct settings
	 *
	 * @since 1.0.0
	 */
	private function check_analytics_profile_settings() {

		if ( $this->has_run_analytics_profile_checks ) {
			return;
		}

		if ( ! $this->is_plugin_settings() ) {
			return;
		}

		$integration = $this->get_integration();

		if ( 'yes' === $integration->get_option( 'use_manual_tracking_id' ) ) {
			return;
		}

		if ( ! $integration->get_access_token() ) {
			return;
		}

		$analytics   = $integration->get_analytics();
		$account_id  = $integration->get_ga_account_id();
		$property_id = $integration->get_ga_property_id();
		$profile_id  = $integration->get_ga_profile_id();

		if ( $account_id && $property_id && $profile_id ) {

			try {

				$profile              = $analytics->management_profiles->get( $account_id, $property_id, $profile_id );
				$property_internal_id = $profile->getInternalWebPropertyId();

				if ( ! $profile->getEnhancedECommerceTracking() ) {

					$url = "https://www.google.com/analytics/web/?hl=en#management/Settings/a{$account_id}w{$property_internal_id}p{$profile_id}/%3Fm.page%3DEcommerceSettings/";

					$this->get_admin_notice_handler()->add_admin_notice(
						'<strong>' . $this->get_plugin_name() . ':</strong> ' .
						/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
						sprintf( __( 'WooCommerce Google Analytics Pro requires Enhanced Ecommerce to be enabled. Please enable Enhanced Ecommerce on your %1$sGoogle Analytics Profile%2$s.', 'woocommerce-google-analytics-pro' ), '<a href="' . $url . '" target="_blank">', '</a>' ),
						'enhanced-ecommerce-not-enabled'
					);
				}


				if ( $profile->getCurrency() != get_woocommerce_currency() ) {

					$url = "https://www.google.com/analytics/web/?hl=en#management/Settings/a{$account_id}w{$property_internal_id}p{$profile_id}/%3Fm.page%3DProfileSettings/";

					$this->get_admin_notice_handler()->add_admin_notice(
						'<strong>' . $this->get_plugin_name() . ':</strong> ' .
						/* translators: Placeholders: %1$s and %2$s - currency code, e.g. USD, %3$s - <a> tag, %4$s - </a> tag */
						sprintf( __( 'Your Google Analytics Profile currency (%1$s) does not match WooCommerce currency (%2$s). You can change it %3$son your Analytics profile%4$s.', 'woocommerce-google-analytics-pro' ), $profile->getCurrency(), get_woocommerce_currency(), '<a href="' . $url . '" target="_blank">', '</a>' ),
						'analytics-currency-mismatch',
						array( 'dismissible' => true, 'always_show_on_settings' => false )
					);
				}

				$this->has_run_analytics_profile_checks = true;
			}

			catch ( Exception $e ) {
				$this->log( $e->getMessage() );
			}
		}
	}


	/**
	 * Is Google Analytics by Yoast active?
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_yoast_ga_active() {
		return defined( 'GAWP_VERSION' );
	}


	/**
	 * Yoast GA helper: Options
	 *
	 * @since 1.0.0
	 * @param string $option_name
	 * @return mixed|null
	 */
	public function get_yoast_ga_option( $option_name ) {
		$options = (array) Yoast_GA_Options::instance()->options;

		return isset( $options[ $option_name ] ) ? $options[ $option_name ] : null;
	}


	/**
	 * Get the product identifier. SKU if available, ID otherwise
	 *
	 * @since 1.0.3
	 * @param mixed $product
	 * @return string|int
	 */
	public function get_product_identifier( $product ) {

		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return '';
		}

		if ( $product->get_sku() ) {
			$identifier = $product->get_sku();
		} else {
			$identifier = '#' . $product->id;
		}

		return $identifier;
	}


	/**
	 * Returns the instance of WC_Google_Analytics_Pro_Integration, the integration class
	 *
	 * @since 1.0.0
	 * @return WC_Google_Analytics_Pro_Integration The integration class instance
	 */
	public function get_integration() {

		if ( is_null( $this->integration ) ) {

			$integrations = WC()->integrations->get_integrations();

			$this->integration = $integrations['google_analytics_pro'];
		}

		return $this->integration;
	}


} // \WC_Google_Analytics_Pro


/**
 * Returns the One True Instance of Google Analytics Pro
 *
 * @since 1.0.0
 * @return WC_Google_Analytics_Pro
 */
function wc_google_analytics_pro() {
	return WC_Google_Analytics_Pro::instance();
}

// fire it up!
wc_google_analytics_pro();

} // init_woocommerce_google_analytics_pro()
