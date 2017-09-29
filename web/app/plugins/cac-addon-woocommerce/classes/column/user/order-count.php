<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_User_Order_Count extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-user-order_count';
		$this->properties['label'] = __( 'Number of orders', 'woocommerce' );
	}

	public function get_value( $user_id ) {
		return $this->get_raw_value( $user_id );
	}

	public function get_raw_value( $user_id ) {
		return count( CPAC_Addon_WC_Helper_Orders::get_all_by_user_id( $user_id, 'any' ) );
	}
}