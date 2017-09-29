<?php

defined( 'ABSPATH' ) or exit;

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Shipping_Class extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-shipping_class';
		$this->properties['label'] = __( 'Shipping Class', 'woocommerce' );
	}

	public function get_value( $post_id ) {
		$label = '';
		if ( $term = get_term_by( 'id', $this->get_raw_value( $post_id ), 'product_shipping_class' ) ) {
			$label = sanitize_term_field( 'name', $term->name, $term->term_id, $term->taxonomy, 'display' );
		}

		return $label;
	}

	public function get_raw_value( $post_id ) {
		$shipping_id = false;
		if ( $product = $this->get_product( $post_id ) ) {
			$shipping_id = $product->get_shipping_class_id();
		}

		return $shipping_id;
	}
}