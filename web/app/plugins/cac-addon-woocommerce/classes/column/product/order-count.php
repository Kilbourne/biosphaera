<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit when accessed directly

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Order_Count extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {

		parent::init();

		// Properties
		$this->properties['type']	= 'column-wc-order_count';
		$this->properties['label']	= __( 'Number of orders', 'woocommerce' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $post_id ) {
		$count = $this->get_raw_value( $post_id );
		return $count ? $count : $this->get_empty_char();
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {
		global $wpdb;

		$num_orders = $wpdb->get_var( $wpdb->prepare( "
			SELECT
				COUNT( 1 )
			FROM
				{$wpdb->prefix}woocommerce_order_items wc_oi
			JOIN
				{$wpdb->prefix}woocommerce_order_itemmeta wc_oim
				ON
					wc_oi.order_item_id = wc_oim.order_item_id
			WHERE
				wc_oim.meta_key = '_product_id'
				AND
				wc_oim.meta_value = %d
			",
			$post_id
		) );

		return $num_orders;
	}

}