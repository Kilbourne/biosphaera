<?php

/**
 * Addon class
 *
 * @since 1.0
 */
class CAC_Sortable_Model_Post extends CAC_Sortable_Model {

	public function set_default_orderby() {
		$this->default_orderby = 'menu_order title';
	}

	public function init_hooks() {
		add_filter( 'request', array( $this, 'handle_sorting_request' ), 1 );
		add_filter( "manage_" . $this->storage_model->get_screen_id() . "_sortable_columns", array( $this, 'add_sortable_headings' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_reset_button' ) );
	}

	public function is_hierarchical_display( $orderby ) {
		return is_post_type_hierarchical( $this->storage_model->get_post_type() ) && $this->default_orderby == $orderby;
	}

	/**
	 * @since 3.7
	 */
	public function get_items( $args ) {
		return $this->get_posts( $args );
	}

	/**
	 * Get sortables
	 *
	 * @see CAC_Sortable_Model::get_sortables()
	 * @since 1.0
	 */
	public function get_sortables() {

		$column_names = array(

			// WP default columns
			'author',
			'categories',
			'tags',

			// Custom Columns
			'column-attachment',
			'column-attachment_count',
			'column-author_name',
			'column-before_moretag',
			'column-comment_count',
			'column-comment_status',
			'column-date_published',
			'column-depth',
			'column-estimated_reading_time',
			'column-excerpt',
			'column-featured_image',
			'column-last_modified_author',
			'column-meta',
			'column-modified',
			'column-order',
			'column-page_template',
			'column-parent',
			'column-path',
			'column-ping_status',
			'column-post_formats',
			'column-postid',
			'column-roles',
			'column-slug',
			'column-status',
			'column-sticky',
			'column-taxonomy',
			'column-used_by_menu',
			'column-word_count',

			// ACF Fields
			'column-acf_field',

			// WooCommerce columns

			// Default WC
			'product_cat',
			'product_tag',

			// WC Product
			'price',
			'sku',
			'column-wc-dimensions',
			'column-wc-backorders_allowed',
			'column-wc-featured',
			'column-wc-parent',
			'column-wc-reviews_enabled',
			'column-wc-shipping_class',
			'column-wc-stock-status',
			'column-wc-variation',
			'column-wc-visibility',
			'column-wc-weight',

			// WC Order
			'order_status',
			'column-wc-cart_discount',
			'column-wc-order_discount',
			'column-wc-payment_method',
			'column-wc-product',

			// WC Coupon
			'type',
			'amount',
			'usage',
			'customer_message',
			'column-wc-free_shipping',
			'column-wc-apply_before_tax',
			'column-wc-product_type',
			'column-wc-payment_method',
			'column-wc-transaction_id',
			'expiry_date',
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

			// WordPress
			'date',
			'title',

			// WC Order
			'order_total',
			'order_date',
			'order_title',

			// WC Product
			'name',
			'sku',
			'price',
			'featured'
		);

		return $columns;
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
	public function handle_sorting_request( $_vars ) {

		// only handle request for this storage type
		if ( empty( $_vars['post_type'] ) || $_vars['post_type'] !== $this->storage_model->key ) {
			return $_vars;
		}

		$vars = $this->apply_sorting_preference( $_vars );

		if ( empty( $vars['orderby'] ) ) {
			return $_vars;
		}

		$column = $this->get_column_by_orderby( $vars['orderby'] );

		if ( empty( $column ) ) {
			return $_vars;
		}

		if ( ! $this->is_sortable( $column ) ) {
			return $_vars;
		}

		$show_all_results = $this->get_show_all_results();

		// Set posts args when user selected a specific post status
		$post_args = array();
		if ( isset( $vars['post_status'] ) ) {
			$post_args['post_status'] = $vars['post_status'];
		}

		$posts = array();
		switch ( $column->properties->type ) :

			// WP Default Columns
			case 'title' :
				$vars['orderby'] = 'title';
				break;

			case 'author' :
				$vars['orderby'] = 'author';
				break;

			case 'categories' :
				$sort_flag = SORT_NUMERIC;
				$posts = $this->get_posts_sorted_by_taxonomy( 'category' );
				break;

			case 'date' :
				// Needs to be here, otherwise default sorting is not working for date
				break;

			case 'tags' :
				$sort_flag = SORT_NUMERIC;
				$posts = $this->get_posts_sorted_by_taxonomy( 'post_tag' );
				break;

			// Custom Columns
			case 'date_published' :
				$vars['orderby'] = 'date';
				break;

			case 'column-postid' :
				$vars['orderby'] = 'ID';
				break;

			case 'column-order' :
				$vars['orderby'] = 'menu_order';
				break;

			case 'column-modified' :
				$vars['orderby'] = 'modified';
				break;

			case 'column-comment_count' :
				$vars['orderby'] = 'comment_count';
				break;

			case 'column-depth' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-excerpt':
			case 'customer_message':
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					if ( ! ( $value = get_post_field( 'post_excerpt', $id ) ) ) {
						$value = trim( strip_tags( get_post_field( 'post_content', $id ) ) );
					}

					if ( $value || $show_all_results ) {
						$posts[ $id ] = $this->prepare_sort_string_value( $value );
					}
				}
				break;

			case 'column-word_count' :
				$sort_flag = SORT_NUMERIC;
				break;

			case 'column-page_template' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'column-path' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-post_formats' :
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'column-attachment' :
			case 'column-attachment_count' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			// @todo: can be improved, slug will sort 'slug-93', 'slug-9' and then 'slug-83'.
			// needs sorting mix with string and numeric
			case 'column-slug' :
				$sort_flag = SORT_REGULAR;
				break;

			case 'column-sticky' :
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value ? 0 : $id;
					}
				}
				break;

			case 'column-featured_image' :
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value ? 0 : $id;
					}
				}
				break;

			case 'column-last_modified_author' :
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$posts[ $id ] = $column->get_value( $id );
				}
				break;

			case 'column-roles' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'column-status' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value . strtotime( $id );
					}
				}
				break;

			case 'column-wc-reviews_enabled' :
			case 'column-comment_status' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value . strtotime( $id );
					}
				}
				break;

			case 'column-ping_status' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value . strtotime( $id );
					}
				}
				break;

			case 'column-taxonomy' :
				$sort_flag = SORT_NUMERIC;
				$posts = $this->get_posts_sorted_by_taxonomy( $column->get_option( 'taxonomy' ) );
				break;

			case 'column-author_name' :
				$sort_flag = SORT_STRING;
				if ( 'userid' == $column->get_option( 'display_author_as' ) ) {
					$sort_flag = SORT_NUMERIC;
				}
				break;

			case 'column-before_moretag' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-parent' :
				$sort_flag = SORT_STRING;
				$post_ids = $this->get_posts( array( 'fields' => 'id=>parent' ) );
				foreach ( $post_ids as $id => $parent_id ) {
					if ( $parent_id || $show_all_results ) {
						$posts[ $id ] = $column->get_post_title( $parent_id ) . strtotime( $id );
					}
				}
				break;


			// Custom Field
			case 'column-meta' :
				if ( $result = $this->get_meta_items_for_sorting( $column, $post_args ) ) {
					$posts = $result['items'];
					$sort_flag = $result['sort_flag'];
				}

				// No sorted items and show_all_results is 'false'
				else {
					$vars['meta_query'][] = array(
						'key'     => $column->get_field_key(),
						'type'    => in_array( $column->get_option( 'field_type' ), array( 'numeric', 'library_id', 'count' ) ) ? 'NUMERIC' : 'CHAR',
						'compare' => '!=',
						'value'   => ''
					);
					$vars['orderby'] = $column->get_field_key();
				}
				break;

			// ACF
			case 'column-acf_field' :

				// make sure acf has not been deactivated in the meanwhile...
				if ( method_exists( $column, 'get_field' ) ) {
					$is_sortable = $this->set_acf_sorting_vars( $column, $vars );
					if ( ! $is_sortable ) {
						$sort_flag = SORT_REGULAR;
						$posts = $this->get_acf_sorting_data( $column, $this->get_posts( $post_args ) );
					}
				}
				break;

			// WooCommerce
			case 'product_cat' :
				$sort_flag = SORT_NUMERIC;
				$posts = $this->get_posts_sorted_by_taxonomy( 'product_cat' );
				break;

			case 'product_tag' :
				$sort_flag = SORT_NUMERIC;
				$posts = $this->get_posts_sorted_by_taxonomy( 'product_tag' );
				break;

			case 'column-wc-parent':
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$parent_id = $column->get_raw_value( $id );
					if ( $parent_id || $show_all_results ) {
						$posts[ $id ] = $column->get_post_title( $parent_id );
					}
				}
				break;

			case 'column-wc-price' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$raw_value = $column->get_raw_value( $id );
					$value = isset( $raw_value['regular_price'] ) ? $raw_value['regular_price'] : '';
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value . strtotime( $id );
					}
				}
				break;

			case 'column-wc-dimensions' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$raw_value = $column->get_raw_value( $id );

					$value = '';
					if ( $raw_value['length'] || $raw_value['width'] || $raw_value['height'] ) {
						$value = $raw_value['length'] * $raw_value['width'] * $raw_value['height'];
					}
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'amount':
				$sort_flag = SORT_NUMERIC;
				break;

			case 'sku':
				$sort_flag = SORT_NATURAL;
				break;

			case 'usage':
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$raw_value = $column->get_raw_value( $id );
					$usage = isset( $raw_value['usage_limit'] ) ? $raw_value['usage_limit'] : false;
					if ( $usage || $show_all_results ) {
						$posts[ $id ] = $usage;
					}
				}
				break;

			case 'column-wc-visibility':
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = $column->get_raw_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'column-wc-product':
				$sort_flag = SORT_REGULAR;
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$labels = $column->get_product_labels( $id );
					if ( $labels || $show_all_results ) {
						$posts[ $id ] = reset( $labels );
					}
				}
				break;

			// Try to sort by raw value.
			// Only used by added custom admin column through the API
			default :

				$sort_flag = SORT_REGULAR;
				if ( 'numeric' == $column->get_property( 'sort_type' ) ) {
					$sort_flag = SORT_NUMERIC;
				}

				// @since 3.6.1
				if ( method_exists( $column, 'get_sorting_value' ) ) {
					foreach ( $this->get_posts( $post_args ) as $id ) {
						$value = $column->get_sorting_value( $id );
						if ( $value || $show_all_results ) {
							$posts[ $id ] = $value;
						}
					}
				}

				// @since 2.0.3
				else if ( method_exists( $column, 'get_raw_value' ) ) {
					foreach ( $this->get_posts( $post_args ) as $id ) {
						$value = $column->get_raw_value( $id );
						if ( $value || $show_all_results ) {
							$posts[ $id ] = $value;
						}
					}

				}


		endswitch;

		// Pagination can break if it's a hierarchical post
		if ( ! $this->is_hierarchical_display( $vars['orderby'] ) ) {

			$per_page = (int) get_user_option( "edit_{$this->storage_model->key}_per_page" );

			$vars['posts_per_archive_page'] = $per_page ? $per_page : 20;
			$vars['posts_per_page'] = $per_page ? $per_page : 20;
		}

		// we will add the sorted post ids to vars['post__in'] and remove unused vars
		if ( isset( $sort_flag ) ) {
			if ( ! $posts ) {
				foreach ( $this->get_posts( $post_args ) as $id ) {
					$value = method_exists( $column, 'get_raw_value' ) ? $column->get_raw_value( $id ) : $column->get_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $this->prepare_sort_string_value( $value );
					}
				}
			}
			$vars = $this->get_vars_post__in( $vars, $posts, $sort_flag );
		}

		/**
		 * Filters the sorting vars
		 *
		 * @since 3.2.1
		 *
		 * @param $vars array WP Query vars
		 * @param $column object Column instance
		 */
		return apply_filters( 'cac/addon/sortable/vars', $vars, $column );
	}
}