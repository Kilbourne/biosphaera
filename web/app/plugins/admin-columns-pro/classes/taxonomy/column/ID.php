<?php
/**
 * CPAC_Column_Term_ID
 *
 * @since 2.0.0
 */
class CPAC_Column_Term_ID extends CPAC_Column {

	public function init() {

		parent::init();

		$this->properties['type']	 = 'column-termid';
		$this->properties['label']	 = __( 'ID', 'codepress-admin-columns' );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.0
	 */
	public function get_value( $term_id ) {
		return $this->get_raw_value( $term_id );
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.3
	 */
	public function get_raw_value( $term_id ) {
		return $term_id;
	}
}