<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Price extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'price';
		$this->properties['label'] = __( 'Price', 'woocommerce' );
		$this->properties['handle'] = 'price';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( $product->is_type( 'variable', 'grouped' ) ) {
			return;
		}

		$sale_from = $product->sale_price_dates_from;
		$sale_to = $product->sale_price_dates_to;

		return array(
			'regular_price'         => $product->get_regular_price(),
			'sale_price'            => $product->get_sale_price(),
			'sale_price_dates_from' => $sale_from ? date( 'Y-m-d', $sale_from ) : '',
			'sale_price_dates_to'   => $sale_to ? date( 'Y-m-d', $sale_to ) : ''
		);
	}
}