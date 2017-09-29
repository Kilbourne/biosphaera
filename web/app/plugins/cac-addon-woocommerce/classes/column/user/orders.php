<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_User_Orders extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-user-orders';
		$this->properties['label'] = __( 'Orders', 'woocommerce' );
	}

	private function get_order_tooltip( $order ) {
		$tooltip = array();
		$tooltip[] = wc_get_order_status_name( $order->get_status() );
		if ( $item_count = $order->get_item_count() ) {
			$tooltip[] = $item_count . ' ' . __( 'items', 'codepress-admin-columns' );
		}
		if ( $total = $order->get_total() ) {
			$tooltip[] = get_woocommerce_currency_symbol( $order->get_order_currency() ) . wc_trim_zeros( number_format( $total, 2 ) );
		}
		$tooltip[] = date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) );

		return implode( ' | ', $tooltip );
	}

	public function get_value( $user_id ) {
		$values = array();

		if ( $order_ids = $this->get_raw_value( $user_id ) ) {
			foreach ( $order_ids as $id ) {
				$order = new WC_Order( $id );

				$label = $order->id;
				if ( $edit_link = get_edit_post_link( $order->id ) ) {
					$label = '<a href="' . get_edit_post_link( $order->id ) . '">' . $order->id . '</a>';
				}

				$html = '<div class="order cpac-tip order-' . esc_attr( $order->get_status() ) . '" data-tip="' . $this->get_order_tooltip( $order ) . '">' . $label . '</div>';


				$values[ $order->get_status() ][] = $html;
			}
		}

		$output = '';
		if ( $values ) {
			foreach ( $values as $status => $orders ) {
				$output .= implode( '', $orders ) . "</br>";
			}
		}

		return $output;
	}

	public function get_raw_value( $user_id ) {
		return CPAC_Addon_WC_Helper_Orders::get_all_by_user_id( $user_id, 'any' );
	}
}