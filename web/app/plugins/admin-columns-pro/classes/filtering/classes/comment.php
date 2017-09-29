<?php

/**
 * Addon class
 *
 * @since 3.5
 */
class CAC_Filtering_Model_Comment extends CAC_Filtering_Model {

	public function init_hooks() {
		add_action( 'pre_get_comments', array( $this, 'handle_filter_requests' ), 2 );
		add_filter( 'pre_get_comments', array( $this, 'handle_filter_range_requests' ), 2 );
		add_action( 'restrict_manage_comments', array( $this, 'add_filtering_markup' ) );
	}

	/**
	 * @since 3.8
	 */
	public function get_filterables() {
		$column_types = array(

			// WP default columns
			'author',
			'response',

			// Custom Columns
			'column-agent',
			'column-approved',
			'column-author_email',
			'column-author_ip',
			'column-author_url',
			'column-author_name',
			'column-date',
			'column-date_gmt',
			'column-reply_to',
			'column-type',
			'column-user',
		);

		return $column_types;
	}

	/**
	 * Filter by default columns
	 *
	 * @since 3.5
	 */
	public function filter_by_agent( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_agent = %s", $this->get_filter_value( 'column-agent' ) );

		return $comments_clauses;
	}

	public function filter_by_author( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_author = %s", $this->get_filter_value( 'author' ) );

		return $comments_clauses;
	}

	public function filter_by_approved( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_approved = %s", $this->get_filter_value( 'column-approved' ) );

		return $comments_clauses;
	}

	public function filter_by_author_ip( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_author_IP = %s", $this->get_filter_value( 'column-author_ip' ) );

		return $comments_clauses;
	}

	public function filter_by_author_url( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_author_url = %s", $this->get_filter_value( 'column-author_url' ) );

		return $comments_clauses;
	}

	public function filter_by_author_name( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_author = %s", $this->get_filter_value( 'column-author_name' ) );

		return $comments_clauses;
	}

	public function filter_by_date( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_date LIKE %s", $this->get_filter_value( 'column-date' ) . '%' );

		return $comments_clauses;
	}

	public function filter_by_date_gmt( $comments_clauses ) {
		$comments_clauses['where'] .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->comments}.comment_date_gmt LIKE %s", $this->get_filter_value( 'column-date_gmt' ) . '%' );

