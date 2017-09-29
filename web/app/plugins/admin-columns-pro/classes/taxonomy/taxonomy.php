<?php
/**
 * Register Storage Model: Taxonomy
 *
 */
function cpac_register_storage_model_taxonomy( $storage_models, $cac ) {

	include_once "storage_model/taxonomy.php";

	if ( $taxonomies = $cac->get_taxonomies() ) {
		foreach ( $taxonomies as $taxonomy ) {
			$storage_model = new CPAC_Storage_Model_Taxonomy( $taxonomy );
			$storage_models[ $storage_model->key ] = $storage_model;
		}
	}

	return $storage_models;
}
add_filter( 'cac/storage_models', 'cpac_register_storage_model_taxonomy', 10, 2 );

/**
 * Register Columns for storage model Taxonomy
 *
 */
function cpac_register_taxonomy_columns( $columns ) {

	$columns['CPAC_Column_Term_ID'] = CAC_PRO_DIR . 'classes/taxonomy/column/ID.php';
	$columns['CPAC_Column_Term_Excerpt'] = CAC_PRO_DIR . 'classes/taxonomy/column/excerpt.php';
	$columns['CPAC_Column_Term_Parent'] = CAC_PRO_DIR . 'classes/taxonomy/column/parent.php';

	return $columns;
}
add_filter( 'cac/columns/custom/type=taxonomy', 'cpac_register_taxonomy_columns' );

/**
 * Custom Field value: Only works in combination with ACF
 *
 */
function cpac_taxonomy_field_raw_value( $raw_value, $id, $field_key, $column ) {

	if ( 'taxonomy' == $column->storage_model->type ) {
		$raw_value = get_option( $column->storage_model->taxonomy . '_' . $id . '_' . $field_key );
	}

	return $raw_value;
}
add_filter( 'cac/column/meta/raw_value', 'cpac_taxonomy_field_raw_value', 10, 4 );