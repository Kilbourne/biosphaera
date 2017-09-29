<?php

/**
 * Pods: Display the formatted options for the select dropdown instead of their raw values when using inline edit
 */
function cac_pods_editable_pick_custom( $settings, $options, $object ) {

	if ( ! function_exists( 'pods_api' ) ) {
		return $settings;
	}

	if ( ! class_exists( 'PodsField_Pick', false ) ) {
		return $settings;
	}

	$column = $object->storage_model->get_column_by_name( $options['column-name'] );
	if ( $column && 'column-meta' == $column->get_type()  ) {

		$field = $column->get_field_key();
		$pod = pods_api()->load_pod( array( 'name' => $object->storage_model->key ), false );

		if ( $pod && isset( $pod['fields'][ $field ] ) ) {
			$pod_field = $pod['fields'][ $field ];

			if ( isset( $pod_field['pick_object'] ) && 'custom-simple' == $pod_field['pick_object'] ) {
				$pick = new PodsField_Pick();

				if ( $options = $pick->get_field_data( $pod_field ) ) {
					$settings['type'] = 'select';
					$settings['options'] = $object->format_options( $options );
				}
			}
		}
	}

	return $settings;
}
add_filter( 'cac/editable/options', 'cac_pods_editable_pick_custom', 10, 3 );