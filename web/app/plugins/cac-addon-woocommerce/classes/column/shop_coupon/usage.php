<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Usage extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'usage';
		$this->properties['label'] = __( 'Usage / Limit', 'woocommerce' );
		$this->properties['handle'] = 'usage';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return array(
			'usage_limit'          => $coupon->usage_limit,
			'usage_limit_per_user' => $coupon->usage_limit_per_user
		);
	}
}