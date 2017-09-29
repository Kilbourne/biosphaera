<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Stock extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {

		parent::init();

		// define properties
		$this->properties['type'] = 'is_in_stock';
		$this->properties['label'] = __( 'Stock', 'woocommerce' );
		$this->properties['handle'] = 'is_in_stock';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( $product->is_type( 'variable', 'grouped', 'external' ) ) {
			return;
		}

		$value = array();
		$value['stock_status'] = $product->is_in_stock() ? 'instock' : 'outofstock';

		if ( get_option( 'woocommerce_manage_stock' ) === 'yes' ) {
			$value['woocommerce_option_manage_stock'] = true;
			$value['manage_stock'] = $product->manage_stock;

			$stock = get_post_meta( $post_id, '_stock', true );
			$value['stock'] = ( $stock !== false ) ? $stock : '';
		}
		else {
			$value['woocommerce_option_manage_stock'] = false;
		}

		return $value;
	}

}