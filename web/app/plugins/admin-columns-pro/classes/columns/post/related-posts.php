<?php

// plugin by Barry Kooij
class CPAC_Column_Related_Posts extends CPAC_Column {

	/**
	 * This function will setup the column data
	 *
	 */
	public function init() {

		// do not delete
		parent::init();

		// Identifier, pick an unique name. Single word, no spaces. Underscores allowed.
		$this->properties['type'] = 'column-related-posts';

		// Default column label.
		$this->properties['label'] = __( 'Related Posts', 'ac-related-posts' );

		// (optional) You can make it support sorting with the pro add-on enabled. Sorting will be done by it's raw value.
		$this->properties['is_sortable'] = true;
		$this->properties['sort_flag'] = 'numeric';

		// (optional) Enable/Disable inline editing support for this column.
		$this->properties['is_editable'] = true;
	}

	/**
	 * Returns options for the filter dropdown menu (TODO: WP_Query filter)
	 *
	 * @return array $options Options for dropdown
	 */
	/*
	public function get_filter_options() {

		$options = array();

		if ( class_exists( 'RP4WP_Constants', false ) ) {
			global $wpdb;

			// get all link ID'S for the current post_type
			$sql = $wpdb->prepare( "SELECT DISTINCT pm.post_id
				FROM {$wpdb->postmeta} AS pm
				WHERE pm.meta_key = %s
				AND pm.meta_value = %s
				;",
				RP4WP_Constants::PM_PT_PARENT,
				$this->storage_model->get_post_type()
			);

			$link_ids = $wpdb->get_col( $sql );

			// Get the linked post type ID's and titles
			$sql = $wpdb->prepare( "SELECT ps.ID, ps.post_title
				FROM {$wpdb->postmeta} AS pm
					INNER JOIN {$wpdb->posts} AS ps ON ps.ID = pm.meta_value
				WHERE pm.meta_key = %s
				AND pm.post_id IN (" . implode( ",", $link_ids ) . ")
				;",
				RP4WP_Constants::PM_CHILD
			);

			$related_posts = $wpdb->get_results( $sql );

			if ( $related_posts ) {
				foreach ( $related_posts as $post ) {
					$options[ $post->ID ] = esc_html( $post->post_title );
				}
			}
		}

		return $options;
	}
	*/

	/**
	 * Get related ID's
	 *
	 */
	public function get_related_ids( $post_id ) {

		if ( ! $this->is_related_posts_active() ) {
			return false;
		}

		$pl_manager = new RP4WP_Post_Link_Manager();
		$related_posts = $pl_manager->get_children( $post_id, array( 'posts_per_page' => -1 ) );

		if ( empty( $related_posts ) ) {
			return false;
		}

		return array_values( array_unique( array_filter( (array) wp_list_pluck( $related_posts, 'ID' ) ) ) );
	}

	/**
	 * Returns the display value for the column.
	 */
	public function get_value( $post_id ) {

		$titles = array();
		if ( $related_post_ids = $this->get_related_ids( $post_id ) ) {
			foreach ( $related_post_ids as $id ) {
				$link = get_edit_post_link( $id );
				if ( $title = $this->get_post_title( $id ) ) {
					$titles[] = $link ? "<a href='{$link}'>{$title}</a>" : $title;
				}
			}
		}
		return implode( ' | ', $titles );
	}

	// Used by inline edit
	public function get_raw_value( $post_id ) {
		return $this->get_related_ids( $post_id );
	}

	public function get_sorting_value( $post_id ) {
		$ids = $this->get_related_ids( $post_id );
		return $ids ? count( $ids ) : false;
	}

	public function apply_conditional() {

		if ( ! $this->is_related_posts_active() ) {
			return false;
		}

		$pt_manager = new RP4WP_Post_Type_Manager();
		return $pt_manager->is_post_type_installed( $this->get_post_type() );
	}

	public function is_related_posts_active() {
		return class_exists( 'RP4WP', false );
	}

	/**
	 * Get editable ajax options
	 */
	public function get_editable_ajax_options( $searchterm ) {

		$pt_manager = new RP4WP_Post_Type_Manager();
		$post_types = $pt_manager->get_installed_post_type( $this->get_post_type() );

		return $this->get_editable()->get_posts_options( array( 's' => $searchterm, 'post_type' => $post_types ) );
	}

	/**
	 * Inline editing settings.
	 */
	public function get_editable_settings() {

		$settings = array(
			'type'				=> 'select2_dropdown',
			'ajax_populate'		=> true,
			'multiple'			=> true,
			'formatted_value'	=> 'post'
		);

		return $settings;
	}

	/**
	 * Saves the value after using inline-edit.
	 */
	public function save( $id, $values ) {

		// remove any false booleans
		$values = array_filter( array_map( 'intval', (array) $values ) );

		$post_link_manager = new RP4WP_Post_Link_Manager();
		$current_related_ids = (array) $this->get_related_ids( $id );

		if ( $removed_ids = array_diff( $current_related_ids, $values ) ) {
			foreach ( $removed_ids as $removed_id ) {
				$post_link_manager->delete( $removed_id );
			}
		}
		if ( $added_ids = array_diff( $values, $current_related_ids ) ) {
			foreach( $added_ids as $added_id ) {
				$post_link_manager->add( $id, $added_id, get_post_type( $id ), false, true );
			}
		}
	}
}