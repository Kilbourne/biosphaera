<?php

/**
 * Addon class
 *
 * @since 1.0
 */
class CAC_Sortable_Model_Link extends CAC_Sortable_Model {

	public function init_hooks() {
		add_filter( 'get_bookmarks', array( $this, 'handle_sorting_request' ), 10, 2 );
		add_filter( "manage_" . $this->storage_model->get_screen_id() . "_sortable_columns", array( $this, 'add_sortable_headings' ) );
	}

	/**
	 * @since 3.7
	 */
	public function get_items( $args ) {
	}

	/**
	 * Get sortables
	 *
	 * @see CAC_Sortable_Model::get_sortables()
	 * @since 1.0
	 */
	function get_sortables() {

		$column_names = array(

			// WP default columns
			'rel',

			// Custom Columns
			'column-link_id',
			'column-owner',
			'column-length',
			'column-target',
			'column-description',
			'column-notes',
			'column-rss'
		);

		return $column_names;
	}

	/**
	 * Admin requests for orderby column
	 *
	 * Only works for WP_Query objects ( such as posts and media )
	 *
	 * @since 1.0.0
	 *
	 * @param array $vars
	 * @return array Vars
	 */
	public function handle_sorting_request( $results, $vars ) {

		$vars = $this->apply_sorting_preference( $vars );

		if ( empty( $vars['orderby'] ) ) {
			return $results;
		}

		$column = $this->get_column_by_orderby( $vars['orderby'] );

		if ( empty( $column ) ) {
			return $results;
		}

		$length = '';

		switch ( $column->properties->type ) :

			// WP Default Columns
			case 'rel':
				$vars['orderby'] = 'link_rel';
				break;

			// Custom columns
			case 'column-link_id':
				$vars['orderby'] = 'link_id';
				break;

			case 'column-owner':
				$vars['orderby'] = 'link_owner';
				break;

			case 'column-length':
				$vars['orderby'] = 'length';
				$length = ", CHAR_LENGTH(link_name) AS length";
				break;

			case 'column-target':
				$vars['orderby'] = 'link_target';
				break;

			case 'column-description':
				$vars['orderby'] = 'link_description';
				break;

			case 'column-notes':
				$vars['orderby'] = 'link_notes';
				break;

			case 'column-rss':
				$vars['orderby'] = 'link_rss';
				break;

			default :
				$vars['orderby'] = '';

		endswitch;

		// get bookmarks by orderby vars
		if ( $vars['orderby'] ) {
			global $wpdb;

			$order = '';

			if ( $vars['order'] ) {
				$order = strtolower( $vars['order'] ) == 'asc' ? 'ASC' : 'DESC';
			}

			$orderby = preg_replace( '/[^a-z0-9\$\_]/', '', $vars['orderby'] );

			$bookmarks = $wpdb->get_results( "SELECT * {$length} FROM {$wpdb->links} ORDER BY $orderby $order" );

			// check for errors
			if ( ! is_wp_error( $bookmarks ) ) {
				$results = $bookmarks;

			}
		}

		return $results;
	}
}