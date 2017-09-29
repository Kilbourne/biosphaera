<?php
/**
 * @since 1.3
 */
class CPAC_Addon_WC_Helper_Orders {

	public static function get_all_by_user_id( $user_id, $status = 'wc-completed' ) {
		$order_ids = get_posts( array(
			'fields' => 'ids',
			'post_type' => 'shop_order',
			'posts_per_page' => -1,
			'post_status' => $status,
			'meta_query' => array(
				array(
					'key' => '_customer_user',
					'value' => $user_id
				)
			)
		));

		return $order_ids;
	}
}