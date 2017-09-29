<?php

/**
 * Post storage model for editability
 *
 * @since 1.0
 */
class CACIE_Editable_Model_Post extends CACIE_Editable_Model {

	/**
	 * @since 3.6.1
	 */
	public function get_ajax_options( $column, $search ) {

		$options = array();

		switch ( $column->properties->type ) {

			case 'column-parent':
			case 'column-wc-parent':
				$options = $this->get_posts_options( array( 's' => $search, 'post_type' => $column->get_post_type() ) );
				break;

			// WooCommerce: Upsells
			// WooCommerce: Crosssells
			// WooCommerce: Included Products
			// WooCommerce: Excluded Products
			case 'column-wc-upsells':
			case 'column-wc-crosssells':
			case 'column-wc-exclude_products':
			case 'column-wc-include_products':
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					's'              => $search,
					'fields'         => 'ids',
					'posts_per_page' => 60
				);

				$args2 = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'meta_query'     => array(
						array(
							'key'     => '_sku',
							'value'   => $search,
							'compare' => 'LIKE'
						)
					),
					'fields'         => 'ids',
					'posts_per_page' => 60
				);

				$posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );

				$options = array();

				foreach ( $posts as $post ) {
					$product = wc_get_product( $post );
					$options[ $post ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
				}
				break;

			// WooCommerce: Parent Product
			case 'column-wc-parent':
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => 100,
					's'              => $search,
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => 'grouped'
						)
					),
					'fields'         => 'ids',
				);

				$posts = get_posts( $args );

				$options = array();

				foreach ( $posts as $post ) {
					$product = wc_get_product( $post );
					$name = str_replace( '&ndash; ', '', $product->get_formatted_name() ); // removes arrow
					$options[ $post ] = $name;
				}
				break;
		}

		return $options;
	}

	/**
	 * @see CACIE_Editable_Model::is_editable()
	 * @since 1.0
	 */
	public function is_editable( $column ) {

		// By default, inherit editability from parent
		$is_editable = parent::is_editable( $column );

		switch ( $column->get_type() ) {
			// Default columns
			case 'author':
			case 'date':
			case 'categories':
			case 'tags':
			case 'title':

				// Custom columns
			case 'column-author_name':
			case 'column-attachment':
			case 'column-comment_status':
			case 'column-content':
			case 'column-date_published':
			case 'column-excerpt':
			case 'column-featured_image':
			case 'column-order':
			case 'column-page_template':
			case 'column-parent':
			case 'column-ping_status':
			case 'column-post_formats':
			case 'column-slug':
			case 'column-status':
			case 'column-sticky':
			case 'column-taxonomy':

				// WooCommerce columns

				// Product
			case 'thumb':
			case 'name':
			case 'sku':
			case 'is_in_stock':
			case 'price':
			case 'product_cat':
			case 'product_tag':
			case 'column-wc-backorders_allowed':
			case 'column-wc-crosssells':
			case 'column-wc-dimensions':
			case 'column-wc-featured':
			case 'column-wc-parent':
			case 'column-wc-reviews_enabled':
			case 'column-wc-shipping_class':
			case 'column-wc-stock-status':
			case 'column-wc-upsells':
			case 'column-wc-visibility':
			case 'column-wc-weight':

				// Order
			case 'order_status':

				// Coupon
			case 'coupon_code':
			case 'type':
			case 'amount':
			case 'description':
			case 'usage':
			case 'column-wc-apply_before_tax':
			case 'column-wc-exclude_products':
			case 'column-wc-free_shipping':
			case 'column-wc-include_products':
			case 'column-wc-minimum_amount':

				$is_editable = true;
				break;
		}

		/**
		 * Filter the editability of a column
		 *
		 * @since 3.4
		 *
		 * @param bool $is_editable Whether the column is editable
		 * @param CPAC_Column $column Column object
		 * @param CACIE_Editable_Model $model Editability storage model
		 */
		$is_editable = apply_filters( 'cac/editable/is_column_editable', $is_editable, $column, $this );
		$is_editable = apply_filters( 'cac/editable/is_column_editable/column=' . $column->get_type(), $is_editable, $column, $this );

		return $is_editable;
	}

	/**
	 * @see CACIE_Editable_Model::get_column_options()
	 * @since 1.0
	 */
	public function get_column_options( $column ) {

		$options = parent::get_column_options( $column );

		switch ( $column['type'] ) {

			// WP Default
			case 'categories':
				$options = $this->get_term_options( 'category' );
				break;
			case 'tags':
				$options = $this->get_term_options( 'post_tag' );
				break;

			// Custom columns
			case 'column-page_template':
				$options = $this->get_page_template_options();
				break;
			case 'column-post_formats':
				$options = get_post_format_strings();
				break;
			case 'column-status':
				if ( ( $_column = $this->storage_model->get_column_by_name( $column['column-name'] ) ) && ( method_exists( $_column, 'get_statuses' ) ) ) {
					$options = $_column->get_statuses();
					$options['trash'] = __( 'Trash' );
				}
				break;
			case 'column-taxonomy':
				$options = $this->get_term_options( $column['taxonomy'] );
				break;

			// WooCommerce columns
			case 'product_cat':
				$options = $this->get_term_options( 'product_cat' );
				break;
			case 'product_tag':
				$options = $this->get_term_options( 'product_tag' );
				break;
			case 'order_status':
				$options = $this->get_wc_order_status_options();
				break;
			case 'column-wc-visibility':
				if ( $_column_object = $this->storage_model->get_column_by_name( $column['column-name'] ) ) {
					$options = $_column_object->get_visibility_options();
				}
				break;
			case 'column-wc-shipping_class':
				$options = $this->get_term_options( 'product_shipping_class', __( 'No shipping class', 'codepress-admin-columns' ) );
				break;
		}

		return $options;
	}

	/**
	 * Get page template columns
	 *
	 * @since 1.0
	 *
	 * @return array Parent post options
	 */
	public function get_page_template_options() {

		return array_merge( array( '' => __( 'Default Template' ) ), array_flip( (array) get_page_templates() ) );
	}

	/**
	 * Get order status options for WooCommerce orders
	 *
	 * @since 1.1
	 *
	 * @return array Order status options ([slug] => [label])
	 */
	public function get_wc_order_status_options() {
		$statuses = array();
		if ( cpac_is_wc_version_gte( '2.2' ) ) {
			$statuses = wc_get_order_statuses();
		}
		else {
			$statuses_raw = (array) get_terms( 'shop_order_status', array( 'hide_empty' => 0, 'orderby' => 'id' ) );
			foreach ( $statuses_raw as $status ) {
				$statuses[ $status->slug ] = $status->name;
			}
		}

		return $statuses;
	}

	/**
	 * Get post parent columns
	 *
	 * @since 1.0
	 *
	 * @return array Parent post options ([post ID] => [post title])
	 */
	public function get_post_parent_options() {
		$options = array();

		$posts_query = new WP_Query( array(
			'post_type'      => $this->storage_model->key,
			'posts_per_page' => - 1
		) );

		if ( $posts_query->have_posts() ) {
			$nestedposts = CACIE_Arrays::array_nest( $posts_query->posts, 0, 'post_parent', 'ID', 'cacie_children' );
			$indentedposts = CACIE_Arrays::convert_nesting_to_indentation( $nestedposts, 'post_title', 'cacie_children' );

			foreach ( $indentedposts as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		return $options;
	}

	/**
	 * @see CACIE_Editable_Model::get_editables_data()
	 * @since 1.0
	 */
	public function get_editables_data() {

		$data = array(

			/**
			 * Default columns
			 *
			 */
			'author'                => array(
				'type'            => 'select2_dropdown',
				'property'        => 'post_author',
				'ajax_populate'   => true,
				'formatted_value' => 'user'
			),
			'categories'            => array(
				'type' => 'select2_tags'
			),
			'date'                  => array(
				'type'     => 'date',
				'property' => 'post_date'
			),
			'tags'                  => array(
				'type' => 'select2_tags'
			),
			'title'                 => array(
				'type'         => 'text',
				'property'     => 'post_title',
				'js'           => array(
					'selector' => 'a.row-title',
				),
				'display_ajax' => false
			),

			/**
			 * Custom columns
			 *
			 */
			'column-author_name'    => array(
				'type'            => 'select2_dropdown',
				'property'        => 'post_author',
				'ajax_populate'   => true,
				'formatted_value' => 'user'
			),
			'column-attachment'     => array(
				'type'       => 'media',
				'attachment' => array(
					'disable_select_current' => true,
				),
				'multiple'   => true
			),
			'column-comment_status' => array(
				'type'     => 'togglable',
				'property' => 'comment_status',
				'options'  => array( 'closed', 'open' )
			),
			'column-date_published' => array(
				'type'     => 'date',
				'property' => 'post_date'
			),
			'column-excerpt'        => array(
				'type'        => 'textarea',
				'property'    => 'post_excerpt',
				'placeholder' => __( 'Excerpt automatically generated from content.', 'codepress-admin-columns' )
			),
			'column-featured_image' => array(
				'type'         => 'media',
				'attachment'   => array(
					'library' => array(
						'type' => 'image'
					)
				),
				'clear_button' => true
			),
			'column-post_formats'   => array(
				'type' => 'select'
			),
			'column-page_template'  => array(
				'type' => 'select'
			),
			'column-parent'         => array(
				'type'            => 'select2_dropdown',
				'property'        => 'post_parent',
				'ajax_populate'   => true,
				'multiple'        => false,
				'clear_button'    => true,
				'formatted_value' => 'post'
			),
			'column-ping_status'    => array(
				'type'     => 'togglable',
				'property' => 'ping_status',
				'options'  => array( 'closed', 'open' )
			),
			'column-content'        => array(
				'type'     => 'textarea',
				'property' => 'post_content',
			),
			'column-order'          => array(
				'type'     => 'text',
				'property' => 'menu_order',
			),
			'column-slug'           => array(
				'type'     => 'text',
				'property' => 'post_name',
			),
			'column-sticky'         => array(
				'type'    => 'togglable',
				'options' => array( 'no', 'yes' )
			),
			// @todo on DOM update also refresh title ( contains post status aswell )
			'column-status'         => array(
				'type'     => 'select',
				'property' => 'post_status'
			),
			'column-taxonomy'       => array(
				'type' => 'select2_tags'
			),

			/**
			 * Custom fields column
			 *
			 */
			'column-meta'           => array(// settings are set in CACIE_Editable_Model::get_columns()
			)
		);

		/**
		 * WooCommerce columns
		 *
		 */
		if ( function_exists( 'WC' ) ) {

			$wc_data = array(
				'name'                         => array(
					'type'         => 'text',
					'property'     => 'post_title',
					'js'           => array(
						'selector' => 'a.row-title',
					),
					'display_ajax' => false
				),
				'amount'                       => array(
					'type' => 'text'
				),
				'column-wc-minimum_amount'     => array(
					'type' => 'text'
				),
				'order_status'                 => array(
					'type' => 'select'
				),
				'coupon_code'                  => array(
					'type'     => 'text',
					'js'       => array(
						'selector' => '.row-actions'
					),
					'property' => 'post_title'
				),
				'column-wc-free_shipping'      => array(
					'type'    => 'togglable',
					'options' => array( 'no', 'yes' )
				),
				'column-wc-apply_before_tax'   => array(
					'type'    => 'togglable',
					'options' => array( 'no', 'yes' )
				),
				'price'                        => array(
					'type' => 'wc_price'
				),
				'column-wc-weight'             => array(
					'type' => 'float',
					'js'   => array(
						'inputclass' => 'small-text'
					)
				),
				'column-wc-dimensions'         => array(
					'type' => 'dimensions'
				),
				'sku'                          => array(
					'type' => 'text'
				),
				'is_in_stock'                  => array(
					'type' => 'wc_stock'
				),
				'column-wc-stock-status'       => array(
					'type'    => 'togglable',
					'options' => array( 'outofstock', 'instock' )
				),
				'type'                         => array(
					'type'    => 'select',
					'options' => wc_get_coupon_types()
				),
				'thumb'                        => array(
					'type'         => 'media',
					'attachment'   => array(
						'library' => array(
							'type' => 'image'
						)
					),
					'clear_button' => true
				),
				'column-wc-upsells'            => array(
					'type'              => 'select2_dropdown',
					'ajax_populate'     => true,
					'advanced_dropdown' => true,
					'multiple'          => true,
					'formatted_value'   => 'wc_product'
				),
				'column-wc-crosssells'         => array(
					'type'              => 'select2_dropdown',
					'ajax_populate'     => true,
					'advanced_dropdown' => true,
					'multiple'          => true,
					'formatted_value'   => 'wc_product'
				),
				'column-wc-parent'             => array(
					'type'              => 'select2_dropdown',
					'ajax_populate'     => true,
					'advanced_dropdown' => true,
					'clear_button'      => true
				),
				'column-wc-exclude_products'   => array(
					'type'              => 'select2_dropdown',
					'ajax_populate'     => true,
					'advanced_dropdown' => true,
					'multiple'          => true,
					'formatted_value'   => 'wc_product'
				),
				'column-wc-include_products'   => array(
					'type'              => 'select2_dropdown',
					'ajax_populate'     => true,
					'advanced_dropdown' => true,
					'multiple'          => true,
					'formatted_value'   => 'wc_product'
				),
				'column-wc-shipping_class'     => array(
					'type' => 'select'
				),
				'usage'                        => array(
					'type' => 'wc_usage'
				),
				'description'                  => array(
					'type'     => 'textarea',
					'property' => 'post_excerpt'
				),
				'column-wc-reviews_enabled'    => array(
					'type'     => 'togglable',
					'property' => 'comment_status',
					'options'  => array( 'closed', 'open' )
				),
				'column-wc-backorders_allowed' => array(
					'type'    => 'select',
					'options' => array(
						'no'     => __( 'Do not allow', 'woocommerce' ),
						'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
						'yes'    => __( 'Allow', 'woocommerce' )
					)
				),

				// Products
				'product_cat'                  => array(
					'type' => 'select2_tags'
				),
				'product_tag'                  => array(
					'type' => 'select2_tags'
				),
				'column-wc-featured'           => array(
					'type'    => 'togglable',
					'options' => array( 'no', 'yes' )
				),
				'column-wc-visibility'         => array(
					'type' => 'select',
				)
			);

			$data = array_merge( $wc_data, $data );
		}

		// Handle capabilities for editing post status
		$post_type_object = get_post_type_object( $this->storage_model->get_post_type() );

		if ( ! current_user_can( $post_type_object->cap->publish_posts ) ) {
			unset( $data['column-status'] );
		}

		/**
		 * Filter the editability settings for a column
		 *
		 * @since 3.4
		 *
		 * @param array $data {
		 *     Editability settings.
		 *
		 * @type string $type Editability type. Accepts 'text', 'select', 'textarea', etc.
		 * @type array $options Optional. Options for dropdown ([value] => [label]), only used when $type is "select"
		 * }
		 *
		 * @param CACIE_Editable_Model $model Editability storage model
		 */
		$data = apply_filters( 'cac/editable/editables_data', $data, $this );
		$data = apply_filters( 'cac/editable/editables_data/type=' . $this->storage_model->get_type(), $data, $this );
		$data = apply_filters( 'cac/editable/editables_data/post_type=' . $this->storage_model->get_post_type(), $data, $this );

		return $data;
	}

	/**
	 * @see CACIE_Editable_Model::get_items()
	 * @since 1.0
	 */
	public function get_items() {

		global $wp_query;

		$items = array();

		foreach ( (array) $wp_query->posts as $post ) {
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}

			$columndata = array();

			foreach ( $this->storage_model->get_columns() as $column_name => $column ) {

				// Edit enabled for this column?
				if ( ! $this->is_edit_enabled( $column ) ) {
					continue;
				}

				// Set current value
				$value = '';

				// WP Default column
				if ( $column->properties->default ) {

					switch ( $column_name ) {
						case 'author':
							$value = $post->post_author;
							break;
						case 'date':
							$value = date( 'Ymd', strtotime( $post->post_date ) );
							break;
						case 'categories':
							$term_ids = wp_get_post_terms( $post->ID, 'category', array( 'fields' => 'ids' ) );
							if ( $term_ids && ! is_wp_error( $term_ids ) ) {
								$value = $term_ids;
							}
							break;
						case 'tags':
							$term_ids = wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'ids' ) );
							if ( $term_ids && ! is_wp_error( $term_ids ) ) {
								$value = $term_ids;
							}
							break;
						case 'name': // woocommerce default
						case 'title':
							$value = $post->post_title;
							break;
						case 'product_cat':
							$term_ids = wp_get_post_terms( $post->ID, 'product_cat', array( 'fields' => 'ids' ) );
							if ( $term_ids && ! is_wp_error( $term_ids ) ) {
								$value = $term_ids;
							}
							break;
						case 'product_tag':
							$term_ids = wp_get_post_terms( $post->ID, 'product_tag', array( 'fields' => 'ids' ) );
							if ( $term_ids && ! is_wp_error( $term_ids ) ) {
								$value = $term_ids;
							}
							break;
					}
				}

				// Custom column
				else {
					$raw_value = $this->get_column_editability_value( $column, $post->ID );

					if ( $raw_value === null ) {
						continue;
					}

					$value = $raw_value;
				}

				/**
				 * Filter the raw value, used for editability, for a column
				 *
				 * @since 3.4
				 *
				 * @param mixed $value Column value used for editability
				 * @param CPAC_Column $column Colum object
				 * @param int $id Post ID to get the column editability for
				 * @param CACIE_Editable_Model $model Editability storage model
				 */
				$value = apply_filters( 'cac/editable/column_value', $value, $column, $post->ID, $this );
				$value = apply_filters( 'cac/editable/column_value/column=' . $column->get_type(), $value, $column, $post->ID, $this );

				// Get item data from Add-ons, like ACF or WC
				$itemdata = array();

				if ( method_exists( $column, 'get_item_data' ) ) {
					$itemdata = $column->get_item_data( $post->ID );
				}

				// Add data
				$columndata[ $column_name ] = array(
					'revisions'        => array( $value ),
					'current_revision' => 0,
					'itemdata'         => $itemdata,
					'editable'         => array(
						'formattedvalue' => $this->get_formatted_value( $column, $value )
					)
				);
			}

			// Add post to items list
			$items[ $post->ID ] = array(
				'ID'         => $post->ID,
				'columndata' => $columndata
			);
		}

		return $items;
	}

	/**
	 * Get editability value for a column
	 *
	 * @since 3.4
	 *
	 * @param CPAC_Column $column Column
	 * @param integer $id Item ID
	 *
	 * @return mixed Raw value
	 */
	public function get_column_editability_value( $column, $id ) {

		$raw_value = null;

		if ( ! $column->properties->default ) {
			$raw_value = $column->get_raw_value( $id );
		}

		switch ( $column->properties->type ) {
			case 'column-wc-stock-status':
				$product = wc_get_product( $id );

				if ( $product->is_type( 'variable', 'grouped', 'external' ) ) {
					$raw_value = null;
				}
				break;
			case 'order_status':
				if ( substr( $raw_value, 0, 3 ) != 'wc-' ) {
					$raw_value = 'wc-' . $raw_value;
				}
				break;
			case '':
				$raw_value = date( 'Ymd', strtotime( $column->get_raw_value( $id ) ) );
				break;
		}

		return $raw_value;
	}

	/**
	 * Display terms
	 * Largerly taken from class-wp-post-list-table.php
	 *
	 * @since 1.0
	 *
	 * @param integer $id
	 * @param string $taxonomy
	 */
	private function display_terms( $id, $taxonomy ) {

		if ( $terms = get_the_terms( $id, $taxonomy ) ) {
			$out = array();
			foreach ( $terms as $t ) {
				$posts_in_term_qv = array(
					'post_type' => 'post',
					'taxonomy'  => $taxonomy,
					'term'      => $t->slug
				);

				$out[] = sprintf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( $posts_in_term_qv, 'edit.php' ) ),
					esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) )
				);
			}

			echo join( __( ', ' ), $out );
		}
	}

	/**
	 * @see CACIE_Editable_Model::manage_value()
	 * @since 1.0
	 */
	public function manage_value( $column, $id ) {

		global $post;

		$editable = $this->get_editable( $column->get_type() );

		if ( ! empty( $editable['default_column'] ) ) {
			echo $this->storage_model->get_original_column_value( $column->get_type(), $id );
		}
		else {
			$post = get_post( $id );
			setup_postdata( $post );

			switch ( $column->properties->type ) {
				case 'author':
					printf(
						'<a href="%s">%s</a>',
						esc_url( add_query_arg( array(
							'post_type' => $post->post_type,
							'author'    => get_the_author_meta( 'ID' )
						), 'edit.php' ) ),
						get_the_author()
					);
					break;
				case 'categories':
					$this->display_terms( $id, 'category' );
					break;
				case 'date':
				case 'column-date_published':
					// variables
					global $post;
					$post = get_post( $id );
					$column_name = 'date';
					$mode = '';


					// source: class-wp-posts-list-table.php - line 622
					// START
					if ( '0000-00-00 00:00:00' == $post->post_date ) {
						$t_time = $h_time = __( 'Unpublished' );
						$time_diff = 0;
					}
					else {
						$t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
						$m_time = $post->post_date;
						$time = get_post_time( 'G', true, $post );

						$time_diff = time() - $time;

						if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
							$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
						}
						else {
							$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
						}
					}

					//echo '<td ' . $attributes . '>';
					if ( 'excerpt' == $mode ) {
						echo apply_filters( 'post_date_column_time', $t_time, $post, $column_name, $mode );
					}
					else {
						echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, $mode ) . '</abbr>';
					}
					echo '<br />';
					if ( 'publish' == $post->post_status ) {
						_e( 'Published' );
					}
					elseif ( 'future' == $post->post_status ) {
						if ( $time_diff > 0 ) {
							echo '<strong class="attention">' . __( 'Missed schedule' ) . '</strong>';
						}
						else {
							_e( 'Scheduled' );
						}
					}
					else {
						_e( 'Last Modified' );
					}
					//echo '</td>';
					// END

					break;
				case 'tags':
					$this->display_terms( $id, 'post_tag' );
					break;
				case 'title':
					// @todo; currently is be set in DOM only by xeditable
					// best option would be to give all of them a return value
					// example: when using a post-status column you want to refresh
					// the title column aswell as it contains the post status aswell.
					// this can only be done if title has it's own manage_value.
					break;
				case 'product_cat':
					$this->display_terms( $id, 'product_cat' );
					break;
				case 'product_tag':
					$this->display_terms( $id, 'product_tag' );
					break;
			}

			wp_reset_postdata();
		}
	}

	/**
	 * @see CACIE_Editable_Model::column_save()
	 * @since 1.0
	 */
	public function column_save( $id, $column, $value ) {

		global $wpdb;

		if ( ! ( $post = get_post( $id ) ) ) {
			exit;
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			exit;
		}

		// Third party columns can use the save() method as a callback for inline-editing
		// If a column features a saving method itself, the "return" statement makes sure default behaviour is prevented
		if ( method_exists( $column, 'save' ) ) {
			$result = $column->save( $id, $value );

			// Return a possible WP_Error yielded by the column save method
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return;
		}

		// Get editability data for the column to be saved
		$editable = $this->get_editable( $column->get_name() );

		switch ( $column->get_type() ) {

			// Default
			case 'categories':
				$this->set_post_terms( $id, $value, 'category' );
				break;
			case 'date':
			case 'column-date_published':
				// preserve the original time
				$time = strtotime( "1970-01-01 " . date( 'H:i:s', strtotime( $post->post_date ) ) );

				wp_update_post( array(
					'ID'        => $post->ID,
					'edit_date' => 1, // needed for GMT date
					'post_date' => date( 'Y-m-d H:i:s', strtotime( $value ) + $time )
				) );
				break;
			case 'tags':
				$this->set_post_terms( $id, $value, 'post_tag' );
				break;

			// Custom columns
			case 'column-attachment':
				// detach
				if ( $attachment_ids = get_posts( array( 'post_type' => 'attachment', 'post_parent' => $post->ID, 'posts_per_page' => - 1, 'fields' => 'ids' ) ) ) {
					foreach ( $attachment_ids as $attachment_id ) {
						wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => '' ) );
					}
				}
				// attach
				if ( ! empty( $value ) ) {
					foreach ( $value as $attachment_id ) {
						wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $post->ID ) );
					}
				}
				break;
			case 'column-featured_image':
			case 'thumb': // woocommerce
				if ( $value ) {
					set_post_thumbnail( $post->ID, $value );
				}
				else {
					delete_post_thumbnail( $post );
				}
				break;
			case 'column-meta':
				$this->update_meta( $post->ID, $column->get_field_key(), $value );
				break;
			case 'column-page_template':
				update_post_meta( $post->ID, '_wp_page_template', $value );
				break;
			case 'column-post_formats':
				set_post_format( $post->ID, $value );
				break;
			case 'column-sticky':
				if ( 'yes' == $value ) {
					stick_post( $post->ID );
				}
				else {
					unstick_post( $post->ID );
				}
				break;
			case 'column-taxonomy':
				$taxonomy = $column->get_option( 'taxonomy' );
				if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
					if ( 'post_format' == $taxonomy && ! empty( $value ) ) {
						$value = $value[0];
					}

					$this->set_post_terms( $id, $value, $taxonomy );
				}
				break;

			/**
			 * WooCommerce Columns
			 *
			 */
			case 'price':
				if ( is_array( $value ) && isset( $value['regular_price'] ) && isset( $value['sale_price'] ) && isset( $value['sale_price_dates_from'] ) && isset( $value['sale_price_dates_to'] ) ) {
					CACIE_WooCommerce::update_product_pricing( $post->ID, array(
						'regular_price'         => $value['regular_price'],
						'sale_price'            => $value['sale_price'],
						'sale_price_dates_from' => $value['sale_price_dates_from'],
						'sale_price_dates_to'   => $value['sale_price_dates_to'],
					) );
				}
				break;
			case 'column-wc-weight':
				$product = wc_get_product( $post->ID );

				if ( ! $product->is_virtual() ) {
					update_post_meta( $post->ID, '_weight', ( $value === '' ) ? '' : wc_format_decimal( $value ) );
				}
				break;
			case 'column-wc-dimensions':
				if ( is_array( $value ) && isset( $value['length'] ) && isset( $value['width'] ) && isset( $value['height'] ) ) {
					$product = wc_get_product( $post->ID );

					if ( ! $product->is_virtual() ) {
						update_post_meta( $post->ID, '_length', ( $value === '' ) ? '' : wc_format_decimal( $value['length'] ) );
						update_post_meta( $post->ID, '_width', ( $value === '' ) ? '' : wc_format_decimal( $value['width'] ) );
						update_post_meta( $post->ID, '_height', ( $value === '' ) ? '' : wc_format_decimal( $value['height'] ) );
					}
				}
				break;
			case 'sku':
				$current_sku = get_post_meta( $post->ID, '_sku', true );
				$new_sku = wc_clean( $value );

				if ( empty( $new_sku ) ) {
					$new_sku = '';
				}

				if ( $new_sku != $current_sku ) {
					$existing_id = $wpdb->get_var( $wpdb->prepare( "
						SELECT $wpdb->posts.ID
					    FROM $wpdb->posts
					    LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id)
					    WHERE $wpdb->posts.post_type = 'product'
					    AND $wpdb->posts.post_status = 'publish'
					    AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = %s
					", $new_sku ) );

					if ( $existing_id ) {
						return new WP_Error( 'cacie_error_sku_exists', __( 'The SKU must be unique.', 'codepress-admin-columns' ) );
					}

					update_post_meta( $post->ID, '_sku', $new_sku );
				}

				break;
			case 'is_in_stock':
				if ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) {
					if ( $value['manage_stock'] == 'yes' ) {
						update_post_meta( $post->ID, '_manage_stock', 'yes' );

						wc_update_product_stock_status( $post->ID, wc_clean( $value['stock_status'] ) );
						wc_update_product_stock( $post->ID, intval( $value['stock'] ) );

					}
					else {
						// Don't manage stock
						update_post_meta( $post->ID, '_manage_stock', 'no' );
						update_post_meta( $post->ID, '_stock', '' );

						wc_update_product_stock_status( $post->ID, wc_clean( $value['stock_status'] ) );
					}
				}
				else {
					wc_update_product_stock_status( $post->ID, wc_clean( $value['stock_status'] ) );
				}

				break;
			case 'column-wc-stock-status':
				wc_update_product_stock_status( $post->ID, wc_clean( $value ) );

				break;
			case 'column-wc-free_shipping':
				update_post_meta( $id, 'free_shipping', ( $value == 'yes' ? 'yes' : 'no' ) );

				break;
			case 'column-wc-shipping_class':
				$this->set_post_terms( $id, $value, 'product_shipping_class' );

				break;
			case 'column-wc-apply_before_tax':
				update_post_meta( $id, 'apply_before_tax', ( $value == 'yes' ? 'yes' : 'no' ) );

				break;
			case 'column-wc-backorders_allowed':
				if ( in_array( $value, array( 'no', 'yes', 'notify' ) ) ) {
					update_post_meta( $post->ID, '_backorders', $value );
				}
				break;
			case 'column-wc-upsells':
				$upsell_ids = array();

				if ( is_array( $value ) ) {
					foreach ( $value as $upsell_id ) {
						if ( $upsell_id && $upsell_id > 0 ) {
							$upsell_ids[] = $upsell_id;
						}
					}
				}

				update_post_meta( $id, '_upsell_ids', $upsell_ids );
				break;
			case 'column-wc-crosssells':
				$crosssell_ids = array();

				if ( is_array( $value ) ) {
					foreach ( $value as $crosssell_id ) {
						if ( $crosssell_id && $crosssell_id > 0 ) {
							$crosssell_ids[] = $crosssell_id;
						}
					}
				}

				update_post_meta( $id, '_crosssell_ids', $crosssell_ids );
				break;
			case 'column-wc-exclude_products':
				$product_ids = array();

				if ( is_array( $value ) ) {
					foreach ( $value as $product_id ) {
						if ( $product_id && $product_id > 0 ) {
							$product_ids[] = $product_id;
						}
					}
				}

				update_post_meta( $id, 'exclude_product_ids', implode( ',', $product_ids ) );
				break;
			case 'column-wc-include_products':
				$product_ids = array();

				if ( is_array( $value ) ) {
					foreach ( $value as $product_id ) {
						if ( $product_id && $product_id > 0 ) {
							$product_ids[] = $product_id;
						}
					}
				}

				update_post_meta( $id, 'product_ids', implode( ',', $product_ids ) );
				break;
			case 'column-wc-parent':
				wp_update_post( array( 'ID' => $id, 'post_parent' => $value ) );
				break;
			case 'column-wc-minimum_amount':
				update_post_meta( $id, 'minimum_amount', wc_format_decimal( $value ) );
				break;
			case 'order_status':
				$order = new WC_Order( $id );
				$order->update_status( $value );
				break;
			case 'usage':
				update_post_meta( $id, 'usage_limit', wc_clean( $value['usage_limit'] ) );
				update_post_meta( $id, 'usage_limit_per_user', wc_clean( $value['usage_limit_per_user'] ) );
				break;
			case 'amount':
				update_post_meta( $id, 'coupon_amount', wc_format_decimal( $value ) );
				break;
			case 'type':
				update_post_meta( $id, 'discount_type', wc_clean( $value ) );
				break;
			case 'product_cat':
				$this->set_post_terms( $id, $value, 'product_cat' );
				break;
			case 'product_tag':
				$this->set_post_terms( $id, $value, 'product_tag' );
				break;
			case 'column-wc-featured':
				update_post_meta( $id, '_featured', $value );
				break;
			case 'column-wc-visibility':
				update_post_meta( $id, '_visibility', $value );
				break;

			// Save basic property such as title or description (data that is available in WP_Post)
			default:
				if ( ! empty( $editable['property'] ) ) {
					$property = $editable['property'];

					if ( isset( $post->{$property} ) ) {
						wp_update_post( array(
							'ID'      => $post->ID,
							$property => $value
						) );
					}
				}
				else {
					$result = null;

					/**
					 * Called when a column is saved, but the saving is not handled by Admin Columns core
					 * This should be used for saving columns that are editable but do not have their own CPAC_Column class
					 * The first parameter, $result, should only be used if an error occurs
					 *
					 * @since 3.4
					 *
					 * @param WP_Error $result Result of saving
					 * @param CPAC_Column $column Column object
					 * @param int $id ID of item to be saved
					 * @param mixed $value Value to be saved
					 * @param CACIE_Editable_Model $model Editability storage model
					 */
					$result = apply_filters( 'cac/editable/column_save', $result, $column, $id, $value, $this );
					$result = apply_filters( 'cac/editable/column_save/column=' . $column->get_type(), $result, $column, $id, $value, $this );

					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}
		}
	}
}