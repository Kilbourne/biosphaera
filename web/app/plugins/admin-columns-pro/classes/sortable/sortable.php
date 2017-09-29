<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAC_SC_URL', plugin_dir_url( __FILE__ ) );
define( 'CAC_SC_DIR', plugin_dir_path( __FILE__ ) );

// only run plugin in the admin interface
if ( ! is_admin() ) {
	return false;
}

/**
 * Addon class
 *
 * @since 1.0
 */
class CAC_Addon_Sortable {

	/**
	 * @var CAC_Addon_Pro The single instance of the class
	 * @since 3.7
	 */
	protected static $_instance = null;

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
	 * @since 1.0
	 */
	function __construct() {

		// init addon
		add_action( 'cac/loaded', array( $this, 'init' ) );

		// add column properties
		add_filter( 'cac/column/properties', array( $this, 'set_column_default_properties' ) );

		// add column options
		add_filter( 'cac/column/default_options', array( $this, 'set_column_default_options' ) );

		add_action( "cac/column_types", array( $this, 'set_wp_default_column_options' ), 10, 2 );

		// add setting field
		add_action( 'cac/column/settings_after', array( $this, 'add_settings_field' ), 10 );

		// add setting sort indicator
		add_action( 'cac/column/settings_meta', array( $this, 'add_label_sort_indicator' ), 10 );

		// add general settings
		add_action( 'cac/settings/general', array( $this, 'add_settings' ) );
		add_filter( 'cac/settings/groups', array( $this, 'settings_group' ), 15 );
		add_action( 'cac/settings/groups/row=sorting', array( $this, 'settings_display' ) );

		add_action( 'admin_init', array( $this, 'handle_settings_request' ) );

		// handle reset request
		add_action( 'admin_init', array( $this, 'handle_reset' ) );

		// enable sorting per column
		add_action( "cac/columns", array( $this, 'enable_sorting' ), 10, 2 );
	}

	/**
	 * Enable sorting
	 *
	 * @param CPAC_Column[] $columns
	 *
	 * @since 1.0
	 */
	public function enable_sorting( $columns, $storage_model ) {
		$sortable = $this->get_model( $storage_model->key );

		if ( ! $sortable ) {
			return;
		}

		$sortables = $sortable->get_sortables();

		foreach ( $columns as $column ) {
			if ( in_array( $column->get_type(), $sortables ) ) {
				$column->set_properties( 'is_sortable', true );
			}
		}
	}

