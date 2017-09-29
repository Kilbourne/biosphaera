<?php
class CACIE_ACF_FieldOptions {

	/**
	 * Field options caught from ACF ([option] => [label])
	 *
	 * @var array
	 * @since 1.0
	 */
	protected $field_options = NULL;

	/**
	 * Get field options for an ACF field
	 *
	 * @since 1.0
	 *
	 * @param array $field ACF field array
	 * @return array {
	 *     List of field options. [option] => [label] or,
	 *     in case of options group:
	 *
	 *     @type array {
	 *          @type string $label Option label.
	 *          @type array $options Options array ([option] => [label]).
	 *     }
	 * }
	 */
	public function get_field_options( $field ) {

		if ( ! in_array( $field['type'], array( 'checkbox', 'page_link', 'post_object', 'radio', 'relationship', 'select', 'taxonomy' ) ) ) {
			return false;
		}

		// Prefill field value as it's required for some types
		if ( ! isset( $field['value'] ) ) {
			$field['value'] = '';
		}

		// Finalized array of options
		$options = array();

		if ( $field['type'] == 'taxonomy' ) {
			// Options for taxonomy
			$args = array(
				'taxonomy' => $field['taxonomy'],
				'hide_empty' => false
			);

			$args = apply_filters( 'acf/fields/taxonomy/wp_list_categories', $args, $field );
			$terms = get_categories( $args );

			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = $term->name;
			}
		}
		else {
			// Fetch field options from ACF
			$options_raw = $this->get_field_options_raw( $field );

			if ( $options_raw === false ) {
				// Possibly not a dropdown field, try to fetch choices directly
				// Select and checkbox fields can have predefined choices
				if ( ! empty( $field['choices'] ) ) {
					$options = $field['choices'];
				}
			}
			else {
				foreach ( $options_raw as $index => $option ) {
					if ( is_array( $option ) ) {
						// Option group
						$options[] = array(
							'label' => $index,
							'options' => $option
						);
					}
					else {
						// Single option
						$options[ $index ] = $option;
					}
				}
			}
		}

		return $options;
	}

	/**
	 * Get raw field options from ACF
	 *
	 * @since 1.0
	 *
	 * @param array $field ACF field array
	 * @return array List of raw field options
	 */
	public function get_field_options_raw( $field ) {

		if ( empty( $field['value'] ) ) {
			$field['value'] = '';
		}

		$this->field_options = NULL;

		$acf_mv = function_exists( 'acf_get_setting' ) ? '5' : '4';

		if ( $acf_mv == '5' ) {
			add_action( 'acf/render_field', array( $this, 'catch_field_options' ) );
			ob_start();
			acf_render_field( $field );
			ob_end_clean();
			remove_action( 'acf/render_field', array( $this, 'catch_field_options' ) );
		}
		else  {
			add_action( 'acf/create_field', array( $this, 'catch_field_options' ) );
			ob_start();
			do_action( 'acf/create_field', $field );
			ob_end_clean();
			remove_action( 'acf/create_field', array( $this, 'catch_field_options' ) );
		}

		if ( is_array( $this->field_options ) ) {
			return $this->field_options;
		}

		return false;
	}

	/**
	 * Catch field options from an ACF field.
	 * Should be called as part of the field choices retrieval process on the acf/render_field
	 * or acf/create_field filters.
	 *
	 * @since 1.0
	 *
	 * @param array $field ACF field array
	 */
	public function catch_field_options( $field ) {

		if ( isset( $field['choices'] ) ) {
			$this->field_options = $field['choices'];
		}
	}

}