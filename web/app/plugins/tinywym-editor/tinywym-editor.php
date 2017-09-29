<?php
/*
Plugin Name: tinyWYM Editor
Description: tinyWYM Editor converts WordPress's WYSIWYG visual editor into a WYSIWYM editor. tinyWYM Editor also give the the control anf flexibility of the text editor without having to leave the visual editor.
Version:     1.3
Author:      Andrew Rickards
License:     GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: twym_editor
Domain Path: /languages

	tinyWYM is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.
 
	tinyWYM  is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
 
	You should have received a copy of the GNU General Public License
	along with tinyWYM. If not, see http://www.gnu.org/licenses/gpl-2.0.txt.

*/

//* If this file is called directly, abort ==================================== */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//* Load Plugin textdomain ==================================================== */
add_action( 'plugins_loaded', 'twym_load_textdomain' );

function twym_load_textdomain() {

	load_plugin_textdomain( 'twym_editor', false, '/tinywym-editor/languages/' );

}

//* Admin Settings ============================================================ */
include 'twym-admin-settings.php';

//* Plugins Page Settings Link
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'twym_settings_link' );

function twym_settings_link ( $links ) {

	$links[] = '<a href="' . admin_url( 'options-general.php?page=tinywym-settings' ) . '">' . __( 'Settings', 'twym_editor' ) . '</a>';

	return $links;
}

//* Remove Theme's Editor Stylesheet ========================================== */
add_action( 'admin_init', 'twym_remove_editor_styles', 999 );

function twym_remove_editor_styles() {

	$settings = get_option( 'twym_settings' );
	$theme_styles = isset( $settings[ 'theme_styles' ] ) ? '1' : false;

	if ( '1' === $theme_styles ) {
		return;
	}

	remove_editor_styles();

}

//* Add Editor Stylesheet ===================================================== */
add_filter( 'mce_css', 'twym_add_editor_styles' );

function twym_add_editor_styles( $mce_css ) {

	if ( ! empty( $mce_css ) ) {
		$mce_css .= ',';
	}

	$mce_css .= plugins_url( 'css/tinywym-styles.css', __FILE__ );

	return $mce_css;

}

//* Register tinyMCE Plugin & Button ========================================== */
add_action( 'init', 'twym_register_mce_plugin' );

function twym_register_mce_plugin() {

	// Get tinyWYM Settings
	$settings = get_option( 'twym_settings' );
	$disabled = isset( $settings[ 'disable' ] ) ? $settings[ 'disable' ] : array();
	$priority = isset( $settings[ 'force_enable' ] ) ? 99999999 : 10;

	// Extract user roles disabled in settings
	extract( $disabled );

	// Define user capabilities
	$admin_capabilities       = current_user_can( 'manage_options' );
	$editor_capabilities      = current_user_can( 'edit_pages' )           && ! current_user_can( 'manage_options' )       ? true : false;
	$author_capabilities      = current_user_can( 'edit_published_posts' ) && ! current_user_can( 'edit_pages' )           ? true : false;
	$contributor_capabilities = current_user_can( 'edit_posts' )           && ! current_user_can( 'edit_published_posts' ) ? true : false;

	// Disable editor for various various user roles
	if ( isset( $administrator ) && $admin_capabilities ) {
		return;
	}

	if ( isset( $editor ) && $editor_capabilities ) {
		return;
	}

	if ( isset( $author ) && $author_capabilities ) {
		return;
	}

	if ( isset( $contributor ) && $contributor_capabilities ) {
		return;
	}

	// Check user permissions
	if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
		return;
	}

	// Check if WYSIWYG is enabled
	if ( get_user_option('rich_editing') == 'true') {

		add_filter( 'mce_external_plugins', 'twym_add_mce_plugin', $priority );
		add_filter( 'mce_buttons', 'twym_register_mce_button', $priority );

		// Admin Styles for Modal Form
		add_action( 'admin_enqueue_scripts', 'twym_enqueue_admin_style' );

		// Load styles in BeaverBuilder's frontend editor
		if ( get_query_var( 'fl_builder', true ) ) {
			add_action( 'wp_enqueue_scripts', 'twym_enqueue_admin_style' );
		}

	}

	// Tranlations
	add_filter( 'mce_external_languages', 'twym_load_translation');

}

//* Register Plugin
function twym_add_mce_plugin( $plugin_array ) {

	$plugin_array['tinyWYM'] = plugins_url( 'js/mce-plugin.js', __FILE__ );

	return $plugin_array;

}

//* Register Button
function twym_register_mce_button( $buttons ) {

	array_push( $buttons, 'twym_any_tag' );
	array_push( $buttons, 'twym_toggle' );

	return $buttons;

}

//* Enqueue Admin Styles for Modal Form ======================================= */
function twym_enqueue_admin_style() {

	wp_register_style( 'twym_admin_css', plugins_url( 'css/modal-styles.css', __FILE__ ), false, '1.0.0' );
	wp_enqueue_style( 'twym_admin_css' );
		
}

//* Load Translation File ===================================================== */
function twym_load_translation( $locales ) {

	$locales[ 'twym_editor' ] = plugin_dir_path ( __FILE__ ) . 'translations.php';

	return $locales;

}