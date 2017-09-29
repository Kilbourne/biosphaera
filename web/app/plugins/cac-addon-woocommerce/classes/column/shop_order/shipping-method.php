<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.4
 */
class CPAC_WC_Column_Post_Order_Shipping_Method extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-order_shipping_method';
		$this->properties['label'] = __( 'Shipping Method', 'woocommerce' );
		$this->properties['is_sortable'] = true;
	}

	public function get_value( $order_id ) {
		return $this->get_raw_value( $order_id );
	}

	public function get_raw_value( $order_id ) {
		$order = new WC_Order( $order_id );

		return $order->get_shipping_method();
	}

	public function get_shipping_methods() {
		global $woocommerce;
		$shipping_methods = $woocommerce->shipping->load_shipping_methods();
		$options = array();

		foreach ( $shipping_methods as $key => $method ) {
			$options[ $key ] = $method->method_title;
		}

		return $options;
	}
}