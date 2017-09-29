<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_User_Total_Sales extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-user-total-sales';
		$this->properties['label'] = __( 'Total Sales', 'codepress-admin-columns' );
	}

	public function get_orders( $user_id ) {
		$orders = array();
		if ( $order_ids = CPAC_Addon_WC_Helper_Orders::get_all_by_user_id( $user_id ) ) {
			foreach ( $order_ids as $id ) {
				$orders[] = new WC_Order( $id );
			}
		}

		return $orders;
	}

	public function get_value( $user_id ) {
		$totals = $this->get_raw_value( $user_id );

		if ( ! $totals ) {
			return false;
		}

		$values = array();
		foreach ( $totals as $currency => $total ) {
			$values[] = get_woocommerce_currency_symbol( $currency ) . wc_trim_zeros( number_format( $total, 2 ) );
		}

		return implode( ' | ', $values );
	}

	public function get_sorting_value( $user_id ) {
		$totals = $this->get_raw_value( $user_id );
		if ( ! $totals ) {
			return false;
		}
		$values = array_values( $totals );

		return array_shift( $values );
	}

	public function get_raw_value( $user_id ) {
		$totals = array();
		if ( $orders = $this->get_orders( $user_id ) ) {
			foreach ( $orders as $order ) {
				$total = $order->get_total();
				if ( ! $total ) {
					continue;
				}
				$currency = $order->get_order_currency();
				if ( ! isset( $totals[ $currency ] ) ) {
					$totals[ $currency ] = 0;
				}
				$totals[ $currency ] += $total;
			}
		}

		return $totals;
	}
}