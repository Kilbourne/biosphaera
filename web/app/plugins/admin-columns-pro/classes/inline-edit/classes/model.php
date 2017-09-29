<?php

/**
 * Storage model for editability
 * This class can be extended for different storage models, such as post, user and taxonomy storage models
 *
 * @since 1.0
 * @abstract
 */
abstract class CACIE_Editable_Model {

	/**
	 * Main storage model class instance
	 *
	 * @since 1.0
	 * @var CPAC_Storage_Model
	 * @access protected
	 */
	public $storage_model;

	/**
	 * Get default properties of editability of column types
	 * The array returned for each column type key contains information about the editability of a column type
	 * For example, it usually holds the editability type in $array['type'], which can be, for example, "text" or "select" or "email"
	 * For getting the editability information for an instance of a column, see CACIE_Editable_Model::get_editable()
	 *
	 * @since 1.0
	 * @abstract
	 *
	 * @return array List of editability information per column ([column_type] => (array) [editable])
	 */
	abstract function get_editables_data();

	/**
	 * Save a column for a certain entry
	 * Called on a succesful AJAX request
	 *
	 * @since 1.0
	 * @abstract
	 *
	 * @param int $id Post ID
	 * @param CPAC_Column $column Column object instance
	 * @param mixed $value Value to be saved
	 */
	abstract public function column_save( $id, $column, $value );

	/**
	 * Get the available items on the current page for passing them to JS
	 *
	 * @since 1.0
	 * @abstract
	 *
	 * @return array Items on the current page ([entry_id] => (array) [entry_data])
	 */
	abstract public function get_items();

	/**
	 * Output value for WP default column
	 *
	 * @since 1.0
	 * @abstract
	 *
	 * @param CPAC_Column $column Column Object
	 * @param int $id Entry ID
	 */
	abstract function manage_value( $column, $id );

	/**
	 * @since 3.6.1
	 */
	abstract function get_ajax_options( $column, $search );

	/**
	 * Constructor
	 *
	 * @since 1.0
	 *
	 * @param CPAC_Storage_Model $storage_model Main storage model class instance
	 */
	function __construct( $storage_model ) {
		$this->storage_model = $storage_model;
	}

	/**
	 * @since 3.7
	 */
	public function is_custom_field_editable() {
		return $this->storage_model->get_general_option( 'custom_field_editable' );
	}

	/**
	 * Check whether a column is editable
	 *
	 * @since 1.0
	 *
	 * @param array $column Column options
	 *
	 * @return bool Whether the column is editable
	 */
	public function is_editable( $column ) {
		$is_editable = false;

		switch ( $column->get_type() ) {

			// ACF
			case 'column-acf_field':
				if ( method_exists( $column, 'get_field' ) ) {
					switch ( $column->get_field_type() ) {
						case 'checkbox':
						case 'color_picker':

							//case 'date_time_picker':
						case 'email':
						case 'file':
						case 'gallery':
							//case 'google_map':
						case 'image':
							//case 'message':
						case 'number':
						case 'oembed':
						case 'page_link':
						case 'password':
						case 'post_object':
						case 'radio':
							//case 'relationship':
							//case 'repeater':
						case 'select':
						case 'taxonomy':
						case 'text':
						case 'textarea':
						case 'true_false':
						case 'url':
						case 'user':
						case 'wysiwyg':
							$is_editable = true;
							break;
						case 'date_picker':
							$is_editable = true;
							$field = $column->get_field();
							if ( isset( $field['date_format'] ) && 'yymmdd' != $field['date_format'] ) {
								$is_editable = false;
							}

					}
				}
				break;

			// Custom Fields
			case 'column-meta':
				if ( $this->is_custom_field_editable() ) {
					switch ( $column->get_option( 'field_type' ) ) {
						case '' :
						case 'checkmark' :
						case 'color' :
						case 'date' :
						case 'excerpt' :
						case 'image' :
						case 'link' :
						case 'library_id' :
						case 'numeric' :
						case 'title_by_id' :
						case 'user_by_id' :
							$is_editable = true;
							break;
					}
				}
				break;
		}

		return $is_editable;
	}

