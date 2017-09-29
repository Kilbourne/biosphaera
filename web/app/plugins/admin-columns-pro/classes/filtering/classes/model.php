<?php

/**
 * Addon class
 *
 * @since 1.0
 */
abstract class CAC_Filtering_Model {

	protected $storage_model;

	protected $filter_values;

	protected $wpdb;

	protected $has_dropdown = false;

	abstract function init_hooks();

	abstract public function get_dropdown_options_by_column( $column );

	abstract public function get_filterables();

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct( $storage_model ) {

		global $wpdb;
		$this->wpdb = $wpdb;

		$this->storage_model = $storage_model;
	}

	public function get_default_filterables() {
	}

	public function get_dropdown_html_element_ids() {
	}

	/**
	 * Get values by meta key
	 *
	 * @since 3.5
	 */
	public function get_values_by_meta_key( $meta_key, $operator = '' ) {
	}

	/**
	 * @since 3.5
	 */
	protected function get_user_ids() {
		return $this->wpdb->get_col( "SELECT ID FROM {$this->wpdb->users}" );
	}

	/**
	 * @since 3.5
	 */
	protected function get_comment_ids() {
		return $this->wpdb->get_col( "SELECT comment_ID FROM {$this->wpdb->comments}" );
	}

	/**
	 * @since 3.5
	 */
	protected function set_filter_value( $key, $value ) {
		$this->filter_values[ $key ] = $value;
	}

	/**
	 * @since 3.6
	 */
	public function get_filter_value( $key ) {
		return isset( $this->filter_values[ $key ] ) ? $this->filter_values[ $key ] : false;
	}

	/**
	 * Enable filtering
	 *
	 * @since 3.8
	 */
	public function enable_filtering( $columns ) {

		$filterables = $this->get_filterables();
		$default_filterables = (array) $this->get_default_filterables();

		foreach ( $columns as $column ) {

			$is_filterable = false;

			if ( in_array( $column->get_type(), $filterables ) ) {
				$is_filterable = true;
			}

			if ( in_array( $column->get_type(), $default_filterables ) ) {
				$is_filterable = true;
			}

			// Custom Field
			if ( 'column-meta' === $column->get_type() ) {
				if ( in_array( $column->get_option( 'field_type' ), array( '', 'checkmark', 'color', 'date', 'excerpt', 'image', 'library_id', 'numeric', 'title_by_id', 'user_by_id' ) ) ) {
					$is_filterable = true;
				}
				if ( in_array( $column->get_option( 'field_type' ), array( 'numeric' ) ) ) {
					$column->set_properties( 'filterable_type', 'numeric' );
				}
			}

			// ACF
			if ( 'column-acf_field' === $column->get_type() && method_exists( $column, 'get_field' ) && class_exists( 'CPAC_Addon_ACF', false ) ) {

				$field = $column->get_field();

				switch ( $field['type'] ) {

					case 'post_object' :
					case 'select' :
					case 'user' :
						// only allow single values
						if ( 0 === $field['multiple'] ) {
							$is_filterable = true;
						}
						break;
					case 'taxonomy' :
						// only allow single values
						if ( in_array( $field['field_type'], array( 'radio', 'select' ) ) ) {
							$is_filterable = true;
						}
						break;
					case 'email' :
					case 'password' :
					case 'oembed' :
					case 'text' :
					case 'image' :
					case 'file' :
					case 'url' :
					case 'radio' :
					case 'true_false' :
					case 'page_link' :
					case 'color_picker' :
						$is_filterable = true;
						break;
					case 'number':
						$is_filterable = true;
						$column->set_properties( 'filterable_type', 'numeric' );
						break;
					case 'date_picker' :
						$is_filterable = true;
						$column->set_properties( 'filterable_type', 'date' );
						break;
					// not supported
					// these fields are stored serialised
					// checkbox, textarea, wysiwyg, gallery, relationship, google_map
				}
			}

			// WooCommerce
			if ( class_exists( 'CPAC_Addon_WC', false ) ) {

				switch ( $column->get_type() ) {

					// Product
					case 'product_cat' :
					case 'product_tag' :
					case 'column-wc-featured' :
					case 'column-wc-visibility' :
					case 'column-wc-shipping_class' :
					case 'column-wc-parent' :
					case 'column-wc-reviews_enabled' :
					case 'column-wc-tax_class' :
					case 'column-wc-tax_status' :
						$is_filterable = true;
						break;

					// Shop order
					case 'order_status' :
					case 'customer_message' :
					case 'column-wc-payment_method' :
					case 'column-wc-order_coupons_used' :
					case 'column-wc-order_shipping_method' :
						$is_filterable = true;
						break;
					case 'column-wc-product' :
						if ( method_exists( $column, 'get_product_property' ) && ! in_array( $column->get_product_property(), array( 'title', 'sku' ) ) ) {
							$is_filterable = true;
						}
						break;

					// Coupon
					case 'column-wc-free_shipping' :
						$is_filterable = true;
						break;
				}
			}

			if ( $is_filterable ) {
				$column->set_properties( 'is_filterable', true );
			}
		}
	}

