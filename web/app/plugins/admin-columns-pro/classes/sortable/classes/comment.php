<?php

/**
 * Addon class
 *
 * @since 1.0
 */
class CAC_Sortable_Model_Comment extends CAC_Sortable_Model {

	public function init_hooks() {
		add_filter( 'comments_clauses', array( $this, 'handle_sorting_request' ), 10, 2 );
		add_filter( "manage_" . $this->storage_model->get_screen_id() . "_sortable_columns", array( $this, 'add_sortable_headings' ) );
		add_action( 'restrict_manage_comments', array( $this, 'add_reset_button' ) );
	}

	/**
	 * @since 3.7
	 */
	public function get_items( $args ) {
	}

	/**
	 * Get custom sortables
	 *
	 * @see CAC_Sortable_Model::get_sortables()
	 * @since 1.0
	 */
	public function get_sortables() {

		$column_names = array(

			// WP default columns
			'comment',

			// Custom Columns
			'column-agent',
			'column-approved',
			'column-author',
			'column-author_ip',
			'column-author_name',
			'column-author_url',
			'column-author_email',
			'column-comment_id',
			'column-date',
			'column-date_gmt',
			'column-excerpt',
			'column-type',
			'column-user',
			'column-meta',
			'column-reply_to',
		);

		return array_merge( $column_names, (array) $this->get_default_sortables() ) ;
	}

	/**
	 * Columns that are sortable by WordPress core
	 *
	 * @since 3.8
	 */
	public function get_default_sortables() {
		$columns = array(
			'author',
			'response',
			'date'
		);
		return $columns;
	}

	/**
	 * Orderby Comments column
	 *
	 * @since 1.0
	 *
	 * @param array $pieces SQL pieces
	 * @param array $ref_comment Comment Query
	 *
	 * @return array SQL pieces
	 */
	public function handle_sorting_request( $pieces, $ref_comment ) {

		$vars = array(
			'orderby' => $ref_comment->query_vars['orderby'],
			'order'   => isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'ASC'
		);

		$vars = $this->apply_sorting_preference( $vars );

		$column = $this->get_column_by_orderby( $vars['orderby'] );

		if ( empty( $column ) ) {
			return $pieces;
		}

		switch ( $column->get_type() ) :

			// WP Default Columns
			case 'comment' :
				$pieces['orderby'] = 'comment_content';
				break;

			// Custom Columns
			case 'column-comment_id' :
				$pieces['orderby'] = 'comment_ID';
				break;

			case 'column-author' :
				$pieces['orderby'] = 'comment_author';
				break;

			case 'column-author_ip' :
				$pieces['orderby'] = 'comment_author_IP';
				break;

			case 'column-author_name' :
				$pieces['orderby'] = 'comment_author';
				break;

			case 'column-author_url' :
				$pieces['orderby'] = 'comment_author_url';
				break;

			case 'column-author_email' :
				$pieces['orderby'] = 'comment_author_email';
				break;

			case 'column-reply_to' :
				$pieces['orderby'] = 'comment_parent';
				break;

			case 'column-approved' :
				$pieces['orderby'] = 'comment_approved';
				break;

			case 'column-date' :
				$pieces['orderby'] = 'comment_date';
				break;

			case 'column-agent' :
				$pieces['orderby'] = 'comment_agent';
				break;

			case 'column-excerpt' :
				$pieces['orderby'] = 'comment_content';
				break;

			case 'column-type' :
				$pieces['orderby'] = 'comment_type';
				break;

			case 'column-user' :
				$pieces['orderby'] = 'user_id';
				break;

			case 'column-date_gmt' :
				$pieces['orderby'] = 'comment_date_gmt'; // this the default for Comment Query
				break;

			case 'column-meta' :
				global $wpdb;
				$pieces['join'] = $pieces['join'] . " JOIN $wpdb->commentmeta cm ON $wpdb->comments.comment_ID = cm.comment_id";
				$pieces['orderby'] = "cm.meta_value";
				$pieces['where'] = $pieces['where'] . $wpdb->prepare( " AND cm.meta_key=%s", $column->get_option( 'field' ) );
				break;

		endswitch;

		// set order. make sure the order hasn't already been set
		if ( false === strpos( $pieces['orderby'], $vars['order'] ) ) {
			$pieces['orderby'] .= ' ' . $vars['order'];
		}

		return $pieces;
	}
}