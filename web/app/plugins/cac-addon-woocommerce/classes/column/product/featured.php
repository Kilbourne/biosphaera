<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.2
 */
class CPAC_WC_Column_Post_Featured extends CPAC_WC_Column {

	public function init() {

		parent::init();

		// define properties
		$this->properties['type'] = 'column-wc-featured';
		$this->properties['label'] = __( 'Featured', 'woocommerce' );
	}

	public function get_value( $post_id ) {
		$is_featured = $this->get_raw_value( $post_id );

		return $is_featured ? '<span class="dashicons dashicons-yes cpac_status_yes"></span>' : '<span class="dashicons dashicons-no cpac_status_no"></span>' ;
	}

	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return $product->is_featured();
	}
}