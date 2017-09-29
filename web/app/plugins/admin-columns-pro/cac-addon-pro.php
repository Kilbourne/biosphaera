<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * The Admin Columns Pro plugin class
 *
 * @since 1.0
 */
final class CAC_Addon_Pro {

	/**
	 * Basename of the plugin, retrieved through plugin_basename function
	 *
	 * @since 1.0
	 * @access private
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * License manager class instance
	 *
	 * @since 1.0
	 * @access private
	 * @var Codepress_Licence_Manager_Settings
	 */
	private $licence_manager;

	/**
	 * @since 3.6
	 */
	private $network_settings_page;

	private $_import_export;

	/**
	 * @since 3.8
	 */
	protected static $_instance = null;

	/**
	 * @since 3.8
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

		$this->plugin_basename = plugin_basename( __FILE__ );

		$this->define_constants();

		// Load modules
		$this->init();

		// 3rd party integrations
		$this->third_party();

		// Hooks
		add_action( 'init', array( $this, 'localize' ) );
		add_action( 'cac/loaded', array( $this, 'init_after_cac_loaded' ) );
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 1, 2 );
		add_action( 'wp_loaded', array( $this, 'after_setup' ) );
		add_action( 'network_admin_menu', array( $this, 'network_settings_menu' ) );
	}

	/**
	 * Define constants
	 *
	 * @since 3.1.2
	 */
	public function define_constants() {

		define( 'CAC_PRO_VERSION', ACP_VERSION );
		define( 'CAC_PRO_URL', plugin_dir_url( __FILE__ ) );
		define( 'CAC_PRO_DIR', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Handle localization
	 *
	 * @since 1.0.1
	 * @uses load_plugin_textdomain()
	 */
	public function localize() {

		load_plugin_textdomain( 'codepress-admin-columns', false, dirname( $this->plugin_basename ) . '/languages/' );
	}

	/**
	 * Fire callbacks for admin columns setup completion
	 *
	 * @since 2.2
	 */
	public function after_setup() {

		/**
		 * Fires when Admin Columns is fully loaded
		 * Use this for setting up addon functionality
		 *
		 * @since 2.0
		 *
		 * @param CPAC $cpac_instance Main Admin Columns plugin class instance
		 */
		do_action( 'cac/pro/loaded', $this );
	}

	/**
	 * General plugin initialization, loading plugin module files
	 *
	 * @since 1.0
	 */
	public function init() {

		if ( ! class_exists( 'CAC_Addon_Filtering', false ) ) {
			include_once CAC_PRO_DIR . 'classes/filtering/filtering.php';
		}

		if ( ! class_exists( 'CAC_Addon_Sortable', false ) ) {
			include_once CAC_PRO_DIR . 'classes/sortable/sortable.php';
		}

		if ( ! class_exists( 'CAC_Storage_Model_Taxonomy', false ) ) {
			include_once CAC_PRO_DIR . 'classes/taxonomy/taxonomy.php';
		}

		if ( ! class_exists( 'CPAC_Storage_Model_MS_User', false ) ) {
			include_once CAC_PRO_DIR . 'classes/ms-user/ms-user.php';
		}

		if ( ! class_exists( 'CACIE_Addon_InlineEdit', false ) ) {
			include_once CAC_PRO_DIR . 'classes/inline-edit/cac-addon-inline-edit.php';
		}

		if ( ! class_exists( 'CACIE_Addon_Columns', false ) ) {
			include_once CAC_PRO_DIR . 'classes/columns/cac-addon-columns.php';
		}

		if ( ! class_exists( 'CAC_Export_Import', false ) ) {
			include_once CAC_PRO_DIR . 'classes/export-import/export-import.php';
			$this->_import_export = new CAC_Export_Import();
		}
	}

	/**
	 * Load third party add-ons
	 *
	 * @since 3.4.1
	 */
	public function third_party() {
		include_once CAC_PRO_DIR . 'classes/third-party/bbpress.php';
		include_once CAC_PRO_DIR . 'classes/third-party/pods.php';
		include_once CAC_PRO_DIR . 'classes/third-party/wordpress-seo.php';
	}

	/**
	 * Init callback after main plugin (CPAC) has been fully loaded.
	 *
	 * @since 1.0
	 */
	public function init_after_cac_loaded( $cpac ) {

		if ( ! class_exists( 'Codepress_Licence_Manager_Settings', false ) ) {

			include_once CAC_PRO_DIR . 'classes/licence-manager-settings.php';

			// When used into Admin Columns Pro use it's root path...
			$this->licence_manager = new Codepress_Licence_Manager_Settings( ACP_FILE, $cpac, $this );

			if ( defined( 'ACP_LICENCE' ) ) {
				$this->licence_manager->set_licence_key( ACP_LICENCE );
			}

			include_once 'classes/layouts/layouts.php';
			$this->profiles = new CAC_Layouts( $cpac );
		}
	}

	/**
	 * @since 3.8
	 */
	public function import_export() {
		return $this->_import_export;
	}

	/**
	 * @since 1.0
	 * @see filter:plugin_action_links
	 */
	public function add_settings_link( $links, $file ) {

		if ( ( ! $this->is_cpac_enabled() ) || ( $file != plugin_basename( __FILE__ ) ) ) {
			return $links;
		}

		array_unshift( $links, '<a href="' . admin_url( "options-general.php" ) . '?page=codepress-admin-columns&tab=settings">' . __( 'Settings' ) . '</a>' );

		return $links;
	}

	/**
	 * Check if main plugin is enabled
	 *
	 * @since 1.0.3
	 */
	public function is_cpac_enabled() {
		return class_exists( 'CPAC', false );
	}

	/**
	 * Get licence manager
	 *
	 * @since 3.1.1
	 */
	public function get_licence_manager() {
		return $this->licence_manager;
	}

	/**
	 * @since 3.6
	 */
	public function get_network_settings_page() {

		return $this->network_settings_page;
	}

	/**
	 * Add network settings page
	 *
	 * @since 3.6
	 */
	public function network_settings_menu() {

		$this->network_settings_page = add_submenu_page( 'settings.php', __( 'Admin Columns Settings', 'codepress-admin-columns' ), __( 'Admin Columns', 'codepress-admin-columns' ), 'manage_admin_columns', 'codepress-admin-columns', array(
			$this,
			'network_display'
		) );
	}

	/**
	 * Displays network settings page
	 *
	 * @since 3.6
	 */
	public function network_display() {

		if ( $groups = apply_filters( 'cac/network_settings/groups', array() ) ) : ?>
			<div id="cpac" class="wrap">
				<h1>Admin Columns</h1>

				<table class="form-table cpac-form-table settings">
					<tbody>

					<?php
					if ( $groups ) :
						foreach ( $groups as $id => $group ) :

							$defaults = array(
								'title' => '',
								'description' => '',
							);

							$group    = (object) array_merge( $defaults, $group );
							?>
							<tr>
								<th scope="row">
									<h3><?php echo $group->title; ?></h3>

									<p><?php echo $group->description; ?></p>
								</th>
								<td class="padding-22">
									<?php
									// Use this Hook to add additonal fields to the group
									do_action( "cac/settings/groups/row={$id}" );
									?>
								</td>
							</tr>
							<?php
						endforeach;
					endif;
					?>

					</tbody>
				</table>

			</div>

			<?php
		endif;
	}
}

function ac_pro() {
	return CAC_Addon_Pro::instance();
}

ac_pro();