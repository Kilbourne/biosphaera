<?php

/**
 * @since 1.0
 */
class CAC_Filtering_Model_Post extends CAC_Filtering_Model_Post_Object {

	/**
	 * @since 3.8
	 */
	public function get_filterables() {
		$column_types = array(

			// WP default columns
			'tags',

			// Custom columns
			'column-author_name',
			'column-before_moretag',
			'column-comment_count',
			'column-comment_status',
			'column-excerpt',
			'column-featured_image',
			'column-last_modified_author',
			'column-parent',
			'column-page_template',
			'column-ping_status',
			'column-post_formats',
			'column-roles',
			'column-status',
			'column-sticky',
			'column-taxonomy',
		);

		return $column_types;
	}

	/**
	 * @since 3.8
	 */
	public function get_dropdown_html_element_ids() {
		return array(
			'date'       => 'filter-by-date',
			'categories' => 'cat'
		);
	}

	/**
	 * @since 3.8
	 */
	public function get_default_filterables() {
		return array(
			'date',
			'categories'
		);
	}

	/**
	 * Alter WP_Query
	 *
	 * @since 3.5
	 */
	public function filter_by_author_name( $where ) {
		return $where . $this->wpdb->prepare( "AND {$this->wpdb->posts}.post_author = %s", $this->get_filter_value( 'column-author_name' ) );
	}

	public function filter_by_before_moretag( $where ) {
		return $where . "AND {$this->wpdb->posts}.post_content" . $this->get_sql_value( $this->get_filter_value( 'column-before_moretag' ), '<!--more-->' );
	}

	public function filter_by_comment_count( $where ) {
		$val = $this->get_filter_value( 'column-comment_count' );
		$sql_val = ' = ' . $val;
		if ( 'cpac_not_empty' == $val ) {
			$sql_val = ' != 0';
		}
		else if ( 'cpac_empty' == $val ) {
			$sql_val = ' = 0';
		}

		return "{$where} AND {$this->wpdb->posts}.comment_count" . $sql_val;
	}

	public function filter_by_comment_status( $where ) {
		return $where . "AND {$this->wpdb->posts}.comment_status" . $this->get_sql_value( $this->get_filter_value( 'column-comment_status' ) );
	}

	public function filter_by_excerpt( $where ) {
		$val = $this->get_filter_value( 'column-excerpt' );
		$sql_val = '1' === $val ? " != ''" : " = ''";

		return "{$where} AND {$this->wpdb->posts}.post_excerpt" . $sql_val;
	}

	public function filter_by_ping_status( $where ) {
		return $where . $this->wpdb->prepare( "AND {$this->wpdb->posts}.ping_status = %s", $this->get_filter_value( 'column-ping_status' ) );
	}

	public function filter_by_sticky( $where ) {
		$val = $this->get_filter_value( 'column-sticky' );
		if ( ! ( $stickies = get_option( 'sticky_posts' ) ) ) {
			return $where;
		}
		$sql_val = '1' === $val ? " IN ('" . implode( "','", $stickies ) . "')" : " NOT IN ('" . implode( "','", $stickies ) . "')";

		return "{$where} AND {$this->wpdb->posts}.ID" . $sql_val;
	}

	public function filter_by_wc_reviews_enabled( $where ) {
		return $where . "AND {$this->wpdb->posts}.comment_status" . $this->get_sql_value( $this->get_filter_value( 'column-wc-reviews_enabled' ) );
	}

	/**
	 * WooCommerce SQL
	 */
	public function join_by_order_itemmeta( $join ) {
		$join .= "LEFT JOIN {$this->wpdb->prefix}woocommerce_order_items AS oi ON ( {$this->wpdb->posts}.ID = oi.order_id ) ";
		$join .= "LEFT JOIN {$this->wpdb->prefix}woocommerce_order_itemmeta AS om ON ( oi.order_item_id = om.order_item_id ) ";

		return $join;
	}

	public function filter_by_wc_product_title( $where ) {
		return $where . $this->wpdb->prepare( "AND om.meta_value = %d AND om.meta_key = '_product_id'", $this->get_filter_value( 'column-wc-product-title' ) );
	}

	public function join_by_postmeta( $join ) {
		return $join . "LEFT JOIN {$this->wpdb->postmeta} AS pm ON ( pm.post_id = om.meta_value AND om.meta_key = '_product_id' ) ";
	}

	public function filter_by_wc_product_sku( $where ) {
		return $where . $this->wpdb->prepare( "AND pm.meta_value = %s AND pm.meta_key = '_sku'", get_post_meta( $this->get_filter_value( 'column-wc-product-sku' ), '_sku', true ) );
	}

