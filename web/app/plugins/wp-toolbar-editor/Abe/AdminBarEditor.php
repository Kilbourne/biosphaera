<?php
class Abe_AdminBarEditor {
	const PLUGIN_NAME = 'WordPress Toolbar Editor';
	const PLUGIN_MENU_TITLE = 'Toolbar Editor';

    const ADMIN_BAR_FILTER_PRIORITY = 50000;
	const MAX_IMPORT_FILE_SIZE = 5242880; //5 MB

	/** @const string Per-site option that specifies whether to use a site-specific config instead of the global one. */
	const MENU_SCOPE_OVERRIDE_OPTION = 'ws_abe_override_global_menu';

	/** @var string Database option that will be used to store plugin settings. */
	protected $optionName = 'ws_abe_admin_bar_settings';
	/** @var array|null */
	protected $settings = null;

	/** @var string Database option that will store the custom admin bar menu. */
	protected $menuOptionName = 'ws_abe_admin_bar_nodes';

	/** @var string Admin page slug. */
	protected $pageSlug = 'ws-admin-bar-editor';

	/** @var StdClass[] Default admin bar configuration. Each node is a plain object (WP format). */
	protected $defaultNodes = array();

	/** @var Abe_Node[] Current admin bar configuration with custom settings and defaults merged. */
	protected $mergedNodes = array();

	/** @var array Query arguments. */
	protected $get;
	/** @var array POST fields. */
	protected $post;
	protected $originalPost;

	protected $updateChecker = null;

	/** @var Wslm_LicenseManagerClient  */
	protected $ameLicenseManager = null;

    public function __construct() {
		if ( is_admin() ) {
			$this->loadSettings();
		}

		//Capture request arguments before WP has had a chance to apply magic quotes.
		$this->get = $_GET;
		$this->post = $this->originalPost = $_POST;
		if ( function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ) {
			$this->post = stripslashes_deep($this->post);
			$this->get = stripslashes_deep($this->get);
		}

        add_action('wp_before_admin_bar_render', array($this, 'filterAdminBar'), self::ADMIN_BAR_FILTER_PRIORITY);
        add_action('admin_menu', array($this, 'addEditorPage'));

		if ( !defined('IS_DEMO_MODE') && !defined('IS_MASTER_MODE') ) {
			//Add-ons are updated separately from the main plugin, but use the same license details.
			require WS_ADMIN_BAR_EDITOR_DIR . '/includes/plugin-updates/plugin-update-checker.php';
			$this->updateChecker = PucFactory::buildUpdateChecker(
				'http://adminmenueditor.com/?get_metadata_for=wp-toolbar-editor',
				WS_ADMIN_BAR_EDITOR_FILE,
				'wp-toolbar-editor',
				12,
				'ws_abe_external_updates' //Set the option name explicitly so that we can delete it when uninstalling.
			);

			if ( isset($GLOBALS['ameProLicenseManager']) ) {
				$this->ameLicenseManager = $GLOBALS['ameProLicenseManager'];

				$this->updateChecker->addQueryArgFilter(array($this, 'filterUpdateChecks'));

				$downloadFilter = array($this, 'filterUpdateDownloadUrl');
				$this->updateChecker->addFilter('request_info_result', $downloadFilter, 20);
				$this->updateChecker->addFilter('pre_inject_update', $downloadFilter);
				$this->updateChecker->addFilter('pre_inject_info', $downloadFilter);
			}
		}
    }

