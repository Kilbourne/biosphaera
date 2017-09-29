<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Minimum_Amount extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-minimum_amount';
		$this->properties['label'] = __( 'Minimum amount', 'codepress-admin-columns' );
	}

	public function get_value( $post_id ) {
		$amount = $this->get_raw_value( $post_id );

		return $amount ? wc_price( $amount ) : $this->get_empty_char();
	}

	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return $coupon->minimum_amount;
	}
}