<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_User_Coupons_Used extends CPAC_WC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-user_coupons_used';
		$this->properties['label'] = __( 'Coupons Used', 'codepress-admin-columns' );
	}

	public function get_value( $user_id ) {
		$coupons = array();
		if ( $order_ids = CPAC_Addon_WC_Helper_Orders::get_all_by_user_id( $user_id ) ) {
			foreach ( $order_ids as $order_id ) {
				$order = new WC_Order( $order_id );
				if ( $order && ( $used_coupons = $order->get_used_coupons() ) ) {
					foreach ( $used_coupons as $coupon ) {
						$label = $coupon;
						if ( $edit_link = get_edit_post_link( $order->id ) ) {
							$label = '<a href="' . get_edit_post_link( $order->id ) . '">' . $coupon . '</a>';
						}

						$coupons[] = '<div class="cpac-tip" data-tip="order: #' . $order->id . '">' . $label . '</div>';
					}
				}
			}
		}

		return implode( ' | ', $coupons );
	}

	public function get_raw_value( $user_id ) {
		$coupons = array();
		if ( $order_ids = CPAC_Addon_WC_Helper_Orders::get_all_by_user_id( $user_id ) ) {
			foreach ( $order_ids as $order_id ) {
				$order = new WC_Order( $order_id );
				$coupons = array_merge( $coupons, $order->get_used_coupons() );
			}
		}

		return count( $coupons );
	}
}