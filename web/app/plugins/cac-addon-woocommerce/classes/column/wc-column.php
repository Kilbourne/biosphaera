<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.2
 */
class CPAC_WC_Column extends CPAC_Column {

	public function init() {
		parent::init();

		$this->properties['group'] = __( 'WooCommerce', 'woocommerce' );
	}

	/**
	 * @see CPAC_Column::get_product()
	 * @since 1.2
	 */
	public function get_product( $post_id ) {
		$product = false;

		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $post_id );
		}
		// WC 2.0<
		elseif ( function_exists( 'get_product' ) ) {
			$product = get_product( $post_id );
		}

		return $product;
	}

	/**
	 * @since 1.3.1
	 */
	public function display_field_product_property_display() {

		$this->display_field_select(
			'product_property_display',
			__( 'Property To Display', 'codepress-admin-columns' ),
			array(
				'title'     => __( 'Title', 'codepress-admin-columns' ), // default
				'id'        => __( 'ID', 'codepress-admin-columns' ),
				'sku'       => __( 'SKU', 'woocommerce' ),
				'thumbnail' => __( 'Thumbnail', 'woocommerce' )
			),
			__( 'Product property to display for related post(s).', 'codepress-admin-columns' ),
			'',
			true // JS refresh on change because not all property types are filterable
		);
	}

	/**
	 * @since 1.3.1
	 */
	public function get_product_label_by_property( $product_id, $property = '' ) {

		switch ( $property ) {
			case 'thumbnail':
				$label = '';
				if ( $image = get_post_thumbnail_id( $product_id ) ) {
					$label = implode( $this->get_thumbnails( $image, (array) $this->options ) );
				}
				break;
			case 'id':
				$label = $product_id;
				break;
			case 'sku':
				$label = get_post_meta( $product_id, '_sku', true );
				break;
			case 'title':
			default:
				$label = get_the_title( $product_id );
				break;
		}

		return $label;
	}

	/**
	 * @since 1.3.2
	 */
	public function get_all_ordered_products( $property = '' ) {

		global $wpdb;
		switch ( $property ) {

			case 'sku':
				$values = $wpdb->get_results(
					"SELECT DISTINCT p.ID as id, pm.meta_value as value
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON ( p.ID = om.meta_value AND om.meta_key = '_product_id' )
					INNER JOIN {$wpdb->postmeta} pm ON ( p.ID = pm.post_id AND pm.meta_key = '_sku' AND pm.meta_value != '' )
					ORDER BY pm.meta_value;"
				);
				break;
			case 'id':
				$values = $wpdb->get_results(
					"SELECT DISTINCT p.ID as id, p.ID as value
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON ( p.ID = om.meta_value AND om.meta_key = '_product_id' )
					ORDER BY p.ID;"
				);
				break;
			case 'title':
			default:
				$values = $wpdb->get_results(
					"SELECT DISTINCT p.ID AS id, p.post_title AS value
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON ( p.ID = om.meta_value AND om.meta_key = '_product_id' )
					ORDER BY post_title;"
				);
				break;
		}

		if ( ! $values ) {
			return false;
		}

		$products = array();
		foreach ( $values as $value ) {
			$products[ $value->id ] = $value->value;
		}

		return $products;
	}
}