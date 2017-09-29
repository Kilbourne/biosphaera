<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Order_Status extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'order_status';
		$this->properties['label'] = __( 'Order Status', 'codepress-admin-columns' );
		$this->properties['handle'] = 'order_status';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {
		$order = new WC_Order( $post_id );

		return $order->get_status();
	}
}