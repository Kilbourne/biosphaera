<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Type extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'type';
		$this->properties['label'] = __( 'Type', 'woocommerce' );
		$this->properties['handle'] = 'type';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return $coupon->type;
	}
}