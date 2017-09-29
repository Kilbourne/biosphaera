<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Dimensions extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		// Properties
		$this->properties['type'] = 'column-wc-dimensions';
		$this->properties['label'] = __( 'Dimensions', 'woocommerce' );
	}

	/**
	 * @see CPAC_Column::apply_conditional()
	 * @since 1.0
	 */
	public function apply_conditional() {

		return function_exists( 'wc_product_dimensions_enabled' ) && wc_product_dimensions_enabled();
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.0
	 */
	public function get_value( $post_id ) {
		$dimensions = $this->get_raw_value( $post_id );

		if ( count( array_filter( $dimensions ) ) > 0 ) {
			return implode( ' x ', $dimensions ) . ' ' . get_option( 'woocommerce_dimension_unit' );
		}

		return '';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( $product->is_virtual() ) {
			return;
		}

		$dimensions = array();
		$dimensions['length'] = $product->length;
		$dimensions['width'] = $product->width;
		$dimensions['height'] = $product->height;

		return $dimensions;
	}
}