    public function filterAdminBar() {
		//Get the admin bar instance.
		global $wp_admin_bar; /** @var WP_Admin_Bar $wp_admin_bar */
		if ( !isset($wp_admin_bar) ) {
			return; //This should never happen, but lets not crash if it does.
		}
		$adminBar = $wp_admin_bar;

		$visibleDefaultNodes = $adminBar->get_nodes();
		//Some buggy themes (e.g. Avada 3.6.2) trigger admin bar rendering *twice*. The list of nodes
		//will be null the second time around. That's fine - we already processed it once.
		if ( $visibleDefaultNodes === null ) {
			return;
		}

		$this->defaultNodes = $visibleDefaultNodes;
		$customNodes = $this->loadCustomMenu();

		//Some admin bar items - like "Edit Post" - are only created in specific contexts, and they
		//won't exist when the user opens the editor. We still want the user to be able to edit those
		//items, though, so we'll need to register them manually.
		//(Only do it on the editor page for performance.)
		if ( $this->isEditorPage() ) {
			$this->defaultNodes = $this->addAllContextualNodes($this->defaultNodes);
		}

		//Merge existing admin bar items with our custom configuration.
		$this->mergedNodes = $customNodes;
		foreach($this->defaultNodes as $wpNode) {
			if ( isset($this->mergedNodes[$wpNode->id]) ) {
				$node = $this->mergedNodes[$wpNode->id];
				$node->setDefaultsFromNodeArgs($wpNode);
			} else {
				$node = Abe_Node::fromNodeArgs($wpNode);
				$this->mergedNodes[$node->id] = $node;
			}
		}

		//Get the current user's roles to determine which items they can see.
		$currentActor = array();
		$user = wp_get_current_user();
		if ( isset($user, $user->roles) && is_array($user->roles) ) {
			foreach($user->roles as $role) {
				$currentActor[] = 'role:' . $role;
			}
		}
		if ( is_multisite() && is_super_admin() ) {
			$currentActor[] = 'special:super_admin';
		}

		//Apply the custom configuration.
		foreach($this->mergedNodes as $node) {
			if ( !$node->isVisibleTo($currentActor) ) {
				$adminBar->remove_node($node->id);
			} else if ( $node->is_custom || isset($visibleDefaultNodes[$node->id]) ) {
				$adminBar->add_node($node->toNodeArgs());
			}
		}

		//Sort admin bar in the user-specified order.
		$this->setAdminBarOrder($adminBar, $this->mergedNodes);
    }

	/**
	 * Load the custom admin bar menu for the current site.
	 *
	 * @return Abe_Node[] List of admin bar nodes.
	 */
	protected function loadCustomMenu() {
		$this->loadSettings();

		if ( $this->shouldUseSiteSpecificMenu() ) {
			$nodes = get_option($this->menuOptionName, array());
		} else {
			$nodes = isset($this->settings['nodes']) ? $this->settings['nodes'] : array();
		}
		$nodes = array_map(array('Abe_Node', 'fromArray'), $nodes);
		return $nodes;
	}

	/**
	 * Set the custom admin bar menu.
	 *
	 * This method will update either the global menu or the per-site menu option
	 * depending on how the plugin is configured for the current site.
	 *
	 * @param array|Abe_Node[] $nodes
	 */
	protected function saveCustomMenu($nodes) {
		if ( !empty($nodes) && (reset($nodes) instanceof Abe_Node) ) {
			foreach($nodes as $index => $node) {
				$nodes[$index] = $node->toArray();
			}
		}

		if ( $this->shouldUseSiteSpecificMenu() ) {
			update_option($this->menuOptionName, $nodes);
		} else {
			$this->loadSettings();
			$this->settings['nodes'] = $nodes;
			$this->saveSettings();
		}
	}

	/**
	 * Determine if we should use a site-specific admin bar menu configuration
	 * for the current site, or fall back to the global config.
	 *
	 * @return bool True = use the site-specific config (if any), false = use the global config.
	 */
	protected function shouldUseSiteSpecificMenu() {
		//If this is a single-site WP installation then there's really
		//no difference between "site-specific" and "global".
		if ( !is_multisite() ) {
			return false;
		}

		$this->loadSettings();
		return ($this->settings['menu_config_scope'] === 'site') || get_option(self::MENU_SCOPE_OVERRIDE_OPTION, false);
	}

	/**
	 * Load plugin settings.
	 *
	 * Note that plugin settings are conceptually distinct from the actual
	 * admin bar configuration. Use loadCustomMenu() to load that instead.
	 *
	 * @param bool $forceReload
	 */
	protected function loadSettings($forceReload = false) {
		if ( isset($this->settings) && !$forceReload ) {
			return;
		}

		$settings = get_site_option($this->optionName, array());
		if ( !is_array($settings) ) {
			$settings = array();
		}
		$defaults = array(
			'menu_config_scope' => 'global', //'global' or 'site'
			'nodes' => array(),
			'plugin_access' => 'super_admin',
			'allowed_user_id' => null,
		);
		$this->settings = array_merge($defaults, $settings);
	}

