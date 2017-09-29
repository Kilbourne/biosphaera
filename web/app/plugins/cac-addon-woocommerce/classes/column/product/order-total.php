<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit when accessed directly

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Order_Total extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {

		parent::init();

		// Properties
		$this->properties['type']	= 'column-wc-total_order_amount';
		$this->properties['label']	= __( 'Total order amount', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $post_id ) {

		$price = $this->get_raw_value( $post_id );

		if ( ! $price ) {
			return '&ndash;';
		}

		return wc_price( $price );
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {

		global $wpdb;

		$num_orders = $wpdb->get_var( $wpdb->prepare( "
			SELECT
				SUM( wc_oim2.meta_value )
			FROM
				{$wpdb->prefix}woocommerce_order_items wc_oi
			JOIN
				{$wpdb->prefix}woocommerce_order_itemmeta wc_oim
				ON
					wc_oi.order_item_id = wc_oim.order_item_id
			JOIN
				{$wpdb->prefix}woocommerce_order_itemmeta wc_oim2
				ON
					wc_oi.order_item_id = wc_oim2.order_item_id
			WHERE
				wc_oim.meta_key = '_product_id'
				AND
				wc_oim.meta_value = %d
				AND
				wc_oim2.meta_key = '_line_total'
			",
			$post_id
		) );

		return $num_orders;
	}

}