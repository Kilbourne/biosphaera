<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit when accessed directly

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Coupon_Code extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'coupon_code';
		$this->properties['label'] = __( 'Code', 'woocommerce' );
		$this->properties['handle'] = 'coupon_code';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return $coupon->code;
	}
}