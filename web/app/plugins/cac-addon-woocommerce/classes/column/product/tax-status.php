<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.4
 */
class CPAC_WC_Column_Post_Tax_Status extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-tax_status';
		$this->properties['label'] = __( 'Tax status', 'woocommerce' );

		$this->properties['is_editable'] = true;
		$this->properties['is_sortable'] = true;
	}

	public function get_tax_status() {
		$status = array(
			'taxable'  => __( 'Taxable', 'woocommerce' ),
			'shipping' => __( 'Shipping only', 'woocommerce' ),
			'none'     => _x( 'None', 'Tax status', 'woocommerce' )
		);

		return $status;
	}

	function get_value( $post_id ) {
		$value = $this->get_raw_value( $post_id );
		$status = $this->get_tax_status();

		return isset( $status[ $value ] ) ? $status[ $value ] : $value;
	}

	public function get_raw_value( $post_id ) {
		return get_post_meta( $post_id, '_tax_status', true );
	}

	public function save( $id, $value ) {
		update_post_meta( $id, '_tax_status', $value );
	}

	public function get_editable_settings() {
		$settings = array(
			'type'    => 'select',
			'options' => $this->get_tax_status()
		);

		return $settings;
	}
}