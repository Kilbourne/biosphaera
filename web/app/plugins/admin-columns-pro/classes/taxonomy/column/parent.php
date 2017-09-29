<?php

/**
 * CPAC_Column_Term_Parent
 *
 * @since 2.0.0
 */
class CPAC_Column_Term_Parent extends CPAC_Column {

	public function init() {
		parent::init();
		$this->properties['type'] = 'column-term_parent';
		$this->properties['label'] = __( 'Parent', 'codepress-admin-columns' );

		$this->options['term_property'] = '';
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.0
	 */
	public function get_value( $term_id ) {
		$parent_id = $this->get_raw_value( $term_id );
		if ( ! $parent_id ) {
			return false;
		}
		switch ( $this->get_option( 'term_property' ) ) {
			case 'slug':
				$label = $this->get_term_field( 'slug', $parent_id, $this->storage_model->taxonomy );
				break;
			case 'id':
				$label = $parent_id;
				break;
			default:
				$label = $this->get_term_field( 'name', $parent_id, $this->storage_model->taxonomy );
				break;
		}
		$link = get_edit_term_link( $parent_id, $this->storage_model->taxonomy );

		return $link ? "<a href='{$link}'>{$label}</a>" : $label;
	}

	/**
	 * @see CPAC_Column::get_value()
	 * @since 2.0.3
	 */
	public function get_raw_value( $term_id ) {
		$term = get_term( $term_id, $this->storage_model->taxonomy );

		return $term->parent;
	}

	public function apply_conditional() {
		return is_taxonomy_hierarchical( $this->storage_model->taxonomy );
	}

	public function display_settings() {
		$this->display_field_select(
			'term_property',
			__( 'Display', 'codepress-admin-columns' ),
			array(
				''     => __( 'Title' ),
				'slug' => __( 'Slug' ),
				'id'   => __( 'ID' )
			)
		);
	}
}