<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Reviews_Enabled extends CPAC_Column_Post_Comment_Status {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-reviews_enabled';
		$this->properties['label'] = __( 'Reviews enabled', 'codepress-admin-columns' );
		$this->properties['group'] = __( 'WooCommerce', 'codepress-admin-columns' );
	}
}