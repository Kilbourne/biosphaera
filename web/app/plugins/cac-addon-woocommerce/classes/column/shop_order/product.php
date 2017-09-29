<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3.1
 */
class CPAC_WC_Column_Post_Product extends CPAC_WC_Column {

	public function init() {
		parent::init();

		// Properties
		$this->properties['type'] = 'column-wc-product';
		$this->properties['label'] = __( 'Products', 'woocommerce' );

		// Options
		$this->options['image_size'] = '';
		$this->options['image_size_w'] = 80;
		$this->options['image_size_h'] = 80;

		$this->options['product_property_display'] = 'title';
	}

	public function get_product_property() {
		return $this->get_option( 'product_property_display' );
	}

	public function get_value( $order_id ) {
		$values = array();
		if ( $labels = $this->get_product_labels( $order_id ) ) {
			foreach ( $labels as $product_id => $label ) {
				if ( $link = get_edit_post_link( $product_id ) ) {
					$label = "<a href='{$link}'>{$label}</a>";
				}
				$values[] = $label;
			}
		}

		return implode( ', ', $values );
	}

	public function get_product_labels( $order_id ) {
		$product_labels = array();
		if ( $product_ids = $this->get_product_ids_by_order( $order_id ) ) {
			$property_display = $this->get_product_property();
			foreach ( $product_ids as $product_id ) {
				if ( $label = $this->get_product_label_by_property( $product_id, $property_display ) ) {
					$product_labels[ $product_id ] = $label;
				}
			}
		}

		return $product_labels;
	}

	// Retrieve ordered product ID's
	public function get_product_ids_by_order( $order_id ) {
		global $wpdb;
		$product_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT om.meta_value
			FROM {$wpdb->prefix}woocommerce_order_items AS oi
			INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS om ON ( oi.order_item_id = om.order_item_id )
			WHERE om.meta_key = '_product_id'
			AND oi.order_id = %d
			ORDER BY om.meta_value;"
			,
			$order_id ) );

		return $product_ids;
	}

	public function get_raw_value( $order_id ) {
		return $this->get_product_ids_by_order( $order_id );
	}

	public function display_settings() {

		$this->display_field_product_property_display();

		if ( 'thumbnail' == $this->get_product_property() ) {
			$this->display_field_preview_size();
		}
	}
}