	/**
	 * @since 3.5
	 */
	public function get_list_selector() {
		return '#the-list';
	}

	/**
	 * Get list of options for posts selection
	 *
	 * Results are formatted as an array of post types, the key being the post type name, the value
	 * being an array with two keys: label (the post type label) and options, an array of options (posts)
	 * for this post type, with the post IDs as keys and the post titles as values
	 *
	 * @since 1.0
	 * @uses WP_Query
	 *
	 * @param array $query_args Additional query arguments for WP_Query
	 *
	 * @return array List of options, grouped by posttype
	 */
	public function get_posts_options( $query_args = array() ) {

		$options = array();

		$args = wp_parse_args( $query_args, array(
			'posts_per_page' => 100, // max 100 records in case we get a very large db
			'post_type'      => 'any',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		$processed = array();

		if ( $posts = get_posts( $args ) ) {
			foreach ( $posts as $post ) {
				if ( ! isset( $options[ $post->post_type ] ) ) {
					$options[ $post->post_type ] = array(
						'label'   => $post->post_type,
						'options' => array(),
					);
				}

				$label = $post->post_title;
				if ( in_array( $label, $processed ) ) {
					$label .= ' - #' . $post->ID;
				}

				$options[ $post->post_type ]['options'][ $post->ID ] = $label;

				$processed[] = $label;
			}
		}

		return $options;
	}

	/**
	 * Get list of options for users selection
	 *
	 * Results are formatted as an array of roles, the key being the role name, the value
	 * being an array with two keys: label (the role label) and options, an array of options (users)
	 * for this role, with the user IDs as keys and the user display names as values
	 *
	 * @since 1.0
	 * @uses WP_User_Query
	 *
	 * @param array $query_args Additional query arguments for WP_User_query
	 * @param string $display Display format
	 *
	 * @return array List of options, grouped by author role
	 */
	public function get_users_options( $query_args = array(), $display_format = array( 'display_name' ) ) {

		global $wp_roles;

		$options = array();

		$query_args = wp_parse_args( $query_args, array(
			'orderby' => 'display_name',
			'number'  => 100 // max 100 records in case we get a very large db
		) );

		if ( isset( $query_args['search'] ) && ! isset( $query_args['search_columns'] ) ) {
			$query_args['search_columns'] = array( 'ID', 'user_login', 'user_nicename', 'user_email', 'user_url' );
		}

		// Get all users
		$users_query = new WP_User_Query( $query_args );
		$users = $users_query->get_results();

		// Get roles
		$roles = $wp_roles->roles;

		// Generate options by grouping users by role
		foreach ( $users as $user ) {
			$role = CACIE_Roles::get_user_role( $user->ID );

			if ( ! isset( $options[ $role ] ) ) {
				$options[ $role ] = array(
					'label'   => translate_user_role( $roles[ $role ]['name'] ),
					'options' => array(),
				);
			}

			// Display format
			$names = array();
			foreach ( $display_format as $field ) {
				if ( ! empty( $user->{$field} ) ) {
					$names[] = $user->{$field};
				}
			}

			if ( empty( $names ) ) {
				$names = array( $user->display_name );
			}

			$options[ $role ]['options'][ $user->ID ] = esc_attr( implode( ' ', $names ) ) . ' (' . $user->ID . ')';
		}

		return $options;
	}

	/**
	 * Get total post count
	 * Used to determine whether post dropdowns should be populated directly or through AJAX
	 *
	 * @since 1.0
	 *
	 * @return int Total amount of published posts amongst all post types
	 */
	protected function get_total_post_count() {

		$count = 0;

		if ( $posttypes = get_post_types() ) {
			foreach ( $posttypes as $posttype ) {
				$counter = wp_count_posts( $posttype );
				$count += $counter->publish;
			}
		}

		return $count;
	}

	/**
	 * Get total user count
	 * Used to determine whether user dropdowns should be populated directly or through AJAX
	 *
	 * @since 1.0
	 *
	 * @return int Total number of users registered
	 */
	protected function get_total_user_count() {
		$count = count_users();

		return $count['total_users'];
	}

	/**
	 * Check if the columns is editable and if the user enabled editing for this column
	 *
	 * @since 1.1
	 *
	 * @param object CPAC_Column
	 *
	 * @return bool
	 */
	public function is_edit_enabled( $column ) {
		return ( 'on' == $column->get_option( 'edit' ) ) && ac_editable()->is_editable( $column );
	}

	/**
	 * Settings based on ACF field type
	 * @since 3.6
	 */
	private function get_acf_editable_settings_by_field( $field ) {
		$editable = array();

		switch ( $field['type'] ) {
			case 'checkbox':
				$editable['type'] = 'checklist';
				break;
			case 'color_picker':
				$editable['type'] = 'text';
				break;
			case 'date_picker':
				$editable['type'] = 'date';
				break;
			case 'email':
				$editable['type'] = 'email';
				break;
			case 'file':
				// @todo Implement "attachment" type
				$editable['type'] = 'attachment';

				if ( empty( $field['required'] ) ) {
					$editable['clear_button'] = true;
				}
				break;
			case 'gallery':
				$editable['type'] = 'media';
				$editable['multiple'] = true;
				$editable['attachment']['disable_select_current'] = true;
				break;
			case 'image':
				$editable['type'] = 'media';
				$editable['attachment']['library']['type'] = 'image';

				if ( empty( $field['required'] ) ) {
					$editable['clear_button'] = true;
				}

				break;
			case 'number':
				$editable['type'] = 'number';
				$editable['range_step'] = 'any';
				break;
			case 'oembed':
				$editable['type'] = 'text';
				break;
			case 'taxonomy':
				$editable['type'] = 'select';
				$editable['advanced_dropdown'] = true;
				break;
			case 'page_link':
			case 'post_object':
				if ( ! function_exists( 'acf_get_setting' ) || version_compare( acf_get_setting( 'version' ), '5.1.0' ) >= 0 ) {
					$editable['type'] = 'select2_dropdown';
					$editable['ajax_populate'] = true;
					$editable['advanced_dropdown'] = true;
					$editable['formatted_value'] = 'post';
				}

				if ( $field['multiple'] == 0 && $field['allow_null'] == 1 ) {
					$editable['clear_button'] = true;
				}
				break;
			case 'password':
				$editable['type'] = 'password';
				break;
			case 'radio':
				$editable['type'] = 'select';
				break;
			case 'select':
				$editable['type'] = 'select';
				$editable['advanced_dropdown'] = true;

				if ( $field['multiple'] == 0 && $field['allow_null'] == 1 ) {
					$editable['clear_button'] = true;
				}
				break;
			case 'text':
				$editable['type'] = 'text';
				break;
			case 'textarea':
				$editable['type'] = 'textarea';
				break;
			case 'true_false':
				$editable['type'] = 'togglable';
				$editable['options'] = array( '0', '1' );
				break;
			case 'url':
				$editable['type'] = 'url';
				break;
			case 'user':
				if ( ! function_exists( 'acf_get_setting' ) || version_compare( acf_get_setting( 'version' ), '5.1.0' ) >= 0 ) {
					$editable['type'] = 'select2_dropdown';
					$editable['ajax_populate'] = true;
					$editable['advanced_dropdown'] = true;
					$editable['formatted_value'] = 'user';
				}
				break;
			case 'wysiwyg':
				$editable['type'] = 'textarea';
				break;
		}

		return $editable;
	}

	/**
	 * Get the editability options for a single column
	 * The array returned contains information about the editability of a column
	 * For example, it usually holds the editability type in $array['type'], which can be, for example, "text" or "select" or "email"
	 *
	 * @since 1.0
	 *
	 * @param array|string $column Column options or column name. In case a column name is provided, the column object is fetched based on the name
	 *
	 * @return bool|array Returns false if the column is not editable, an array with editability settings otherwise
	 */
	public function get_editable( $column ) {

		// Get column data by column name
		if ( is_string( $column ) ) {
			$columns = $this->storage_model->get_stored_columns();

			if ( empty( $columns[ $column ] ) ) {
				return false;
			}

			$column = $columns[ $column ];
		}

		// Edit possible for this column type
		if ( ! isset( $column['edit'] ) || $column['edit'] != 'on' ) {
			return false;
		}

		// Get default column editable data
		$editables = $this->get_editables_data();
		$editable = ! empty( $editables[ $column['type'] ] ) ? $editables[ $column['type'] ] : array();

		// ACF Field
		switch ( $column['type'] ) {

			// ACF Field
			case 'column-acf_field' :

				// make sure acf and the add-on are still active...
				if ( ! function_exists( 'acf' ) || ! function_exists( 'cpac_get_acf_field' ) ) {
					return false;
				}

				// Load field settings from ACF
				if ( $field = cpac_get_acf_field( $column['field'] ) ) {

					$editable['advanced_dropdown'] = false;

					// add acf editable settings
					$editable = array_merge( $editable, $this->get_acf_editable_settings_by_field( $field ) );

					// Create an advanced dropdown menu
					if ( $editable['advanced_dropdown'] ) {
						if ( ! empty( $field['multiple'] ) || ( ! empty( $field['field_type'] ) && in_array( $field['field_type'], array( 'checkbox', 'multi_select' ) ) ) ) {
							$editable['type'] = 'select2_dropdown';
							$editable['multiple'] = true;
						}
						else {
							if ( ! empty( $field['allow_null'] ) ) {
								if ( $field['type'] == 'taxonomy' ) {
									$option_null = array(
										'' => __( 'None' ),
									);
								}
								else {
									$option_null = array(
										'null' => __( '- Select -', 'codepress-admin-columns' ),
									);
								}

								if ( ! isset( $editable['options'] ) || ! is_array( $editable['options'] ) ) {
									$editable['options'] = array();
								}

								$editable['options'] = $option_null + $editable['options'];
							}
						}
					}

					if ( ! empty( $field['required'] ) ) {
						$editable['required'] = true;
					}

					if ( ! empty( $field['placeholder'] ) ) {
						$editable['placeholder'] = $field['placeholder'];
					}

					if ( ! empty( $field['maxlength'] ) ) {
						$editable['maxlength'] = $field['maxlength'];
					}

					if ( ! empty( $field['min'] ) ) {
						$editable['range_min'] = $field['min'];
					}

					if ( ! empty( $field['max'] ) ) {
						$editable['range_max'] = $field['max'];
					}

					if ( ! empty( $field['step'] ) ) {
						$editable['range_step'] = $field['step'];
					}

					if ( ! empty( $field['library'] ) ) {
						if ( $field['library'] == 'uploadedTo' ) {
							$editable['attachment']['library']['uploaded_to_post'] = true;
						}
					}

					if ( empty( $field['ajax_populate'] ) ) {
						// Options from ACF
						$fieldoptions = new CACIE_ACF_FieldOptions();
						$options = $fieldoptions->get_field_options( $field );

						if ( $options !== false ) {
							$editable['options'] = $options;
						}
					}
				}
				break;

			// Custom Fields
			case 'column-meta' :

				switch ( $column['field_type'] ) {
					case 'excerpt' :
						$editable['type'] = 'textarea';
						break;
					case 'date':
						$editable['type'] = 'date';
						break;
					case 'checkmark' :
						$editable['type'] = 'togglable';
						$editable['options'] = array( '0', '1' );
						break;
					case 'library_id' :
						$editable['type'] = 'attachment';
						$editable['clear_button'] = true;
						break;
					case 'title_by_id' :
						$editable['type'] = 'select2_dropdown';
						$editable['ajax_populate'] = true;
						$editable['formatted_value'] = 'post';
						break;
					case 'user_by_id' :
						$editable['type'] = 'select2_dropdown';
						$editable['ajax_populate'] = true;
						$editable['formatted_value'] = 'user';
						break;
					default :
						$editable['type'] = 'text';
				}
				break;

			// Taxonomy column
			case 'column-taxonomy' :
			case 'categories' :
			case 'tags' :

				if ( isset( $column['enable_term_creation'] ) && 'on' == $column['enable_term_creation'] ) {
					$editable['type'] = 'select2_tags';
				}
				else {
					$editable['type'] = 'select2_dropdown';
					$editable['multiple'] = true;
				}
				break;
		}

		// Developers can define editable settings with their column by using get_editable_settings();
		if ( empty( $editable ) ) {
			if ( $_column = $this->storage_model->get_column_by_name( $column['column-name'] ) ) {
				if ( method_exists( $_column, 'get_editable_settings' ) ) {
					$editable = $_column->get_editable_settings();
				}
			}
		}

		// Add all available options if we are not using ajax, and they haven't been set yet
		if ( empty( $editable['ajax_populate'] ) ) {
			// Fetch column options, and only use them if an array of options is passed
			$options = $this->get_column_options( $column );

			if ( $options !== false ) {
				$editable['options'] = $options;
			}
			if ( ! empty( $editable['options'] ) ) {
				$editable['options'] = $this->format_options( $editable['options'] );
			}
		}

		// deprecated
		$editable = apply_filters( 'cacie/inline_edit/options', $editable, $column );

		/**
		 * Filters the options for the editable field.
		 *
		 * @since 3.2.2
		 *
		 * @param array $editable List of edit options ([type] => [field_type] )
		 * @param array $column Stored column settings
		 */
		return apply_filters( 'cac/editable/options', $editable, $column, $this );
	}

	/**
	 * Get a list of editable columns, with the default column options and an
	 * addon_cacie array key, containing the add-on data
	 *
	 * @since 1.0
	 *
	 * @return array List of columns ([column_name] => [column_options])
	 */
	public function get_columns() {
		$columns = array();
		foreach ( $this->storage_model->get_columns() as $column ) {
			$column_name = $column->get_name();
			if ( $this->is_edit_enabled( $column ) && ( false !== ( $editable = $this->get_editable( $column_name ) ) ) ) {
				$columns[ $column_name ] = array(
					'type'        => $column->get_type(),
					'addon_cacie' => array( 'editable' => $editable ),
				);
			}
		}

		return $columns;
	}

	/**
	 * Get possible options for column with a defined set of possible options
	 *
	 * @since 1.0
	 *
	 * @param array $column Column array with column options
	 *
	 * @return array List of options with option value as key and option label as value
	 */
	public function get_column_options( $column ) {

		return false;
	}

	/**
	 * Update editability preference
	 *
	 * @since 3.8.4
	 */
	public function update_editability_preference( $is_enabled = false ) {
		update_user_meta( get_current_user_id(), 'cacie_editability_state' . $this->storage_model->key, $is_enabled ? '1' : '0' );
	}

	/**
	 * Get editability preference
	 *
	 * @since 3.2.1
	 */
	public function get_editability_preference() {

		$is_active = '1' === get_user_meta( get_current_user_id(), 'cacie_editability_state' . $this->storage_model->key, true );

		/**
		 * Filters the default state of editability of cells on overview pages
		 *
		 * @since 3.0.9
		 *
		 * @param $is_active bool Whether the default state is active (true) or inactive (false)
		 */
		$is_active = apply_filters( 'cacie/inline_edit/active', $is_active );
		$is_active = apply_filters( 'cacie/inline_edit/active/storage_key=' . $this->storage_model->key, $is_active );

		return $is_active;
	}

	/**
	 * Add the option of inline editing to columns
	 *
	 * @since 1.0
	 */
	public function enable_inlineedit( $columns ) {
		foreach ( $columns as $column ) {
			if ( $this->is_editable( $column ) ) {
				$column->set_properties( 'is_editable', true );
			}
		}
	}

	/**
	 * Update Meta
	 *
	 * @since 1.0
	 */
	protected function update_meta( $id, $meta_key, $value ) {
		update_metadata( $this->storage_model->meta_type, $id, $meta_key, $value );
	}

	/**
	 * @since 3.8
	 */
	protected function delete_meta( $id, $meta_key ) {
		delete_metadata( $this->storage_model->meta_type, $id, $meta_key );
	}

	/**
	 * Format options to be in JS
	 *
	 * @since 1.0
	 *
	 * @param array $options List of options, possibly with option groups
	 *
	 * @return array Formatted option list
	 */
	public function format_options( $options ) {
		$newoptions = array();

		if ( $options ) {
			foreach ( $options as $index => $option ) {
				if ( is_array( $option ) && isset( $option['options'] ) ) {
					$option['options'] = $this->format_options( $option['options'] );
					$newoptions[] = $option;
				}
				else {
					$newoptions[] = array(
						'value' => $index,
						'label' => $option,
					);
				}
			}
		}

		return $newoptions;
	}

	/**
	 * @since ?
	 */
	public function get_formatted_value( $column, $raw_value ) {

		if ( empty( $raw_value ) || is_wp_error( $raw_value ) ) {
			return null;
		}

		$formattedvalues = null;

		$editable = $this->get_editable( $column->get_name() );
		$formatted_value = isset( $editable['formatted_value'] ) ? $editable['formatted_value'] : '';

		switch ( $formatted_value ) {
			case 'post' :
				$formattedvalues = array();
				foreach ( (array) $raw_value as $id ) {
					$formattedvalues[ $id ] = get_post_field( 'post_title', $id );
				}
				break;
			case 'user' :
				$formattedvalues = array();
				foreach ( (array) $raw_value as $id ) {
					$user = get_user_by( 'id', $id );
					if ( is_a( $user, 'WP_User' ) ) {
						$formattedvalues[ $id ] = $user->display_name;
					}
				}
				break;
			case 'wc_product' :
				$formattedvalues = array();
				foreach ( (array) $raw_value as $id ) {
					if ( $product = wc_get_product( $id ) ) {
						$formattedvalues[ $id ] = $product->get_title();
					}
				}
				break;
			case 'term' :
				foreach ( (array) $raw_value as $id ) {
					if ( $term = get_term_by( 'ID', $id, $this->storage_model->get_taxonomy() ) ) {
						$formattedvalues[ $id ] = $term->name;
					}
				}
				break;
		}

		return $formattedvalues;
	}

	/**
	 * Get term options for a taxonomy
	 *
	 * @since 1.0
	 *
	 * @param string $taxonomy Taxonomy name
	 * @param string $default Default option ( key is always zero )
	 *
	 * @return List of term options (term_id => name)
	 */
	public function get_term_options( $taxonomy, $default = '' ) {
		$options = array();

		if ( $default ) {
			$options[0] = $default;
		}

		$terms = get_terms( $taxonomy, array(
			'hide_empty' => 0,
		) );

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->term_id ] = htmlspecialchars_decode( $term->name );
			}
		}

