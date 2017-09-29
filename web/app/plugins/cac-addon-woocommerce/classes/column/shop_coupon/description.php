<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Description extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type']	= 'description';
		$this->properties['label']	= __( 'Description', 'woocommerce' );
		$this->properties['handle'] = 'description';
		$this->properties['is_cloneable'] = false;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		return get_post_field( 'post_excerpt', $post_id );
	}
}