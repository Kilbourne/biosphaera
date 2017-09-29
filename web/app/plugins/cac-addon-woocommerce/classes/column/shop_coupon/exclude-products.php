<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Exclude_Products extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {
		parent::init();

		$this->properties['type'] = 'column-wc-exclude_products';
		$this->properties['label'] = __( 'Excluded products', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $post_id ) {
		$product_ids = $this->get_raw_value( $post_id );
		$products = array();

		foreach ( $product_ids as $id ) {
			if ( ! $id ) {
				continue;
			}

			$title = get_the_title( $id );

			if ( $link = get_edit_post_link( $id ) ) {
				$title = "<a href='{$link}'>{$title}</a>";
			}

			$products[] = $title;
		}

		return implode( ', ', $products );
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {
		$coupon = new WC_Coupon( get_post_field( 'post_title', $post_id, 'raw' ) );

		return $coupon->exclude_product_ids;
	}
}