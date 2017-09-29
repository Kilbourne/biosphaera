<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Addon information
define( 'CAC_INLINEEDIT_URL', plugin_dir_url( __FILE__ ) );
define( 'CAC_INLINEEDIT_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Main Inline Edit Addon plugin class
 *
 * @since 1.0
 */
class CACIE_Addon_InlineEdit {

	protected static $_instance = null;

	/**
	 * Admin Columns main plugin class instance
	 *
	 * @since 1.0
	 * @var CPAC
	 */
	public $cpac;

	/**
	 * Main plugin directory
	 *
	 * @since 1.0
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * @since 3.8.4
	 */
	private $models;

	/**
	 * @since 3.7
	 * @return CAC_Addon_Pro|CAC_Addon_Sortable
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	function __construct() {

		$this->plugin_basename = plugin_basename( __FILE__ );

		// add column properties for column types
		add_filter( 'cac/column/properties', array( $this, 'set_column_default_properties' ) );

		// add column options
		add_filter( 'cac/column/default_options', array( $this, 'set_column_default_options' ) );

		// add setting field to column editing box
		add_action( 'cac/column/settings_after', array( $this, 'add_settings_field' ), 8 );

		// add setting editing indicator
		add_action( 'cac/column/settings_meta', array( $this, 'add_label_edit_indicator' ), 8 );

		// add general settings
		add_action( 'cac/settings/general', array( $this, 'add_settings' ) );

		// Save column value from inline edit
		add_action( 'wp_ajax_cacie_column_save', array( $this, 'ajax_column_save' ) );

		// Save user preference of the editability state
		add_action( 'wp_ajax_cacie_editability_state_save', array( $this, 'ajax_editability_state_save' ) );

		// Get options for editable field by ajax
		add_action( 'wp_ajax_cacie_get_options', array( $this, 'ajax_get_options' ) );

		// Add columns to javascript
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 20 );

		// Enable inline edit per column
		add_action( "cac/columns", array( $this, 'enable_inlineedit' ), 10, 2 );
	}

	/**
	 * @since 3.8.4
	 */
	public function get_models() {

		if ( empty( $this->models ) ) {

			// load files
			require_once CAC_INLINEEDIT_DIR . 'inc/roles.php';
			require_once CAC_INLINEEDIT_DIR . 'inc/arrays.php';
			require_once CAC_INLINEEDIT_DIR . 'inc/acf-fieldoptions.php';
			require_once CAC_INLINEEDIT_DIR . 'inc/woocommerce.php';

			// models
			include_once CAC_INLINEEDIT_DIR . 'classes/model.php';

			foreach ( cpac()->get_storage_models() as $storage_model ) {

				if ( $storage_model->subpage ) {
					continue;
				}

				$editable_model = false;

				switch ( $storage_model->get_type() ) {

					case 'post' :
						include_once CAC_INLINEEDIT_DIR . 'classes/post.php';
						$editable_model = new CACIE_Editable_Model_Post( $storage_model );
						break;

					case 'user' :
						include_once CAC_INLINEEDIT_DIR . 'classes/user.php';
						$editable_model = new CACIE_Editable_Model_User( $storage_model );
						break;

					case 'media' :
						include_once CAC_INLINEEDIT_DIR . 'classes/media.php';
						$editable_model = new CACIE_Editable_Model_Media( $storage_model );
						break;

					case 'comment' :
						include_once CAC_INLINEEDIT_DIR . 'classes/comment.php';
						$editable_model = new CACIE_Editable_Model_Comment( $storage_model );
						break;

					case 'taxonomy' :
						include_once CAC_INLINEEDIT_DIR . 'classes/taxonomy.php';
						$editable_model = new CACIE_Editable_Model_Taxonomy( $storage_model );
						break;
				}

				if ( $editable_model ) {
					$this->models[ $storage_model->key ] = $editable_model;
				}
			}
		}

		return $this->models;
	}

	/**
	 * @since 3.8.4
	 */
	public function get_model( $key ) {
		$models = $this->get_models();

		return isset( $models[ $key ] ) ? $models[ $key ] : false;
	}

	/**
	 * @since 3.1.2
	 */
	public function add_settings( $options ) {

		$is_custom_field_editable = isset( $options['custom_field_editable'] ) ? $options['custom_field_editable'] : '';
		?>
		<p>
			<label for="custom_field_editable">
				<input name="cpac_general_options[custom_field_editable]" id="custom_field_editable" type="checkbox" value="1" <?php checked( $is_custom_field_editable, '1' ); ?>>
				<?php _e( 'Enable inline editing for Custom Fields. Default is <code>off</code>', 'codepress-admin-columns' ); ?>
			</label>
			<a href="javascript:;" class="cpac-pointer" rel="acp-custom_field_editable" data-pos="right"><?php _e( 'Instructions', 'codepress-admin-columns' ); ?></a>
		</p>
		<div id="acp-custom_field_editable" style="display:none;">
			<h3><?php _e( 'Notice', 'codepress-admin-columns' ); ?></h3>
			<p>
				<?php _e( 'Inline edit will display all the raw values in an editable text field.', 'codepress-admin-columns' ); ?>
			</p>
			<p>
				<?php _e( 'Except for Checkmark, Media Library, Post Title and Username.', 'codepress-admin-columns' ); ?>
			</p>
			<p>
				<?php printf( __( "Please read <a href='%s'>our documentation</a> if you plan to use these fields.", 'codepress-admin-columns' ), cpac()->settings()->get_url( 'documentation' ) . 'faq/enable-inline-editing-custom-fields/' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Register and enqueue scripts and styles
	 *
	 * @since 1.0
	 */
	public function scripts() {

		$minified = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Column screen
		$storage_model = cpac()->get_current_storage_model();

		if ( $storage_model && ( $editable_model = $this->get_model( $storage_model->key ) ) ) {

			// Libraries
			// TODO: remove bootstrap
			wp_register_script( 'cacie-bootstrap', CAC_INLINEEDIT_URL . 'library/bootstrap/bootstrap.min.js', array( 'jquery' ), CAC_PRO_VERSION );
			wp_register_script( 'cacie-select2', CAC_INLINEEDIT_URL . 'library/select2/select2.min.js', array( 'jquery' ), CAC_PRO_VERSION );
			wp_register_style( 'cacie-select2-css', CAC_INLINEEDIT_URL . 'library/select2/select2.css', array(), CAC_PRO_VERSION );
			wp_register_style( 'cacie-select2-bootstrap', CAC_INLINEEDIT_URL . 'library/select2/select2-bootstrap.css', array(), CAC_PRO_VERSION );
			wp_register_script( 'cacie-bootstrap-editable', CAC_INLINEEDIT_URL . "library/bootstrap-editable/js/bootstrap-editable{$minified}.js", array( 'jquery', 'cacie-bootstrap' ), CAC_PRO_VERSION );
			wp_register_style( 'cacie-bootstrap-editable', CAC_INLINEEDIT_URL . 'library/bootstrap-editable/css/bootstrap-editable.css', array(), CAC_PRO_VERSION );

			// Core
			wp_register_script( 'cacie-xeditable-input-wc-price', CAC_INLINEEDIT_URL . 'assets/js/xeditable/input/wc-price.js', array( 'jquery', 'cacie-bootstrap-editable' ), CAC_PRO_VERSION );
			wp_register_script( 'cacie-xeditable-input-wc-stock', CAC_INLINEEDIT_URL . 'assets/js/xeditable/input/wc-stock.js', array( 'jquery', 'cacie-bootstrap-editable' ), CAC_PRO_VERSION );
			wp_register_script( 'cacie-xeditable-input-wc-usage', CAC_INLINEEDIT_URL . 'assets/js/xeditable/input/wc-usage.js', array( 'jquery', 'cacie-bootstrap-editable' ), CAC_PRO_VERSION );
			wp_register_script( 'cacie-xeditable-input-dimensions', CAC_INLINEEDIT_URL . 'assets/js/xeditable/input/dimensions.js', array( 'jquery', 'cacie-bootstrap-editable' ), CAC_PRO_VERSION );
			wp_register_script( 'cacie-admin-edit', CAC_INLINEEDIT_URL . 'assets/js/admin-edit.js', array( 'jquery', 'cacie-bootstrap-editable', 'cacie-select2', 'cacie-xeditable-input-wc-price', 'cacie-xeditable-input-wc-stock', 'cacie-xeditable-input-wc-usage', 'cacie-xeditable-input-dimensions' ), CAC_PRO_VERSION );
			wp_register_style( 'cacie-admin-edit', CAC_INLINEEDIT_URL . 'assets/css/admin-edit.css', array(), CAC_PRO_VERSION );

			// jQuery
			wp_enqueue_script( 'jquery' );

			// Libraries CSS
			wp_enqueue_style( 'cacie-select2-css' );
			wp_enqueue_style( 'cacie-select2-bootstrap' );
			wp_enqueue_style( 'cacie-bootstrap-editable' );

			// Core
			wp_enqueue_script( 'cacie-admin-edit' );
			wp_enqueue_style( 'cacie-admin-edit' );

			// Translations
			wp_localize_script( 'cacie-admin-edit', 'qie_i18n', array(
				'select_author' => __( 'Select author', 'codepress-admin-columns' ),
				'edit'          => __( 'Edit' ),
				'redo'          => __( 'Redo', 'codepress-admin-columns' ),
				'undo'          => __( 'Undo', 'codepress-admin-columns' ),
				'delete'        => __( 'Delete', 'codepress-admin-columns' ),
				'download'      => __( 'Download', 'codepress-admin-columns' ),
				'errors'        => array(
					'field_required' => __( 'This field is required.', 'codepress-admin-columns' ),
					'invalid_float'  => __( 'Please enter a valid float value.', 'codepress-admin-columns' ),
					'invalid_floats' => __( 'Please enter valid float values.', 'codepress-admin-columns' ),
				),
				'inline_edit'   => __( 'Inline Edit', 'codepress-admin-columns' ),
			) );

			// WP Mediapicker
			wp_enqueue_media();

			// WP Colorpicker
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );

			// Translations
			$locale = substr( get_locale(), 0, 2 );

			// Select 2 translations
			if ( file_exists( CAC_INLINEEDIT_DIR . 'library/select2/select2_locale_' . $locale . '.js' ) ) {
				wp_register_script( 'select2-locale', CAC_INLINEEDIT_URL . 'library/select2/select2_locale_' . $locale . '.js', array( 'jquery' ), CAC_PRO_VERSION );
				wp_enqueue_script( 'select2-locale' );
			}

			// Allow JS to access the column and item data for this storage model on the edit page
			wp_localize_script( 'cacie-admin-edit', 'CACIE_List_Selector', $editable_model->get_list_selector() );
			wp_localize_script( 'cacie-admin-edit', 'CACIE_Columns', $editable_model->get_columns() );
			wp_localize_script( 'cacie-admin-edit', 'CACIE_Items', $editable_model->get_items() );
			wp_localize_script( 'cacie-admin-edit', 'CACIE', array(
				'inline_edit'   => array(
					'active' => $editable_model->get_editability_preference(),
				),
				'layout'        => $storage_model->get_layout(),
				'storage_model' => $storage_model->key,
				'nonce'         => wp_create_nonce( 'ac-editing' ),
			) );

		}

		// Column settings
		else if ( cac_is_setting_screen() ) {
			wp_enqueue_script( 'jquery' );

			wp_register_script( 'cacie-admin-options-admincolumns', CAC_INLINEEDIT_URL . "assets/js/admin-options-admincolumns{$minified}.js", array( 'jquery' ), CAC_PRO_VERSION );
			wp_register_style( 'cacie-admin-options-admincolumns', CAC_INLINEEDIT_URL . "assets/css/admin-options-admincolumns{$minified}.css", array(), CAC_PRO_VERSION );

			wp_enqueue_script( 'cacie-admin-options-admincolumns' );
			wp_enqueue_style( 'cacie-admin-options-admincolumns' );
		}
	}

	/**
	 * Add column type setting defaults
	 *
	 * @since 1.0
	 */
	public function set_column_default_properties( $properties ) {
		if ( ! isset( $properties['is_editable'] ) ) {
			$properties['is_editable'] = false;
		}

		return $properties;
	}

	/**
	 * Add option defaults for columns
	 *
	 * @since 1.0
	 */
	public function set_column_default_options( $options ) {
		$options['edit'] = 'off';
		$options['enable_term_creation'] = 'off';

		return $options;
	}

	/**
	 * Is column editable? ( can be defined using CPAC_Column::is_editable() )
	 *
	 * @since 3.8.6
	 */
	public function is_editable( $column ) {
		return method_exists( $column, 'is_editable' ) ? $column->is_editable() : $column->get_property( 'is_editable' );
	}

	/**
	 * Add settings fields to column edit box
	 *
	 * @since 1.0
	 */
	public function add_settings_field( $column ) {
		if ( ! $this->is_editable( $column ) ) {
			return;
		}
		?>
		<tr class="column_editing">
			<?php $column->label_view( __( 'Enable editing?', 'codepress-admin-columns' ), __( 'This will make the column support inline editing.', 'codepress-admin-columns' ), 'editing' ); ?>
			<td class="input" data-toggle-id="<?php $column->attr_id( 'edit' ); ?>">
				<label for="<?php $column->attr_id( 'edit' ); ?>-on">
					<input type="radio" value="on" name="<?php $column->attr_name( 'edit' ); ?>" id="<?php $column->attr_id( 'edit' ); ?>-on"<?php checked( $column->get_option( 'edit' ), 'on' ); ?> />
					<?php _e( 'Yes' ); ?>
				</label>
				<label for="<?php $column->attr_id( 'edit' ); ?>-off">
					<input type="radio" value="off" name="<?php $column->attr_name( 'edit' ); ?>" id="<?php $column->attr_id( 'edit' ); ?>-off"<?php checked( $column->get_option( 'edit' ), '' ); ?><?php checked( $column->get_option( 'edit' ), 'off' ); ?> />
					<?php _e( 'No' ); ?>
				</label>
			</td>
		</tr>
		<?php

		// Additional settings fields
		switch ( $column->get_type() ) {
			case 'column-taxonomy':
			case 'categories':
			case 'tags':
				$column->display_field_radio(
					'enable_term_creation',
					__( 'Allow creating new terms', 'codepress-admin-columns' ),
					array(
						'on'  => __( 'Yes' ),
						'off' => __( 'No' ),
					),
					'', // description
					'edit' // toggle_id
				);
				break;
		}
	}

	/**
	 * Label in column admin screen column header
	 *
	 * @since 1.0
	 */
	public function add_label_edit_indicator( $column ) {
		if ( $this->is_editable( $column ) ) : ?>
			<span class="editing <?php echo $column->get_option( 'edit' ); ?>" data-indicator-id="<?php $column->attr_id( 'edit' ); ?>" title="<?php echo esc_attr( __( 'Enable editing?', 'codepress-admin-columns' ) ); ?>"></span>
		<?php endif;
	}

	/**
	 * Whether the main plugin is enabled
	 *
	 * @since 1.0
	 *
	 * @return bool Returns true if the main Admin Columns is enabled, false otherwise
	 */
	public function is_cpac_enabled() {
		return class_exists( 'CPAC', false );
	}

	/**
	 * Ajax callback for saving a column
	 *
	 * @since 1.0
	 */
	public function ajax_column_save() {
		check_ajax_referer( 'ac-editing' );

		// Basic request validation
		if ( empty( $_POST['plugin_id'] ) || empty( $_POST['pk'] ) || empty( $_POST['column'] ) ) {
			wp_send_json_error( __( 'Required fields missing.', 'codepress-admin-columns' ) );
		}

		// Get ID of entry to edit
		if ( ! ( $id = intval( $_POST['pk'] ) ) ) {
			wp_send_json_error( __( 'Invalid item ID.', 'codepress-admin-columns' ) );
		}

		$column = cpac()->get_column( $_POST['storage_model'], $_POST['layout'], $_POST['column'] );

		if ( ! $column ) {
			wp_send_json_error( __( 'Invalid column.', 'codepress-admin-columns' ) );
		}

		$value = isset( $_POST['value'] ) ? $_POST['value'] : '';

		/**
		 * Filter for changing the value before storing it to the DB
		 *
		 * @since 3.2.1
		 *
		 * @param mixed $value Value send from inline edit ajax callback
		 * @param object CPAC_Column instance
		 * @param int $id ID
		 */
		$value = apply_filters( 'cac/inline-edit/ajax-column-save/value', $value, $column, $id );

		$editable_model = $this->get_model( $column->get_storage_model_key() );

		// Store column
		$save_result = $editable_model->column_save( $id, $column, $value );

		if ( is_wp_error( $save_result ) ) {
			status_header( 400 );
			echo $save_result->get_error_message();
			exit;
		}

		ob_start();

		$storage_model = $column->get_storage_model();

		// WP default column
		if ( $column->is_default() ) {
			$editable_model->manage_value( $column, $id );
		}

		// Taxonomy
		else if ( 'taxonomy' == $storage_model->get_type() ) {
			echo $storage_model->manage_value( '', $column->get_name(), $id );
		}

		// Custom Admin column
		else {
			echo $storage_model->manage_value( $column->get_name(), $id );
		}

		$contents = ob_get_clean();

		/**
		 * Fires after a inline-edit succesfully saved a value
		 *
		 * @since ????
		 *
		 * @param CPAC_Column $column Column instance
		 * @param int $id Item ID
		 * @param string $value User submitted input
		 * @param object $this CACIE_Editable_Model $editable_model_instance Editability model instance
		 */
		do_action( 'cac/inline-edit/after_ajax_column_save', $column, $id, $value, $editable_model );

		// Some plugins columns are not initialized on an ajax call, therefor we use this specific filter
		$contents = apply_filters( 'cac/editable/after_ajax_column_save/value', $contents, $column, $id );

		$jsondata = array(
			'success' => true,
			'data'    => array(
				'value' => $contents,
			),
		);

		// We don't want a Nullable rawvalue  in our JSON because select2 will break
		$raw_value = $editable_model->get_column_editability_value( $column, $id );
		if ( null !== $raw_value ) {
			$jsondata['data']['rawvalue'] = $raw_value;
		}

		if ( is_callable( array( $column, 'get_item_data' ) ) ) {
			$jsondata['data']['itemdata'] = $column->get_item_data( $id );
		}

		wp_send_json( $jsondata );
	}

	/**
	 * Ajax callback for storing user preference of the default state of editability on an overview page
	 *
	 * @since 3.2.1
	 */
	public function ajax_editability_state_save() {
		check_ajax_referer( 'ac-editing' );

		if ( $editable_model = $this->get_model( $_POST['storage_model'] ) ) {
			$editable_model->update_editability_preference( $_POST['value'] );
		}
		exit;
	}

	/**
	 * AJAX callback for retrieving options for a column
	 * Results can be formatted in two ways: an array of options ([value] => [label]) or
	 * an array of option groups ([group key] => [group]) with [group] being an array with
	 * two keys: label (the label displayed for the group) and options (an array ([value] => [label])
	 * of options)
	 *
	 * @since 1.0
	 *
	 * @return array List of options, possibly grouped
	 */
	public function ajax_get_options() {
		check_ajax_referer( 'ac-editing' );

		if ( empty( $_GET['column'] ) || empty( $_GET['storage_model'] ) || ! isset( $_GET['layout'] ) ) {
			wp_send_json_error( __( 'Invalid request.', 'codepress-admin-columns' ) );
		}

		$column = cpac()->get_column( $_GET['storage_model'], $_GET['layout'], $_GET['column'] );

		if ( ! $column ) {
			wp_send_json_error( __( 'Invalid column.', 'codepress-admin-columns' ) );
		}

		$editable_model = $this->get_model( $column->get_storage_model_key() );

		if ( ! $editable_model ) {
			wp_send_json_error( __( 'Invalid model.', 'codepress-admin-columns' ) );
		}

		$search = filter_input( INPUT_GET, 'searchterm' );

		// Third party
		if ( method_exists( $column, 'get_editable_ajax_options' ) ) {
			$column->set_editable( $editable_model );
			$options = $column->get_editable_ajax_options( $search );
		}

		// Storage model specific
		else {
			$options = $editable_model->get_ajax_options( $column, $search );
		}

		// TODO: refactor
		// For all models
		if ( ! $options ) {

			switch ( $column->get_type() ) {

				// Display Author As
				case 'column-author_name' :
					$display_format = $column->get_option( 'display_author_as' );
					if ( 'first_last_name' == $display_format ) {
						$display_format = array( 'first_name', 'last_name' );
					}
					$options = $editable_model->get_users_options( array(
						'search' => '*' . $search . '*',
					), $display_format );
					break;

				// Custom Field
				case 'column-meta':
					switch ( $column->get_option( 'field_type' ) ) {
						case 'title_by_id':
							$options = $editable_model->get_posts_options( array( 's' => $search ) );
							break;
						case 'user_by_id':
							$options = $editable_model->get_users_options( array(
								'search' => '*' . $search . '*',
							) );
							break;
					}
					break;

				// ACF
				case 'column-acf_field':

					switch ( $column->get_field_type() ) {
						case 'page_link':
						case 'post_object':

							$field = $column->get_field();

							$post_type = 'any';
							if ( ! empty( $field['post_type'] ) ) {
								$post_type = $field['post_type'];
							}

							$options = $editable_model->get_posts_options( array( 's' => $search, 'post_type' => $post_type ) );

							break;
						case 'user':

							$options = $editable_model->get_users_options( array(
								'search' => '*' . $search . '*',
							) );

							break;
					}
					break;

				case 'author':
				case 'column-user': // comment column
					$options = $editable_model->get_users_options( array(
						'search' => '*' . $search . '*',
					) );
					break;

			} // endswitch
		}

		wp_send_json_success( $editable_model->format_options( $options ) );
	}

	/**
	 * @since 3.8.4
	 */
	public function enable_inlineedit( $columns, $storage_model ) {
		if ( $editable_model = $this->get_model( $storage_model->key ) ) {
			$editable_model->enable_inlineedit( $columns );
		}
	}
}

function ac_editable() {
	return CACIE_Addon_InlineEdit::instance();
}

// Global for backwards compatibility.
$GLOBALS['ac_editable'] = ac_editable();