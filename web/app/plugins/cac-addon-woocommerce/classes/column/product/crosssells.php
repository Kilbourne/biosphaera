<?php
defined( 'ABSPATH' ) or die();

/**
 * @since 1.1
 */
class CPAC_WC_Column_Post_Crosssells extends CPAC_WC_Column {

	/**
	 * @see CPAC_Column::init()
	 * @since 1.1
	 */
	public function init() {
		parent::init();

		// Properties
		$this->properties['type'] = 'column-wc-crosssells';
		$this->properties['label'] = __( 'Crosssells', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 1.1
	 */
	public function get_value( $post_id ) {
		$crosssell_ids = $this->get_raw_value( $post_id );
		$crosssells = array();

		foreach ( $crosssell_ids as $id ) {
			if ( ! $id ) {
				continue;
			}

			$title = get_the_title( $id );

			if ( $link = get_edit_post_link( $id ) ) {
				$title = "<a href='{$link}'>{$title}</a>";
			}

			$crosssells[] = $title;
		}

		return implode( ', ', $crosssells );
	}

	/**
	 * @see CPAC_Column::get_raw_value()
	 * @since 1.1
	 */
	public function get_raw_value( $post_id ) {
		$product = wc_get_product( $post_id );

		return $product->get_cross_sells();
	}
}