	/**
	 * Get taxonomy filter vars
	 *
	 * @since 3.4.3
	 *
	 * @param string $value Column value
	 * @param string $taxonomy Taxonomy name
	 *
	 * @return array WP_Query Tax Query vars
	 */
	protected function get_taxonomy_query( $value, $taxonomy ) {
		if ( 'cpac_empty' == $value ) {
			$tax_query = array(
				'taxonomy' => $taxonomy,
				'terms'    => false,
				'operator' => 'NOT EXISTS'
			);
		}
		else if ( 'cpac_not_empty' === $value ) {
			$tax_query = array(
				'taxonomy' => $taxonomy,
				'terms'    => false,
				'operator' => 'EXISTS'
			);
		}
		else {
			$tax_query = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $value
			);
		}

		return $tax_query;
	}

	/**
	 * @since 3.7.3
	 */
	protected function get_meta_query( $key, $value, $type = '' ) {
		if ( 'cpac_empty' === $value ) {
			$meta_query = array(
				'relation' => 'OR',
				array(
					'key'     => $key,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => $key,
					'value' => '',
				)
			);
		}
		else if ( 'cpac_not_empty' === $value ) {
			$meta_query = array(
				'key'     => $key,
				'value'   => '',
				'compare' => '!=',
			);
		}
		else {
			$meta_query = array(
				'key'   => $key,
				'value' => $value,
				'type'  => in_array( $type, array( 'numeric' ) ) ? 'NUMERIC' : 'CHAR'
			);
		}

		return $meta_query;
	}

	/**
	 * @since 3.7
	 */
	protected function get_meta_acf_query( $key, $value, $field_type, $field_format = '' ) {
		$meta_query = array();

		if ( in_array( $value, array( 'cpac_empty', 'cpac_not_empty' ) ) ) {
			return $this->get_meta_query( $key, $value );
		}

		switch ( $field_type ) {

			// Date
			case ( 'date_picker' ) :

				switch ( $field_format ) {
					case 'monthly':
						$meta_query = array(
							'relation' => 'AND',
							array(
								'key'     => $key,
								'value'   => date( 'Ymd', strtotime( $value . '01' ) ),
								'compare' => '>=',
								'type'    => 'NUMERIC'
							),
							array(
								'key'     => $key,
								'value'   => date( 'Ymd', strtotime( "+1 month", strtotime( $value . '01' ) ) ),
								'compare' => '<',
								'type'    => 'NUMERIC'
							)
						);
						break;
					case 'yearly':
						$meta_query = array(
							'relation' => 'AND',
							array(
								'key'     => $key,
								'value'   => date( 'Ymd', strtotime( $value . '0101' ) ),
								'compare' => '>=',
								'type'    => 'NUMERIC'
							),
							array(
								'key'     => $key,
								'value'   => date( 'Ymd', strtotime( "+1 year", strtotime( $value . '0101' ) ) ),
								'compare' => '<',
								'type'    => 'NUMERIC'
							)
						);
						break;
					case 'future_past':
						if ( 'future' == $value ) {
							$meta_query = array(
								'key'     => $key,
								'value'   => date( 'Ymd' ),
								'compare' => '>=',
								'type'    => 'NUMERIC'
							);
						}
						else if ( 'past' == $value ) {
							$meta_query = array(
								'relation' => 'AND',
								array(
									'key'     => $key,
									'value'   => date( 'Ymd' ),
									'compare' => '<=',
									'type'    => 'NUMERIC'
								),
								array(
									'key'     => $key,
									'value'   => '',
									'compare' => '!=',
								),
							);
						}
						break;

					// Daily; Exact match
					default:
						$meta_query = array(
							'key'   => $key,
							'value' => $value,
						);
						break;
				}
				break; // case: date_picker

			// Number
			case 'number' :
				$meta_query = $this->get_meta_query( $key, $value, 'numeric' );
				break;

			// Meta query
			default :
				$meta_query = $this->get_meta_query( $key, $value );
		}

		return $meta_query;
	}

	/**
	 * @since 3.7
	 */
	public function get_meta_query_range( $min_values, $max_values ) {

		$meta_queries = array();

		foreach ( $min_values as $name => $min ) {
			if ( $column = $this->storage_model->get_column_by_name( $name ) ) {

				$max = $max_values[ $name ];

				$meta_query = array();

				switch ( $column->properties->type ) {
					case 'column-acf_field' :
					case 'column-meta' :
						$key = $column->get_field_key();
						switch ( $column->get_property( 'filterable_type' ) ) {

							case 'numeric' :
								if ( $min ) {
									$meta_query[] = array(
										'key'     => $key,
										'value'   => $min,
										'compare' => '>=',
										'type'    => 'NUMERIC'
									);
								}
								if ( $max ) {
									$meta_query[] = array(
										'key'     => $key,
										'value'   => $max,
										'compare' => '<=',
										'type'    => 'NUMERIC'
									);
								}
								break;

							case 'date' :
								if ( $min ) {
									$meta_query[] = array(
										'key'     => $key,
										'value'   => date( 'Ymd', strtotime( $min ) ),
										'compare' => '>=',
										'type'    => 'NUMERIC'
									);
								}
								if ( $max ) {
									$meta_query[] = array(
										'key'     => $key,
										'value'   => date( 'Ymd', strtotime( $max ) ),
										'compare' => '<=',
										'type'    => 'NUMERIC'
									);
								}
								break;

							case 'date_format' :

								/**
								 * Filter the date format of a column
								 *
								 * @since 3.7.2
								 *
								 * @param string The darte format as it is stored in the DB. Default is WordPress format.
								 * @param CPAC_Column $column Column object
								 */

								$date_format = apply_filters( 'cac/filtering/date_range_format', 'Y-m-d', $column );

								if ( $min ) {
									$meta_query[] = array(
										'key'     => $key,
										'value'   => date( $date_format, strtotime( $min ) ),
										'compare' => '>=',
										'type'    => 'DATE'
									);
								}
								if ( $max ) {
									$meta_query[] = array(
										'key'     => $key,
										'value'   => date( $date_format, strtotime( $max ) ),
										'compare' => '<=',
										'type'    => 'DATE'
									);
								}
								break;
						}
				}

				// prevents notices from WP_Meta_Query
				if ( count( $meta_query ) > 1 ) {
					$meta_query['relation'] = 'AND';
				}

				if ( $meta_query ) {
					$meta_queries[] = $meta_query;
				}
			}
		}

		return $meta_queries;
	}

	/**
	 * Indents any object as long as it has a unique id and that of its parent.
	 *
	 * @since 1.0
	 *
	 * @param type $array
	 * @param type $parentId
	 * @param type $parentKey
	 * @param type $selfKey
	 * @param type $childrenKey
	 *
	 * @return array Indented Array
	 */
	protected function indent( $array, $parentId = 0, $parentKey = 'post_parent', $selfKey = 'ID', $childrenKey = 'children' ) {
		$indent = array();

		$i = 0;
		foreach ( $array as $v ) {
			if ( $v->$parentKey == $parentId ) {
				$indent[ $i ] = $v;
				$indent[ $i ]->$childrenKey = $this->indent( $array, $v->$selfKey, $parentKey, $selfKey );

				$i++;
			}
		}

		return $indent;
	}

	/**
	 * @since 3.5
	 */
	protected function get_meta_options( $column ) {
		$options = array();
		$empty_option = true;

		if ( $results = $this->get_values_by_meta_key( $column->get_field_key() ) ) {

			foreach ( $results as $data ) {

				// serialized data can not be filtered using WP_Query or in an efficient way, no point of displaying it.
				if ( is_serialized( $data->value ) ) {
					continue;
				}

				// these strings are way too big... That's what she said!
				if ( strlen( $data->value ) > 800 ) {
					continue;
				}

				$label = '';

				switch ( $column->get_option( 'field_type' ) ) :
					case "date" :
						if ( $date = $column->get_date_by_string( $data->value ) ) {
							$label = $date;
						}
						break;
					case "user_by_id" :
						if ( $username = $column->get_username_by_id( $data->value ) ) {
							$label = $username;
						}
						break;
					case "title_by_id" :
						if ( $title = $column->get_post_title( $data->value ) ) {
							$label = $title;
						}
						break;
					default:
						$label = $data->value;
						break;
				endswitch;

				if ( $label = trim( strip_tags( $label ) ) ) {
					$options[ $data->value ] = $label;
				}
			}
		}

		return array(
			'empty_option' => $empty_option,
			'options'      => $options,
		);
	}

	/**
	 * @since 3.5
	 */
	protected function get_acf_options( $column ) {
		if ( ! method_exists( $column, 'get_field_key' ) ) {
			return false;
		}

		$meta_key = $column->get_field_key();

		if ( ! $meta_key ) {
			return false;
		}

		$field_type = $column->get_field_type();

		if ( 'repeater' == $field_type ) {
			return false;
		}

		$options = array();
		$order = true;
		$empty_option = true;

		// Get options for filterable type date
		if ( 'date' == $column->get_property( 'filterable_type' ) ) {
			$order = false;
			$options = (array) $this->get_date_values_by_filter_type( $column->get_option( 'filter_format' ), $meta_key );
		}

		// Get options for default meta data
		else {

			$values = $this->get_values_by_meta_key( $meta_key );
			if ( ! $values ) {
				return false;
			}

			$field = $column->get_field();

			foreach ( $values as $data ) {

				if ( is_serialized( $data->value ) ) {
					continue;
				}

				$field_value = $data->value;

				switch ( $field_type ) :

					case "select" :
					case "checkbox" :
					case "radio" :
						$field_value = ( isset( $field['choices'] ) && isset( $field['choices'][ $data->value ] ) ) ? $field['choices'][ $data->value ] : false;
						break;
					case "true_false" :
						$empty_option = false;
						if ( 0 == $data->value ) {
							$field_value = __( 'False', 'codepress-admin-columns' );
						}
						if ( 1 == $data->value ) {
							$field_value = __( 'True', 'codepress-admin-columns' );
						}
						break;
					case "page_link" :
					case "post_object" :
						$field_value = $column->get_post_title( $data->value );
						if ( in_array( $field_value, $options ) ) {
							$field_value .= ' (' . $column->get_raw_post_field( 'post_name', $data->value ) . ')';
						}
						break;
					case "taxonomy" :
						$term = get_term( $data->value, $field['taxonomy'] );
						if ( $term && ! is_wp_error( $term ) ) {
							$field_value = $term->name;
							if ( in_array( $field_value, $options ) ) {
								$field_value .= ' (' . $term->slug . ')';
							}
						}
						break;
					case "user" :
						if ( $user = get_userdata( $data->value ) ) {
							$field_value = $column->get_display_name( $data->value );

							// duplicate names will get email added
							if ( $user->user_email && in_array( $field_value, $options ) ) {
								$field_value .= ' (' . $user->user_email . ')';
							}
						}
						break;

				endswitch;

				if ( $field_value ) {
					$options[ $data->value ] = $field_value;
				}
			}
		}

		return array(
			'order'        => $order,
			'empty_option' => $empty_option,
			'options'      => $options,
		);
	}

	/**
	 * @since 3.6
	 */
	protected function get_date_values_by_filter_type( $type, $meta_key ) {
		$options = array();
		$operator = false;

		switch ( $type ) {
			case 'yearly':
				$operator = "YEAR( meta_value ) AS year";
				break;
			case 'monthly':
				$operator = "YEAR( meta_value ) AS year, MONTH( meta_value ) AS month";
				break;
			case 'future_past':
				$options = array(
					'future' => __( 'Future dates', 'codepress-admin-columns' ),
					'past'   => __( 'Past dates', 'codepress-admin-columns' )
				);
				break;

			// daily
			default:
				$operator = "YEAR( meta_value ) AS year, MONTH( meta_value ) AS month, DAY( meta_value ) AS day";
		}

		if ( $operator ) {
			if ( $values = $this->get_values_by_meta_key( $meta_key, $operator ) ) {
				global $wp_locale;
				foreach ( $values as $value ) {
					$day = ! empty( $value->day ) ? $value->day : '';
					$day_zeroise = ! empty( $value->day ) ? zeroise( $value->day, 2 ) : '';
					$month_zeroise = ! empty( $value->month ) ? zeroise( $value->month, 2 ) : '';
					$month_label = ! empty( $value->month ) ? $wp_locale->get_month( $value->month ) : '';
					$k = $value->year . $month_zeroise . $day_zeroise;

					$options[ $k ] = $day . ' ' . $month_label . ' ' . $value->year;
				}

				krsort( $options, SORT_NUMERIC );
			}
		}

		return $options;
	}

	/**
	 * Applies indenting markup for taxonomy dropdown
	 *
	 * @since 1.0
	 *
	 * @param array $array
	 * @param int $level
	 * @param array $ouput
	 *
	 * @return array Output
	 */
	protected function apply_indenting_markup( $array, $level = 0, $output = array() ) {

		$processed = array();

		foreach ( $array as $v ) {

			$prefix = '';
			for ( $i = 0; $i < $level; $i++ ) {
				$prefix .= '&nbsp;&nbsp;';
			}

			// rename duplicates
			$label = $v->name;
			if ( in_array( $v->name, $processed ) ) {
				$label = $v->name . ' (' . $v->slug . ')';
			}

			$output[ $v->slug ] = $prefix . $label;

			$processed[] = $v->name;

			if ( ! empty( $v->children ) ) {
				$output = $this->apply_indenting_markup( $v->children, ( $level + 1 ), $output );
			}
		}

		return $output;
	}

	/**
	 * Daterange markup
	 * @since 3.7
	 */
	private function display_date_range( $column_name, $label, $min = '', $max = '' ) {
		$min_id = 'cpac_filter-min-' . $column_name;
		$max_id = 'cpac_filter-max-' . $column_name;
		?>
		<div class="cpac_range date<?php echo ( $min || $max ) ? ' active' : ''; ?>">
			<div class="input_group">
				<label class="prepend" for="<?php echo $min_id ?>"><?php echo $label; ?></label>
				<input class="min<?php echo $min ? ' active' : ''; ?>" type="text" placeholder="<?php echo __( 'Start date', 'codepress-admin-columns' ); ?>" name="cpac_filter-min[<?php echo $column_name; ?>]" value="<?php echo esc_attr( $min ); ?>" id="<?php echo $min_id ?>">
				<label class="append" for="<?php echo $max_id ?>"><?php _e( 'until', 'codepress-admin-columns' ); ?></label>
				<input class="max<?php echo $max ? ' active' : ''; ?>" type="text" placeholder="<?php echo __( 'End date', 'codepress-admin-columns' ); ?>" name="cpac_filter-max[<?php echo $column_name; ?>]" value="<?php echo esc_attr( $max ); ?>" id="<?php echo $max_id ?>">
			</div>
		</div>
		<?php
	}

	/**
	 * Number markup
	 * @since 3.7
	 */
	private function display_number_range( $column_name, $label, $min = '', $max = '' ) {
		$min_id = 'cpac_filter-min-' . $column_name;
		$max_id = 'cpac_filter-max-' . $column_name; ?>

		<div class="cpac_range number<?php echo ( $min || $max ) ? ' active' : ''; ?>">
			<div class="input_group">
				<label class="prepend" for="<?php echo $min_id ?>"><?php echo $label; ?></label>
				<input class="min<?php echo $min ? ' active' : ''; ?>" type="number" placeholder="<?php _e( 'Min', 'codepress-admin-columns' ); ?>" name="cpac_filter-min[<?php echo $column_name; ?>]" value="<?php echo esc_attr( $min ); ?>" id="<?php echo $min_id ?>">
				<label class="append" for="<?php echo $max_id ?>"><?php _e( 'to', 'codepress-admin-columns' ); ?></label>
				<input class="max<?php echo $max ? ' active' : ''; ?>" type="number" placeholder="<?php _e( 'Max', 'codepress-admin-columns' ); ?>" name="cpac_filter-max[<?php echo $column_name; ?>]" value="<?php echo esc_attr( $max ); ?>" id="<?php echo $max_id ?>">
			</div>
		</div>
		<?php
	}

	/**
	 * Dropdown markup
	 * @since 3.6
	 */
	private function display_dropdown( $column_name, $options, $add_empty_option, $current_item = '', $top_label = '', $label ) { ?>
		<label for="cpac_filter_<?php echo $column_name; ?>" class="screen-reader-text"><?php echo sprintf( __( 'Filter by %s', 'codepress-admin-columns' ), $label ); ?></label>
		<select class="postform cpac_filter<?php echo ( '' !== $current_item ) ? ' active' : ''; ?>" name="cpac_filter[<?php echo $column_name; ?>]" id="cpac_filter_<?php echo $column_name; ?>" data-current="<?php echo esc_attr( urlencode( $current_item ) ); ?>">
			<?php if ( $top_label ) : ?>
				<option value="">
					<?php echo $top_label; ?>
				</option>
			<?php endif; ?>
			<?php foreach ( $options as $value => $label ) : ?>
				<?php $label = strlen( $label ) > 60 ? substr( $label, 0, 58 ) . '..' : $label; ?>
				<option value="<?php echo esc_attr( urlencode( $value ) ); ?>" <?php selected( $value, $current_item ); ?>><?php echo $label; ?></option>
			<?php endforeach; ?>
			<?php if ( $add_empty_option ) : ?>
				<option disabled>──────────</option>
				<option value="cpac_empty" <?php selected( 'cpac_empty', $current_item ); ?>><?php _e( 'Empty', 'codepress-admin-columns' ); ?></option>
				<option value="cpac_not_empty" <?php selected( 'cpac_not_empty', $current_item ); ?>><?php _e( 'Not empty', 'codepress-admin-columns' ); ?></option>
			<?php endif; ?>
		</select>
		<?php
	}

	/**
	 * Create dropdown
	 *
	 * @since 1.0
	 *
	 * @param object $column Column Object
	 * @param array $options Array with options
	 * @param bool $add_empty_option Add two options for filtering on 'EMPTY' and 'NON EMPTY' values
	 * @param string $current_item Current item
	 *
	 * @return string Dropdown HTML select element
	 */
	public function dropdown( $column_name, $options, $add_empty_option = false, $current_item = '' ) {

		$column = $this->storage_model->get_column_by_name( $column_name );

		/**
		 * Filter all dropdown options
		 *
		 * @since 3.0.8.5
		 *
		 * @param array $options All the filtering options: value => label
		 * @param CPAC_Column $column_instance Column class instance
		 */
		$options = apply_filters( 'cac/addon/filtering/options', $options, $column );

		/**
		 * Filter empty option
		 *
		 * @param bool True / False
		 * @param CPAC_Column $column_instance Column class instance
		 */
		$add_empty_option = apply_filters( 'cac/addon/filtering/dropdown_empty_option', $add_empty_option, $column );

		if ( empty( $options ) && ! $add_empty_option ) {
			return false;
		}

		$top_label = sprintf( __( 'All %s', 'codepress-admin-columns' ), $column->get_option( 'label' ) );

		/**
		 * Filter the top label of the dropdown menu
		 *
		 * @param string $label
		 * @param CPAC_Column $column_instance Column class instance
		 */
		$top_label = apply_filters( 'cac/addon/filtering/dropdown_top_label', $top_label, $column );

		$this->display_dropdown( $column->properties->name, $options, $add_empty_option, $current_item, $top_label, $column->get_option( 'label' ) );
	}

	public function use_cache() {
		return apply_filters( 'cac/is_cache_enabled', true );
	}

	/**
	 * Add filtering markup
	 *
	 * @since 1.0
	 * @todo: Add support for customfield values longer then 30 characters.
	 */
	public function add_filtering_markup() {

		// we only need one set of filter dropdown
		if ( $this->has_dropdown ) {
			return;
		}

		foreach ( $this->storage_model->get_columns() as $column ) {

			// ignore default filterables: like date
			if ( in_array( $column->get_type(), (array) $this->get_default_filterables() ) ) {
				continue;
			}

			if ( ! $this->is_filterable( $column ) ) {
				continue;
			}

			// dev
			if ( ! $this->use_cache() ) {
				$this->delete_cache( $column->get_name() );
			}

			// Range inputs
			if ( 'range' == $column->get_option( 'filter_format' ) ) {

				$min = isset( $_GET['cpac_filter-min'] ) && isset( $_GET['cpac_filter-min'][ $column->get_name() ] ) ? urldecode( $_GET['cpac_filter-min'][ $column->get_name() ] ) : '';
				$max = isset( $_GET['cpac_filter-max'] ) && isset( $_GET['cpac_filter-max'][ $column->get_name() ] ) ? urldecode( $_GET['cpac_filter-max'][ $column->get_name() ] ) : '';

				switch ( $column->get_property( 'filterable_type' ) ) {
					case 'date' :
					case 'date_format' :
						$this->display_date_range( $column->get_name(), $column->get_label(), $min, $max );
						break;
					case 'numeric':
						$this->display_number_range( $column->get_name(), $column->get_label(), $min, $max );
						break;
				}
			}

			// Select dropdown
			else {

				$dropdown_options = $this->get_cache( $column->get_name() );

				// Placeholder text
				if ( ! $dropdown_options ) {
					$dropdown_options['options'] = array( '' => __( 'Loading values ..', 'codepress-admin-columns' ) );
					$dropdown_options['empty_option'] = false;
				}

				$current = isset( $_GET['cpac_filter'] ) && isset( $_GET['cpac_filter'][ $column->get_name() ] ) ? urldecode( $_GET['cpac_filter'][ $column->get_name() ] ) : '';

				$this->dropdown( $column->get_name(), $dropdown_options['options'], $dropdown_options['empty_option'], $current );
			}

			$this->has_dropdown = true;
		}
	}

	/**
	 * @since 3.6
	 */
	public function is_filterable( $column ) {
		return $column && $column->get_property( 'is_filterable' ) && 'on' == $column->get_option( 'filter' );
	}

	/**
	 * @since 3.5
	 */
	protected function get_cache_id( $id ) {
		return md5( 'filtering' . $this->storage_model->key . $this->storage_model->layout . $id );
	}

	/**
	 * @since 3.6
	 */
	private function get_cache( $id ) {
		return get_transient( $this->get_cache_id( $id ) );
	}

	/**
	 * @since 3.6
	 */
	private function delete_cache( $id ) {
		delete_transient( $this->get_cache_id( $id ) );
	}

	/**
	 * @since 3.6
	 */
	public function set_cache( $id, $value, $time = 0 ) {
		set_transient( $this->get_cache_id( $id ), $value, $time );
	}

	/**
	 * @since 3.7
	 */
	private function get_cache_time_left( $id ) {
		return max( get_option( '_transient_timeout_' . $this->get_cache_id( $id ) ) - time(), 0 );
	}

	/**
	 * @since 3.6
	 * @return int|string Cache timer left in seconds or 'External cache' string
	 */
	public function is_timeout() {

		// time left on cache in seconds, unless it's being done with an external cache
		$timer = wp_using_ext_object_cache() ? 'external-cache' : $this->get_cache_time_left( 'cache-timer' );

		return $this->get_cache( 'cache-timer' ) ? $timer : 0;
	}

	public function set_timeout() {
		$this->set_cache( 'cache-timer', true, 60 ); // 1 minute
	}

	public function clear_timeout() {
		$this->delete_cache( 'cache-timer' );
	}
}
