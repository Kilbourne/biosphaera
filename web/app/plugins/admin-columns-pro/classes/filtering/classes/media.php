<?php

/**
 * @since 3.5
 */
class CAC_Filtering_Model_Media extends CAC_Filtering_Model_Post_Object {

	/**
	 * @since 3.8
	 */
	public function get_filterables() {
		$column_types = array(

			// WP default columns
			'author',
			'comments',
			'date',
			'parent',

			// Custom columns
			'column-description',
			'column-mime_type',
			'column-taxonomy',
		);

		return $column_types;
	}

	/**
	 * @since 3.5
	 */
	public function filter_by_comments( $where ) {
		if ( '0' == $this->get_filter_value( 'comments' ) ) {
			$where .= "AND {$this->wpdb->posts}.comment_count = '0'";
		}
		elseif ( '1' == $this->get_filter_value( 'comments' ) ) {
			$where .= "AND {$this->wpdb->posts}.comment_count <> '0'";
		}
		return $where;
	}
	public function filter_by_mime_type( $where ) {
		return $where . $this->wpdb->prepare( "AND {$this->wpdb->posts}.post_mime_type = %s", $this->get_filter_value( 'column-mime_type' ) );
	}
	public function filter_by_description( $where ) {
		return $where . $this->wpdb->prepare( "AND {$this->wpdb->posts}.post_content = %s", $this->get_filter_value( 'column-description' ) );
	}

	/**
	 * Handle filter request
	 *
	 * @since 3.5
	 */
	public function handle_filter_requests( $vars ) {

		if ( empty( $_REQUEST['cpac_filter'] ) ) {
			return $vars;
		}

		foreach ( $_REQUEST['cpac_filter'] as $name => $value ) {

			$value = urldecode( $value );

			if ( strlen( $value ) < 1 ) {
				continue;
			}

			if ( ! $column = $this->storage_model->get_column_by_name( $name ) ) {
				continue;
			}

			// add the value to so we can use it in the 'post_where' callback
			$this->set_filter_value( $column->get_type(), $value );

			switch ( $column->get_type() ) :

				// WP Default
				case 'author' :
					$vars['author'] = $value;
					break;

				case 'date' :
					$vars['date_query'][] = array(
						'year' => absint( substr( $value, 0, 4 ) ),
						'month' => absint( substr( $value, -2 ) ),
					);
					break;

				case 'parent' :
					$vars['post_parent'] = $value;
					break;

				case 'comments' :
					add_filter( 'posts_where', array( $this, 'filter_by_comments' ) );
					break;

				// Custom
				case 'column-description' :
					add_filter( 'posts_where', array( $this, 'filter_by_description' ) );
					break;

				case 'column-mime_type' :
					add_filter( 'posts_where', array( $this, 'filter_by_mime_type' ) );
					break;

				case 'column-taxonomy' :
					$vars['tax_query']['relation'] = 'AND';
					$vars['tax_query'][] = $this->get_taxonomy_query( $value, $column->get_option( 'taxonomy' ) );
					break;

				// Custom Fields
				case 'column-meta' :
					$vars['meta_query'][] = $this->get_meta_query( $column->get_field_key(), $value, $column->get_option( 'field_type' ) );
					break;

				// ACF
				case 'column-acf_field' :
					if ( method_exists( $column, 'get_field_key' ) ) {
						$vars['meta_query'][] = $this->get_meta_acf_query( $column->get_field_key(), $value, $column->get_field_type(), $column->get_option( 'filter_format' ) );
					}
					break;

				// Try to filter by using the column's custom defined filter method
				default :
					if ( method_exists( $column, 'get_filter_post_vars' ) ) {
						$column->set_filter( $this ); // use $column->get_filter() to use the model inside a column object
						$vars = array_merge( $vars, (array) $column->get_filter_post_vars() );
					}

			endswitch;
		}

		return $vars;
	}

	/**
	 * @since 3.6
	 */
	public function get_dropdown_options_by_column( $column ) {

		$options = array();
		$empty_option = false;
		$order = 'ASC';

		switch ( $column->properties->type ) :

			// WP Default
			case 'author' :
				if ( $values = $this->get_post_fields( 'post_author' ) ) {
					foreach ( $values as $value ) {
						$user = get_user_by( 'id', $value );
						$options[ $value ] = $user->display_name;
					}
				}
				break;

			case 'comments' :
				$options = array(
					'' => __( 'No comments', 'capc' ),
					1 => __( 'Has comments', 'capc' ),
				);
				break;

			case 'date' :
				$order = '';
				foreach ( $this->get_post_fields( 'post_date' ) as $_value ) {
					$date = substr( $_value, 0, 7 ); // only year and month
					$options[ $date ] = date_i18n( 'F Y', strtotime( $_value ) );
				}
				krsort( $options );
				break;

			case 'parent' :
				foreach ( $this->get_post_fields( 'post_parent' ) as $_value ) {
					$options[ $_value ] = $column->get_post_title( $_value );
				}
				break;

			// Custom
			case 'column-description' :
				foreach ( $this->get_post_fields( 'post_content' ) as $_value ) {
					$options[ $_value ] = strip_tags( $_value );
				}
				break;

			case 'column-mime_type' :
				$mime_types = array_flip( wp_get_mime_types() );
				foreach ( $this->get_post_fields( 'post_mime_type' ) as $_value ) {
					$options[ $_value ] = $mime_types[ $_value ];
				}
				break;

			case 'column-taxonomy' :
				if ( taxonomy_exists( $column->get_option( 'taxonomy' ) ) ) {
					$empty_option = true;
					$options = $this->apply_indenting_markup( $this->get_terms_by_post_type( $column->get_option( 'taxonomy' ), $column->get_post_type() ) );
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
					$order = $_options['order'];
					$empty_option = $_options['empty_option'];
					$options = $_options['options'];
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