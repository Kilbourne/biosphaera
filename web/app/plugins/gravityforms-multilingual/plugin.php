<?php
/*
Plugin Name: Gravity Forms Multilingual
Plugin URI: http://wpml.org/documentation/related-projects/gravity-forms-multilingual/
Description: Add multilingual support for Gravity Forms | <a href="https://wpml.org">Documentation</a> | <a href="https://wpml.org/version/wpml-1-3-13/">WPML 1.3.13 release notes</a>
Author: OnTheGoSystems
Author URI: http://www.onthegosystems.com/
Version: 1.3.13
Plugin Slug: gravityforms-multilingual
*/

if ( defined( 'GRAVITYFORMS_MULTILINGUAL_VERSION' ) ) {
	return;
}

define( 'GRAVITYFORMS_MULTILINGUAL_VERSION', '1.3.13' );
define( 'GRAVITYFORMS_MULTILINGUAL_PATH', dirname( __FILE__ ) );

$autoloader_dir = GRAVITYFORMS_MULTILINGUAL_PATH . '/vendor';
if ( version_compare( PHP_VERSION, '5.3.0' ) >= 0 ) {
	$autoloader = $autoloader_dir . '/autoload.php';
} else {
	$autoloader = $autoloader_dir . '/autoload_52.php';
}
require_once $autoloader;

add_action( 'wpml_gfml_has_requirements', 'load_gfml' );

new WPML_GFML_Requirements();

function load_gfml() {
	if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
		require GRAVITYFORMS_MULTILINGUAL_PATH . '/vendor/wpml/commons/src/dependencies/class-wpml-dependencies.php';
		require GRAVITYFORMS_MULTILINGUAL_PATH . '/inc/gfml-string-name-helper.class.php';
		require GRAVITYFORMS_MULTILINGUAL_PATH . '/inc/gravity-forms-multilingual.class.php';

		require GRAVITYFORMS_MULTILINGUAL_PATH . '/inc/gfml-tm-api.class.php';
		$GLOBALS['wpml_gfml_tm_api'] = new GFML_TM_API();

		global $sitepress;
		$current_language = $sitepress->get_current_language();
		new WPML_GFML_Filter_Field_Meta( $current_language );
		do_action( 'wpml_gfml_tm_api_loaded', $GLOBALS['wpml_gfml_tm_api'] );
	}
}

// Disable the normal wpml admin language switcher for gravity forms.
function gfml_disable_wpml_admin_lang_switcher($state)
{
	global $pagenow;

	if ($pagenow == 'admin.php' && isset($_GET['page']) &&
		$_GET['page'] == 'gf_edit_forms') {

		$state = false;
	}

	return $state;
}
add_filter('wpml_show_admin_language_switcher', 'gfml_disable_wpml_admin_lang_switcher');

/**
 * GFML Quiz compatibility
 * Instantiate the plugin after GFML
 * to get to inject the instance of $gfml_tm_api
 *
 * @param GFML_TM_API $gfml_tm_api
 */
function wpml_gf_quiz_init( $gfml_tm_api ) {
	if ( !defined( 'GF_QUIZ_VERSION' ) || version_compare( ICL_SITEPRESS_VERSION, '3.2', '<' ) ) {
		return;
	}

	new WPML_GF_Quiz( $gfml_tm_api );
}
add_action( 'wpml_gfml_tm_api_loaded', 'wpml_gf_quiz_init' );
