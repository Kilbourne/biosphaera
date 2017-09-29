<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.0
 */
class CPAC_WC_Column_Post_Product_Thumbnails extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.0
	 */
	public function init() {
		parent::init();

		// Properties
		$this->properties['type'] = 'column-wc-product_thumbnails';
		$this->properties['label'] = __( 'Product Thumbnails', 'woocommerce' );


		// Options
		$this->options['image_size'] = '';
		$this->options['image_size_w'] = 80;
		$this->options['image_size_h'] = 80;
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.0
	 */
	public function get_value( $post_id ) {

		$thumbnail_id = $this->get_raw_value( $post_id );

		if ( ! $thumbnail_id ) {
			return false;
		}

		$images = array();
		foreach ( $thumbnail_id as $product_id => $image_id ) {
			$image = implode( $this->get_thumbnails( $image_id, (array) $this->options ) );
			$link = get_edit_post_link( $product_id );

			$images[] = $link ? "<a href='{$link}'>{$image}</a>" : $image;
		}

		return implode( '', $images );
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.0
	 */
	public function get_raw_value( $post_id ) {
		$order = new WC_Order( $post_id );

		$thumbnails_ids = array();
		if ( $items = $order->get_items() ) {
			foreach ( $items as $item ) {
				if ( $image = get_post_thumbnail_id( $item['product_id'] ) ) {
					$thumbnails_ids[ $item['product_id'] ] = $image;
				}
			}
		}

		if ( ! $thumbnails_ids ) {
			return false;
		}

		return $thumbnails_ids;
	}

	/**
	 * @see CPAC_Column::display_settings()
	 * @since 2.0
	 */
	public function display_settings() {
		$this->display_field_preview_size();
	}
}