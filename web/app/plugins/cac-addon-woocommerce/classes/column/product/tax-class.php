<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.4
 */
class CPAC_WC_Column_Post_Tax_Class extends CPAC_WC_Column {

	private $classes;

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-tax_class';
		$this->properties['label'] = __( 'Tax Class', 'woocommerce' );
		$this->properties['is_sortable'] = true;
		$this->properties['is_editable'] = true;
	}

	public function get_tax_classes() {
		if ( ! $this->classes ) {
			$classes = array();
			foreach ( WC_TAX::get_tax_classes() as $tax_class ) {
				$classes[ WC_TAX::format_tax_rate_class( $tax_class ) ] = $tax_class;
			}
			$this->classes = $classes;
		}

		return $this->classes;
	}

	public function get_value( $post_id ) {
		$value = $this->get_raw_value( $post_id );
		$classes = $this->get_tax_classes();
		if ( isset( $classes[ $value ] ) ) {
			$value = $classes[ $value ];
		}

		return $value;
	}

	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return $product->get_tax_class();
	}

	public function get_editable_settings() {
		$options = array( '' => __( 'Standard', 'codepress-admin-columns' ) );
		$options = array_merge( $options, $this->get_tax_classes() );
		$settings = array(
			'type'    => 'select',
			'options' => $options
		);

		return $settings;
	}

	public function save( $id, $value ) {
		update_post_meta( $id, '_tax_class', $value );
	}
}