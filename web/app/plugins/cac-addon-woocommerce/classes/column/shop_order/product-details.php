<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.4
 */
class CPAC_WC_Column_Post_Product_Details extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-product_details';
		$this->properties['label'] = __( 'Product Details', 'woocommerce' );
	}

	// Based on the default WooCommerce column order_items
	public function get_value( $order_id ) {
		$result = array();

		$order = wc_get_order( $order_id );;

		if ( sizeof( $order->get_items() ) == 0 ) {
			return '';
		}

		foreach ( $order->get_items() as $item ) {
			$product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
			$product_name = apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false );
			$quantity = '<span class="qty">' . absint( $item['qty'] ) . 'x</span>';
			$item_meta = new WC_Order_Item_Meta( $item, $product );

			$output = '';
			if ( $product ) {
				$output .= '<strong>' . $quantity . '<a href="' . get_edit_post_link( $product->id ) . '" title="' . esc_attr( $product_name ) . '">' . esc_html( $product_name ) . '</a> ' . '</strong>';
				$output .= ( wc_product_sku_enabled() && $product->get_sku() ) ? '<div class="meta">' . __( 'SKU', 'woocommerce' ) . ': ' . $product->get_sku() . '</div>' : '';
			}
			else {
				$output .= '<strong>' . $quantity . esc_html( $product_name ) . '</strong>';
			}

			if ( $item_meta && ( $_item_meta_html = $item_meta->display( true, true ) ) ) {
				$output .= '<div class="meta">' . $_item_meta_html . '</div>';
			}

			$result[] = '<div class="cpac_wc_product">' . $output . '</div>';
		}

		return implode( '', $result );
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
}