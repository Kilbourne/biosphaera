<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Apply_Before_Tax extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-apply_before_tax';
		$this->properties['label'] = __( 'Applied before tax', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.0
	 */
	public function get_value( $post_id ) {
		$applied_before_tax = $this->get_raw_value( $post_id );

		if ( $applied_before_tax == 'yes' ) {
			$value = '<span class="cpac-tip" data-tip="' . esc_attr__( 'The coupon is applied before calculating cart tax.', 'codepress-admin-columns' ) . '"><span class="dashicons dashicons-yes cpac_status_yes" title="' . esc_attr( $applied_before_tax ) .  '"></span></span>';
		}
		else {
			$value = '<span class="cpac-tip" data-tip="' . esc_attr__( 'The coupon is applied after calculating cart tax.', 'codepress-admin-columns' ) . '"><span class="dashicons dashicons-no cpac_status_no" title="' . esc_attr( $stock_status ) .  '"></span></span>';
		}

		return $value . $applied_before_tax;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 2.0.3
	 */
	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return $coupon->apply_before_tax() ? 'yes' : 'no';
	}
}