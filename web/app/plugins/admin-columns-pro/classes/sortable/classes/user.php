<?php

/**
 * Addon class
 *
 * @since 1.0
 */
class CAC_Sortable_Model_User extends CAC_Sortable_Model {

	public function init_hooks() {
		add_action( 'pre_user_query', array( $this, 'handle_sorting_request' ), 2 ); // prio after filtering
		add_filter( "manage_" . $this->storage_model->get_screen_id() . "_sortable_columns", array( $this, 'add_sortable_headings' ) );
		add_action( 'restrict_manage_users', array( $this, 'add_reset_button' ) );
	}

	/**
	 * @since 3.7
	 */
	public function get_items( $args ) {
		return $this->get_user_ids_by_query( $args );
	}

	/**
	 * @see CAC_Sortable_Model::get_sortables()
	 * @since 1.0
	 */
	public function get_sortables() {

		$column_names = array(

			// WP default columns
			'role',
			'posts',

			// Custom Columns
			'column-first_name',
			'column-display_name',
			'column-last_name',
			'column-meta',
			'column-nickname',
			'column-roles',
			'column-user_commentcount',
			'column-user_description',
			'column-user_id',
			'column-user_postcount',
			'column-user_registered',
			'column-user_url',

			// ACF Fields
			'column-acf_field',

			// WooCommerce
			'column-wc-user-orders',
			'column-wc-user-order_count',
		);

		return array_merge( $column_names, (array) $this->get_default_sortables() );
	}

	/**
	 * Columns that are sortable by WordPress core
	 *
	 * @since 3.8
	 */
	public function get_default_sortables() {
		$columns = array(
			'username',
			'name',
			'email',
		);

		return $columns;
	}

	/**
	 * @param $user_query
	 *
	 * @return array User IDS
	 */
	private function get_user_ids_by_query( $user_query ) {
		$_user_query = clone $user_query;
		$_user_query->set( 'fields', 'ids' ); // Less resources
		$_user_query->query_limit = null; // ALL users
		$_user_query->query();

		return (array) $_user_query->get_results();
	}

	/**
	 * Admin requests for orderby column
	 *
	 * Only works for WP_Query objects ( such as posts and media )
	 *
	 * @since 1.0
	 *
	 * @param array $vars
	 *
	 * @return array Vars
	 */
	public function handle_sorting_request( $user_query ) {
		global $wpdb;

		if ( empty( $user_query->query_vars['orderby'] ) ) {
			return;
		}

		// skip if it's already sorted
		if ( isset( $user_query->query_vars['skip_sorting'] ) ) {
			return;
		}

		$vars = $this->apply_sorting_preference( $user_query->query_vars );

		$column = $this->get_column_by_orderby( $vars['orderby'] );
		if ( empty( $column ) ) {
			return;
		}

		$show_all_results = $this->get_show_all_results();

		$_users = array();

		switch ( $column->properties->type ) :

			// WP Default Columns
			case 'role' :
			case 'column-roles' :
				global $wp_roles, $wpdb;
				$sort_flag = SORT_REGULAR;
				$prefix = $wpdb->get_blog_prefix();
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					if ( $caps = get_user_meta( $id, $prefix . 'capabilities', true ) ) {

						// Filter out caps that are not role names and assign to $this->roles
						if ( $roles = array_filter( array_keys( $caps ), array( $wp_roles, 'is_role' ) ) ) {
							$role = $roles[0];
							if ( isset( $wp_roles->roles[ $role ] ) ) {
								$_users[ $id ] = $this->prepare_sort_string_value( translate_user_role( $wp_roles->roles[ $role ]['name'] ) );
							}
						}
					}
				}
				break;