	/**
	 * Init Addons
	 *
	 * @since 1.0
	 */
	function init( $cpac ) {
		if ( ! $cpac->is_cac_screen() ) {
			return;
		}

		include_once CAC_SC_DIR . 'classes/model.php';

		foreach ( cpac()->get_storage_models() as $storage_model ) {

			$sortable_model = false;

			if ( $storage_model->subpage ) {
				continue;
			}

			switch ( $storage_model->get_type() ) {

				case 'post' :
					include_once CAC_SC_DIR . 'classes/post.php';
					$sortable_model = new CAC_Sortable_Model_Post( $storage_model );
					break;

				case 'user' :
					include_once CAC_SC_DIR . 'classes/user.php';
					$sortable_model = new CAC_Sortable_Model_User( $storage_model );
					break;

				case 'media' :
					include_once CAC_SC_DIR . 'classes/media.php';
					$sortable_model = new CAC_Sortable_Model_Media( $storage_model );
					break;

				case 'comment' :
					include_once CAC_SC_DIR . 'classes/comment.php';
					$sortable_model = new CAC_Sortable_Model_Comment( $storage_model );
					break;

				case 'link' :
					include_once CAC_SC_DIR . 'classes/link.php';
					$sortable_model = new CAC_Sortable_Model_Link( $storage_model );
					break;
			}

			if ( $sortable_model ) {
				$this->models[ $storage_model->key ] = $sortable_model;
			}
		}

		// Init hooks for columns screen
		if ( $storage_model = $cpac->get_current_storage_model() ) {
			if ( $model = $this->get_model( $storage_model->key ) ) {
				$model->init_hooks();
			}
		}

		// scripts and styles
		if ( $cpac->is_settings_screen() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		}

		else if ( $cpac->is_columns_screen() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'sorting_indicator' ) );
		}
	}

	/**
	 * Adds indicator to sorted heading
	 *
	 * @since 3.8
	 */
	public function sorting_indicator() {
		if ( $storage_model = cpac()->get_current_storage_model() ) {
			if ( $model = $this->get_model( $storage_model->key ) ) {
				$preference = $model->get_sorting_preference();
				if ( $preference && ( $column = $model->get_column_by_orderby( $preference['orderby'] ) ) ) {
					wp_enqueue_script( 'ac-sortable', CAC_SC_URL . "assets/js/sortable.js", array( 'jquery' ), CPAC_VERSION );
					wp_localize_script( 'ac-sortable', 'AC_SORTABLE', array(
						'column_name' => $column->properties->name,
						'order'       => $preference['order'],
					) );
				}
			}
		}
	}

	/**
	 * @since 1.0
	 */
	public function add_settings( $options ) { ?>
		<p>
			<label for="show_all_results">
				<input name="cpac_general_options[show_all_results]" id="show_all_results" type="checkbox" value="1" <?php checked( isset( $options['show_all_results'] ) ? $options['show_all_results'] : '', '1' ); ?>>
				<?php _e( 'Show all results when sorting. Default is <code>off</code>.', 'codepress-admin-columns' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Add Addon to Admin Columns list
	 *
	 * @since 1.0
	 */
	public function add_addon( $addons ) {
		$addons['cac-sortable'] = __( 'Sortable add-on', 'codepress-admin-columns' );

		return $addons;
	}

	/**
	 * @since 1.0
	 */
	public function scripts() {
		wp_enqueue_style( 'cac-addon-sortable-columns-css', CAC_SC_URL . 'assets/css/sortable.css', array(), CAC_PRO_VERSION, 'all' );
	}

	/**
	 * @since 1.0
	 */
	function set_column_default_properties( $properties ) {
		if ( ! isset( $properties['is_sortable'] ) ) {
			$properties['is_sortable'] = false;
		}

		return $properties;
	}

	/**
	 * @since 1.0
	 */
	function set_column_default_options( $options ) {
		if ( ! isset( $options['sort'] ) ) {
			$options['sort'] = 'off';
		}

		return $options;
	}

	/**
	 * @since 3.8.7
	 *
	 * @param CPAC_Column[] $columns
	 * @param $storage_model
	 */
	public function set_wp_default_column_options( $columns, $storage_model ) {
		if ( $sortable = $this->get_model( $storage_model->key ) ) {
			$default_sortables = $sortable->get_default_sortables();
			foreach ( $columns as $column ) {
				if ( in_array( $column->get_type(), (array) $default_sortables ) ) {
					$column->set_options( 'sort', 'on' );
				}
			}
		}
	}

	/**
	 * @since 1.0
	 */
	function add_settings_field( $column ) {

		if ( ! $column->properties->is_sortable ) {
			return false;
		}

		$sort = 'on' === $column->get_option( 'sort' );
		?>
		<tr class="column_sorting">
			<?php $column->label_view( __( 'Enable sorting?', 'codepress-admin-columns' ), __( 'This will make the column support sorting.', 'codepress-admin-columns' ), 'sorting' ); ?>
			<td class="input" data-toggle-id="<?php $column->attr_id( 'sort' ); ?>">
				<label for="<?php $column->attr_id( 'sort' ); ?>-on">
					<input type="radio" value="on" name="<?php $column->attr_name( 'sort' ); ?>" id="<?php $column->attr_id( 'sort' ); ?>-on"<?php checked( $sort, true ); ?>/>
					<?php _e( 'Yes' ); ?>
				</label>
				<label for="<?php $column->attr_id( 'sort' ); ?>-off">
					<input type="radio" value="off" name="<?php $column->attr_name( 'sort' ); ?>" id="<?php $column->attr_id( 'sort' ); ?>-off"<?php checked( $sort, false ); ?> />
					<?php _e( 'No' ); ?>
				</label>
			</td>
		</tr>

		<?php
	}

	/**
	 * Meta Label in the column header
	 *
	 * @since 1.0
	 */
	function add_label_sort_indicator( $column ) {
		if ( $column->properties->is_sortable ) : ?>
			<span class="sorting <?php echo esc_attr( $column->get_option( 'sort' ) ); ?>" data-indicator-id="<?php $column->attr_id( 'sort' ); ?>" title="<?php echo esc_attr( __( 'Enable sorting?', 'codepress-admin-columns' ) ); ?>"></span>
			<?php
		endif;

	}

	public function get_model( $key ) {
		return isset( $this->models[ $key ] ) ? $this->models[ $key ] : false;
	}

	public function settings_group( $groups ) {
		if ( isset( $groups['sorting'] ) ) {
			return $groups;
		}

		$groups['sorting'] = array(
			'title'       => __( 'Sorting Preferences', 'codepress-admin-columns' ),
			'description' => __( 'This will reset the sorting preference for all users.', 'codepress-admin-columns' ),
		);

		return $groups;
	}

	public function settings_display() { ?>
		<form action="" method="post">
			<input type="hidden" name="reset-preference" value="1">
			<?php wp_nonce_field( 'reset-sorting-preference' ); ?>
			<input type="submit" class="button" value="<?php _e( 'Reset sorting preferences', 'codepress-admin-columns' ); ?>">
		</form>
		<?php
	}

	public function handle_settings_request() {
		if ( isset( $_POST['reset-preference'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'reset-sorting-preference' ) && current_user_can( 'manage_admin_columns' ) ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ac_sortedby_%';" );

			cpac_admin_message( __( 'All sorting preferences have been reset.', 'codepress-admin-columns' ) );
		}
	}

	/**
	 * Handle reset request
	 *
	 * @since 1.0
	 */
	public function handle_reset() {
		if ( ! empty( $_REQUEST['reset-sorting'] ) && $storage_model = cpac()->get_current_storage_model() ) {
			if ( $sortable_model = $this->get_model( $storage_model->key ) ) {

				$sortable_model->delete_sorting_preference();

				// redirect back to admin
				$admin_url = trailingslashit( admin_url() ) . $storage_model->page . '.php';
				if ( 'post' == $storage_model->get_type() ) {
					$admin_url = add_query_arg( array( 'post_type' => $storage_model->get_post_type() ), $admin_url );
				}

				wp_safe_redirect( $admin_url );
				exit;
			}
		}
	}
}

function ac_sortable() {
	return CAC_Addon_Sortable::instance();
}

// Global for backwards compatibility.
$GLOBALS['ac_sortable'] = ac_sortable();