	protected function saveSettings() {
		update_site_option($this->optionName, $this->settings);
	}

	/**
	 * Sort admin bar nodes according to a list of IDs.
	 *
	 * This method will re-arrange the admin bar to match the key order of the $order array.
	 * Any nodes that don't have a matching key will be moved to the end of the admin bar.
	 *
	 * @param WP_Admin_Bar $adminBar
	 * @param array $order An array indexed by node ID.
	 */
	protected function setAdminBarOrder($adminBar, $order) {
		//Unfortunately, WP_Admin_Bar has no "sort" or "move_node" method, and it is not possible
		//to add one because the $nodes array is private. So we'll have to do this the hard way.
		$nodes = $adminBar->get_nodes();

		//1. Remove all nodes.
		foreach($nodes as $wpNode) {
			$adminBar->remove_node($wpNode->id);
		}

		//2. Add them back in the right order.
		foreach($order as $id => $unusedValue) {
			if ( isset($nodes[$id]) ) { //Hidden nodes have been removed by this point.
				$wpNode = $nodes[$id];
				$adminBar->add_node($wpNode);
				unset($nodes[$id]);
			}
		}

		//3. Add back any left-over nodes (theoretically, this should never happen).
		if ( !empty($nodes) ) {
			foreach($nodes as $wpNode) {
				$adminBar->add_node($wpNode);
			}
		}
	}

	public function addEditorPage() {
		if ( $this->userCanAccessPlugin() ) {
			$page = add_options_page(
				self::PLUGIN_NAME,
				self::PLUGIN_MENU_TITLE,
				'manage_options', //Should we use a different cap if access is restricted to a specific user?
				$this->pageSlug,
				array($this, 'doAdminPage')
			);

			add_action('admin_print_scripts-' . $page, array($this, 'enqueueScripts'));
			add_action('admin_print_styles-' . $page, array($this, 'enqueueStyles'));
		}
	}

	/**
	 * Check if the current user can access this plugin.
	 *
	 * @return bool
	 */
	protected function userCanAccessPlugin() {
		$this->loadSettings();
		$access = $this->settings['plugin_access'];

		if ( $access === 'super_admin' ) {
			return is_super_admin();
		} else if ( $access === 'specific_user' ) {
			return get_current_user_id() == $this->settings['allowed_user_id'];
		} else {
			return current_user_can($access);
		}
	}

	public function doAdminPage() {
		if ( !$this->userCanAccessPlugin() ) {
			wp_die(sprintf(
				'You do not have sufficient permissions to edit the WordPress Toolbar. Required: <code>%s</code>.',
				htmlentities($this->settings['plugin_access'])
			));
		}

		//Dispatch form action.
		if ( isset($this->post['action']) ) {
			$action = strval($this->post['action']);
			check_admin_referer($action);

			if ( $action == 'save_menu' ) {
				$this->actionSaveMenu();
			} else if ( $action == 'export_menu' ) {
				$this->actionExportMenu();
			} else if ( $action == 'import_menu' ) {
				$this->actionImportMenu();
			} else if ( $action == 'save_settings' ) {
				$this->actionSaveSettings();
			}
		}

		$hideDemoNotice = isset($_COOKIE['abe_hide_demo_notice']) && !empty($_COOKIE['abe_hide_demo_notice']);
		if ( $this->isDemoMode() && !$hideDemoNotice ) {
			$this->displayDemoNotice();
		}

		$subSection = isset($this->get['sub_section']) ? $this->get['sub_section'] : null;
		if ( $subSection == 'settings' ) {
			$this->displaySettingsPage();
		} else {
			$this->displayEditorPage();
		}
	}

	public function displayEditorPage() {
		//These variables are used by the editor page template.
		$currentConfiguration = Abe_Node::nodeListToArray($this->mergedNodes);
		$defaultConfiguration = Abe_Node::nodeListToArray($this->defaultNodes);

		$imagesUrl = esc_attr(plugins_url('images/', WS_ADMIN_BAR_EDITOR_FILE));
		$pageSlug = $this->pageSlug;
		$settingsPageUrl = $this->getSettingsPageUrl();

		$actors = array();
		foreach(self::getRoleNames() as $roleId => $name) {
			$actors['role:' . $roleId] = $name;
		};
		asort($actors);

		if ( function_exists('is_multisite') && is_multisite() ) {
			$actors['special:super_admin'] = 'Super Admin';
		}

		require WS_ADMIN_BAR_EDITOR_DIR . '/templates/editor-page.php';
	}

