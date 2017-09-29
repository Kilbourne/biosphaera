<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Product_Type extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-product_type';
		$this->properties['label'] = __( 'Product type', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $post_id ) {
		$product_type = $this->get_raw_value( $post_id );

		$types = apply_filters( 'product_type_selector', array(
			'simple'   => __( 'Simple product', 'woocommerce' ),
			'grouped'  => __( 'Grouped product', 'woocommerce' ),
			'external' => __( 'External/Affiliate product', 'woocommerce' ),
			'variable' => __( 'Variable product', 'woocommerce' )
		), $product_type );

		$value = isset( $types[ $product_type ] ) ? $types[ $product_type ] : $product_type;

		return $value;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {

		if ( $terms = wp_get_object_terms( $post_id, 'product_type' ) ) {
			$product_type = sanitize_title( current( $terms )->name );
		}
		else {
			$product_type = apply_filters( 'default_product_type', 'simple' );
		}

		return $product_type;
	}
}