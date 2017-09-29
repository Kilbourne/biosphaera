<?php

defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Transaction_ID extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-transaction_id';
		$this->properties['label'] = __( 'Transaction ID', 'woocommerce' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.0
	 */
	public function get_value( $post_id ) {
		$transaction_id = $this->get_raw_value( $post_id );

		return $transaction_id ? $transaction_id : $this->get_empty_char();
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		return get_post_meta( $post_id, '_transaction_id', true );
	}
}