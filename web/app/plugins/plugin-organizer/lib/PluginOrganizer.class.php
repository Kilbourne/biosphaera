<?php
require_once('PO_Template.class.php');
require_once('PO_Ajax.class.php');

class PluginOrganizer {
	var $pluginPageActions = "1";
	var $regex, $absPath, $urlPath, $nonce, $ajax, $tpl, $pluginDirPath;

	function __construct($mainFile) {
		$this->pluginDirPath = $this->get_plugin_dir();
		$this->absPath = $this->pluginDirPath . "/" . plugin_basename(dirname($mainFile));
		$this->urlPath = plugins_url("", $mainFile);
		$this->regex = array(
			"permalink" => "/^((https?):((\/\/)|(\\\\))+[\w\d:#@%\/;$()~_?\+-=\\\.&]*)$/",
			"PO_group_name" => "/^[A-Za-z0-9_\-]+$/",
			"default" => "/^(.|\\n)*$/"
		);
		$this->ajax = new PO_Ajax($this);
		$this->tpl = new PO_Template($this);
		$this->addHooks($mainFile);
	}

	function get_plugin_dir() {
		return preg_replace('/\\' . DIRECTORY_SEPARATOR . 'plugin-organizer\\' . DIRECTORY_SEPARATOR . 'lib$/', '', dirname(__FILE__));
	}
	
	function addHooks($mainFile) {
		$this->ajax = new PO_Ajax($this);

		register_activation_hook($mainFile,array($this, 'activate'));
		register_deactivation_hook($mainFile, array($this, 'deactivate'));
		
		add_action( 'activated_plugin', array($this, 'activated_plugin' ), 10, 2 );
		add_action( 'deactivated_plugin', array($this, 'deactivated_plugin' ), 10, 2 );
		add_action('admin_menu', array($this, 'check_version'), 9);

		if (!is_network_admin()) {
			add_action('admin_init', array($this, 'register_admin_style'));
			add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_style'));
			add_action('init', array($this, 'init'));
			add_filter('views_plugins', array($this, 'add_group_views'));
			add_action('admin_menu', array($this, 'admin_menu'), 9);
			
			add_action('all_plugins', array($this, 'get_requested_group'));
			
			add_action('admin_menu', array($this, 'disable_plugin_box'));
			add_action('save_post', array($this, 'save_post_meta_box'));
			
			add_action('delete_post', array($this, 'delete_plugin_lists'));
			add_action('pre_current_active_plugins', array($this, 'recreate_plugin_order'));
			add_action('activated_plugin', array($this, 'recreate_plugin_order'));

			add_action('manage_plugins_columns', array($this, 'get_column_headers'));
			add_filter('manage_plugins_custom_column', array($this, 'set_custom_column_values'), 10, 3);
			add_action('manage_edit-plugin_filter_columns', array($this, 'get_pf_column_headers'));
			add_filter('manage_plugin_filter_posts_custom_column', array($this, 'set_pf_custom_column_values'), 10, 3);
			add_filter('gettext', array($this, 'change_page_title'), 10, 2);
			add_filter('title_save_pre', array($this, 'change_plugin_filter_title'));
			add_action('init', array($this, 'register_type'));
			add_action('init', array($this, 'register_taxonomy'));
			add_filter('post_updated_messages', array($this, 'custom_updated_messages'));
			add_filter('transition_post_status', array($this, 'update_post_status'), 10, 3);
			
			add_filter("manage_edit-plugin_filter_sortable_columns", array($this, 'plugin_filter_sort'));
			add_action('admin_notices', array($this, 'add_ajax_notices'));
			
			##Ajax functions
			add_action('wp_ajax_PO_plugin_organizer', array($this->ajax, 'save_order'));
			add_action('wp_ajax_PO_create_new_group', array($this->ajax, 'create_group'));
			add_action('wp_ajax_PO_delete_group', array($this->ajax, 'delete_group'));
			add_action('wp_ajax_PO_remove_plugins_from_group', array($this->ajax, 'remove_plugins_from_group'));
			add_action('wp_ajax_PO_add_to_group', array($this->ajax, 'add_to_group'));
			add_action('wp_ajax_PO_edit_plugin_group_name', array($this->ajax, 'edit_plugin_group_name'));
			add_action('wp_ajax_PO_save_global_plugins', array($this->ajax, 'save_global_plugins'));
			add_action('wp_ajax_PO_save_search_plugins', array($this->ajax, 'save_search_plugins'));
			add_action('wp_ajax_PO_get_pt_id_list', array($this->ajax, 'get_pt_id_list'));
			add_action('wp_ajax_PO_save_pt_plugins', array($this->ajax, 'save_pt_plugins'));
			add_action('wp_ajax_PO_get_pt_plugins', array($this->ajax, 'get_pt_plugins'));
			add_action('wp_ajax_PO_reset_pt_settings', array($this->ajax, 'reset_pt_settings'));
			add_action('wp_ajax_PO_redo_permalinks', array($this->ajax, 'redo_permalinks'));
			add_action('wp_ajax_PO_manage_mu_plugin', array($this->ajax, 'manage_mu_plugin'));
			add_action('wp_ajax_PO_reset_to_default_order', array($this->ajax, 'reset_plugin_order'));
			add_action('wp_ajax_PO_submit_mobile_user_agents', array($this->ajax, 'save_mobile_user_agents'));
			add_action('wp_ajax_PO_disable_admin_notices', array($this->ajax, 'disable_admin_notices'));
			add_action('wp_ajax_PO_submit_admin_css_settings', array($this->ajax, 'submit_admin_css_settings'));
			add_action('wp_ajax_PO_reset_post_settings', array($this->ajax, 'reset_post_settings'));
			add_action('wp_ajax_PO_submit_gen_settings', array($this->ajax, 'save_gen_settings'));
			add_action('wp_ajax_PO_get_plugin_group_container', array($this->ajax, 'get_plugin_group_container'));
			add_action('wp_ajax_PO_get_group_list', array($this->ajax, 'get_group_list'));
		}
	}
	
	function check_version() {
		global $pagenow;
		##Check version and activate if needed.
		if (get_option("PO_version_num") != "8.1" && !in_array($pagenow, array("plugins.php", "update-core.php", "update.php"))) {
			$this->activate();
		}
	}
	
	function init() {
		global $wpdb;
		
		##Create nonce
		$this->nonce = wp_create_nonce(plugin_basename(__FILE__));
		
		$this->check_version();


		##Check for posts that have been deleted
		if (false === get_site_transient('PO_delete_missing_posts')) {
			$allPostsQuery = "SELECT DISTINCT(post_id) FROM ".$wpdb->prefix."po_plugins WHERE post_id != 0";
			$allPosts = $wpdb->get_results($allPostsQuery, ARRAY_A);
			foreach ($allPosts as $post) {
				if (false === get_post_status($post['post_id'])) {
					$deletePluginQuery = "DELETE FROM ".$wpdb->prefix."po_plugins WHERE post_id = %d";
					$wpdb->query($wpdb->prepare($deletePluginQuery, $post['post_id']));
				}
			}
			set_site_transient('PO_delete_missing_posts', 1, 604800);
		}

		if (is_multisite() && get_option('PO_order_access_net_admin') == 1 && !current_user_can('manage_network')) {
			$this->pluginPageActions = "0";
		}
	}

	function verify_nonce($nonce) {
		return wp_verify_nonce( $nonce, plugin_basename(__FILE__) );
	}
	
