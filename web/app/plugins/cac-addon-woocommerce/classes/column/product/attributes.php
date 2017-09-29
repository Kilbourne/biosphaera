<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Attributes extends CPAC_WC_Column {

	private $product_taxonomies;

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {
		parent::init();

		// Properties
		$this->properties['type']  = 'column-wc-attributes';
		$this->properties['label'] = __( 'Attributes', 'woocommerce' );

		$this->options['product_taxonomy_display'] = '';
	}

	private function get_product_taxonomies() {
		if ( empty( $this->product_taxonomies ) ) {
			if ( $taxonomies = get_taxonomies( array( 'object_type' => array( 'product' ) ), 'objects' ) ) {
				foreach ( $taxonomies as $name => $taxonomy ) {
					if ( substr( $name, 0, strlen( 'pa_' ) ) === 'pa_' ) {
						$this->product_taxonomies[ $name ] = $taxonomy->labels->name;
					}
				}
			}
		}

		return $this->product_taxonomies;
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $product_id ) {
		$attributes = $this->get_raw_value( $product_id );

		$for_taxonomy       = $this->get_option( 'product_taxonomy_display' );
		$product_taxonomies = $this->get_product_taxonomies();

		$values = array();

		foreach ( $attributes as $name => $attribute ) {

			if ( $for_taxonomy && $name != $for_taxonomy ) {
				continue;
			}

			$value      = str_replace( ' |', ', ', $attribute['value'] );
			$name_label = $attribute['name'];

			if ( $attribute['is_taxonomy'] ) {
				$product    = wc_get_product( $product_id );
				$att_values = $product->get_attribute( $name );
				$value      = $att_values;
				$name_label = $product_taxonomies[ $name ];
			}

			$tooltip = array();
			if ( $attribute['is_visible'] ) {
				$tooltip[] = __( 'Visible on the product page', 'woocommerce' );
			}
			if ( $attribute['is_variation'] ) {
				$tooltip[] = __( 'Used for variations', 'woocommerce' );
			}
			if ( $attribute['is_taxonomy'] ) {
				$tooltip[] = __( 'Is a taxonomy', 'codepress-admin-columns' );
			}

			$label = '<strong class="label cpac-tip" data-tip="' . implode( ' | ', $tooltip ) . '">' . $name_label . ':</strong>';
			if ( $name == $for_taxonomy ) {
				$label = '';
			}

			$values[] = '
				<div class="attribute">
					' . $label . '
					<span class="values">' . $value . '</span>
				</div>
				';
		}

		return implode( '', $values );
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $product_id ) {
		return get_post_meta( $product_id, '_product_attributes', true );
	}


	/**
	 * @see CPAC_Column::display_settings()
	 * @since 1.3
	 */
	public function display_settings() {
		$this->display_field_product_taxonomy_display();
	}

	/**
	 * Display settings field for post property to display
	 *
	 * @since 2.4.7
	 */
	public function display_field_product_taxonomy_display() {
		$product_taxonomies = $this->get_product_taxonomies();
		if ( count( $product_taxonomies ) >= 1 ) {

			$product_taxonomies = array( '' => __( 'Show all attributes', 'codepress-admin-columns' ) ) + $product_taxonomies;

			$this->display_field_select(
				'product_taxonomy_display',
				__( 'Show Single', 'codepress-admin-columns' ),
				$product_taxonomies,
				__( 'Display a single attribute. Only works for taxonomy attributes.', 'codepress-admin-columns' )
			);
		}

		// In case all taxonomies are removed
		else {
			$this->display_field_hidden( 'product_taxonomy_display' );
		}
	}
}