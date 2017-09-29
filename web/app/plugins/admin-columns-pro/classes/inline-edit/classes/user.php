<?php

/**
 * User storage model for editability
 *
 * @since 3.3
 */
class CACIE_Editable_Model_User extends CACIE_Editable_Model {

	private $users;

	public $items;

	/**
	 * Constructor
	 *
	 * @since 3.3
	 */
	public function __construct( $storage_model ) {
		parent::__construct( $storage_model );

		add_action( 'pre_user_query', array( $this, 'populate_users' ), 99 );
	}

	/**
	 * @since 3.6.1
	 */
	public function get_ajax_options( $column, $search ) {
	}

	/**
	 * @see CACIE_Editable_Model::is_editable()
	 * @since 3.3
	 */
	public function is_editable( $column ) {

		// By default, inherit editability from parent
		$is_editable = parent::is_editable( $column );

		switch ( $column->properties->type ) {
			// Default columns
			case 'email':
			case 'role':
			case 'username':

				// Custom columns
			case 'column-first_name':
			case 'column-last_name':
			case 'column-nickname':
			case 'column-roles':
			case 'column-rich_editing':
			case 'column-user_description':
			case 'column-user_url':

				$is_editable = true;
				break;
		}

		/**
		 * Filter the editability of a column
		 *
		 * @since 3.4
		 *
		 * @param bool $is_editable Whether the column is editable
		 * @param CPAC_Column $column Colum object
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
			case 'role':
			case 'column-roles':
				if ( $_roles = get_editable_roles() ) {
					foreach ( $_roles as $k => $role ) {
						$options[ $k ] = translate_user_role( $role['name'] );
					}
				}
				break;

		}

		return $options;
	}

	/**
	 * @see CACIE_Editable_Model::get_editables_data()
	 * @since 3.3
	 */
	public function get_editables_data() {

		$data = array(

			/**
			 * Default columns
			 *
			 */
			'email'                   => array(
				'type'     => 'text',
				'property' => 'user_email'
			),
			'role'                    => array(
				'type' => 'select',
			),
			'username'                => array(
				'type'         => 'text',
				'js'           => array(
					'selector' => 'strong > a',
				),
				'display_ajax' => false
			),

			/**
			 * Custom columns
			 *
			 */
			'column-first_name'       => array(
				'type' => 'text',
			),
			'column-last_name'        => array(
				'type' => 'text',
			),
			'column-nickname'         => array(
				'type' => 'text',
			),
			'column-roles'            => array(
				'type'     => 'select2_dropdown',
				'multiple' => true
			),
			'column-rich_editing'     => array(
				'type'    => 'togglable',
				'options' => array( true, false )
			),
			'column-user_description' => array(
				'type' => 'textarea',
			),
			'column-user_url'         => array(
				'type'     => 'text',
				'property' => 'user_url'
			),

			/**
			 * Custom fields column
			 *
			 */
			'column-meta'             => array(// settings are set in CACIE_Editable_Model::get_columns()
			)
		);

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

		return $data;
	}

	/**
	 * Populate Users
	 *
	 * @since 3.3
	 */
	public function populate_users( $user_query ) {

		global $pagenow;

		// is this the users page?
		if ( 'users.php' !== $pagenow ) {
			return;
		}

		// Check whether this is the users overview page
		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'delete' ) {
			return;
		}

		// run query
		$user_query->query();

		$items = array();

		if ( $users = $user_query->results ) {

			foreach ( $users as $user ) {

				if ( ! is_a( $user, 'WP_User' ) ) {
					continue;
				}

				if ( ! current_user_can( 'edit_user', $user->ID ) ) {
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
							case 'email':
								$value = $user->user_email;
								break;
							case 'role':
								$value = '';
								if ( isset( $user->roles[0] ) ) {
									$value = $user->roles[0];
								}
								break;
							case 'username':
								$value = $user->user_login;
								break;
						}
					} // Custom column
					else {
						$raw_value = $this->get_column_editability_value( $column, $user->ID );

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
					$value = apply_filters( 'cac/editable/column_value', $value, $column, $user->ID, $this );
					$value = apply_filters( 'cac/editable/column_value/column=' . $column->get_type(), $value, $column, $user->ID, $this );

					// Get item data
					$itemdata = array();

					if ( method_exists( $column, 'get_item_data' ) ) {
						$itemdata = $column->get_item_data( $user->ID );
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

				$items[ $user->ID ] = array(
					'ID'         => $user->ID,
					'columndata' => $columndata
				);
			}
		}

		$this->items = $items;
	}

	/**
	 * Get the available items on the current page for passing them to JS
	 *
	 * @since 3.3
	 *
	 * @return array Items on the current page
	 */
	public function get_items() {

		return $this->items;
	}

	/**
	 * @see CACIE_Editable_Model::manage_value()
	 * @since 3.3
	 */
	public function manage_value( $column, $id ) {

		switch ( $column->properties->type ) {
			case 'username':
				$user = get_user_by( 'id', $id );
				echo $user->user_login;
				break;
			case 'email':
				$user = get_user_by( 'id', $id );
				echo '<a href="mailto:' . esc_attr( $user->user_email ) . '" title="' . esc_attr( sprintf( __( 'E-mail: %s' ), $user->user_email ) ) . '">' . $user->user_email . '</a>';
				break;
			case 'role':
			case 'column-roles':
				$user = get_user_by( 'id', $id );
				global $wp_roles;
				if ( $wp_roles && isset( $wp_roles->roles[ $user->roles[0] ] ) ) {
					echo translate_user_role( $wp_roles->roles[ $user->roles[0] ]['name'] );
				}
				break;
		}
	}

	/**
	 * @see CACIE_Editable_Model::column_save()
	 * @since 3.3
	 */
	public function column_save( $id, $column, $value ) {

		if ( ! ( $user = get_user_by( 'id', $id ) ) ) {
			exit;
		}
		if ( ! current_user_can( 'edit_user', $id ) ) {
			exit;
		}

		// Third party columns can use the save() method as a callback for inline-editing
		if ( method_exists( $column, 'save' ) ) {
			$column->save( $id, $value );

			return;
		}

		$editable = $this->get_editable( $column->get_name() );

		switch ( $column->get_type() ) {

			/**
			 * Default Columns
			 *
			 */
			case 'role':
				// users can not change their own role
				if ( current_user_can( 'edit_users' ) && ( get_current_user_id() !== $user->ID ) && $value ) {
					$user->set_role( $value );
				}
				break;
			case 'username':
				global $wpdb;

				$value = sanitize_user( $value, true );

				if ( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(1) FROM {$wpdb->users} WHERE user_login = %s AND ID != %d", $value, $id ) ) ) {
					return new WP_Error( 'cacie_error_username_exists', __( 'The username already exists.', 'codepress-admin-columns' ) );
				}

				$wpdb->update(
					$wpdb->users,
					array( 'user_login' => $value ),
					array( 'ID' => $id ),
					array( '%s' ),
					array( '%d' )
				);

				clean_user_cache( $id );

				break;

			/**
			 * Custom Columns
			 */
			case 'column-roles':
				if ( current_user_can( 'edit_users' ) ) {

					// prevent the removal of your own admin role
					if ( current_user_can( 'administrator' ) && get_current_user_id() == $user->ID ) {
						$value[] = 'administrator';
					}
					if ( ! empty( $value ) ) {
						$user->set_role( array_pop( $value ) );
						foreach ( $value as $key ) {
							$user->add_role( $key );
						}
					}
				}
				break;
			case 'column-meta':
				$this->update_meta( $user->ID, $column->get_field_key(), $value );
				break;
			case 'column-first_name':
				$this->update_meta( $user->ID, 'first_name', $value );
				break;
			case 'column-last_name':
				$this->update_meta( $user->ID, 'last_name', $value );
				break;
			case 'column-nickname':
				$this->update_meta( $user->ID, 'nickname', $value );
				break;
			case 'column-rich_editing':
				$this->update_meta( $user->ID, 'rich_editing', 'true' == $value ? 1 : 0 );
				break;
			case 'column-user_description':
				$this->update_meta( $user->ID, 'description', $value );
				break;


			// Save basic property such as title or description (data that is available in WP_Post)
			default:
				if ( ! empty( $editable['property'] ) ) {
					$property = $editable['property'];

					if ( isset( $user->{$property} ) ) {
						wp_update_user( array(
							'ID'      => $user->ID,
							$property => $value
						) );
					}
				}
		}
	}

	/**
	 * @see CACIE_Editable_Model
	 * @version 3.6
	 */
	public function get_column_editability_value( $column, $id ) {

		$value = parent::get_column_editability_value( $column, $id );

		if ( $column->properties->type == 'username' ) {
			$user = get_user_by( 'id', $id );

			return $user->user_login;
		}

		return $value;
	}
}