<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '_WP_Editors' ) ) {
	require( ABSPATH . WPINC . '/class-wp-editor.php' );
}

function twym_translation() {

	$strings = array(
		'title_edit_modal'   => __( 'Edit element', 'twym_editor' ),
		'title_create_modal' => __( 'Create tag', 'twym_editor' ),
		'label_tag'          => __( 'Tag', 'twym_editor' ),
		'label_attributes'   => __( 'Attributes', 'twym_editor' ),
		'modal_cancel'       => __( 'Cancel', 'twym_editor' ),
		'modal_submit'       => __( 'Submit', 'twym_editor' ),
		'alert_no_tag'       => __( 'Tag is required', 'twym_editor' ),
		'tooltip_button'     => __( 'Wrap selection or element in any HTML tag', 'twym_editor' ),
		'tooltip_toggle'     => __( 'Toggle tinyWYM', 'twym_editor' ),
	);

	$locale = _WP_Editors::$mce_locale;
	$translated = 'tinyMCE.addI18n("' . $locale . '.twym_editor", ' . json_encode( $strings ) . ");\n";

	return $translated;

}

$strings = twym_translation();