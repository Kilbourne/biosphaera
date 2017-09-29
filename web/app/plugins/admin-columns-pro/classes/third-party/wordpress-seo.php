<?php

// WordPress SEO active?
function cpac_load_wpseo() {
	if ( defined( 'WPSEO_VERSION' ) ) {

		// Register the column as editable
		add_filter( 'cac/editable/is_column_editable/column=wpseo-title', '__return_true' );
		add_filter( 'cac/editable/is_column_editable/column=wpseo-metadesc', '__return_true' );
		add_filter( 'cac/editable/is_column_editable/column=wpseo-focuskw', '__return_true' );
	}
}

add_action( 'wp_loaded', 'cpac_load_wpseo' );

// Set the editable properties
function cac_wordpress_seo_column_editable_settings( $editable_data, $model ) {

	$editable_data['wpseo-title']['default_column'] = true;
	$editable_data['wpseo-title']['type'] = 'text';
	$editable_data['wpseo-title']['placeholder'] = __( 'Enter your SEO Title', 'codepress-admin-columns' );

	$editable_data['wpseo-metadesc']['default_column'] = true;
	$editable_data['wpseo-metadesc']['type'] = 'textarea';
	$editable_data['wpseo-metadesc']['placeholder'] = __( 'Enter your SEO Meta Description', 'codepress-admin-columns' );

	$editable_data['wpseo-focuskw']['default_column'] = true;
	$editable_data['wpseo-focuskw']['type'] = 'text';
	$editable_data['wpseo-focuskw']['placeholder'] = __( 'Enter your SEO Focus Keywords', 'codepress-admin-columns' );

	return $editable_data;
}

add_filter( 'cac/editable/editables_data', 'cac_wordpress_seo_column_editable_settings', 10, 2 );

// Retrieve the value that should be used for editing
function cac_wordpress_seo_title_column_value( $value, $column, $id, $model ) {
	return get_post_meta( $id, '_yoast_wpseo_title', true );
}

add_filter( 'cac/editable/column_value/column=wpseo-title', 'cac_wordpress_seo_title_column_value', 10, 4 );

function cac_wordpress_seo_metadesc_column_value( $value, $column, $id, $model ) {
	return get_post_meta( $id, '_yoast_wpseo_metadesc', true );
}

add_filter( 'cac/editable/column_value/column=wpseo-metadesc', 'cac_wordpress_seo_metadesc_column_value', 10, 4 );

function cac_wordpress_seo_focuskw_column_value( $value, $column, $id, $model ) {
	return get_post_meta( $id, '_yoast_wpseo_focuskw', true );
}

add_filter( 'cac/editable/column_value/column=wpseo-focuskw', 'cac_wordpress_seo_focuskw_column_value', 10, 4 );

// Store the value that has been entered with inline-edit to the database
function cac_wordpress_seo_title_column_save( $result, $column, $id, $value, $model ) {
	update_post_meta( $id, '_yoast_wpseo_title', $value );
}

add_filter( 'cac/editable/column_save/column=wpseo-title', 'cac_wordpress_seo_title_column_save', 10, 5 );

function cac_wordpress_seo_metadesc_column_save( $result, $column, $id, $value, $model ) {
	update_post_meta( $id, '_yoast_wpseo_metadesc', $value );
}

add_filter( 'cac/editable/column_save/column=wpseo-metadesc', 'cac_wordpress_seo_metadesc_column_save', 10, 5 );

function cac_wordpress_seo_focuskw_column_save( $result, $column, $id, $value, $model ) {
	update_post_meta( $id, '_yoast_wpseo_focuskw', $value );
}

add_filter( 'cac/editable/column_save/column=wpseo-focuskw', 'cac_wordpress_seo_focuskw_column_save', 10, 5 );

function cac_wordpress_seo_ajax_return_values( $contents, $column, $id ) {
	switch ( $column->get_type() ) {
		case 'wpseo-title':
			$contents = get_post_meta( $id, '_yoast_wpseo_title', true );
			break;
		case 'wpseo-metadesc':
			$contents = get_post_meta( $id, '_yoast_wpseo_metadesc', true );
			break;
		case 'wpseo-focuskw':
			$contents = get_post_meta( $id, '_yoast_wpseo_focuskw', true );
			break;
	}

	return $contents;
}

add_filter( 'cac/editable/after_ajax_column_save/value', 'cac_wordpress_seo_ajax_return_values', 10, 3 );

function cac_wordpress_seo_default_column_widths( $columns ) {
	$columns['wpseo-score'] = array( 'width' => 63, 'unit' => 'px' );

	return $columns;
}

add_filter( 'cac/default_column_widths', 'cac_wordpress_seo_default_column_widths' );