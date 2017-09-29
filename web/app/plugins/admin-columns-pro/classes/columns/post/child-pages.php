<?php

class CPAC_Column_Post_Child_Pages extends CPAC_Column {

	public function init() {
		parent::init();

		$this->properties['type'] = 'column-child-pages';
		$this->properties['label'] = __( 'Child Pages', 'codepress-admin-columns' );
	}

	public function get_value( $post_id ) {
		$titles = array();

		// display title with link
		if ( $ids = $this->get_raw_value( $post_id ) ) {
			foreach ( $ids as $id ) {
				$link = get_edit_post_link( $id );
				if ( $title = $this->get_post_title( $id ) ) {
					$title = $link ? "<a href='{$link}'>{$title}</a>" : $title;
					$titles[] = $title . "<br/>";
				}
			}
		}

		return implode( $titles );

	}

	public function get_raw_value( $post_id ) {
		$ids = get_posts( array(
			'post_type'      => $this->get_post_type(),
			'post_parent'    => $post_id,
			'fields'         => 'ids',
			'posts_per_page' => - 1
		) );

		return $ids;
	}

	public function apply_conditional() {
		return is_post_type_hierarchical( $this->get_post_type() ) || post_type_supports( $this->get_post_type(), 'page-attributes' );
	}
}