<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit when accessed directly

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Free_Shipping extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type']	= 'column-wc-free_shipping';
		$this->properties['label']	= __( 'Free shipping', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.0
	 */
	public function get_value( $post_id ) {
		$free_shipping = $this->get_raw_value( $post_id );

		if ( $free_shipping == 'yes' ) {
			$value = '<span class="cpac-tip" data-tip="' . esc_attr__( 'The free shipping method must be enabled with the &quot;must use coupon&quot; setting.', 'codepress-admin-columns' ) . '"><span class="dashicons dashicons-yes cpac_status_yes" title="' . esc_attr( $free_shipping ) .  '"></span></span>';
		}
		else {
			$value = '<span class="dashicons dashicons-no cpac_status_no" title="' . esc_attr( $free_shipping ) .  '"></span>';
		}

		return $value;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 2.0.3
	 */
	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return $coupon->enable_free_shipping() ? 'yes' : 'no';
	}
}