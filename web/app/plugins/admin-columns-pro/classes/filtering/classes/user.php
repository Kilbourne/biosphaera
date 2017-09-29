<?php

/**
 * Addon class
 *
 * @since 3.5
 */
class CAC_Filtering_Model_User extends CAC_Filtering_Model {

	public function init_hooks() {
		add_action( 'pre_get_users', array( $this, 'handle_filter_requests' ), 1 );
		add_action( 'pre_get_users', array( $this, 'handle_filter_range_requests' ), 1 );
		add_action( 'restrict_manage_users', array( $this, 'add_filtering_markup' ) );
		add_action( 'restrict_manage_users', array( $this, 'add_filtering_button' ), 11 ); // placement after dropdowns
	}

	/**
	 * @since 3.8
	 */
	public function get_filterables() {
		$column_types = array(

			// WP default columns
			'email',
			'role',
			'username',

			// Custom columns
			'column-first_name',
			'column-last_name',
			'column-roles',
			'column-rich_editing',
			'column-user_registered',
			'column-user_url',
		);

		return $column_types;
	}

	public function get_default_filterables() {
		return array( 'role' );
	}

	/**
	 * Filter by default columns
	 *
	 * @since 3.5
	 */
	public function filter_by_email( $query ) {
		$query->query_where .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->users}.user_email = %s", $this->get_filter_value( 'email' ) );

