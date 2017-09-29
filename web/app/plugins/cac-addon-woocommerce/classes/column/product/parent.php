<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Parent extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {

		parent::init();

		// Properties
		$this->properties['type']  = 'column-wc-parent';
		$this->properties['label'] = __( 'Parent product', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $post_id ) {

		$raw_value = $this->get_raw_value( $post_id );

		if ( ! $raw_value ) {
			return '';
		}

		$link = get_edit_post_link( $raw_value );

		switch ( $this->get_option( 'post_property_display' ) ) {
			case 'author':
				$label = get_the_author_meta( 'display_name', get_post_field( 'post_author', $raw_value ) );
				$link  = get_edit_user_link( get_post_field( 'post_author', $raw_value ) );
				break;
			case 'id':
				$label = $raw_value;
				break;
			default:
				$label = get_the_title( $raw_value );
				break;
		}

		$value = $link ? "<a href='{$link}'>{$label}</a>" : $label;

		return $value;
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return $product->get_parent();
	}

	/**
	 * @see CPAC_Column::display_settings()
	 * @since 1.3
	 */
	public function display_settings() {

		$this->display_field_product_property_display();
	}
}