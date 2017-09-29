<?php

define( 'CAC_LAY_URL', plugin_dir_url( __FILE__ ) );
define( 'CAC_LAY_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Layouts
 * @since 3.8
 */
class CAC_Layouts {

	CONST LAYOUT_KEY = 'cpac_layouts';

	private $cpac;

	function __construct( $cpac ) {
		$this->cpac = $cpac;

		add_action( 'cac/settings/sidebox', array( $this, 'settings' ) );
		add_action( 'cac/settings/after_title', array( $this, 'menu' ) );
		add_action( 'cac/settings/after_menu', array( $this, 'layout_help' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_init', array( $this, 'select2_conflict_fix' ), 1 );
		add_action( 'admin_init', array( $this, 'request_settings' ) );
		add_action( 'admin_init', array( $this, 'request_listings' ), 9 ); // needs to run before init_listings_layout
		add_action( 'admin_footer', array( $this, 'switcher' ) );

		// ajax
		add_action( 'wp_ajax_ac_layout_get_users', array( $this, 'ajax_get_users' ) );
		add_action( 'wp_ajax_ac_update_layout', array( $this, 'ajax_update_layout' ) );
	}

	public function ajax_update_layout() {
		check_ajax_referer( 'ac-layout' );

		if ( ! current_user_can( 'manage_admin_columns' ) ) {
			wp_die();
		}

		if ( ! $storage_model = $this->cpac->get_storage_model( filter_input( INPUT_POST, 'storage_model' ) ) ) {
			wp_die();
		}

		if ( ! $formdata = filter_input( INPUT_POST, 'data' ) ) {
			wp_die();
		}

		parse_str( $formdata, $data );

		if ( ! isset( $data['layout_id'] ) ) {
			wp_die();
		}

		$layout = $storage_model->save_layout( $data['layout_id'], array(
			'name'  => isset( $data['layout_name'] ) ? $data['layout_name'] : '',
			'roles' => isset( $data['layout_roles'] ) ? $data['layout_roles'] : '',
			'users' => isset( $data['layout_users'] ) ? $data['layout_users'] : ''
		) );

		if ( ! $layout ) {
			wp_die();
		}

		if ( is_wp_error( $layout ) ) {
			wp_send_json_error( $layout->get_error_code() );
		}

		wp_send_json_success( array(
				'title_description' => $this->get_title_description( $layout )
			)
		);
	}

	// Try to prevent older version 3.x of select2 from loading and causing conflicts with 4.x
	public function select2_conflict_fix() {
		if ( cac_is_setting_screen() ) {
			wp_enqueue_script( 'disable-older-version-select2', CAC_LAY_URL . "assets/js/select2_conflict_fix.js", array(), CAC_PRO_VERSION );
		}
	}

	public function scripts() {
		$minified = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( $this->cpac->is_settings_screen() ) {

			wp_deregister_script( 'select2' ); // try to remove any other version of select2

			wp_enqueue_style( 'cpac-layouts', CAC_LAY_URL . "assets/css/layouts{$minified}.css", array(), CAC_PRO_VERSION, 'all' );
			wp_enqueue_style( 'cpac-layouts-select2', CAC_LAY_URL . "assets/css/select2.min.css", array(), '4.0.2', 'all' );

			wp_register_script( 'cpac-layouts-select2', CAC_LAY_URL . "assets/js/select2{$minified}.js", array( 'jquery' ), CAC_PRO_VERSION );
			wp_enqueue_script( 'cpac-layouts', CAC_LAY_URL . "assets/js/layouts.js", array( 'cpac-layouts-select2' ), CAC_PRO_VERSION );

			wp_localize_script( 'cpac-layouts', 'cpac_layouts', array(
				'roles'  => __( 'Select roles', 'codepress-admin-columns' ),
				'users'  => __( 'Select users', 'codepress-admin-columns' ),
				'_nonce' => wp_create_nonce( 'ac-layout' )
			) );
		}

		if ( $this->cpac->is_columns_screen() ) {
			wp_enqueue_script( 'layouts-listings-screen', CAC_LAY_URL . "assets/js/layouts-listings-screen.js", array( 'jquery' ), CAC_PRO_VERSION );
			wp_enqueue_style( 'layouts-listings-screen', CAC_LAY_URL . "assets/css/layouts-listings-screen{$minified}.css", array(), CAC_PRO_VERSION, 'all' );
		}
	}

	public function request_settings() {
		$nonce = isset( $_REQUEST['_cpac_nonce'] ) ? $_REQUEST['_cpac_nonce'] : '';

		// Settings screen
		if ( isset( $_REQUEST['cpac_key'] ) && cac_is_setting_screen() ) {

			$action = isset( $_REQUEST['cpac_action'] ) ? $_REQUEST['cpac_action'] : 'select_layout';
			$key = $_REQUEST['cpac_key'];

			switch ( $action ) :

				case 'create_layout' :
					if ( wp_verify_nonce( $nonce, 'create-layout' ) ) {
						if ( $storage_model = $this->cpac->get_storage_model( $key ) ) {

							$name = filter_input( INPUT_POST, 'layout_name' );
							$roles = filter_input( INPUT_POST, 'layout_roles', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
							$users = filter_input( INPUT_POST, 'layout_users', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

							$orginal_layout_id = ! empty( $_POST['cpac_layout'] ) ? $_POST['cpac_layout'] : null;

							// Get original columns
							$storage_model->set_layout( $orginal_layout_id );
							$original_columns = $storage_model->get_stored_columns();

							// Create default layout
							// This saves the old column setting to a default layout when first
							if ( ! $storage_model->get_layouts() ) {
								$storage_model->create_layout( array(
									'name' => __( 'Original', 'codepress-admin-columns' )
								), true );
							}

							// New layout
							$layout_id = $storage_model->create_layout( array(
								'name'  => $name,
								'roles' => $roles,
								'users' => $users
							) );

							if ( is_wp_error( $layout_id ) ) {
								cpac_settings_message( $layout_id->get_error_message(), 'error' );

								return;
							}

							$storage_model->set_layout( $layout_id );
							$storage_model->store( $original_columns );
							$storage_model->set_user_layout_preference();

							cpac_settings_message( sprintf( __( 'Set %s succesfully created.', 'codepress-admin-columns' ), "<strong>\"" . esc_html( $name ) . "\"</strong>" ), 'updated' );
						}
					}
					break;

				case 'delete_layout' :
					if ( wp_verify_nonce( $nonce, 'delete-layout' ) ) {
						if ( $storage_model = $this->cpac->get_storage_model( $key ) ) {

							$id = filter_input( INPUT_POST, 'layout_id' );
							$layout = $storage_model->get_layout_by_id( $id );
							if ( $layout && $storage_model->delete_layout( $id ) ) {

								$storage_model->set_layout( $id );
								$storage_model->restore();
								$storage_model->set_single_layout_id();

								cpac_settings_message( sprintf( __( 'Set %s succesfully deleted.', 'codepress-admin-columns' ), "<strong>\"" . esc_html( $layout->name ) . "\"</strong>" ), 'updated' );
							}
							else {
								cpac_settings_message( __( "Screen does not exist.", 'codepress-admin-columns' ), 'error' );
							}
						}
					}
					break;

				// Default
				case 'select_layout' :
					if ( $storage_model = $this->cpac->get_storage_model( $key ) ) {

						if ( isset( $_REQUEST['layout_id'] ) ) {

							// Specified layout
							if ( $storage_model->layout_exists( $_REQUEST['layout_id'] ) ) {
								$storage_model->set_layout( $_REQUEST['layout_id'] );
							}

							// First one found
							else {
								$storage_model->set_single_layout_id();
							}

							$storage_model->set_user_layout_preference();
						}
					}
					break;
			endswitch;
		}
	}

	public function request_listings() {
		if ( 'select_layout' == filter_input( INPUT_POST, 'cpac_action' ) && ( $storage_model = cpac()->get_current_storage_model() ) ) {
			if ( wp_verify_nonce( filter_input( INPUT_POST, '_cpac_nonce' ), 'select-layout' ) ) {
				$storage_model->set_layout( filter_input( INPUT_POST, 'layout' ) );
				$storage_model->set_user_layout_preference();
			}
		}
	}

	public function ajax_get_users() {
		check_ajax_referer( 'ac-layout' );

		if ( ! current_user_can( 'manage_admin_columns' ) ) {
			wp_die();
		}

		$query_args = array(
			'orderby'        => 'display_name',
			'number'         => 100,
			'search'         => '*' . filter_input( INPUT_POST, 'search' ) . '*',
			'search_columns' => array( 'ID', 'user_login', 'user_nicename', 'user_email', 'user_url' )
		);

		$options = array();

		$users_query = new WP_User_Query( $query_args );
		if ( $users = $users_query->get_results() ) {
			$names = array();

			foreach ( $users as $user ) {
				$name = $this->get_user_name( $user );

				if ( in_array( $name, $names ) ) {
					$name .= ' (' . $user->user_email . ')';
				}

				// Select2 format
				$options[] = array(
					'id'   => $user->ID,
					'text' => $name
				);


				// for duplicates
				$names[] = $name;
			}
		}

		wp_send_json_success( $options );
	}

	public function get_grouped_role_names() {
		$roles = array();

		$_roles = get_editable_roles();
		foreach ( $_roles as $name => $role ) {
			$group = 'other';

			// Core roles
			if ( in_array( $name, array( 'super_admin', 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) ) ) {
				$group = __( 'Default', 'codepress-admin-columns' );
			}

			// WooCommerce roles
			if ( in_array( $name, array( 'customer', 'shop_manager' ) ) ) {
				$group = __( 'Shop', 'codepress-admin-columns' );
			}

			// bbPress roles
			if ( substr( $name, 0, 4 ) === "bbp_" ) {
				$group = __( 'bbPress', 'codepress-admin-columns' );
			}
			$roles[ $group ][ $name ] = $role['name'];
		}

		return $roles;
	}

	public function switcher() {
		if ( ! $this->cpac || ! ( $storage_model = $this->cpac->get_current_storage_model() ) ) {
			return;
		}
		$layouts = $storage_model->get_layouts_for_current_user();
		if ( count( $layouts ) > 1 ) : ?>
			<form method="post" class="layout-switcher">
				<?php wp_nonce_field( 'select-layout', '_cpac_nonce' ); ?>
				<input type="hidden" name="cpac_action" value="select_layout"/>
				<span class="label"><?php _e( 'Column View', 'codepress-admin-columns' ); ?></span>
				<span class="spinner"></span>
				<select name="layout">
					<?php foreach ( $layouts as $layout ) : ?>
						<option value="<?php echo $layout->id; ?>"<?php selected( $layout->id, $storage_model->layout ); ?>><?php echo esc_html( $layout->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<script type="text/javascript">
					jQuery( document ).ready( function( $ ) {
						$( '.layout-switcher' ).change( function() {
							$( this ).addClass( 'loading' ).submit().find( 'select' ).attr( 'disabled', 1 );
						} );
					} );
				</script>
			</form>
			<?php
		endif;
	}

	public function menu( $storage_model ) {
		$current = $storage_model->get_layout();
		if ( $layouts = $storage_model->get_layouts() ) : ?>
			<div class="layout-selector">
				<ul class="subsubsub">
					<li class="first"><?php _e( 'Column Sets', 'codepress-admin-columns' ); ?>:</li>
					<?php $count = 0;
					foreach ( $layouts as $layout ) : ?>
						<li data-screen="<?php echo $layout->id; ?>">
							<?php echo ( $count ++ ) != 0 ? ' | ' : ''; ?>
							<a class="<?php echo $layout->id == $current ? 'current' : ''; ?>" href="<?php echo $storage_model->get_edit_link_by_layout( $layout->id ? $layout->id : '' ); ?>"><?php echo esc_html( $layout->name ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		endif;
	}

	private function display_select_roles( $attr_id, $current_roles = array(), $is_disabled = false ) {
		$grouped_roles = $this->get_grouped_role_names();
		?>
		<select class="roles" name="layout_roles[]" multiple="multiple" id="layout-roles-<?php echo $attr_id; ?>" style="width: 100%;"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
			<?php foreach ( $grouped_roles as $group => $roles ) : ?>
				<optgroup label="<?php echo esc_attr( $group ); ?>">
					<?php foreach ( $roles as $name => $label ) : ?>
						<option value="<?php echo esc_attr( $name ); ?>"<?php echo in_array( $name, (array) $current_roles ) ? ' selected="selected"' : ''; ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</optgroup>
			<?php endforeach; ?>
		</select>
		<?php
	}

	private function get_user_name( $user ) {
		$name_parts = array();
		if ( $user->first_name ) {
			$name_parts[] = $user->first_name;
		}
		if ( $user->last_name ) {
			$name_parts[] = $user->last_name;
		}
		$name = $name_parts ? implode( ' ', $name_parts ) : $user->display_name;

		return ucfirst( $name );
	}

	private function display_select_users( $attr_id, $current_users = array(), $is_disabled = false ) {
		?>
		<select class="users" name="layout_users[]" multiple="multiple" id="layout-users-<?php echo $attr_id; ?>" style="width: 100%;"<?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>>
			<?php if ( $current_users ) : ?>
				<?php foreach ( $current_users as $user_id ) : $user = get_userdata( $user_id ); ?>
					<option value="<?php echo $user->ID; ?>" selected="selected"><?php echo esc_html( $this->get_user_name( $user ) ); ?></option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php
	}

	public function input_rows( $attr_id, $layout = false, $is_disabled = false ) {
		?>
		<div class="row name">
			<label for="layout-name-<?php echo $attr_id; ?>">
				<?php _e( 'Name', 'codepress-admin-columns' ); ?>
			</label>
			<div class="input">
				<div class="ac-error-message">
					<p>
						<?php _e( 'Please enter a name.', 'codepress-admin-columns' ); ?>
					<p>
				</div>
				<input class="name" id="layout-name-<?php echo $attr_id; ?>" name="layout_name" value="<?php echo $layout ? esc_attr( $layout->name ) : ''; ?>" data-value="<?php echo $layout ? esc_attr( $layout->name ) : ''; ?>" placeholder="<?php _e( 'Enter name', 'codepress-admin-coliumns' ); ?>" <?php echo $is_disabled ? ' disabled="disabled"' : ''; ?>/>
			</div>
		</div>
		<div class="row info">
			<em><?php _e( 'Make this set available only for specific users or roles (optional)', 'codepress-admin-columns' ); ?></em>
		</div>
		<div class="row roles">
			<label for="layout-roles-<?php echo $attr_id; ?>">
				<?php _e( 'Roles', 'codepress-admin-columns' ); ?>
				<span>(<?php _e( 'optional', 'codepress-admin-columns' ); ?>)</span>
			</label>
			<div class="input">
				<?php $this->display_select_roles( $attr_id, isset( $layout->roles ) ? $layout->roles : false, $is_disabled ); ?>
			</div>
		</div>
		<div class="row users">
			<label for="layout-users-<?php echo $attr_id; ?>">
				<?php _e( 'Users', 'codepress-admin-columns' ); ?>
				<span>(<?php _e( 'optional', 'codepress-admin-columns' ); ?>)</span>
			</label>
			<div class="input">
				<?php $this->display_select_users( $attr_id, isset( $layout->users ) ? $layout->users : false, $is_disabled ); ?>
			</div>
		</div>
		<?php
	}

	private function get_title_description( $layout ) {
		$description = array();

		if ( ! empty( $layout->roles ) ) {
			if ( 1 == count( $layout->roles ) ) {
				$_roles = get_editable_roles();
				$role = $layout->roles[0];
				$description[] = isset( $_roles[ $role ] ) ? $_roles[ $role ]['name'] : $role;
			}
			else {
				$description[] = __( 'Roles', 'codepress-admin-columns' );
			}
		}

		if ( ! empty( $layout->users ) ) {
			if ( 1 == count( $layout->users ) ) {
				$user = get_userdata( $layout->users[0] );
				$description[] = $user ? $this->get_user_name( $user ) : __( 'User', 'codepress-admin-columns' );
			}
			else {
				$description[] = __( 'Users', 'codepress-admin-columns' );
			}
		}

		return implode( ' & ', array_filter( $description ) );
	}

	public function instructions() { ?>
		<a href="javascript:;" class="instructions cpac-pointer" rel="layout-help" data-pos="left" data-width="305" data-noclick="1">
			<?php _e( 'Instructions', 'codepress-admin-columns' ); ?>
		</a>
		<?php
	}

	public function settings( $storage_model ) {
		?>
		<div class="sidebox layouts" data-type="<?php echo $storage_model->key; ?>">

			<div class="header">
				<h3>
					<?php _e( 'Column Sets', 'codepress-admin-columns' ); ?>
					<a class="button add-new">
						<span class="add"><?php _e( '+ Add set', 'codepress-admin-columns' ); ?></span>
						<span class="close"><?php _e( 'Cancel', 'codepress-admin-columns' ); ?></span>
					</a>
				</h3>
			</div>
			<div class="item new">
				<form method="post" action="<?php echo $storage_model->settings_url(); ?>">
					<input type="hidden" name="cpac_action" value="create_layout">
					<?php wp_nonce_field( 'create-layout', '_cpac_nonce', false ); ?>
					<input type="hidden" name="cpac_key" value="<?php echo $storage_model->key; ?>">
					<input type="hidden" name="cpac_layout" value="<?php echo $storage_model->layout; ?>">
					<div class="body">
						<div class="row info">
							<p><?php printf( __( "Create new sets to switch between different column views on the %s screen.", 'codepress-admin-columns' ), $storage_model->label ); ?></p>
						</div>

						<?php $this->input_rows( $storage_model->key ); ?>

						<div class="row actions">

							<?php $this->instructions(); ?>

							<input class="save button-primary" type="submit" value="<?php _e( 'Add', 'codepress-admin-columns' ); ?>">
						</div>
					</div>

				</form>
			</div>

			<?php if ( $layouts = $storage_model->get_layouts() ) : ?>
				<?php foreach ( $layouts as $i => $layout ) : ?>
					<?php $onclick = $this->cpac->use_delete_confirmation() ? ' onclick="return confirm(\'' . esc_attr( addslashes( sprintf( __( "Warning! The %s columns data will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop", 'codepress-admin-columns' ), "'" . $layout->name . "'" ) ) ) . '\');"' : ''; ?>
					<?php $is_current = $storage_model->get_layout() == $layout->id; ?>
					<?php $not_editable = ! empty( $layout->not_editable ); ?>
					<div class="item layout<?php echo $is_current ? ' current' : ''; ?><?php echo $i === ( count( $layouts ) - 1 ) ? ' last' : ''; ?><?php echo $not_editable ? ' not_editable' : ''; ?>" data-screen="<?php echo $layout->id; ?>">
						<div class="head">
							<div class="left">
								<div class="title-div">
									<span class="title"><?php echo esc_html( $layout->name ); ?></span>
									<span class="description"><?php echo esc_html( $this->get_title_description( $layout ) ); ?></span>
									<?php if ( $not_editable ) : ?>
										<span class="description"><?php echo '(' . esc_html( __( 'Not editable', 'codepress-admin-columns' ) ) . ')'; ?></span>
									<?php endif; ?>
								</div>
								<div class="actions">
									<form method="post" class="delete">
										<input type="hidden" name="layout_id" value="<?php echo esc_attr( $layout->id ); ?>">
										<input type="hidden" name="cpac_action" value="delete_layout">
										<input type="hidden" name="cpac_key" value="<?php echo $storage_model->key; ?>">
										<?php wp_nonce_field( 'delete-layout', '_cpac_nonce', false ); ?>
										<input type="submit" class="delete" value="<?php echo esc_attr( __( 'Delete', 'codepress-admin-columns' ) ); ?>"<?php echo $onclick; ?>/>
									</form>

									<?php if ( ! $is_current ) : ?>
										<span class="pipe">|</span>
										<a class="select" href="<?php echo $storage_model->get_edit_link_by_layout( $layout->id ); ?>">
											<?php _e( 'Select', 'codepress-admin-columns' ); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
							<div class="right">
								<span class="toggle"></span>
							</div>
						</div>

						<div class="body">

							<div class="save-message">
								<?php _e( 'Saved', 'codepress-admin-columns' ); ?>
							</div>

							<?php if ( $not_editable ) : ?>
								<div class="error-notice">
									<?php _e( 'This set is loaded via PHP and can therefore not be edited', 'codepress-admin-columns' ); ?>
								</div>
							<?php endif; ?>

							<form method="post">
								<input type="hidden" name="layout_id" value="<?php echo $layout->id; ?>">

								<?php $this->input_rows( $storage_model->key . '-' . $layout->id, $layout, $not_editable ); ?>

							</form>
							<div class="row actions">
								<?php $this->instructions(); ?>

								<?php if ( ! $not_editable ) : ?>
									<input class="save button-primary" type="submit" value="<?php _e( 'Update', 'codepress-admin-columns' ); ?>">
								<?php endif; ?>
								<span class="spinner"></span>
							</div>


						</div>

					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public function layout_help() {
		?>
		<div id="layout-help" style="display:none;">
			<h3><?php _e( 'Sets', 'codepress-admin-columns' ); ?></h3>

			<p>
				<?php _e( "Sets allow users to switch between different column views.", 'codepress-admin-columns' ); ?>
			</p>
			<p>
				<?php _e( "Available sets are selectable from the overview screen. Users can have their own column view preference.", 'codepress-admin-columns' ); ?>
			<p>
			<p>
				<img src="<?php echo CAC_LAY_URL; ?>/assets/images/layout-selector.png"/>
			</p>
			<p>
				<a href="https://www.admincolumns.com/documentation/how-to/make-multiple-column-sets/" target="_blank"><?php _e( 'Online documentation', 'codepress-admin-columns' ); ?></a>
			</p>
		</div>
		<?php
	}
}