		return $query;
	}

	public function filter_by_username( $query ) {
		$query->query_where .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->users}.user_login = %s", $this->get_filter_value( 'username' ) );

		return $query;
	}

	/**
	 * Filter by custom columns
	 *
	 * @since 3.5
	 */
	public function filter_by_user_registered( $query ) {
		$query->query_where .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->users}.user_registered LIKE %s", $this->get_filter_value( 'column-user_registered' ) . '%' );

		return $query;
	}

	public function filter_by_user_url( $query ) {
		$query->query_where .= ' ' . $this->wpdb->prepare( "AND {$this->wpdb->users}.user_url = %s", $this->get_filter_value( 'column-user_url' ) );

		return $query;
	}

	/**
	 * Handle filter request for ranges
	 *
	 * @since 3.7
	 */
	public function handle_filter_range_requests( $user_query ) {
		if ( isset( $_REQUEST['cpac_filter-min'] ) ) {
			$user_query->query_vars['meta_query'][] = $this->get_meta_query_range( $_REQUEST['cpac_filter-min'], $_REQUEST['cpac_filter-max'] );
		}

		return $user_query;
	}

	/**
	 * Handle filter request
	 *
	 * @since 3.5
	 */
	public function handle_filter_requests( $user_query ) {

		if ( empty( $_REQUEST['cpac_filter'] ) || ! isset ( $_GET['cpac_filter_action'] ) ) {
			return $user_query;
		}

		// go through all filter requests per column
		foreach ( $_REQUEST['cpac_filter'] as $name => $value ) {

			$value = urldecode( $value );

			if ( strlen( $value ) < 1 ) {
				continue;
			}

			if ( ! $column = $this->storage_model->get_column_by_name( $name ) ) {
				continue;
			}

			// add the value to so we can use it in the 'post_where' callback
			$this->set_filter_value( $column->properties->type, $value );

			// meta arguments
			$meta_value = in_array( $value, array( 'cpac_empty', 'cpac_not_empty' ) ) ? '' : $value;
			$meta_query_compare = 'cpac_not_empty' == $value ? '!=' : '=';

			switch ( $column->properties->type ) :

				// WP Default
				case 'email' :
					add_filter( 'pre_user_query', array( $this, 'filter_by_email' ) );
					break;

				case 'role' :
					$user_query->set( 'role', $value );
					break;

				case 'username' :
					add_filter( 'pre_user_query', array( $this, 'filter_by_username' ) );
					break;

				// Custom
				case 'column-first_name' :
					$user_query->query_vars['meta_query'][] = array(
						array(
							'key'     => 'first_name',
							'value'   => $meta_value,
							'compare' => $meta_query_compare
						)
					);
					break;

				case 'column-last_name' :
					$user_query->query_vars['meta_query'][] = array(
						array(
							'key'     => 'last_name',
							'value'   => $meta_value,
							'compare' => $meta_query_compare
						)
					);
					break;

				case 'column-roles' :
					$user_query->set( 'role', $value );
					break;

				case 'column-rich_editing' :
					$user_query->query_vars['meta_query'][] = array(
						array(
							'key'     => 'rich_editing',
							'value'   => '1' === $value ? 'true' : 'false',
						)
					);
					break;

				case 'column-user_registered' :
					add_filter( 'pre_user_query', array( $this, 'filter_by_user_registered' ) );
					break;

				case 'column-user_url' :
					add_filter( 'pre_user_query', array( $this, 'filter_by_user_url' ) );
					break;

				// Custom Fields
				case 'column-meta' :
					$user_query->query_vars['meta_query'][] = $this->get_meta_query( $column->get_field_key(), $value, $column->get_option( 'field_type' ) );
					break;

				// ACF
				case 'column-acf_field' :
					if ( method_exists( $column, 'get_field_key' ) ) {
						$user_query->query_vars['meta_query'][] = $this->get_meta_acf_query( $column->get_field_key(), $value, $column->get_field_type(), $column->get_option( 'filter_format' ) );
					}
					break;

				// Try to filter by using the column's custom defined filter method
				default :
					if ( method_exists( $column, 'get_filter_user_vars' ) ) {
						$column->set_filter( $this );
						$user_query = $column->get_filter_user_vars( $user_query );
					}

			endswitch;
		}

		return $user_query;
	}

	/**
	 * Get values by user field
	 *
	 * @since 3.5
	 */
	public function get_values_by_user_field( $user_field ) {

		$user_field = sanitize_key( $user_field );

		$values = $this->wpdb->get_col( "
			SELECT DISTINCT {$user_field}
			FROM {$this->wpdb->users}
			WHERE {$user_field} <> ''
			ORDER BY 1
		" );

		if ( is_wp_error( $values ) || ! $values ) {
			return array();
		}

		return $values;
	}

	/**
	 * Get values by meta key
	 *
	 * @since 3.5
	 */
	public function get_values_by_meta_key( $meta_key, $operator = 'DISTINCT meta_value AS value' ) {

		$sql = "
			SELECT {$operator}
			FROM {$this->wpdb->usermeta} um
			INNER JOIN {$this->wpdb->users} u ON um.user_id = u.ID
			WHERE um.meta_key = %s
			AND um.meta_value != ''
			ORDER BY 1
		";

		$values = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $meta_key ) );

		if ( is_wp_error( $values ) || ! $values ) {
			return array();
		}

		return $values;
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
			case 'email' :
				if ( $values = $this->get_values_by_user_field( 'user_email' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $value;
					}
				}
				break;

			case 'role' :
			case 'column-roles' :
				$roles = new WP_Roles();
				foreach ( $this->get_user_ids() as $id ) {
					$u = get_userdata( $id );
					if ( ! empty( $u->roles[0] ) ) {
						$options[ $u->roles[0] ] = $roles->roles[ $u->roles[0] ]['name'];
					}
				}
				break;

			case 'username' :
				if ( $values = $this->get_values_by_user_field( 'user_login' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $value;
					}
				}
				break;

			// Custom
			case 'column-rich_editing' :
				$options = array(
					0 => __( 'No' ),
					1 => __( 'Yes' ),
				);
				break;

			case 'column-user_registered' :
				$order = '';
				foreach ( $this->get_user_ids() as $id ) {
					$registered_date = $column->get_raw_value( $id );
					$date = substr( $registered_date, 0, 7 ); // only year and month
					$options[ $date ] = date_i18n( 'F Y', strtotime( get_date_from_gmt( $registered_date ) ) );
				}
				krsort( $options );
				break;


			case 'column-user_url' :
				$empty_option = true;
				if ( $values = $this->get_values_by_user_field( 'user_url' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $value;
					}
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

			default :
				if ( method_exists( $column, 'get_filter_options' ) ) {
					$options = $column->get_filter_options();
				}

		endswitch;

		// sort the options
		if ( 'ASC' == $order ) {
			asort( $options );
		}
		if ( 'DESC' == $order ) {
			arsort( $options );
		}

		return array( 'options' => $options, 'empty_option' => $empty_option );
	}

	/**
	 * @since 3.5
	 */
	public function add_filtering_button() {
		if ( $this->has_dropdown ) : ?>
			<input type="submit" name="cpac_filter_action" class="button" value="<?php _e( 'Filter' ); ?>">
		<?php endif;
	}
}