	function move_old_posts($oldPosts) {
		global $wpdb;
		foreach($oldPosts as $post) {
			$enabledMobilePlugins = get_post_meta($post->ID, '_PO_enabled_mobile_plugins', $single=true);
			$disabledMobilePlugins = get_post_meta($post->ID, '_PO_disabled_mobile_plugins', $single=true);
			$enabledPlugins = get_post_meta($post->ID, '_PO_enabled_plugins', $single=true);
			$disabledPlugins = get_post_meta($post->ID, '_PO_disabled_plugins', $single=true);
			$children = get_post_meta($post->ID, '_PO_affect_children', $single=true);
			
			$secure=0;
			if (preg_match('/^.{1,5}:\/\//', get_post_meta($post->ID, '_PO_permalink', $single=true), $matches)) {
				switch ($matches[0]) {
					case "https://":
						$secure=1;
						break;
					default:
						$secure=0;
				}
			}
			
			$permalink = preg_replace('/^.{1,5}:\/\//', '', get_post_meta($post->ID, '_PO_permalink', $single=true));
			
			$splitPermalink = explode('?', $permalink);
			$permalinkNoArgs = $splitPermalink[0];

			$dirCount = substr_count($permalink, "/");
			
			$wpdb->insert($wpdb->prefix."po_plugins", array("enabled_mobile_plugins"=>serialize($enabledMobilePlugins), "disabled_mobile_plugins"=>serialize($disabledMobilePlugins), "enabled_plugins"=>serialize($enabledPlugins), "disabled_plugins"=>serialize($disabledPlugins), "post_type"=>get_post_type($post->ID), "permalink"=>$permalink, "permalink_hash"=>md5($permalinkNoArgs), "permalink_hash_args"=>md5($permalink), "children"=>$children, "secure"=>$secure, "post_id"=>$post->ID, "post_priority"=>0, "dir_count"=>$dirCount));
		}
		update_option('PO_old_posts_moved', 1);
		

		delete_post_meta_by_key('_PO_affect_children');
		delete_post_meta_by_key('_PO_disabled_plugins');
		delete_post_meta_by_key('_PO_enabled_plugins');
		delete_post_meta_by_key('_PO_disabled_mobile_plugins');
		delete_post_meta_by_key('_PO_enabled_mobile_plugins');
		delete_post_meta_by_key('_PO_permalink');
	}
	
