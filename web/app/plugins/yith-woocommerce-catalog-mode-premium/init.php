<?php
/*
Plugin Name: YITH WooCommerce Catalog Mode Premium
Plugin URI: http://yithemes.com/themes/plugins/yith-woocommerce-catalog-mode/
Description: YITH WooCommerce Catalog Mode allows you to disable shop functions.
Author: YITHEMES
Text Domain: yith-woocommerce-catalog-mode
Version: 1.5.5
Author URI: http://yithemes.com/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

function ywctm_install_premium_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php _e( 'YITH WooCommerce Catalog Mode is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-catalog-mode' ); ?></p>
	</div>
	<?php
}

if ( ! function_exists( 'yit_deactive_free_version' ) ) {
	require_once 'plugin-fw/yit-deactive-plugin.php';
}

yit_deactive_free_version( 'YWCTM_FREE_INIT', plugin_basename( __FILE__ ) );

if ( function_exists( 'yith_deactive_jetpack_module' ) ) {
	global $yith_jetpack_1;
	yith_deactive_jetpack_module( $yith_jetpack_1, 'YWCTM_PREMIUM', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YWCTM_VERSION' ) ) {
	define( 'YWCTM_VERSION', '1.5.5' );
}

if ( ! defined( 'YWCTM_INIT' ) ) {
	define( 'YWCTM_INIT', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'YWCTM_SLUG' ) ) {
	define( 'YWCTM_SLUG', 'yith-woocommerce-catalog-mode' );
}

if ( ! defined( 'YWCTM_SECRET_KEY' ) ) {
	define( 'YWCTM_SECRET_KEY', '8KywmSzFxgb5m0SFKMac' );
}

if ( ! defined( 'YWCTM_PREMIUM' ) ) {
	define( 'YWCTM_PREMIUM', '1' );
}

if ( ! defined( 'YWCTM_FILE' ) ) {
	define( 'YWCTM_FILE', __FILE__ );
}

if ( ! defined( 'YWCTM_DIR' ) ) {
	define( 'YWCTM_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'YWCTM_URL' ) ) {
	define( 'YWCTM_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'YWCTM_ASSETS_URL' ) ) {
	define( 'YWCTM_ASSETS_URL', YWCTM_URL . 'assets/' );
}

if ( ! defined( 'YWCTM_ASSETS_PATH' ) ) {
	define( 'YWCTM_ASSETS_PATH', YWCTM_DIR . 'assets/' );
}

if ( ! defined( 'YWCTM_TEMPLATE_PATH' ) ) {
	define( 'YWCTM_TEMPLATE_PATH', YWCTM_DIR . 'templates/' );
}

/* Plugin Framework Version Check */
if ( ! function_exists( 'yit_maybe_plugin_fw_loader' ) && file_exists( YWCTM_DIR . 'plugin-fw/init.php' ) ) {
	require_once( YWCTM_DIR . 'plugin-fw/init.php' );
}
yit_maybe_plugin_fw_loader( YWCTM_DIR );

function ywctm_premium_init() {
	/* Load YWCTM text domain */
	load_plugin_textdomain( 'yith-woocommerce-catalog-mode', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	$GLOBALS['YITH_WC_Catalog_Mode'] = YITH_WCTM();

}

add_action( 'ywctm_premium_init', 'ywctm_premium_init' );

function ywctm_premium_install() {

	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'ywctm_install_premium_woocommerce_admin_notice' );
	} else {
		do_action( 'ywctm_premium_init' );
	}

}

add_action( 'plugins_loaded', 'ywctm_premium_install', 11 );

/**
 * Init default plugin settings
 */
if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}

register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

if ( ! function_exists( 'YITH_WCTM' ) ) {

	/**
	 * Unique access to instance of YITH_WC_Catalog_Mode
	 *
	 * @since   1.1.5
	 * @return  YITH_WC_Catalog_Mode|YITH_WC_Catalog_Mode_Premium
	 * @author  Alberto Ruggiero
	 */
	function YITH_WCTM() {

		// Load required classes and functions
		require_once( YWCTM_DIR . 'class.yith-woocommerce-catalog-mode.php' );

		if ( defined( 'YWCTM_PREMIUM' ) && file_exists( YWCTM_DIR . 'class.yith-woocommerce-catalog-mode-premium.php' ) ) {

			require_once( YWCTM_DIR . 'class.yith-woocommerce-catalog-mode-premium.php' );

			return YITH_WC_Catalog_Mode_Premium::get_instance();
		}

		return YITH_WC_Catalog_Mode::get_instance();

	}

}

register_activation_hook( __FILE__, 'ywctm_plugin_activation_premium' );

function ywctm_plugin_activation_premium() {

	$pages_to_check = array(
		get_option( 'woocommerce_cart_page_id' ),
		get_option( 'woocommerce_checkout_page_id' )
	);

	foreach ( $pages_to_check as $page_id ) {
		if ( get_post_status( $page_id ) != 'publish' ) {
			$page = array(
				'ID'          => $page_id,
				'post_status' => 'publish'
			);

			wp_update_post( $page );
		}
	}
}