	protected function getEditorPageUrl() {
		return admin_url(
			add_query_arg(
				array('page' => $this->pageSlug),
				'options-general.php'
			)
		);
	}

	protected function actionSaveMenu() {
		$newNodes = json_decode($this->post['nodes'], true);
		if ( empty($newNodes) ) {
			$newNodes = json_decode($this->originalPost['nodes'], true);
		}

		if ( empty($newNodes) ) {
			$debugData = '';
			$debugData .= "Original POST:\n" . print_r($this->originalPost, true) . "\n\n";
			$debugData .= "Processed:\n" . print_r($this->post, true) . "\n\n";
			$debugData .= "\$_POST:\n" . print_r($_POST, true);

			$debugData = sprintf(
				"<textarea rows=\"30\" cols=\"100\">%s</textarea>",
				htmlentities($debugData)
			);
			wp_die('Invalid node data. Send this debugging information to the developer: <br>' . $debugData);
		}

		$this->saveCustomMenu($newNodes);

		wp_redirect(admin_url(
			add_query_arg(
				array(
					'page' => $this->pageSlug,
					'updated' => 1,
				),
				'options-general.php'
			)
		));
	}

	protected function actionExportMenu() {
		if ( !isset($this->post['export_data']) || empty($this->post['export_data']) ) {
			die("Error: The 'export_data' field is empty or missing.");
		}

		$exportData = $this->post['export_data'];

		//Include the blog's domain name in the export filename to make it easier to
		//distinguish between multiple export files.
		$domain = @parse_url(site_url(), PHP_URL_HOST);
		if ( empty($domain) ) {
			$domain = '';
		}

		$exportFileName = trim(sprintf(
			'%s toolbar (%s).json',
			$domain,
			date('Y-m-d')
		));

		//Force file download
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="' . $exportFileName . '"');
		header("Content-Type: application/force-download");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . strlen($exportData));

		/* The three lines below basically make the download non-cacheable */
		header("Cache-control: private");
		header("Pragma: private");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

