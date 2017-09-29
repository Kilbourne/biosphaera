<?php
class CACIE_WooCommerce {

	private function __construct() {}

	/**
	 * Get the highest hierarchy role for a user
	 *
	 * @param int $userid ID of the user to get the role for
	 * @return bool|string User role name or false if the user doesn't exist
	 */
	static public function update_product_pricing( $product, $args ) {
		$product = get_product( $product );

		if ( ! $product ) {
			return;
		}

		if ( $product->is_type( 'variable', 'grouped' ) ) {
			return;
		}

		$args = wp_parse_args( $args, array(
			'regular_price' => $product->get_regular_price(),
			'sale_price' => $product->get_sale_price(),
			'sale_price_dates_from' => $product->sale_price_dates_from,
			'sale_price_dates_to' => $product->sale_price_dates_to
		) );

		$regular_price = ( $args['regular_price'] === '' ) ? '' : wc_format_decimal( $args['regular_price'] );
		$sale_price = ( $args['sale_price'] === '' ) ? '' : wc_format_decimal( $args['sale_price'] );

		update_post_meta( $product->id, '_regular_price', $regular_price );
		update_post_meta( $product->id, '_sale_price', $sale_price );

		$date_from = $args['sale_price_dates_from'] ? $args['sale_price_dates_from'] : '';
		$date_to = $args['sale_price_dates_to'] ? $args['sale_price_dates_to'] : '';

		// Dates
		if ( $date_from ) {
			update_post_meta( $product->id, '_sale_price_dates_from', strtotime( $date_from ) );
		}
		else {
			update_post_meta( $product->id, '_sale_price_dates_from', '' );
		}

		if ( $date_to ) {
			update_post_meta( $product->id, '_sale_price_dates_to', strtotime( $date_to ) );
		}
		else {
			update_post_meta( $product->id, '_sale_price_dates_to', '' );
		}

		if ( $date_to && ! $date_from ) {
			update_post_meta( $product->id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );
		}

		// Update price if on sale
		if ( $sale_price !== '' && $date_to == '' && $date_from == '' ) {
			update_post_meta( $product->id, '_price', wc_format_decimal( $sale_price ) );
		}
		else {
			update_post_meta( $product->id, '_price', ( $regular_price === '' ) ? '' : wc_format_decimal( $regular_price ) );
		}

		if ( $sale_price !== '' && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) )
			update_post_meta( $product->id, '_price', wc_format_decimal( $sale_price ) );

		if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
			update_post_meta( $product->id, '_price', ( $regular_price === '' ) ? '' : wc_format_decimal( $regular_price ) );
			update_post_meta( $product->id, '_sale_price_dates_from', '' );
			update_post_meta( $product->id, '_sale_price_dates_to', '' );
		}
	}
}