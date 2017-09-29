<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_Post_Order_Coupons_Used extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-order_coupons_used';
		$this->properties['label'] = __( 'Coupons Used', 'codepress-admin-columns' );
	}

	public function get_value( $post_id ) {
		$coupons = '';
		$order = new WC_Order( $post_id );
		if ( $order && ( $used_coupons = $order->get_used_coupons() ) ) {
			$coupons = implode( ' | ', $used_coupons );
		}

		return $coupons;
	}

	public function get_raw_value( $post_id ) {
		$order = new WC_Order( $post_id );

		return count( $order->get_used_coupons() );
	}
}