	function activate() {
		global $wpdb;
		##Remove the capital letters from the plugins table if it already exists.
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."PO_plugins'") == $wpdb->prefix."PO_plugins" && $wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."po_plugins'") != $wpdb->prefix."po_plugins") {
			$wpdb->query("RENAME TABLE ".$wpdb->prefix."PO_plugins TO ".$wpdb->prefix."po_plugins");
		}
		
		$poPluginTableSQL = "CREATE TABLE ".$wpdb->prefix."po_plugins (
			pl_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			permalink longtext NOT NULL,
			permalink_hash varchar(32) NOT NULL default '',
			permalink_hash_args varchar(32) NOT NULL default '',
			post_type varchar(20) NOT NULL default '',
			status varchar(20) NOT NULL default 'publish',
			secure int(1) NOT NULL default 0,
			children int(1) NOT NULL default 0,
			pt_override int(1) NOT NULL default 0,
			disabled_plugins longtext NOT NULL,
			enabled_plugins longtext NOT NULL,
			disabled_mobile_plugins longtext NOT NULL,
			enabled_mobile_plugins longtext NOT NULL,
			disabled_groups longtext NOT NULL,
			enabled_groups longtext NOT NULL,
			disabled_mobile_groups longtext NOT NULL,
			enabled_mobile_groups longtext NOT NULL,
			post_priority int(3) NOT NULL default 0,
			dir_count int(3) NOT NULL default 0,
			PRIMARY KEY (pl_id),
			KEY PO_post_id (post_id),
			KEY PO_permalink_hash (permalink_hash),
			KEY PO_permalink_hash_args (permalink_hash_args)
			);";
		if($wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."po_plugins'") != $wpdb->prefix."po_plugins") {
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($poPluginTableSQL);
		}

		$primaryKey = $wpdb->get_row("SHOW KEYS FROM ".$wpdb->prefix."po_plugins WHERE key_name = 'PRIMARY'", ARRAY_A);
		if ($primaryKey['Column_name'] == 'post_id') {
			$deletePrimaryResult = $wpdb->query("ALTER TABLE ".$wpdb->prefix."po_plugins DROP PRIMARY KEY");
		}
		
		//Add new columns to po_plugins table
		$showColumnSql = "SHOW COLUMNS FROM ".$wpdb->prefix."po_plugins";
		$showColumnResults = $wpdb->get_results($showColumnSql);
		$newColumns = array(
			'pt_override' => array(0, 'int(1) NOT NULL default 0'),
			'disabled_groups' => array(0, 'longtext NOT NULL'),
			'enabled_groups' => array(0, 'longtext NOT NULL'),
			'disabled_mobile_groups' => array(0, 'longtext NOT NULL'),
			'enabled_mobile_groups' => array(0, 'longtext NOT NULL'),
			'post_priority' => array(0, 'int(3) NOT NULL default 0'),
			'dir_count' => array(0, 'int(3) NOT NULL default 0'),
			'pl_id' => array(0, 'bigint(20) unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT')
		);
		foreach ($showColumnResults as $column) {
			if (array_key_exists($column->Field, $newColumns)) {
				$newColumns[$column->Field][0] = 1;
			}
		}

		foreach ($newColumns as $column=>$value) {
			if ($value[0] == 0) {
				$addColumnSql = "ALTER TABLE ".$wpdb->prefix."po_plugins ADD COLUMN " . $column . " " . $value[1] . ";";
				$addColumnResult = $wpdb->query($addColumnSql);
			}
		}
		
		$newIndex = array(
			'PO_post_id' => 'post_id',
			'PO_permalink_hash' => 'permalink_hash',
			'PO_permalink_hash_args' => 'permalink_hash_args'
		);
		
		foreach ($newIndex as $index=>$value) {
			$checkIndexSql = "SHOW INDEX FROM ".$wpdb->prefix."po_plugins WHERE key_name = '".$index."';";
			$checkIndexResult = $wpdb->query($checkIndexSql);
			if ($checkIndexResult == '0') {
				$addIndexSql = "ALTER TABLE ".$wpdb->prefix."po_plugins ADD INDEX ".$index." (".$value.");";
				$addIndexResult = $wpdb->query($addIndexSql);
			}
		}
		
		##Cleanup from previous versions
		delete_option('PO_old_posts_moved');
		delete_option('PO_old_urls_moved');
		delete_option('PO_old_groups_moved');
		delete_option('PO_preserve_settings');
		delete_option('PO_group_members_corrected');

		$postTypeSupport = get_option("PO_custom_post_type_support");
		if (!is_array($postTypeSupport)) {
			$postTypeSupport = array('plugin_filter');
		} else {
			$postTypeSupport[] = 'plugin_filter';
		}
		
		$existingPosts = get_posts(array('posts_per_page' => -1, 'post_type'=>$postTypeSupport, 'meta_key'=>'_PO_permalink'));
		if (sizeof($existingPosts) > 0) {
			$this->move_old_posts($existingPosts);
		}
		
		if (!file_exists(WPMU_PLUGIN_DIR)) {
			@mkdir(WPMU_PLUGIN_DIR);
		}

		if (file_exists(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
			@unlink(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
		}
		
		if (file_exists($this->pluginDirPath . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php")) {
			@copy($this->pluginDirPath . "/" . plugin_basename(dirname(__FILE__)) . "/PluginOrganizerMU.class.php", WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
		}
		
		if (!is_array(get_option("PO_custom_post_type_support"))) {
			update_option("PO_custom_post_type_support", array("post", "page"));
		}
		
		if (get_option('PO_fuzzy_url_matching') == "") {
			update_option('PO_fuzzy_url_matching', "1");
		}
		
		if (get_option('PO_disable_plugins') == "2") {
			update_option('PO_disable_plugins', 1);
		}
		
		if (get_option("PO_version_num") != "8.1") {
			update_option("PO_version_num", "8.1");
		}

		if (get_option('PO_mobile_user_agents') == '' || (is_array(get_option('PO_mobile_user_agents')) && sizeof(get_option('PO_mobile_user_agents')) == 0)) {
			update_option('PO_mobile_user_agents', array('mobile', 'bolt', 'palm', 'series60', 'symbian', 'fennec', 'nokia', 'kindle', 'minimo', 'netfront', 'opera mini', 'opera mobi', 'semc-browser', 'skyfire', 'teashark', 'uzard', 'android', 'blackberry', 'iphone', 'ipad'));
		}

		//Update dir_count on all saved posts
		$savedPosts = $wpdb->get_results("SELECT pl_id, permalink FROM ".$wpdb->prefix."po_plugins", ARRAY_A);
		
		foreach($savedPosts as $savedPost) {
			$dirCount = substr_count($savedPost['permalink'], "/");
			$wpdb->update($wpdb->prefix."po_plugins", array("dir_count"=>$dirCount), array("pl_id"=>$savedPost['pl_id']));
		}
			
		//Add capabilities to the administrator role
		$administrator = get_role( 'administrator' );
		if ( is_object($administrator) ) {			
			$administrator->add_cap('edit_plugin_filter');
			$administrator->add_cap('edit_plugin_filters');
			$administrator->add_cap('edit_private_plugin_filters');
			$administrator->add_cap('delete_plugin_filter');
			$administrator->add_cap('delete_plugin_filters');
			$administrator->add_cap('edit_others_plugin_filters');
			$administrator->add_cap('read_plugin_filters');
			$administrator->add_cap('read_private_plugin_filters');
			$administrator->add_cap('publish_plugin_filters');
			$administrator->add_cap('delete_others_plugin_filters');
			$administrator->add_cap('delete_published_plugin_filters');
			$administrator->add_cap('delete_private_plugin_filters');
			$administrator->add_cap('edit_filter_group');
			$administrator->add_cap('manage_filter_groups');
			

			$administrator->add_cap('edit_plugin_group');
			$administrator->add_cap('edit_plugin_groups');
			$administrator->add_cap('edit_private_plugin_groups');
			$administrator->add_cap('delete_plugin_group');
			$administrator->add_cap('delete_plugin_groups');
			$administrator->add_cap('edit_others_plugin_groups');
			$administrator->add_cap('read_plugin_groups');
			$administrator->add_cap('read_private_plugin_groups');
			$administrator->add_cap('publish_plugin_groups');
			$administrator->add_cap('delete_others_plugin_groups');
			$administrator->add_cap('delete_published_plugin_groups');
			$administrator->add_cap('delete_private_plugin_groups');
		}

		//Make sure all active plugins are valid
		$activePlugins = $this->get_active_plugins();
		$newActivePlugins = array();
		$pluginDisabled = 0;
		foreach ($activePlugins as $key=>$plugin) {
			if (file_exists($this->pluginDirPath . "/" . $plugin)) {
				$newActivePlugins[] = $plugin;
			} else {
				$pluginDisabled = 1;
			}
		}
		if ($pluginDisabled == 1) {
			update_option("active_plugins", $newActivePlugins);
		}
	}
	
	function deactivate() {
		update_option("PO_disable_plugins", 2);
		

		$administrator = get_role( 'administrator' );
		if ( is_object($administrator) ) {			
			$administrator->remove_cap('edit_plugin_filter');
			$administrator->remove_cap('edit_plugin_filters');
			$administrator->remove_cap('edit_private_plugin_filters');
			$administrator->remove_cap('delete_plugin_filter');
			$administrator->remove_cap('delete_plugin_filters');
			$administrator->remove_cap('edit_others_plugin_filters');
			$administrator->remove_cap('read_plugin_filters');
			$administrator->remove_cap('read_private_plugin_filters');
			$administrator->remove_cap('publish_plugin_filters');
			$administrator->remove_cap('delete_others_plugin_filters');
			$administrator->remove_cap('delete_published_plugin_filters');
			$administrator->remove_cap('delete_private_plugin_filters');
			$administrator->remove_cap('edit_filter_group');
			$administrator->remove_cap('manage_filter_groups');

			$administrator->remove_cap('edit_plugin_group');
			$administrator->remove_cap('edit_plugin_groups');
			$administrator->remove_cap('edit_private_plugin_groups');
			$administrator->remove_cap('delete_plugin_group');
			$administrator->remove_cap('delete_plugin_groups');
			$administrator->remove_cap('edit_others_plugin_groups');
			$administrator->remove_cap('read_plugin_groups');
			$administrator->remove_cap('read_private_plugin_groups');
			$administrator->remove_cap('publish_plugin_groups');
			$administrator->remove_cap('delete_others_plugin_groups');
			$administrator->remove_cap('delete_published_plugin_groups');
			$administrator->remove_cap('delete_private_plugin_groups');
		}
	}
	
	function create_default_group() {
		$post_id = wp_insert_post(array('post_title'=>"Default", 'post_type'=>'plugin_group', 'post_status'=>'publish'));
		if (!is_wp_error($post_id)) {
			update_post_meta($post_id, '_PO_group_members', array());
		}
		update_option("PO_default_group", $post_id);
	}
	
	function validate_field($fieldname) {
		if (isset($_POST[$fieldname])) {
			if (isset($this->regex[$fieldname]) && preg_match($this->regex[$fieldname], $_POST[$fieldname])) {
				return true;
			} else if (preg_match($this->regex['default'], $_POST[$fieldname])) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function admin_menu() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$this->tpl = new PO_Template($this);
			
			$plugin_page=add_menu_page( 'Plugin Organizer', 'Plugin Organizer', 'activate_plugins', 'Plugin_Organizer', array($this->tpl, 'settings_page'), 'dashicons-PO-icon-puzzle-piece');
			
			$settings_page=add_submenu_page('Plugin_Organizer', 'Settings', 'Settings', 'activate_plugins', 'Plugin_Organizer', array($this->tpl, 'settings_page'));
			add_action('admin_print_styles-'.$settings_page, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$settings_page, array($this->tpl, 'settings_page_js'));
			add_action('admin_head-'.$settings_page, array($this->tpl, 'common_js'));

			$plugin_page=add_submenu_page('Plugin_Organizer', 'Global Plugins', 'Global Plugins', 'activate_plugins', 'PO_global_plugins', array($this->tpl, 'global_plugins_page'));
			add_action('admin_print_styles-'.$plugin_page, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'global_plugins_js'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'common_js'));
			
			$plugin_page=add_submenu_page('Plugin_Organizer', 'Search Plugins', 'Search Plugins', 'activate_plugins', 'PO_search_plugins', array($this->tpl, 'search_plugins_page'));
			add_action('admin_print_styles-'.$plugin_page, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'search_plugins_js'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'common_js'));

			$plugin_page=add_submenu_page('Plugin_Organizer', 'Post Type Plugins', 'Post Type Plugins', 'activate_plugins', 'PO_pt_plugins', array($this->tpl, 'pt_plugins_page'));
			add_action('admin_print_styles-'.$plugin_page, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'pt_plugins_js'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'common_js'));

			$plugin_page=add_submenu_page('Plugin_Organizer', 'Group And Order Plugins', 'Group And Order Plugins', 'activate_plugins', 'PO_group_and_order_plugins', array($this->tpl, 'group_and_order_plugins_page'));
			add_action('admin_print_styles-'.$plugin_page, array($this->tpl, 'admin_css'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'group_and_order_plugins_js'));
			add_action('admin_head-'.$plugin_page, array($this->tpl, 'common_js'));
			
			add_submenu_page('Plugin_Organizer', 'Filter Groups', 'Filter Groups', 'activate_plugins', 'edit-tags.php?taxonomy=filter_group&post_type=plugin_filter');
		}

	}

	function register_admin_style() {
		wp_register_style('PO-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
		wp_register_style('PO-icons', $this->urlPath . '/css/PO-icons.css');
		wp_register_style('PO-admin', $this->urlPath . '/css/PO-admin.css');
		wp_register_style('PO-colorpicker', $this->urlPath . '/css/colorpicker.css');
		
		wp_register_script('PO-colorpicker', $this->urlPath . '/js/colorpicker.js');
	}

	function enqueue_admin_style() {
		wp_enqueue_style('PO-font-awesome');
		wp_enqueue_style('PO-icons');
		wp_enqueue_style('PO-colorpicker');
		wp_enqueue_script('PO-colorpicker');
	}
		
	function add_ajax_notices() {
		?>
		<div id="PO-ui-notices" title="Basic dialog">
			<div id="PO-ajax-notices-container">
			</div>
		</div>
		<?php
	}
	
	function admin_notices() {
		global $pagenow;
		$errMsg = $this->check_mu_plugin();
		
		if ($errMsg != '') {
			?>
			<div class="updated" id="PO_admin_notices">
				<h2>Plugin Organizer is not set up correctly.</h2>
				<?php _e( $errMsg, 'plugin-organizer' ); ?>
				<a href="#" id="PO_disable_admin_notices">Disable admin notices</a> - You will still recieve admin notices when you visit the Plugin Organizer settings page.
			</div>
			<script type="text/javascript" language="javascript">
				jQuery('#PO_disable_admin_notices').click(function() {
					jQuery.post(encodeURI(ajaxurl + '?action=PO_disable_admin_notices'), {PO_nonce: '<?php print $this->nonce; ?>'}, function (result) {
						jQuery('#PO_admin_notices').remove();
					});
					return false;
				});
			</script>
			<?php
		}
	}
	
	function check_mu_plugin() {
		$muPlugins = get_mu_plugins();
		if (!isset($muPlugins['PluginOrganizerMU.class.php']['Version'])) {
			return "<p>You are missing the MU Plugin.  Please use the tool provided on the settings page to move the plugin into place or manually copy ".$this->absPath."/lib/PluginOrganizerMU.class.php to ".WPMU_PLUGIN_DIR."/PluginOrganizerMU.class.php.  If you don't do this the plugin will not work.  This message will disappear when everything is correct.</p>";
		} else if (isset($muPlugins['PluginOrganizerMU.class.php']['Version']) && $muPlugins['PluginOrganizerMU.class.php']['Version'] != get_option("PO_version_num")) {
			return "<p>You are running an old version of the MU Plugin.  Please use the tool provided on the settings page to move the updated version into place or manually copy ".$this->absPath."/lib/PluginOrganizerMU.class.php to ".WPMU_PLUGIN_DIR."/PluginOrganizerMU.class.php.  If you don't do this the plugin will not work.  This message will disappear when everything is correct.</p>";
		} else {
			return "";
		}
	}
	
	function add_group_views($views) {
		$groups = get_posts(array('post_type'=>'plugin_group', 'posts_per_page'=>-1));
		if (!array_key_exists('all', $views)) {
			$views = array_reverse($views, true);
			$views['all'] = '<a href="'.get_admin_url().'plugins.php?plugin_status=all">All <span class="count">('.count(get_plugins()).')</span></a>';
			$views = array_reverse($views, true);
		}
		foreach ($groups as $group) {
			$groupMembers = $this->get_group_members($group->ID);
			if (isset($groupMembers[0]) && $groupMembers[0] != 'EMPTY') {
				$groupCount = sizeof($groupMembers);
			} else {
				$groupCount = 0;
			}
			$groupName = $group->post_title;
			$loopCount = 0;
			while(array_key_exists($groupName, $views) && $loopCount < 10) {
				$groupName = $group->post_title.$loopCount;
				$loopCount++;
			}
			$views[$groupName] = '<a href="'.get_admin_url().'plugins.php?PO_group_view='.$group->ID.'">'.$group->post_title.' <span class="count">('.$groupCount.')</span></a> ';
		}
		return $views;
	}
	
	function get_group_members($groupID) {
		$groupMembers = get_post_meta($groupID, '_PO_group_members', $single=true);
		$allPlugins = get_plugins();
		$groupCount = sizeof($groupMembers);

		if (isset($groupMembers[0]) && $groupMembers[0] != 'EMPTY') {
			foreach($groupMembers as $key=>$memberPlugin) {
				if (!array_key_exists($memberPlugin, $allPlugins)) {
					unset($groupMembers[$key]);
				}
			}
			if (sizeof($groupMembers) != $groupCount) {
				update_post_meta($groupID, '_PO_group_members', $groupMembers);
			}
		}

		return($groupMembers);
	}
	
	function create_plugin_lists($pluginList, $pluginExludeList) {
		$returnArray = array(array(), array());
		if (is_array($pluginList)) {
			foreach ($pluginList as $plugin) {
				if (!in_array($plugin, $pluginExludeList)) {
					$returnArray[0][] = $plugin;
				}
			}

			foreach ($pluginExludeList as $plugin) {
				if (!in_array($plugin, $pluginList)) {
					$returnArray[1][] = $plugin;
				}
			}
		} else {
			foreach ($pluginExludeList as $plugin) {
				$returnArray[1][] = $plugin;
			}
		}
		return $returnArray;
	}
	
	function get_active_plugins() {
		global $PluginOrganizerMU;
		if (is_object($PluginOrganizerMU)) {
			remove_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);
		}
		
		$plugins = get_option("active_plugins");
		
		#print_r($plugins);
		$networkPlugins = get_site_option('active_sitewide_plugins');
		if (is_array($networkPlugins)) {
			$networkPluginMissing = 0;
			foreach($networkPlugins as $key=>$pluginFile) {
				if (array_search($key, $plugins) === FALSE && file_exists($this->pluginDirPath . "/" . $key)) {
					$plugins[] = $key;
					$networkPluginMissing = 1;
				}
			}
			#print_r($plugins);
			if ($networkPluginMissing == 1) {
				update_option("active_plugins", $plugins);
			}
		}
		
		if (is_object($PluginOrganizerMU)) {
			add_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);
		}
		
		return $plugins;
	}
	
	function get_requested_group($allPluginList) {
		if (isset($_REQUEST['PO_group_view']) && is_numeric($_REQUEST['PO_group_view'])) {
			$plugins = $this->get_active_plugins();
		
			$activePlugins = Array();
			$newPluginList = Array();
			$activePluginOrder = Array();
			
			$members = $this->get_group_members($_REQUEST['PO_group_view']);
			$members = stripslashes_deep($members);
			foreach ($allPluginList as $key=>$val) {
				if (is_array($members) && in_array($key, $members)) {
					$activePlugins[$key] = $val;
					$activePluginOrder[] = array_search($key, $plugins);
				}
			}
			array_multisort($activePluginOrder, $activePlugins);
			$newPluginList = $activePlugins;
		} else {
			$newPluginList = $allPluginList;
		}
		return $newPluginList;
	}
	
	function reorder_plugins($allPluginList) {
		global $pagenow;
		$plugins = $this->get_active_plugins();
		
		
		if (is_admin() && $this->pluginPageActions == 1 && in_array($pagenow, array("plugins.php"))) {
			$perPage = get_user_option("plugins_per_page");
			if (!is_numeric($perPage)) {
				$perPage = 999;
			}
			if (sizeOf($plugins) > $perPage) {
				$this->pluginPageActions = 0;
				return $allPluginList;
			}
		}
		$activePlugins = Array();
		$inactivePlugins = Array();
		$newPluginList = Array();
		$activePluginOrder = Array();
		
		foreach ($allPluginList as $key=>$val) {
			if (in_array($key, $plugins)) {
				$activePlugins[$key] = $val;
				$activePluginOrder[] = array_search($key, $plugins);
			} else {
				$inactivePlugins[$key] = $val;
			}
		}
		array_multisort($activePluginOrder, $activePlugins);
		
		$newPluginList = array_merge($activePlugins, $inactivePlugins);	
		return $newPluginList;
	}


	function get_pf_column_headers($columns) {
		$columns['PO_PF_permalink'] = __('Permalinks');
		return $columns;
	}

	function set_pf_custom_column_values($column_name, $post_id ) {
		global $wpdb;
		switch ($column_name) {
			case 'PO_PF_permalink' :
				$postSettingsQuery = "SELECT permalink FROM ".$wpdb->prefix."po_plugins WHERE post_id = %d";
				$permalinks = $wpdb->get_results($wpdb->prepare($postSettingsQuery, $post_id), ARRAY_A);
				if (sizeof($permalinks) > 0) {
					foreach($permalinks as $permalink) {
						print $permalink['permalink'] . "<br />";
					}
				}
				break;
			default:
		}
	}
	
	
	function get_column_headers($columns) {
		$count = 0;
		$columns['PO_groups'] = __('Groups');
		return $columns;
	}

	function set_custom_column_values($column_name, $pluginPath, $plugin ) {
		switch ($column_name) {
			case 'PO_groups' :
				$groups = get_posts(array('post_type'=>'plugin_group', 'posts_per_page'=>-1));
				$assignedGroups = "";
				foreach ($groups as $group) {
					$members = $this->get_group_members($group->ID);
					$members = stripslashes_deep($members);
					if (is_array($members) && array_search($pluginPath, $members) !== FALSE) {
						$assignedGroups .= '<a href="'.get_admin_url().'plugins.php?PO_group_view='.$group->ID.'">'.$group->post_title.'</a> ,';
					}
				}
				print rtrim($assignedGroups, ',');
				break;
			default:
		}
	}

	
	function change_page_title($translation, $original) {
		global $pagenow;
		if ($pagenow == "plugins.php" && $original == 'Plugins') {
			if (isset($_REQUEST['PO_group_view']) && is_numeric($_REQUEST['PO_group_view'])) {
				$group = get_posts(array('ID'=>$_REQUEST['PO_group_view'], 'post_type'=>'plugin_group'));
				if (is_array($group[0])) {
					return 'Plugin Group: '.$group[0]->post_title;
				}
			}
		}
		return $translation;
	}

	function disable_plugin_box() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$supportedPostTypes = get_option("PO_custom_post_type_support");
			$supportedPostTypes[] = 'plugin_filter';
			if (is_array($supportedPostTypes) && get_option('PO_disable_plugins') == 1) {
				foreach ($supportedPostTypes as $postType) {
					add_meta_box(
						'plugin_organizer',
						'Plugin Organizer',
						array(new PO_Template($this), 'get_post_meta_box'),
						$postType,
						'normal'
					);
				}
			}
		}
	}

	function find_parent_plugins($currID, $permalink, $mobile, $secure) {
		global $wpdb;
		$postTypeSupport = get_option('PO_custom_post_type_support');
		$postTypeSupport[] = 'plugin_filter';
		
		$fuzzyPlugins = array(
			'post_id'=>0,
			'plugins'=>array(
				'disabled_plugins'=>array(),
				'enabled_plugins'=>array(),
				'disabled_groups'=>array(),
				'enabled_groups'=>array()
			)
		);
		
		if (get_option('PO_ignore_arguments') == '1') {
			$permalink = preg_replace('/\?.*$/', '', $permalink);
			$permalinkSearchField = 'permalink_hash';
		} else {
			$permalinkSearchField = 'permalink_hash_args';
		}
		$endChar = (preg_match('/\/$/', get_option('permalink_structure')) || is_admin())? '/':'';
		$lastUrl = $_SERVER['HTTP_HOST'].$endChar;
		
		$fuzzyPost = array();
		//Dont allow an endless loop
		$loopCount = 0;

		$permalinkHashes = array();
		$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (";
		$previousIndex = 8;
		$lastOcc = strrpos($permalink, "/");
		while ($loopCount < 25 && $previousIndex < $lastOcc) {
			$startReplace = strpos($permalink, '/', $previousIndex);
			$endReplace = strpos($permalink, '/', $startReplace+1);
			if ($endReplace === false) {
				$endReplace = strlen($permalink);
			}
			$permalinkHashes[] = $wpdb->prepare('%s', md5(substr_replace($permalink, "/*/", $startReplace, ($endReplace-$startReplace)+1)));
			$previousIndex = $endReplace;
			$loopCount++;
		}

		if (sizeof($permalinkHashes) > 0) {
			if (get_option('PO_ignore_protocol') == '0') {
				$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$permalinkSearchField." = ".implode(" OR ".$permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND secure = %d AND post_type IN ([IN]) AND post_id != %d ORDER BY dir_count DESC, post_priority DESC, FIELD(post_type, [IN])";
				$fuzzyPostQuery = $wpdb->prepare($fuzzyPostQuery, $secure, $currID);
				$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $postTypeSupport), ARRAY_A);
				
			} else {
				$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$permalinkSearchField." = ".implode(" OR ".$permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND post_type IN ([IN]) AND post_id != %d ORDER BY dir_count DESC, post_priority DESC, FIELD(post_type, [IN])";
				$fuzzyPostQuery = $wpdb->prepare($fuzzyPostQuery, $currID);
				$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $postTypeSupport), ARRAY_A);
			}
		}


		#print $this->prepare_in($fuzzyPostQuery, $postTypeSupport);
		if (sizeof($fuzzyPost) == 0) {
			$permalinkHashes = array();
			$loopCount = 0;
			while ($loopCount < 25 && $permalink != $lastUrl && ($permalink = preg_replace('/\/[^\/]+\/?$/', $endChar, $permalink))) {
				$loopCount++;
				$permalinkHashes[] = $wpdb->prepare('%s', md5($permalink));
			}
			
			if (sizeof($permalinkHashes) > 0) {
				if (get_option('PO_ignore_protocol') == '0') {
					$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$permalinkSearchField." = ".implode(" OR ".$permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND secure = %d AND children = 1 AND post_type IN ([IN]) AND post_id != %d ORDER BY dir_count DESC, post_priority DESC, FIELD(post_type, [IN])";
					$fuzzyPostQuery = $wpdb->prepare($fuzzyPostQuery, $secure, $currID);
					$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $postTypeSupport), ARRAY_A);
					
				} else {
					$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$permalinkSearchField." = ".implode(" OR ".$permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND children = 1 AND post_type IN ([IN]) AND post_id != %d ORDER BY dir_count DESC, post_priority DESC, FIELD(post_type, [IN])";
					$fuzzyPostQuery = $wpdb->prepare($fuzzyPostQuery, $currID);
					$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $postTypeSupport), ARRAY_A);
				}
			}
		}

			
		#print $this->prepare_in($fuzzyPostQuery, $postTypeSupport);
		#print_r($fuzzyPost);
		$matchFound = 0;
		if (sizeof($fuzzyPost) > 0) {
			foreach($fuzzyPost as $currPost) {
				if ($mobile == 0) {
					$disabledFuzzyPlugins = @unserialize($currPost['disabled_plugins']);
					$enabledFuzzyPlugins = @unserialize($currPost['enabled_plugins']);
					$disabledFuzzyGroups = @unserialize($currPost['disabled_groups']);
					$enabledFuzzyGroups = @unserialize($currPost['enabled_groups']);
				} else {
					$disabledFuzzyPlugins = @unserialize($currPost['disabled_mobile_plugins']);
					$enabledFuzzyPlugins = @unserialize($currPost['enabled_mobile_plugins']);
					$disabledFuzzyGroups = @unserialize($currPost['disabled_mobile_groups']);
					$enabledFuzzyGroups = @unserialize($currPost['enabled_mobile_groups']);
				}
				if ((is_array($disabledFuzzyPlugins) && sizeof($disabledFuzzyPlugins) > 0) || (is_array($enabledFuzzyPlugins) && sizeof($enabledFuzzyPlugins) > 0) || (is_array($disabledFuzzyGroups) && sizeof($disabledFuzzyGroups) > 0) || (is_array($enabledFuzzyGroups) && sizeof($enabledFuzzyGroups) > 0)) {
					$matchFound = 1;
					break;
				}
			}
			
			if ($matchFound > 0) {
				if (!is_array($disabledFuzzyPlugins)) {
					$disabledFuzzyPlugins = array();
				}

				if (!is_array($enabledFuzzyPlugins)) {
					$enabledFuzzyPlugins = array();
				}

				if (!is_array($disabledFuzzyGroups)) {
					$disabledFuzzyGroups = array();
				}

				if (!is_array($enabledFuzzyGroups)) {
					$enabledFuzzyGroups = array();
				}

				$fuzzyPlugins['plugins']['disabled_plugins'] = $disabledFuzzyPlugins;
				$fuzzyPlugins['plugins']['enabled_plugins'] = $enabledFuzzyPlugins;
				$fuzzyPlugins['plugins']['disabled_groups'] = $disabledFuzzyGroups;
				$fuzzyPlugins['plugins']['enabled_groups'] = $enabledFuzzyGroups;

				$fuzzyPlugins['post_id'] = $currPost['post_id'];
			}
		}
		return $fuzzyPlugins;
	}
	
	
	function find_duplicate_permalinks($postID, $permalink) {
		global $wpdb;
		$returnDup = array();
		$dupPostQuery = "SELECT post_id FROM ".$wpdb->prefix."po_plugins WHERE permalink = %s and post_id != %d and status != 'trash'";
		$dupPosts = $wpdb->get_results($wpdb->prepare($dupPostQuery, $permalink, $postID), ARRAY_A);
		if (sizeOf($dupPosts) > 0) {
			foreach ($dupPosts as $dup) {
				$returnDup[] = $dup['post_id'];
			}
		}
		return $returnDup;
	}

	function change_plugin_filter_title($title) {
		global $post;
		$supportedPostTypes = get_option("PO_custom_post_type_support");
		$supportedPostTypes[] = 'plugin_filter';
		if ( is_object($post) && ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post->ID) || !current_user_can( 'edit_post', $post->ID ) || !current_user_can( 'activate_plugins' ) || !in_array(get_post_type($post->ID), $supportedPostTypes) || !isset($_POST['poSubmitPostMetaBox']))) {
			return $title;
		}
		
		if (is_object($post) && get_post_type($post->ID) == 'plugin_filter') {
			if (isset($_POST['PO_filter_name']) && $_POST['PO_filter_name'] != '') {
				return $_POST['PO_filter_name'];
			} else if (!isset($_POST['PO_permalink_filter']) || $_POST['PO_permalink_filter'] == '') {
				$randomTitle = "";
				for($i=0; $i<10; $i++) {
					$randomTitle .= chr(mt_rand(109,122));
				}
				return $randomTitle;
			} else {
				return $_POST['PO_permalink_filter'];
			}
		} else {
			return $title;
		}
	}
	
	function save_post_meta_box($post_id) {
		global $wp_rewrite, $wpdb;

		$supportedPostTypes = get_option("PO_custom_post_type_support");
		$supportedPostTypes[] = 'plugin_filter';
		
		$postType = get_post_type($post_id);
		if (isset($_POST['PO_pt_override'])) {
			$ptOverride = 1;
		} else {
			$ptOverride = 0;
		}
		
		if ($ptOverride == 0 && is_array(get_option('PO_disabled_pt_plugins_'.$postType))) {
			$ptSettingsFound = 1;
		} else {
			$ptSettingsFound = 0;
		}
		
		if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can( 'edit_post', $post_id ) || ($ptSettingsFound !=1 && !current_user_can( 'activate_plugins' )) || !in_array(get_post_type($post_id), $supportedPostTypes) || !isset($_POST['poSubmitPostMetaBox'])) {
			return $post_id;
		}

		
		if (isset($_POST['affectChildren'])) {
			$affectChildren = 1;
		} else {
			$affectChildren = 0;
		}
		
		if ($ptSettingsFound == 1) {
			$disabledPlugins = get_option('PO_disabled_pt_plugins_'.$postType);
			$enabledPlugins = get_option('PO_enabled_pt_plugins_'.$postType);
			$disabledMobilePlugins = get_option('PO_disabled_mobile_pt_plugins_'.$postType);
			$enabledMobilePlugins = get_option('PO_enabled_mobile_pt_plugins_'.$postType);
			$disabledGroups = get_option('PO_disabled_pt_groups_'.$postType);
			$enabledGroups = get_option('PO_enabled_pt_groups_'.$postType);
			$disabledMobileGroups = get_option('PO_disabled_mobile_pt_groups_'.$postType);
			$enabledMobileGroups = get_option('PO_enabled_mobile_pt_groups_'.$postType);
		} else {
		
			$submittedPlugins = $this->get_submitted_plugin_lists();
			$disabledPlugins = $submittedPlugins[0];
			$enabledPlugins = $submittedPlugins[1];
			$disabledMobilePlugins = $submittedPlugins[2];
			$enabledMobilePlugins = $submittedPlugins[3];
			$disabledGroups = $submittedPlugins[4];
			$enabledGroups = $submittedPlugins[5];
			$disabledMobileGroups = $submittedPlugins[6];
			$enabledMobileGroups = $submittedPlugins[7];
		}
		
		
		$postStatus = get_post_status($post_id);
		if (!$postStatus) {
			$postStatus = 'publish';
		}

		$permalinks = array();
		
		if (get_post_type($post_id) != 'plugin_filter') {
			$pluginListID = $wpdb->get_var($wpdb->prepare("SELECT pl_id FROM ".$wpdb->prefix."po_plugins WHERE post_id=%d", $post_id));
			if (is_numeric($pluginListID)) {
				$permalinks[] = array($pluginListID, get_permalink($post_id));
			} else {
				$permalinks[] = array('tmp', get_permalink($post_id));
			}
		} else {
			if (isset($_POST['PO_pl_id'])) {
				foreach($_POST['PO_pl_id'] as $plID) {
					$permalinks[] = array($plID, $this->fix_trailng_slash($_POST['PO_permalink_filter_'.$plID]));
				}
			}
		}

		if (isset($_POST['PO_post_priority']) && is_numeric($_POST['PO_post_priority'])) {
			$postPriority = $_POST['PO_post_priority'];
		} else {
			$postPriority = 0;
		}
		
		foreach($permalinks as $permalink) {
			$secure=0;
			if (preg_match('/^.{1,5}:\/\//', $permalink[1], $matches)) {
				switch ($matches[0]) {
					case "https://":
						$secure=1;
						break;
					default:
						$secure=0;
				}
			}

			if (is_numeric($permalink[0])) {
				$postExists = ($wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->prefix."po_plugins WHERE pl_id=%d", $permalink[0])) > 0) ? 1 : 0;
			} else {
				$postExists = 0;
			}
			
			$permalink[1] = preg_replace('/^.{1,5}:\/\//', '', $permalink[1]);
			
			$permalinkNoArgs = preg_replace('/\?.*$/', '', $permalink[1]);
			
			$disabledPluginsAfterParent = array();
			$enabledPluginsAfterParent = array();
			$disabledGroupsAfterParent = array();
			$enabledGroupsAfterParent = array();
			if ($permalink[1] != '' && get_option("PO_fuzzy_url_matching") == "1" && get_post_type($post_id) != 'plugin_filter') {
				$fuzzyPluginList = $this->find_parent_plugins($post_id, $permalink[1], 0, $secure);
				foreach ($disabledPlugins as $plugin) {
					if (!in_array($plugin, $fuzzyPluginList['plugins']['disabled_plugins'])) {
						$disabledPluginsAfterParent[] = $plugin;
					}
				}

				foreach ($enabledPlugins as $plugin) {
					if (!in_array($plugin, $fuzzyPluginList['plugins']['enabled_plugins'])) {
						$enabledPluginsAfterParent[] = $plugin;
					}
				}

				foreach ($disabledGroups as $group) {
					if (!in_array($group, $fuzzyPluginList['plugins']['disabled_groups'])) {
						$disabledGroupsAfterParent[] = $group;
					}
				}

				foreach ($enabledGroups as $group) {
					if (!in_array($group, $fuzzyPluginList['plugins']['enabled_groups'])) {
						$enabledGroupsAfterParent[] = $group;
					}
				}

				if (sizeof($disabledPluginsAfterParent) == 0 && sizeof($enabledPluginsAfterParent) == 0 && sizeof($disabledGroupsAfterParent) == 0 && sizeof($enabledGroupsAfterParent) == 0) {
					$disabledPlugins = array();
					$enabledPlugins = array();
					$disabledGroups = array();
					$enabledGroups = array();
				}

				$disabledMobilePluginsAfterParent = array();
				$enabledMobilePluginsAfterParent = array();
				$disabledMobileGroupsAfterParent = array();
				$enabledMobileGroupsAfterParent = array();
				$fuzzyMobilePluginList = $this->find_parent_plugins($post_id, $permalink[1], 1, $secure);
				foreach ($disabledMobilePlugins as $plugin) {
					if (!in_array($plugin, $fuzzyMobilePluginList['plugins']['disabled_plugins'])) {
						$disabledMobilePluginsAfterParent[] = $plugin;
					}
				}

				foreach ($enabledMobilePlugins as $plugin) {
					if (!in_array($plugin, $fuzzyMobilePluginList['plugins']['enabled_plugins'])) {
						$enabledMobilePluginsAfterParent[] = $plugin;
					}
				}

				foreach ($disabledMobileGroups as $group) {
					if (!in_array($group, $fuzzyMobilePluginList['plugins']['disabled_groups'])) {
						$disabledMobileGroupsAfterParent[] = $group;
					}
				}

				foreach ($enabledMobileGroups as $group) {
					if (!in_array($group, $fuzzyMobilePluginList['plugins']['enabled_groups'])) {
						$enabledMobileGroupsAfterParent[] = $group;
					}
				}

				if (sizeof($disabledMobilePluginsAfterParent) == 0 && sizeof($enabledMobilePluginsAfterParent) == 0 && sizeof($disabledMobileGroupsAfterParent) == 0 && sizeof($enabledMobileGroupsAfterParent) == 0) {
					$disabledMobilePlugins = array();
					$enabledMobilePlugins = array();
					$disabledMobileGroups = array();
					$enabledMobileGroups = array();
				}
			}
			
			
			
			$dirCount = substr_count($permalink[1], "/");
			
			if (sizeof($enabledPlugins) > 0 || sizeof($disabledPlugins) > 0 || sizeof($enabledMobilePlugins) > 0 || sizeof($disabledMobilePlugins) > 0 || sizeof($enabledGroups) > 0 || sizeof($disabledGroups) > 0 || sizeof($enabledMobileGroups) > 0 || sizeof($disabledMobileGroups) > 0 || get_post_type($post_id) == "plugin_filter" || $ptOverride == 1) {
				if ($postExists == 1) {
					$wpdb->update($wpdb->prefix."po_plugins", array("permalink"=>$permalink[1], "permalink_hash"=>md5($permalinkNoArgs), "permalink_hash_args"=>md5($permalink[1]), "children"=>$affectChildren, "pt_override"=>$ptOverride, "enabled_plugins"=>serialize($enabledPlugins), "disabled_plugins"=>serialize($disabledPlugins), "enabled_mobile_plugins"=>serialize($enabledMobilePlugins), "disabled_mobile_plugins"=>serialize($disabledMobilePlugins), "enabled_groups"=>serialize($enabledGroups), "disabled_groups"=>serialize($disabledGroups), "enabled_mobile_groups"=>serialize($enabledMobileGroups), "disabled_mobile_groups"=>serialize($disabledMobileGroups), "secure"=>$secure, "post_type"=>get_post_type($post_id), "status"=>$postStatus, "post_priority"=>$postPriority, "dir_count"=>$dirCount), array("pl_id"=>$permalink[0]));
				} else {
					$wpdb->insert($wpdb->prefix."po_plugins", array("post_id"=>$post_id, "permalink"=>$permalink[1], "permalink_hash"=>md5($permalinkNoArgs), "permalink_hash_args"=>md5($permalink[1]), "children"=>$affectChildren, "pt_override"=>$ptOverride, "enabled_plugins"=>serialize($enabledPlugins), "disabled_plugins"=>serialize($disabledPlugins), "enabled_mobile_plugins"=>serialize($enabledMobilePlugins), "disabled_mobile_plugins"=>serialize($disabledMobilePlugins), "enabled_groups"=>serialize($enabledGroups), "disabled_groups"=>serialize($disabledGroups), "enabled_mobile_groups"=>serialize($enabledMobileGroups), "disabled_mobile_groups"=>serialize($disabledMobileGroups), "secure"=>$secure, "post_type"=>get_post_type($post_id), "status"=>$postStatus, "post_priority"=>$postPriority, "dir_count"=>$dirCount));
				}
			} else if ($postExists == 1) {
				$deletePluginQuery = "DELETE FROM ".$wpdb->prefix."po_plugins WHERE pl_id = %d";
				$wpdb->query($wpdb->prepare($deletePluginQuery, $permalink[0]));
			}
		}
	}


	function get_submitted_plugin_lists() {
		$returnPluginArray = array();
		
		$globalPlugins = get_option('PO_disabled_plugins');
		if (!is_array($globalPlugins)) {
			$globalPlugins = array();
		}
		$checkPluginList = (isset($_POST['PO_disabled_std_plugin_list'])) ? $_POST['PO_disabled_std_plugin_list'] : '';
		
		$tempPluginLists = $this->create_plugin_lists($checkPluginList, $globalPlugins);
		##Add plugin lists to return array
		$returnPluginArray[] = $tempPluginLists[0];
		$returnPluginArray[] = $tempPluginLists[1];
		
		
		### Mobile plugins
		if (get_option('PO_disable_mobile_plugins') == 1) {
			$globalMobilePlugins = get_option('PO_disabled_mobile_plugins');
			if (!is_array($globalMobilePlugins)) {
				$globalMobilePlugins = array();
			}
			$checkPluginList = (isset($_POST['PO_disabled_mobile_plugin_list'])) ? $_POST['PO_disabled_mobile_plugin_list'] : '';
			
			##Add plugin lists to return array
			$tempPluginLists = $this->create_plugin_lists($checkPluginList, $globalMobilePlugins);
			$returnPluginArray[] = $tempPluginLists[0];
			$returnPluginArray[] = $tempPluginLists[1];
		} else {
			$returnPluginArray[] = array();
			$returnPluginArray[] = array();
		}


		
		##Groups
		$globalGroups = get_option('PO_disabled_groups');
		if (!is_array($globalGroups)) {
			$globalGroups = array();
		}
		$checkPluginList = (isset($_POST['PO_disabled_std_group_list'])) ? $_POST['PO_disabled_std_group_list'] : '';
		
		##Add plugin lists to return array
		$tempPluginLists = $this->create_plugin_lists($checkPluginList, $globalGroups);
		$returnPluginArray[] = $tempPluginLists[0];
		$returnPluginArray[] = $tempPluginLists[1];

		##Mobile Groups
		if (get_option('PO_disable_mobile_plugins') == 1) {
			$globalMobileGroups = get_option('PO_disabled_mobile_groups');
			if (!is_array($globalMobileGroups)) {
				$globalMobileGroups = array();
			}
			$checkPluginList = (isset($_POST['PO_disabled_mobile_group_list'])) ? $_POST['PO_disabled_mobile_group_list'] : '';
		
			##Add plugin lists to return array
			$tempPluginLists = $this->create_plugin_lists($checkPluginList, $globalMobileGroups);
			$returnPluginArray[] = $tempPluginLists[0];
			$returnPluginArray[] = $tempPluginLists[1];
		} else {
			$returnPluginArray[] = array();
			$returnPluginArray[] = array();
		}

		return $returnPluginArray;
	}
	
	function delete_plugin_lists($post_id) {
		global $wpdb;
		if ( !current_user_can( 'activate_plugins', $post_id ) ) {
			return $post_id;
		}
		if (is_numeric($post_id)) {
			$deletePluginQuery = "DELETE FROM ".$wpdb->prefix."po_plugins WHERE post_id = %d";
			$wpdb->query($wpdb->prepare($deletePluginQuery, $post_id));
		}
	}
	
	function recreate_plugin_order() {
		$plugins = $this->get_active_plugins();
		$pluginOrder = get_option("PO_plugin_order");
		$newPlugArray = $plugins;
		$activePlugins = $plugins;
		$newPluginOrder = array();
		if (is_array($pluginOrder)) {
			foreach ($pluginOrder as $newPlug) {
				if (is_plugin_active_for_network($newPlug)) {
					$newPluginOrder[] = $newPlug;
				}
			}

			foreach ($pluginOrder as $newPlug) {
				if (!is_plugin_active_for_network($newPlug)) {
					$newPluginOrder[] = $newPlug;
				}
			}
			
			if (sizeof(array_diff_assoc($plugins, $newPluginOrder)) > 0) {
				$newPlugins = array_diff($plugins, $newPluginOrder);
				foreach ($newPlugins as $newPlug) {
					$pluginOrder[] = $newPlug;
				}
				$pluginLoadOrder = Array();
				$activePlugins = array();
				foreach ($plugins as $val) {
					$activePlugins[] = $val;
					$pluginLoadOrder[] = array_search($val, $newPluginOrder);
				}
				array_multisort($pluginLoadOrder, $activePlugins);
				update_option("active_plugins", $activePlugins);
				update_option("PO_plugin_order", $activePlugins);
			}
		}
	}

	function plugin_filter_sort($columns) {
		$custom = array(
			'taxonomy-filter_group' => 'taxonomy-filter_group',
			'PO_PF_permalink' => 'PO_PF_permalink'
		);
		return wp_parse_args($custom, $columns);
	}

	function register_taxonomy() {
		$labels = array(
			'name' => _x( 'Filter Groups', 'taxonomy general name' ),
			'singular_name' => _x( 'Filter Group', 'taxonomy singular name' ),
			'search_items' =>  __( 'Search Filter Groups' ),
			'all_items' => __( 'All Filter Groups' ),
			'parent_item' => __( 'Parent Filter Group' ),
			'parent_item_colon' => __( 'Parent Filter Group:' ),
			'edit_item' => __( 'Edit Filter Group' ),
			'update_item' => __( 'Update Filter Group' ),
			'add_new_item' => __( 'Add New Filter Group' ),
			'new_item_name' => __( 'New Filter Group Name' )
		);

		$settings = array(
			'hierarchical' => true,
			'public' => false,
			'capability_type' => 'filter_group',
			'show_admin_column' => true,
			'labels' => $labels,
			'show_ui' => true,
			'capabilities' => array('assign_terms'=>'edit_filter_group','manage_terms' => 'manage_filter_groups','edit_terms' => 'manage_filter_groups','delete_terms' => 'manage_filter_groups'),
			'rewrite' => array( 'slug' => 'filter_group' )
		);

		register_taxonomy('filter_group', array('plugin_filter'), $settings);
	}
	
	function register_type() {
		$labels = array(
			'name' => _x('Plugin Filters', 'post type general name'),
			'singular_name' => _x('Plugin Filter', 'post type singular name'),
			'add_new' => _x('Add Plugin Filter', 'neo_theme'),
			'add_new_item' => __('Add New Plugin Filter'),
			'edit_item' => __('Edit Plugin Filter'),
			'new_item' => __('New Plugin Filter'),
			'view_item' => __('View Plugin Filter'),
			'search_items' => __('Search Plugin Filter'),
			'not_found' =>  __('No Plugin Filters found'),
			'not_found_in_trash' => __('No Plugin Filters found in Trash'), 
			'parent_item_colon' => 'Parent Plugin Filter:',
			'parent' => 'Parent Plugin Filter'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true, 
			'menu_icon' => $this->urlPath . '/image/po-icon-16x16.png', 		
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('custom-fields'),
			'capability_type' => 'plugin_filter',
			'capabilities' => array( 'delete_posts' => 'delete_plugin_filters' ),
			'show_in_menu' => 'Plugin_Organizer'
		); 
		register_post_type('plugin_filter',$args);
		
		$labels = array(
			'name' => _x('Plugin Groups', 'post type general name'),
			'singular_name' => _x('Plugin Group', 'post type singular name'),
			'add_new' => _x('Add Plugin Group', 'neo_theme'),
			'add_new_item' => __('Add New Plugin Group'),
			'edit_item' => __('Edit Plugin Group'),
			'new_item' => __('New Plugin Group'),
			'view_item' => __('View Plugin Group'),
			'search_items' => __('Search Plugin Group'),
			'not_found' =>  __('No PPlugin Groups found'),
			'not_found_in_trash' => __('No Plugin Groups found in Trash'), 
			'parent_item_colon' => 'Parent Plugin Group:',
			'parent' => 'Parent Plugin Group'
		);
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => false, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('custom-fields'),
			'capability_type' => 'plugin_group'
		); 
		register_post_type('plugin_group',$args);
	}
	
	function custom_updated_messages( $messages ) {
		global $post, $post_ID;
		$messages['plugin_filter'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Plugin Filter updated.'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Plugin Filter updated.'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Plugin Filter restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Plugin Filter published.'), esc_url( get_permalink($post_ID) ) ),
			7 => __('theme saved.'),
			8 => sprintf( __('Plugin Filter submitted.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Plugin Filter scheduled for: <strong>%1$s</strong>.'),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Plugin Filter draft updated.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

		$messages['plugin_group'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Plugin Group updated.'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Plugin Group updated.'),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Plugin Group restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Plugin Group published.'), esc_url( get_permalink($post_ID) ) ),
			7 => __('theme saved.'),
			8 => sprintf( __('Plugin Group submitted.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Plugin Group scheduled for: <strong>%1$s</strong>.'),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Plugin Group draft updated.'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}

	function deactivated_plugin($plugin, $networkWide = null) {
		global $wpdb;
		if ($networkWide != null) {
			$sites = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
			foreach ($sites as $site) {
				if (switch_to_blog($site->blog_id)) {
					$activePlugins = $this->get_active_plugins();
					$activePlugins = array_values(array_diff($activePlugins, array($plugin)));
					update_option('active_plugins', $activePlugins);
				}
			}
			restore_current_blog();
		}
	}

	function activated_plugin($plugin, $networkWide = null) {
		global $wpdb;
		if ($networkWide != null) {
			$sites = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
			foreach ($sites as $site) {
				if (switch_to_blog($site->blog_id)) {
					$activePlugins = $this->get_active_plugins();
					if (!in_array($plugin, $activePlugins)) {
						$activePlugins[] = $plugin;
						update_option('active_plugins', $activePlugins);
					}
				}
			}
			restore_current_blog();
		}
	}

	function update_post_status($newStatus, $oldStatus, $post) {
		global $wpdb;
		$postExists = ($wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->prefix."po_plugins WHERE post_id=%d", $post->ID)) > 0) ? 1 : 0;
		
		if ($postExists) {
			$postSettingsQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE post_id = %d";
			$postSettings = $wpdb->get_results($wpdb->prepare($postSettingsQuery, $post->ID), ARRAY_A);
				
			foreach($postSettings as $currPostSettings) {
				if (get_post_type($post->ID) != 'plugin_filter') {
					$permalink = get_permalink($post->ID);
				} else {
					$permalink = $currPostSettings['permalink'];
				}

				$secure=0;
				if (preg_match('/^.{1,5}:\/\//', $permalink, $matches)) {
					switch ($matches[0]) {
						case "https://":
							$secure=1;
							break;
						default:
							$secure=0;
					}
				}

				$permalink = preg_replace('/^.{1,5}:\/\//', '', $permalink);
				
				$permalinkNoArgs = preg_replace('/\?.*$/', '', $permalink);
				
				$dirCount = substr_count($permalink, "/");
				
				$wpdb->update($wpdb->prefix."po_plugins", array("status"=>$newStatus, "permalink"=>$permalink, "permalink_hash"=>md5($permalinkNoArgs), "permalink_hash_args"=>md5($permalink), "secure"=>$secure, "dir_count"=>$dirCount), array("pl_id"=>$currPostSettings['pl_id']));
			}
		}
	}

	function fix_trailng_slash($permalink) {
		global $wpdb;
		if ($permalink == '' || get_option('PO_auto_trailing_slash') == 0) { return $permalink; }
		
		$wpDomain = preg_replace(array('/^(https?:\/\/)?/', '/\/$/'), array('',''), get_bloginfo('url'));
		$wpAdminURL = preg_replace('/^(https?:\/\/)?/', '', admin_url());
		
		$permalinkNoProtocol = preg_replace('/^(https?:\/\/)?/', '', $permalink);
		$filePath = preg_replace('/^(https?:\/\/)?'.preg_quote($wpDomain, '/').'\/?/', '', $permalink);
		
		##get unfiltered siteurl value directly from database since wordpress won't let you have it any other way.  This includes the trailing slash if set in the options.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'siteurl' ) );
		$realSiteUrl = '';
		if (is_object($row)) {
			$realSiteUrl = preg_replace('/^(https?:\/\/)?/', '', $row->option_value);
			if ($_SERVER['HTTP_HOST'] == preg_replace('/\/$/', '', $realSiteUrl)) {
				$realSiteUrl = trailingslashit($realSiteUrl);
			}
		}
		if (!is_file(get_home_path() . $filePath) && !preg_match('/^'.preg_quote($wpAdminURL, '/').'/', $permalinkNoProtocol) && strpos($permalink, "?") === FALSE) {
			if (preg_replace('/\/$/', '', $realSiteUrl) == preg_replace('/\/$/', '', $permalinkNoProtocol)) {
				if (preg_match('/\/$/', $realSiteUrl)) {
					$permalink = trailingslashit($permalink);
				} else {
					$permalink = untrailingslashit($permalink);
				}
			} else {
				$permalink = user_trailingslashit($permalink);
			}
		}
		return $permalink;
	}

	function sort_posts($a, $b) {
		if ($a['post_type'] == 'plugin_filter' && $b['post_type'] != 'plugin_filter') {
			return 1;
		} else if($a['post_type'] != 'plugin_filter' && $b['post_type'] == 'plugin_filter') {
			return -1;
		} else {
			return 0;
		}
	}

	function prepare_in($sql, $vals){
		global $wpdb;
		$in_count = substr_count($sql, '[IN]');
		if ( $in_count > 0 ){
			$args = array( str_replace('[IN]', implode(', ', array_fill(0, count($vals), '%s')), str_replace('%', '%%', $sql)));
			// This will populate ALL the [IN]'s with the $vals, assuming you have more than one [IN] in the sql
			for ($i=0; $i < substr_count($sql, '[IN]'); $i++) {
				$args = array_merge($args, $vals);
			}
			$sql = call_user_func_array(array($wpdb, 'prepare'), array_merge($args));
		}
		return $sql;
	}
}
?>