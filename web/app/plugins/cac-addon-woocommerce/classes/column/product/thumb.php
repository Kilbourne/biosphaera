<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Thumb extends CPAC_WC_Column_Default {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		// Properties
		$this->properties['type'] = 'thumb';
		$this->properties['label'] = __( 'Image', 'woocommerce' );
		$this->properties['handle'] = 'thumb';
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		if ( ! has_post_thumbnail( $post_id ) ) {
			return false;
		}

		return get_post_thumbnail_id( $post_id );
	}
}