	public function filter_by_wc_shipping_method( $where ) {
		return $where . $this->wpdb->prepare( "AND om.meta_value = %s AND om.meta_key = 'method_id'", $this->get_filter_value( 'column-wc-order_shipping_method' ) );
	}

	/**
	 * Get SQL compare
	 *
	 * @since 1.0
	 *
	 * @param string $filter_value Selected filter value
	 * @param string $value_to_match_empty Overwrite the filter value
	 *
	 * @return string SQL compare
	 */
	private function get_sql_value( $filter_value, $value_to_match_empty = '' ) {
		$sql_query_compare = " = '{$filter_value}'";

		if ( 'cpac_not_empty' === $filter_value || '1' === $filter_value ) {
			$val = $value_to_match_empty ? $value_to_match_empty : $filter_value;
			$sql_query_compare = " LIKE '%{$val}%'";
		}
		else if ( 'cpac_empty' == $filter_value || '0' === $filter_value ) {
			$val = $value_to_match_empty ? $value_to_match_empty : $filter_value;
			$sql_query_compare = " NOT LIKE '%{$val}%'";
		}

		return $sql_query_compare;
	}

	/**
	 * Handle filter request
	 *
	 * @since 1.0
	 */
	public function handle_filter_requests( $vars ) {

		// go through all filter requests per column
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

			// meta arguments
			$meta_value = in_array( $value, array( 'cpac_empty', 'cpac_not_empty' ) ) ? '' : $value;
			$meta_query_compare = 'cpac_not_empty' == $value ? '!=' : '=';

			switch ( $column->get_type() ) :

				// Default
				case 'tags' :
					$vars['tax_query']['relation'] = 'AND';
					$vars['tax_query'][] = $this->get_taxonomy_query( $value, 'post_tag' );
					break;

				// Custom
				case 'column-author_name' :
					add_filter( 'posts_where', array( $this, 'filter_by_author_name' ) );
					break;

				case 'column-before_moretag' :
					add_filter( 'posts_where', array( $this, 'filter_by_before_moretag' ) );
					break;

				case 'column-comment_count' :
					add_filter( 'posts_where', array( $this, 'filter_by_comment_count' ) );
					break;

				case 'column-comment_status':
					add_filter( 'posts_where', array( $this, 'filter_by_comment_status' ) );
					break;

				case 'column-excerpt' :
					add_filter( 'posts_where', array( $this, 'filter_by_excerpt' ) );
					break;

				case 'column-featured_image' :
					if ( 'cpac_empty' == $value ) {
						$meta_query_compare = 'NOT EXISTS';
					}

					$vars['meta_query'][] = array(
						'key'     => '_thumbnail_id',
						'value'   => $meta_value,
						'compare' => $meta_query_compare
					);
					break;

				case 'column-last_modified_author' :
					$vars['meta_query'][] = array(
						'key'     => '_edit_last',
						'value'   => $meta_value,
						'compare' => $meta_query_compare
					);
					break;
				case 'column-parent':
					$vars['post_parent'] = $value;
					break;
				case 'column-page_template' :
					$vars['meta_query'][] = array(
						'key'     => '_wp_page_template',
						'value'   => $meta_value,
						'compare' => $meta_query_compare
					);
					break;

				case 'column-ping_status' :
					add_filter( 'posts_where', array( $this, 'filter_by_ping_status' ) );
					break;

				case 'column-post_formats' :
					$vars['tax_query'][] = array(
						'taxonomy' => 'post_format',
						'field'    => 'slug',
						'terms'    => $value
					);
					break;

				case 'column-roles' :
					$user_ids = get_users( array( 'role' => $value, 'fields' => 'id' ) );
					$vars['author'] = implode( ',', $user_ids );
					break;

				case 'column-sticky' :
					add_filter( 'posts_where', array( $this, 'filter_by_sticky' ) );
					break;

				case 'column-status' :
					$vars['post_status'] = $value;
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

				// WooCommerce
				case 'product_cat' :
					$vars['tax_query'][] = array(
						'taxonomy' => 'product_cat',
						'field'    => 'slug',
						'terms'    => $value
					);
					break;

				case 'product_tag' :
					$vars['tax_query'][] = array(
						'taxonomy' => 'product_tag',
						'field'    => 'slug',
						'terms'    => $value
					);
					break;

				case 'column-wc-featured' :
					$vars['meta_query'][] = array(
						'key'     => '_featured',
						'value'   => $meta_value,
						'compare' => $meta_query_compare
					);
					break;

				case 'column-wc-visibility' :
					$vars['meta_query'][] = array(
						'key'     => '_visibility',
						'value'   => $meta_value,
						'compare' => $meta_query_compare
					);
					break;

				case 'column-wc-free_shipping':
					$vars['meta_query'][] = array(
						'key'   => 'free_shipping',
						'value' => $meta_value
					);
					break;

				case 'column-wc-order_coupons_used':
					if ( 'no' == $meta_value ) {
						$meta_query_compare = 'NOT EXISTS';
					}

					$vars['meta_query'][] = array(
						'key'     => '_recorded_coupon_usage_counts',
						'value'   => $meta_value,
						'compare' => $meta_query_compare
					);

					break;

				case 'column-wc-shipping_class':
					$vars['tax_query']['relation'] = 'AND';
					$vars['tax_query'][] = $this->get_taxonomy_query( $value, 'product_shipping_class' );
					break;

				case 'column-wc-parent':
					$vars['post_parent'] = $value;
					break;

				case 'column-wc-payment_method':
					$vars['meta_query'][] = array(
						'key'   => '_payment_method',
						'value' => $meta_value
					);
					break;

				case 'column-wc-product':

					switch ( $column->get_product_property() ) {
						case 'title' :
							$this->set_filter_value( 'column-wc-product-title', $value );

							add_filter( 'posts_join', array( $this, 'join_by_order_itemmeta' ) );
							add_filter( 'posts_where', array( $this, 'filter_by_wc_product_title' ) );
							break;
						case 'sku' :
							$this->set_filter_value( 'column-wc-product-sku', $value );

							add_filter( 'posts_join', array( $this, 'join_by_order_itemmeta' ) );
							add_filter( 'posts_join', array( $this, 'join_by_postmeta' ) );
							add_filter( 'posts_where', array( $this, 'filter_by_wc_product_sku' ) );
							break;
					}
					break;

				case 'column-wc-reviews_enabled':
					add_filter( 'posts_where', array( $this, 'filter_by_wc_reviews_enabled' ) );
					break;

				case 'column-wc-order_shipping_method':
					add_filter( 'posts_join', array( $this, 'join_by_order_itemmeta' ) );
					add_filter( 'posts_where', array( $this, 'filter_by_wc_shipping_method' ) );
					break;

				case 'column-wc-tax_class':
					$vars['meta_query'][] = array(
						'key'   => '_tax_class',
						'value' => $meta_value,
					);
					break;

				case 'column-wc-tax_status':
					$vars['meta_query'][] = array(
						'key'   => '_tax_status',
						'value' => $meta_value,
					);
					break;

				case 'order_status':
					$vars['post_status'] = ( substr( $value, 0, 3 ) == 'wc-' ) ? $value : 'wc-' . $value;
					break;

				case 'customer_message' :
					add_filter( 'posts_where', array( $this, 'filter_by_excerpt' ) );
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

		switch ( $column->get_type() ) :

			// Default
			case 'tags' :
				$empty_option = true;
				$terms_args = apply_filters( 'cac/addon/filtering/taxonomy/terms_args', array() );
				$options = $this->apply_indenting_markup( get_terms( 'post_tag', $terms_args ) );
				break;

			// Custom
			case 'column-sticky' :
				$options = array(
					0 => __( 'Not sticky', 'codepress-admin-columns' ),
					1 => __( 'Sticky', 'codepress-admin-columns' ),
				);
				break;

			case 'column-roles' :
				$options = $column->get_roles();
				break;

			case 'column-page_template' :
				if ( $values = $this->get_values_by_meta_key( '_wp_page_template' ) ) {
					foreach ( $values as $data ) {
						$page_template = $data->value;
						if ( $label = array_search( $page_template, get_page_templates() ) ) {
							$page_template = $label;
						}
						$options[ $data->value ] = $page_template;
					}
				}
				break;
			case 'column-parent':
				if ( $values = $this->get_post_fields( 'post_parent' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $column->get_post_title( $value );
					}
				}
				break;
			case 'column-ping_status' :
				if ( $values = $this->get_post_fields( 'ping_status' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = ucfirst( $value );
					}
				}
				break;

			case 'column-post_formats' :
				$options = $this->apply_indenting_markup( $this->indent( get_terms( 'post_format', array( 'hide_empty' => false ) ), 0, 'parent', 'term_id' ) );
				break;

			case 'column-excerpt' :
				$options = array(
					0 => __( 'Empty', 'codepress-admin-columns' ),
					1 => __( 'Has excerpt', 'codepress-admin-columns' ),
				);
				break;

			case 'column-comment_count' :
				$empty_option = true;
				if ( $values = $this->get_post_fields( 'comment_count' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $value;
					}
				}
				break;

			case 'column-before_moretag' :
				$options = array(
					0 => __( 'Without more tag', 'codepress-admin-columns' ),
					1 => __( 'Has more tag', 'codepress-admin-columns' ),
				);
				break;

			case 'column-author_name' :
				if ( $values = $this->get_post_fields( 'post_author' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $column->get_display_name( $value );
					}
				}
				break;

			case 'column-featured_image' :
				$empty_option = true;
				if ( $values = $this->get_values_by_meta_key( '_thumbnail_id' ) ) {
					foreach ( $values as $data ) {
						$options[ $data->value ] = $data->value;
					}
				}
				break;

			case 'column-comment_status':
			case 'column-wc-reviews_enabled':
				$options = array(
					'open'   => __( 'Open' ),
					'closed' => __( 'Closed' )
				);
				break;

			case 'column-status' :
				if ( $values = $this->get_post_fields( 'post_status' ) ) {
					foreach ( $values as $value ) {
						if ( 'auto-draft' != $value ) {
							$options[ $value ] = esc_html( $column->get_status( $value ) );
						}
					}
				}
				break;

			case 'column-taxonomy' :
				if ( taxonomy_exists( $column->get_option( 'taxonomy' ) ) ) {
					$empty_option = true;
					$order = false; // do not sort, messes up the indenting
					$terms_args = apply_filters( 'cac/addon/filtering/taxonomy/terms_args', array() );
					$options = $this->apply_indenting_markup( $this->indent( get_terms( $column->get_option( 'taxonomy' ), $terms_args ), 0, 'parent', 'term_id' ) );
				}
				break;

			case 'column-last_modified_author' :
				if ( $values = $this->get_values_by_meta_key( '_edit_last' ) ) {
					foreach ( $values as $data ) {
						$options[ $data->value ] = $column->get_display_name( $data->value );
					}
				}
				break;

			// Custom Field column
			case 'column-meta' :
				if ( $_options = $this->get_meta_options( $column ) ) {
					$empty_option = $_options['empty_option'];
					$options = $_options['options'];
				}
				break;

			// ACF column
			case 'column-acf_field' :
				if ( $_options = $this->get_acf_options( $column ) ) {
					$order = $_options['order'];
					$empty_option = $_options['empty_option'];
					$options = $_options['options'];
				}
				break;

			// WooCommerce columns
			case 'column-wc-featured':
				$options = array(
					'no'  => __( 'No' ),
					'yes' => __( 'Yes' )
				);
				break;

			case 'column-wc-visibility':
				$options = $column->get_visibility_options();
				break;

			case 'column-wc-free_shipping':
				$options = array(
					'no'  => __( 'No' ),
					'yes' => __( 'Yes' )
				);
				break;

			case 'column-wc-order_coupons_used':
				$options = array(
					'no'  => __( 'No' ),
					'yes' => __( 'Yes' )
				);
				break;

			case 'column-wc-shipping_class':
				$empty_option = true;
				$order = false; // do not sort, messes up the indenting
				$terms_args = apply_filters( 'cac/addon/filtering/taxonomy/terms_args', array() );
				$options = $this->apply_indenting_markup( $this->indent( get_terms( 'product_shipping_class', $terms_args ), 0, 'parent', 'term_id' ) );
				break;

			case 'column-wc-parent':
				$empty_option = true;

				if ( $values = $this->get_post_fields( 'post_parent' ) ) {
					foreach ( $values as $value ) {
						$options[ $value ] = $column->get_post_title( $value );
					}
				}
				break;
			case 'column-wc-payment_method':
				$empty_option = true;

				if ( WC()->payment_gateways() ) {
					$payment_gateways = WC()->payment_gateways->payment_gateways();
				}
				else {
					$payment_gateways = array();
				}

				foreach ( $payment_gateways as $gateway ) {
					if ( $gateway->enabled == 'yes' ) {
						$options[ $gateway->id ] = $gateway->get_title();
					}
				}
				break;

			case 'column-wc-product':
				$options = (array) $column->get_all_ordered_products( $column->get_product_property() );
				break;

			case 'order_status':
				$options = array();

				if ( cpac_is_wc_version_gte( '2.2' ) ) {
					$options = wc_get_order_statuses();
				}
				else {
					$statuses_raw = (array) get_terms( 'shop_order_status', array(
						'hide_empty' => 0,
						'orderby'    => 'id'
					) );

					foreach ( $statuses_raw as $status ) {
						$options[ $status->slug ] = $status->name;
					}
				}
				break;
			case 'column-wc-order_shipping_method':
				$options = (array) $column->get_shipping_methods();
				break;

			case 'column-wc-tax_class':
				$options = (array) $column->get_tax_classes();
				break;

			case 'column-wc-tax_status':
				$options = (array) $column->get_tax_status();
				break;

			case 'customer_message':
				$options = array(
					0 => __( 'Empty', 'codepress-admin-columns' ),
					1 => __( 'Has customer message', 'codepress-admin-columns' ),
				);
				break;

			default :
				if ( method_exists( $column, 'get_filter_options' ) ) {
					$options = (array) $column->get_filter_options();
				}

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