<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit when accessed directly

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Stock_Status extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type']	= 'column-wc-stock-status';
		$this->properties['label']	= __( 'Stock status', 'woocommerce' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.0
	 */
	public function get_value( $post_id ) {
		$stock_status = $this->get_raw_value( $post_id );

		$value = '&ndash;';

		$data_tip = '';
		$product = wc_get_product( $post_id );

		if ( $product->is_type( 'variable', 'grouped', 'external' ) ) {
			$data_tip = ' (<em>' . __( 'Stock status editing not supported for variable, grouped and external products.', 'codepress-admin-columns' ) . '</em>)';
		}

		if ( 'instock' == $stock_status ) {
			$value = '<span class="cpac-tip" data-tip="' . esc_attr__( 'In stock', 'codepress-admin-columns' ) . $data_tip . '"><span class="dashicons dashicons-yes cpac_status_yes" title="' . esc_attr( $stock_status ) .  '"></span></span>';
		}
		else if ( 'outofstock' == $stock_status ) {
			$value = '<span class="cpac-tip" data-tip="' . esc_attr__( 'Out of stock', 'woocommerce' ) . $data_tip . '"><span class="dashicons dashicons-no cpac_status_no" title="' . esc_attr( $stock_status ) .  '"></span></span>';
		}
		else {
		}

		return $value;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 2.0.3
	 */
	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return $product->stock_status;
	}

}