		return $options;
	}

	/**
	 * Update post terms
	 *
	 * @since 1.0
	 *
	 * @param mixed $post Post object or post ID. Pass NULL to use the current post in the loop.
	 * @param array $term_ids Term IDs (int) or names (string, will be added as new term if it doesn't exist yet)  to be stored
	 * @param string $taxonomy Taxonomy to set the terms for
	 */
	public function set_post_terms( $post, $term_ids, $taxonomy ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return;
		}

		// Filter list of terms
		if ( empty( $term_ids ) ) {
			$term_ids = array();
		}

		$term_ids = array_unique( (array) $term_ids );

		// maybe create terms?
		$created_term_ids = array();

		foreach ( (array) $term_ids as $index => $term_id ) {
			if ( is_numeric( $term_id ) ) {
				continue;
			}

			if ( $term = get_term_by( 'name', $term_id, $taxonomy ) ) {
				$term_ids[ $index ] = $term->term_id;
			}
			else {
				$created_term = wp_insert_term( $term_id, $taxonomy );
				$created_term_ids[] = $created_term['term_id'];
			}
		}

		// merge
		$term_ids = array_merge( $created_term_ids, $term_ids );

		//to make sure the terms IDs is integers:
		$term_ids = array_map( 'intval', (array) $term_ids );
		$term_ids = array_unique( $term_ids );

		if ( $taxonomy == 'category' && is_object_in_taxonomy( $post->post_type, 'category' ) ) {
			wp_set_post_categories( $post->ID, $term_ids );
		}
		else if ( $taxonomy == 'post_tag' && is_object_in_taxonomy( $post->post_type, 'post_tag' ) ) {
			wp_set_post_tags( $post->ID, $term_ids );
		}
		else {
			wp_set_object_terms( $post->ID, $term_ids, $taxonomy );
		}
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
		if ( ! $column->properties->default ) {
			return $column->get_raw_value( $id );
		}

		return null;
	}
}