			case 'posts' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					$value = $column->get_user_postcount( $id, 'post' );
					if ( $value || $show_all_results ) {
						$_users[ $id ] = $value;
					}
				}
				break;

			// Custom Columns
			case 'column-user_id' :
				$user_query->query_orderby = "ORDER BY ID {$vars['order']}";
				$vars['orderby'] = 'ID';
				break;

			case 'column-user_registered' :
				$user_query->query_orderby = "ORDER BY user_registered {$vars['order']}";
				$vars['orderby'] = 'registered';
				break;

			case 'column-nickname' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-first_name' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-display_name' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-last_name' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-user_url' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-user_description' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-user_commentcount' :
				// @todo: maybe use WP_Comment_Query to generate this subquery? penalty is extra query and bloat, advantage is WP_Comment_Query filters used
				$sub_query = "
					LEFT JOIN (
						SELECT user_id, COUNT(user_id) AS comment_count
						FROM {$wpdb->comments}
						WHERE user_id <> 0
						GROUP BY user_id
					) AS comments
					ON {$wpdb->users}.ID = comments.user_id
				";

				$user_query->query_from .= $sub_query;
				$user_query->query_orderby = "ORDER BY comment_count " . $vars['order'];

				if ( ! $show_all_results ) {
					$user_query->query_where .= " AND comment_count IS NOT NULL";
				}

				break;
			case 'column-user_postcount' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					$value = $column->get_count( $id );
					if ( $value || $show_all_results ) {
						$_users[ $id ] = $value;
					}
				}
				break;

			case 'column-meta' :
				if ( $result = $this->get_meta_items_for_sorting( $column, $user_query ) ) {
					$_users = $result['items'];
					$sort_flag = $result['sort_flag'];
				}
				else {
					$is_numeric = in_array( $column->get_option( 'field_type' ), array(
						'numeric',
						'library_id',
						'count',
					) );
					$sort_flag = $is_numeric ? SORT_NUMERIC : SORT_REGULAR;
				}
				break;

			case 'column-acf_field' :
				if ( method_exists( $column, 'get_field' ) ) {
					$sort_flag = SORT_REGULAR;
					$_users = $this->get_acf_sorting_data( $column, $this->get_user_ids_by_query( $user_query ) );
				}
				break;

			// WooCommerce
			case 'column-wc-user-orders':
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$_users[ $id ] = count( $value );
					}
				}
				break;

			case 'column-wc-user-order_count':
				$sort_flag = SORT_NUMERIC;
				break;

			case 'column-wc-user-total-sales':
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					$value = $column->get_sorting_value( $id );
					if ( $value || $show_all_results ) {
						$_users[ $id ] = $value;
					}
				}
				break;

			// Try to sort by raw value.
			default :
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$_users[ $id ] = $value;
					}
				}

		endswitch;

		if ( isset( $sort_flag ) ) {

			if ( empty( $_users ) ) {

				// Use original user_query to retrieve user IDs. In case the user query has been filtered we'll
				// be sorting a lot less users
				foreach ( $this->get_user_ids_by_query( $user_query ) as $id ) {
					$value = method_exists( $column, 'get_raw_value' ) ? $column->get_raw_value( $id ) : $column->get_value( $id );
					if ( $value || $show_all_results ) {
						$_users[ $id ] = $this->prepare_sort_string_value( $value );
					}
				}
			}

			// sorting
			if ( 'ASC' == $vars['order'] ) {
				asort( $_users, $sort_flag );
			}
			else {
				arsort( $_users, $sort_flag );
			}

			if ( ! empty( $_users ) ) {

				// for MU site compatibility
				$prefix = $wpdb->base_prefix;

				$column_names = implode( ',', array_keys( $_users ) );
				$user_query->query_where .= " AND {$prefix}users.ID IN ({$column_names})";
				$user_query->query_orderby = "ORDER BY FIELD({$prefix}users.ID,{$column_names})";
			}

			// cleanup
			$vars['order'] = '';
			$vars['orderby'] = '';
		}

		$user_query->query_vars = array_merge( $user_query->query_vars, $vars );
	}
}