		return $comments_clauses;
	}

	/**
	 * Handle filter request for ranges
	 *
	 * @since 3.7
	 */
	public function handle_filter_range_requests( $comment_query ) {
		if ( isset( $_REQUEST['cpac_filter-min'] ) ) {
			$comment_query->meta_query->queries[] = $this->get_meta_query_range( $_REQUEST['cpac_filter-min'], $_REQUEST['cpac_filter-max'] );
		}

		return $comment_query;
	}

	/**
	 * Handle filter request for single values
	 *
	 * @since 3.5
	 */
	public function handle_filter_requests( $comment_query ) {
		if ( empty( $_REQUEST['cpac_filter'] ) ) {
			return $comment_query;
		}

		foreach ( $_REQUEST['cpac_filter'] as $name => $value ) {

			$value = urldecode( $value );

			if ( strlen( $value ) < 1 ) {
				continue;
			}

			if ( ! $column = $this->storage_model->get_column_by_name( $name ) ) {
				continue;
			}

			// add the value to so we can use it in the 'comments_clauses' callback
			$this->set_filter_value( $column->properties->type, $value );

			switch ( $column->properties->type ) :

				// WP Default
				case 'author' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_author' ) );
					break;

				case 'response' :
					$comment_query->query_vars['post_id'] = $value;
					break;

				// Custom
				case 'column-agent' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_agent' ) );
					break;

				case 'column-approved' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_approved' ) );
					break;

				case 'column-author_email' :
					$comment_query->query_vars['author_email'] = $value;
					break;

				case 'column-author_ip' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_author_ip' ) );
					break;

				case 'column-author_url' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_author_url' ) );
					break;

				case 'column-author_name' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_author_name' ) );
					break;

				case 'column-date' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_date' ) );
					break;

				case 'column-date_gmt' :
					add_filter( 'comments_clauses', array( $this, 'filter_by_date_gmt' ) );
					break;

				case 'column-reply_to' :
					$comment_query->query_vars['parent'] = $value;
					break;

				case 'column-user' :
					$comment_query->query_vars['user_id'] = $value;
					break;

				case 'column-type' :
					$comment_query->query_vars['type'] = $value;
					break;

				// Custom Fields
				case 'column-meta' :
					$comment_query->meta_query->queries[] = $this->get_meta_query( $column->get_field_key(), $value, $column->get_option( 'field_type' ) );
					break;

				// ACF
				case 'column-acf_field' :
					if ( method_exists( $column, 'get_field_key' ) ) {
						$comment_query->meta_query->queries['relation'] = 'AND';
						$comment_query->meta_query->queries[] = $this->get_meta_acf_query( $column->get_field_key(), $value, $column->get_field_type(), $column->get_option( 'filter_format' ) );
					}
					break;

			endswitch;

			$comment_query->query_vars['filtered_by_ac'] = true;
		}

		return $comment_query;
	}

	/**
	 * Get values by user field
	 *
	 * @since 3.5
	 */
	public function get_values_by_comment_field( $comment_field ) {
		$comment_field = sanitize_key( $comment_field );

		$sql = "
			SELECT DISTINCT {$comment_field}
			FROM {$this->wpdb->comments}
			WHERE {$comment_field} <> ''
			ORDER BY 1
		";

		$values = $this->wpdb->get_results( $sql, ARRAY_N );

		if ( is_wp_error( $values ) || ! $values ) {
			return array();
		}

		return $values;
	}

	/**
	 * @since 3.5
	 */
	private function get_comment_fields( $field ) {
		return (array) $this->wpdb->get_col( "
			SELECT " . sanitize_key( $field ) . "
			FROM {$this->wpdb->comments} AS c
			INNER JOIN {$this->wpdb->posts} ps ON ps.ID = c.comment_post_ID
			WHERE c." . sanitize_key( $field ) . " <> '';
		" );
	}

	/**
	 * Get values by meta key
	 *
	 * @since 3.5
	 */
	public function get_values_by_meta_key( $meta_key, $operator = 'DISTINCT meta_value AS value' ) {
		$sql = "
			SELECT {$operator}
			FROM {$this->wpdb->commentmeta} cm
			INNER JOIN {$this->wpdb->comments} c ON cm.comment_id = c.comment_ID
			WHERE cm.meta_key = %s
			AND cm.meta_value != ''
			ORDER BY 1
		";

		$values = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $meta_key ) );

		if ( is_wp_error( $values ) || ! $values ) {
			return array();
		}

		return $values;
	}

	/**
	 * Add filtering dropdown
	 *
	 * @since 3.5
	 * @todo: Add support for customfield values longer then 30 characters.
	 */
	public function get_dropdown_options_by_column( $column ) {

		$options = array();
		$empty_option = false;
		$order = 'ASC';

		switch ( $column->get_type() ) :

			// WP Default
			case 'author' :
				foreach ( $this->get_comment_fields( 'comment_author' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'response' :
				foreach ( $this->get_comment_fields( 'comment_post_ID' ) as $_value ) {
					if ( $title = $column->get_post_title( $_value ) ) {
						$options[ $_value ] = $title;
					}
				}
				break;

			// Custom
			case 'column-agent' :
				foreach ( $this->get_comment_fields( 'comment_agent' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'column-approved' :
				$options = array(
					0 => __( 'No' ),
					1 => __( 'Yes' ),
				);
				break;

			case 'column-author_email' :
				foreach ( $this->get_comment_fields( 'comment_author_email' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'column-author_ip' :
				foreach ( $this->get_comment_fields( 'comment_author_IP' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'column-author_url' :
				foreach ( $this->get_comment_fields( 'comment_author_url' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'column-author_name' :
				foreach ( $this->get_comment_fields( 'comment_author' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'column-date' :
				$order = '';
				foreach ( $this->get_comment_fields( 'comment_date' ) as $_value ) {
					$date = substr( $_value, 0, 7 ); // only year and month
					$options[ $date ] = date_i18n( 'F Y', strtotime( $_value ) );
				}
				krsort( $options );
				break;

			case 'column-date_gmt' :
				$order = false; // we are sorting by key
				foreach ( $this->get_comment_fields( 'comment_date_gmt' ) as $_value ) {
					$date = substr( $_value, 0, 7 ); // only year and month
					$options[ $date ] = date_i18n( 'F Y', strtotime( $_value ) );
				}
				krsort( $options );
				break;

			case 'column-reply_to' :
				foreach ( $this->get_comment_fields( 'comment_parent' ) as $_value ) {
					$options[ $_value ] = get_comment_author( $_value ) . ' (' . $_value . ')';
				}
				break;

			case 'column-type' :
				foreach ( $this->get_comment_fields( 'comment_type' ) as $_value ) {
					$options[ $_value ] = $_value;
				}
				break;

			case 'column-user' :
				foreach ( $this->get_comment_fields( 'user_id' ) as $_value ) {
					$options[ $_value ] = $column->get_display_name( $_value );
				}
				break;

			case 'column-meta' :
				if ( $_options = $this->get_meta_options( $column ) ) {
					$empty_option = $_options['empty_option'];
					$options = $_options['options'];
				}
				break;

			case 'column-acf_field' :
				if ( $_options = $this->get_acf_options( $column ) ) {
					$empty_option = $_options['empty_option'];
					$options = $_options['options'];
				}
				break;

			// Filter by raw value
			case 'column-first_name' :
			case 'column-last_name' :
				$empty_option = true;
				foreach ( $this->get_user_ids() as $id ) {
					if ( $raw_value = $column->get_raw_value( $id ) ) {
						$options[ $raw_value ] = $raw_value;
					}
				}
				break;

		endswitch;

		// sort the options
		if ( $order ) {
			natcasesort( $options );
			if ( 'DESC' === $order ) {
				$options = array_reverse( $options );
			}
		}

		return array( 'options' => $options, 'empty_option' => $empty_option );
	}
}