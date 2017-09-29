<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.3
 */
class CPAC_WC_Column_User_Order_Custom_Field extends CPAC_Column_Custom_Field {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-user-orders-meta';
		$this->properties['label'] = __( 'Custom Field Orders', 'codepress-admin-columns' );
	}

	public function get_value( $user_id ) {
		$order_ids = CPAC_Addon_WC_Helper_Orders::get_all_by_user_id( $user_id );

		if ( ! $order_ids ) {
			return false;
		}

		$values = array();
		foreach ( $order_ids as $id ) {
			$meta = get_post_meta( $id, $this->get_field_key(), true );
			if ( $value = $this->get_value_by_meta( $meta ) ) {
				if ( $link = get_edit_post_link( $id ) ) {
					$values[] = '<a href="' . $link . '" class="cpac-tip" data-tip="#' . $id . '">' . $value . "</a>";
				}
				else {
					$values[] = '<span class="cpac-tip" data-tip="#' . $id . '">' . $value . "</span>";
				}
			}
		}

		$value = implode( ", ", $values );

		/**
		 * Filter the display value for Custom Field columns
		 *
		 * @param mixed $value Custom field value
		 * @param int $id Object ID
		 * @param object $this Column instance
		 */
		$value = apply_filters( 'cac/column/meta/value', $value, $id, $this );

		return $value;
	}

	public function get_raw_value( $user_id, $single = true ) {

	}

	public function get_meta_keys() {

		global $wpdb;

		$post_type = 'shop_order';

		if ( $cache = wp_cache_get( $post_type, 'cac_columns' ) ) {
			$keys = $cache;
		}
		else {
			$keys = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.post_type = %s ORDER BY 1", $post_type ), ARRAY_N );
			wp_cache_add( $post_type, $keys, 'cac_columns', 10 ); // 10 sec.
		}

		if ( is_wp_error( $keys ) || empty( $keys ) ) {
			$keys = false;
		}
		else {
			$keys = $this->storage_model->format_meta_keys( $keys );
		}

		return $keys;
	}
}