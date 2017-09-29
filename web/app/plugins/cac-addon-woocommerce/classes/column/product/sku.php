<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_SKU extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {

		parent::init();

		// Properties
		$this->properties['type'] = 'sku';
		$this->properties['label'] = __( 'SKU', 'woocommerce' );
		$this->properties['handle'] = 'sku';
		$this->properties['is_cloneable'] = false;
	}

	// For sorting onlyw
	public function get_value( $post_id ) {
		return $this->get_raw_value( $post_id );
	}

	// For sorting only
	public function get_raw_value( $post_id ) {
		return get_post_meta( $post_id, '_sku', true );
	}
}