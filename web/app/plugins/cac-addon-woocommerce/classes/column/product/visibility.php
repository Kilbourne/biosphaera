<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.2
 */
class CPAC_WC_Column_Post_Visibility extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-visibility';
		$this->properties['label'] = __( 'Visiblity', 'codepress-admin-columns' );
	}

	public function get_visibility_options() {
		return apply_filters( 'woocommerce_product_visibility_options', array(
			'visible' => __( 'Catalog/search', 'woocommerce' ),
			'catalog' => __( 'Catalog', 'woocommerce' ),
			'search'  => __( 'Search', 'woocommerce' ),
			'hidden'  => __( 'Hidden', 'woocommerce' )
		) );
	}

	public function get_value( $post_id ) {
		$current_visibility = $this->get_raw_value( $post_id );

		// Visible?
		$visibility_options = $this->get_visibility_options();
		$value = isset( $visibility_options[ $current_visibility ] ) ? esc_html( $visibility_options[ $current_visibility ] ) : esc_html( $current_visibility );

		return $value;
	}

	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return $product->visibility;
	}
}