		echo $exportData;
		exit();
	}

	protected function actionImportMenu() {
		//An import file must be specified.
		if ( !isset($_FILES['import_file']) ) {
			$this->outputJsonForJqueryForm(array('error' => 'No file specified.'));
			exit;
		}

		//Check for upload errors.
		if ( !empty($_FILES['import_file']['error']) ) {
			$this->outputJsonForJqueryForm(array(
				'error' => 'File upload failed. Error code: ' . $_FILES['import_file']['error']
			));
			exit;
		}

		//Sanity-check the file size. I expect import files will not exceed 1 MB in practice.
		$size = filesize($_FILES['import_file']['tmp_name']);
		if ( $size > self::MAX_IMPORT_FILE_SIZE ) {
			$this->outputJsonForJqueryForm(array('error' => 'File too large.'));
			exit;
		} else if ( $size == 0 ) {
			$this->outputJsonForJqueryForm(array('error' => 'You can not import an empty file.'));
			exit;
		}

		//Validate the file contents. It must be a valid JSON document.
		$importData = file_get_contents($_FILES['import_file']['tmp_name']);
		$json = json_decode($importData);
		if ( $json === null ) {
			$this->outputJsonForJqueryForm(array('error' => 'Unknown file format. This is not a valid JSON document.'));
			exit;
		}

		$this->outputJsonForJqueryForm($json);
		die();
	}

	protected function actionSaveSettings() {
		$this->loadSettings();

		//Plugin access setting.
		$validAccessSettings = array('super_admin', 'manage_options', 'specific_user');
		if ( isset($this->post['plugin_access']) && in_array($this->post['plugin_access'], $validAccessSettings) ) {
			$this->settings['plugin_access'] = $this->post['plugin_access'];

			if ( $this->settings['plugin_access'] === 'specific_user' ) {
				$this->settings['allowed_user_id'] = get_current_user_id();
			} else {
				$this->settings['allowed_user_id'] = null;
			}
		}

		//Configuration scope.
		$validScopes = array('global', 'site');
		if ( isset($this->post['menu_config_scope']) && in_array($this->post['menu_config_scope'], $validScopes) ) {
			$this->settings['menu_config_scope'] = $this->post['menu_config_scope'];

			//On multisite it is also possible to override the global toolbar
			//configuration on a per-site basis.
			if ( $this->settings['menu_config_scope'] === 'global' ) {
				$override = isset($this->post['override_scope']) && !empty($this->post['override_scope']);
				if ( $override ) {
					update_option(self::MENU_SCOPE_OVERRIDE_OPTION, true);
				} else {
					delete_option(self::MENU_SCOPE_OVERRIDE_OPTION);
				}
			}
		}

		$this->saveSettings();
		wp_redirect(add_query_arg('updated', 1, $this->getSettingsPageUrl()));
	}

	/**
	 * Utility method that outputs data in a format suitable to the jQuery Form plugin.
	 *
	 * Specifically, the docs recommend enclosing JSON data in a <textarea> element if
	 * the request was not sent by XMLHttpRequest. This is because the plugin uses IFrames
	 * in older browsers, which supposedly causes problems with JSON responses.
	 *
	 * @param mixed $data Response data. It will be encoded as JSON and output to the browser.
	 */
	private function outputJsonForJqueryForm($data) {
		$response = json_encode($data);

		$isXhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		if ( !$isXhr ) {
			$response = '<textarea>' . $response . '</textarea>';
		}

		echo $response;
	}

	public function enqueueScripts() {
		wp_register_auto_versioned_script(
			'knockout',
			plugins_url('js/knockout-2.2.1.js', WS_ADMIN_BAR_EDITOR_FILE)
		);
		wp_register_auto_versioned_script(
			'jquery-json',
			plugins_url('js/jquery.json-2.4.js', WS_ADMIN_BAR_EDITOR_FILE),
			array('jquery')
		);
		wp_register_auto_versioned_script(
			'jquery-ajax-form',
			plugins_url('js/jquery.form.js', WS_ADMIN_BAR_EDITOR_FILE),
			array('jquery')
		);
		wp_register_auto_versioned_script(
			'jquery-qtip2',
			plugins_url('js/qtip/jquery.qtip.min.js', WS_ADMIN_BAR_EDITOR_FILE),
			array('jquery')
		);
		wp_register_auto_versioned_script(
			'mjs-jquery-nested-sortable',
			plugins_url('js/jquery.mjs.nestedSortable.js', WS_ADMIN_BAR_EDITOR_FILE),
			array('jquery-ui-sortable')
		);
		wp_register_auto_versioned_script(
			'ws-abe-nested-sortable',
			plugins_url('js/knockout-nested-sortable.js', WS_ADMIN_BAR_EDITOR_FILE),
			array('knockout', 'mjs-jquery-nested-sortable')
		);

        wp_register_auto_versioned_script(
            'ws-abe-node-view-model',
            plugins_url('js/node-view-model.js', WS_ADMIN_BAR_EDITOR_FILE),
            array('knockout', 'jquery')
        );

		wp_register_auto_versioned_script(
			'ws-abe-settings-script',
			plugins_url('js/settings-page.js', WS_ADMIN_BAR_EDITOR_FILE),
			array('jquery')
		);

		if ( $this->isEditorPage() ) {
			wp_enqueue_auto_versioned_script(
				'ws-admin-bar-editor',
				plugins_url('js/admin-bar-editor.js', WS_ADMIN_BAR_EDITOR_FILE),
				array(
					'jquery', 'knockout', 'jquery-json', 'jquery-ajax-form', 'jquery-ui-dialog',
					'mjs-jquery-nested-sortable', 'ws-abe-nested-sortable', 'ws-abe-node-view-model',
					'jquery-qtip2',
				)
			);
		} else if ( $this->isSettingsPage() ) {
			wp_enqueue_script('ws-abe-settings-script');
		}

		if ( $this->isDemoMode() ) {
			wp_register_auto_versioned_script(
				'ws-abe-jquery-cookie',
				plugins_url('js/jquery-cookie/jquery.cookie.js', WS_ADMIN_BAR_EDITOR_FILE)
			);
			wp_enqueue_auto_versioned_script(
				'ws-abe-demo-helper',
				plugins_url('js/demo.js', WS_ADMIN_BAR_EDITOR_FILE),
				array('ws-abe-jquery-cookie')
			);
		}
	}

	public function enqueueStyles() {
		wp_register_auto_versioned_style(
			'abe-jquery-ui',
			plugins_url('css/smoothness/jquery-ui.min.css', WS_ADMIN_BAR_EDITOR_FILE)
		);
		wp_register_auto_versioned_style(
			'abe-jquery-ui-theme',
			plugins_url('css/smoothness/jquery.ui.theme.css', WS_ADMIN_BAR_EDITOR_FILE),
			array('abe-jquery-ui')
		);
		wp_register_auto_versioned_style(
			'jquery-qtip2-styles',
			plugins_url('js/qtip/jquery.qtip.min.css', WS_ADMIN_BAR_EDITOR_FILE)
		);

		wp_enqueue_auto_versioned_style(
			'ws-admin-bar-editor-style',
			plugins_url('css/admin-bar-editor.css', WS_ADMIN_BAR_EDITOR_FILE),
			array('abe-jquery-ui', 'abe-jquery-ui-theme', 'jquery-qtip2-styles')
		);
	}

	/**
	 * Check if the current page is the "Toolbar Editor" admin page.
	 *
	 * @return bool
	 */
	protected function isEditorPage() {
		return is_admin()
			&& isset($this->get['page']) && ($this->get['page'] == $this->pageSlug)
			&& ( !isset($this->get['sub_section']) || empty($this->get['sub_section']) );
	}

	/**
	 * Check if the current page is the "Settings" sub-section of our admin page.
	 *
	 * @return bool
	 */
	protected function isSettingsPage() {
		return is_admin()
			&& isset($this->get['sub_section']) && ($this->get['sub_section'] == 'settings')
			&& isset($this->get['page']) && ($this->get['page'] == $this->pageSlug);
	}

	public function displaySettingsPage() {
		$this->loadSettings();

		//These variables are used by the template.
		$settings = $this->settings;
		$editorPageUrl = $this->getEditorPageUrl();
		$settingsPageUrl = $this->getSettingsPageUrl();

		require WS_ADMIN_BAR_EDITOR_DIR . '/templates/settings-page.php';
	}

	protected function getSettingsPageUrl() {
		return add_query_arg(
			array('sub_section' => 'settings'),
			$this->getEditorPageUrl()
		);
	}

	protected function addAllContextualNodes($defaultNodes) {
		//Most of these represent menus that get created in /wp-includes/admin-bar.php.
		$extraNodes = array(
			array(
				'after' => 'logout',
				'parent' => 'top-secondary',
				'id'     => 'search',
				'title'  => '[Search Form]',
				'meta'   => array(
					'class'    => 'admin-bar-search',
					'tabindex' => -1,
				)
			),

			array(
				'after' => 'new-content',
				'id' => 'view',
				'title' => 'View Item',
				'href' => '[post or page URL]'
			),

			array(
				'after' => 'new-content',
				'id' => 'edit',
				'title' => 'Edit Item',
				'href'  => '[post editor URL]'
			),

			array(
				'parent' => 'site-name',
				'id'     => 'dashboard',
				'title'  => __('Dashboard'),
				'href'   => admin_url(),
			),

			array(
				'after' => 'dashboard',
				'parent' => 'site-name',
				'id' => 'appearance',
				'group' => true,
			),

			array(
				'after' => 'site-name',
				'id'    => 'updates',
				'title' => 'Updates',
				'href'  => network_admin_url('update-core.php'),
				'meta'  => array(
					'title' => '[update count]',
				),
			),

			array( 'parent' => 'appearance', 'id' => 'themes', 'title' => __('Themes'), 'href' => admin_url('themes.php') ),

			array(
				'parent' => 'appearance',
				'id'     => 'customize',
				'title'  => __('Customize'),
				'href'   => '[theme customizer]',
				'meta'   => array(
					'class' => 'hide-if-no-customize',
				),
			),

			array( 'parent' => 'appearance', 'id' => 'widgets',	'title' => __('Widgets'), 'href' => admin_url('widgets.php') ),
			array( 'parent' => 'appearance', 'id' => 'menus', 'title' => __('Menus'), 'href' => admin_url('nav-menus.php') ),
			array( 'parent' => 'appearance', 'id' => 'background', 'title' => __('Background'), 'href' => admin_url('themes.php?page=custom-background') ),
			array( 'parent' => 'appearance', 'id' => 'header', 'title' => __('Header'), 'href' => admin_url('themes.php?page=custom-header') ),
		);

		foreach($extraNodes as $node) {
			if ( !isset($defaultNodes[$node['id']]) ) {
				$after = isset($node['after']) ? $node['after'] : null;
				unset($node['after']);
				$node = (object) $node;

				if ( $after !== null ) {
					$defaultNodes = $this->insertAfter(
						$defaultNodes,
						$after,
						array($node->id => $node)
					);
				} else {
					$defaultNodes[$node->id] = $node;
				}
			}
		}

		return $defaultNodes;
	}

	/**
	 * Insert one or more elements into an associative array after a specific key.
	 *
	 * If the input array does not contain the specified key this function
	 * will simply append the new elements to the end of the array.
	 *
	 * @param array $input
	 * @param string $key Insert items after this key.
	 * @param array $insert The list of items to insert into the array.
	 * @return array Modified input array.
	 */
	protected function insertAfter($input, $key, $insert) {
		$index = array_search($key, array_keys($input));
		if ( $index === false ) {
			return array_merge($input, $insert);
		}

		return array_slice($input, 0, $index + 1, true)
			+ $insert
			+ array_slice($input, $index + 1, null, true);
	}

	/**
	 * Add AME Pro license data to update requests.
	 *
	 * @param array $queryArgs
	 * @return array
	 */
	public function filterUpdateChecks($queryArgs) {
		if ( $this->ameLicenseManager->getSiteToken() !== null ) {
			$queryArgs['license_token'] = $this->ameLicenseManager->getSiteToken();
		}
		$queryArgs['license_site_url'] = $this->ameLicenseManager->getSiteUrl();
		return $queryArgs;
	}

	/**
	 * Add license data to the update download URL if we have a valid license,
	 * or remove the URL (thus disabling one-click updates) if we don't.
	 *
	 * @param PluginUpdate|PluginInfo $pluginInfo
	 * @return PluginUpdate|PluginInfo
	 */
	public function filterUpdateDownloadUrl($pluginInfo) {
		if ( isset($pluginInfo, $pluginInfo->download_url) && !empty($pluginInfo->download_url) ) {
			$license = $this->ameLicenseManager->getLicense();
			if ( $license->isValid() ) {
				//Append license data to the download URL so that the server can verify it.
				$args = array_filter(array(
					'license_key' => $this->ameLicenseManager->getLicenseKey(),
					'license_token' => $this->ameLicenseManager->getSiteToken(),
					'license_site_url' => $this->ameLicenseManager->getSiteUrl(),
				));
				$pluginInfo->download_url = add_query_arg($args, $pluginInfo->download_url);
			} else {
				//No downloads without a license!
				$pluginInfo->download_url = null;
			}
		}
		return $pluginInfo;
	}

	/**
	 * Get a list of all roles defined on this site.
	 *
	 * @return array Associative array of role names indexed by role ID/slug.
	 */
	private static function getRoleNames() {
		global $wp_roles;
		if ( !isset($wp_roles) ) {
			$wp_roles = new WP_Roles();
		}

		$roles = array();
		if ( isset($wp_roles->roles) ) {
			foreach($wp_roles->roles as $role_id => $role){
				$roles[$role_id] = $role['name'];
			}
		}

		return $roles;
	}

	private function isDemoMode() {
		return defined('IS_DEMO_MODE') && constant('IS_DEMO_MODE');
	}

	private function displayDemoNotice() {
		printf(
			'<div class="updated" id="abe-demo-notice">
			 <p>
				<a href="http://adminmenueditor.com/toolbar-editor/">Toolbar Editor</a>
				is an optional add-on that is included for free
				with the "Business" license. You can also purchase it separately.

			  	&mdash; <a href="#" id="ws-abe-hide-demo-notice">Hide this notice.</a>
			  </p>
			  </div>'
		);
	}
}

