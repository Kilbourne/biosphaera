<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.4
 */
class CPAC_WC_Column_Default extends CPAC_Column_Default {

	public function init() {
		parent::init();

		$this->properties['group'] = __( 'WooCommerce', 'woocommerce' );
		$this->properties['is_cloneable'] = false;
	}
}