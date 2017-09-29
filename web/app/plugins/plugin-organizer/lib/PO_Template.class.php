<?php
class PO_Template {
	var $PO;
	function __construct($PO) {
		$this->PO = $PO;
	}
	
	function common_js() {
		require_once($this->PO->absPath . "/tpl/common_js.php");
	}
	
	function search_plugins_js() {
		require_once($this->PO->absPath . "/tpl/search_plugins_js.php");
	}

	function pt_plugins_js() {
		require_once($this->PO->absPath . "/tpl/pt_plugins_js.php");
	}
	
	function group_and_order_plugins_js() {
		wp_enqueue_style('PO-dash-icons');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		require_once($this->PO->absPath . "/tpl/group_and_order_js.php");
	}
	
	function global_plugins_js() {
		require_once($this->PO->absPath . "/tpl/global_plugins_js.php");
	}

	function settings_page_js() {
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
		if (get_option('PO_disable_admin_notices') == 1) {
			add_action('admin_notices', array($this->PO, 'admin_notices'));
		}
		require_once($this->PO->absPath . "/tpl/settings_page_js.php");
	}

	function admin_css() {
		wp_enqueue_style('PO-admin');
		if (get_option('PO_disable_admin_notices') != 1) {
			add_action('admin_notices', array($this->PO, 'admin_notices'));
		}
		require_once($this->PO->absPath . "/tpl/admin_css.php");
	}

	function settings_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			require_once($this->PO->absPath . "/tpl/settings.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function search_plugins_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = $this->PO->reorder_plugins(get_plugins());
			$enabledPluginList = get_option('PO_enabled_search_plugins');
			$disabledPluginList = get_option('PO_disabled_search_plugins');
			$enabledMobilePluginList = get_option('PO_enabled_mobile_search_plugins');
			$disabledMobilePluginList = get_option('PO_disabled_mobile_search_plugins');
			$enabledGroupList = get_option('PO_enabled_search_groups');
			$disabledGroupList = get_option('PO_disabled_search_groups');
			$enabledMobileGroupList = get_option('PO_enabled_mobile_search_groups');
			$disabledMobileGroupList = get_option('PO_disabled_mobile_search_groups');
			$activePlugins = $this->PO->get_active_plugins();
			$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
			$groupList = get_posts(array('posts_per_page'=>-1, 'post_type'=>'plugin_group'));
			if (!is_array($enabledPluginList)) {
				$enabledPluginList = array();
			}
			if (!is_array($disabledPluginList)) {
				$disabledPluginList = array();
			}
			if (!is_array($enabledMobilePluginList)) {
				$enabledMobilePluginList = array();
			}
			if (!is_array($disabledMobilePluginList)) {
				$disabledMobilePluginList = array();
			}
			if (!is_array($enabledGroupList)) {
				$enabledGroupList = array();
			}
			if (!is_array($disabledGroupList)) {
				$disabledGroupList = array();
			}
			if (!is_array($enabledMobileGroupList)) {
				$enabledMobileGroupList = array();
			}
			if (!is_array($disabledMobileGroupList)) {
				$disabledMobileGroupList = array();
			}
			
			
			$globalPlugins = get_option('PO_disabled_plugins');
			if (!is_array($globalPlugins)) {
				$globalPlugins = array();
			}

			$globalMobilePlugins = get_option('PO_disabled_mobile_plugins');
			if (!is_array($globalMobilePlugins)) {
				$globalMobilePlugins = array();
			}

			$globalGroups = get_option('PO_disabled_groups');
			if (!is_array($globalGroups)) {
				$globalGroups = array();
			}

			$globalMobileGroups = get_option('PO_disabled_mobile_groups');
			if (!is_array($globalMobileGroups)) {
				$globalMobileGroups = array();
			}

			require_once($this->PO->absPath . "/tpl/searchPlugins.php");
		}
	}

	function pt_plugins_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = $this->PO->reorder_plugins(get_plugins());
			$enabledPluginList = get_option('PO_enabled_pt_plugins');
			$disabledPluginList = get_option('PO_disabled_pt_plugins');
			$enabledMobilePluginList = get_option('PO_enabled_mobile_pt_plugins');
			$disabledMobilePluginList = get_option('PO_disabled_mobile_pt_plugins');
			$enabledGroupList = get_option('PO_enabled_pt_groups');
			$disabledGroupList = get_option('PO_disabled_pt_groups');
			$enabledMobileGroupList = get_option('PO_enabled_mobile_pt_groups');
			$disabledMobileGroupList = get_option('PO_disabled_mobile_pt_groups');
			$activePlugins = $this->PO->get_active_plugins();
			$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
			$groupList = get_posts(array('posts_per_page'=>-1, 'post_type'=>'plugin_group'));
			if (!is_array($enabledPluginList)) {
				$enabledPluginList = array();
			}
			if (!is_array($disabledPluginList)) {
				$disabledPluginList = array();
			}
			if (!is_array($enabledMobilePluginList)) {
				$enabledMobilePluginList = array();
			}
			if (!is_array($disabledMobilePluginList)) {
				$disabledMobilePluginList = array();
			}
			if (!is_array($enabledGroupList)) {
				$enabledGroupList = array();
			}
			if (!is_array($disabledGroupList)) {
				$disabledGroupList = array();
			}
			if (!is_array($enabledMobileGroupList)) {
				$enabledMobileGroupList = array();
			}
			if (!is_array($disabledMobileGroupList)) {
				$disabledMobileGroupList = array();
			}
			
			
			$globalPlugins = get_option('PO_disabled_plugins');
			if (!is_array($globalPlugins)) {
				$globalPlugins = array();
			}

			$globalMobilePlugins = get_option('PO_disabled_mobile_plugins');
			if (!is_array($globalMobilePlugins)) {
				$globalMobilePlugins = array();
			}

			$globalGroups = get_option('PO_disabled_groups');
			if (!is_array($globalGroups)) {
				$globalGroups = array();
			}

			$globalMobileGroups = get_option('PO_disabled_mobile_groups');
			if (!is_array($globalMobileGroups)) {
				$globalMobileGroups = array();
			}

			$ptOverride = 0;
			require_once($this->PO->absPath . "/tpl/ptPlugins.php");
		}
	}
	
	function group_and_order_plugins_page() {
		require_once($this->PO->absPath . "/tpl/groupAndOrder.php");
	}
	
	function global_plugins_page() {
		if ( current_user_can( 'activate_plugins' ) ) {
			$plugins = $this->PO->reorder_plugins(get_plugins());
			$disabledPluginList = get_option('PO_disabled_plugins');
			$enabledPluginList = array();
			$disabledMobilePluginList = get_option('PO_disabled_mobile_plugins');
			$enabledMobilePluginList = array();
			$disabledGroupList = get_option('PO_disabled_groups');
			$enabledGroupList = array();
			$disabledMobileGroupList = get_option('PO_disabled_mobile_groups');
			$enabledMobileGroupList = array();
			$activePlugins = $this->PO->get_active_plugins();
			$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
			$groupList = get_posts(array('posts_per_page'=>-1, 'post_type'=>'plugin_group'));
			$globalPlugins = array();
			$globalMobilePlugins = array();
			$globalGroups = array();
			$globalMobileGroups = array();
			
			if (!is_array($disabledPluginList)) {
				$disabledPluginList = array();
			}
			if (!is_array($disabledMobilePluginList)) {
				$disabledMobilePluginList = array();
			}
			if (!is_array($disabledGroupList)) {
				$disabledGroupList = array();
			}
			if (!is_array($disabledMobileGroupList)) {
				$disabledMobileGroupList = array();
			}
			require_once($this->PO->absPath . "/tpl/globalPlugins.php");
		} else {
			wp_die("You dont have permissions to access this page.");
		}
	}

	function get_post_meta_box($post) {
		global $wpdb;
		$errMsg = "";
		$this->admin_css();
		$this->common_js();
		if ($post->ID != "" && is_numeric($post->ID)) {
			$filterName = $post->post_title;
			$postSettingsQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE post_id = %d";
			$postSettings = $wpdb->get_row($wpdb->prepare($postSettingsQuery, $post->ID), ARRAY_A);
			
			$affectChildren = $postSettings['children'];
			$ptOverride = $postSettings['pt_override'];
			
			$disabledPluginList = @unserialize($postSettings['disabled_plugins']);
			if (!is_array($disabledPluginList)) {
				$disabledPluginList = array();
			}

			$enabledPluginList = @unserialize($postSettings['enabled_plugins']);
			if (!is_array($enabledPluginList)) {
				$enabledPluginList = array();
			}

			$disabledMobilePluginList = @unserialize($postSettings['disabled_mobile_plugins']);
			if (!is_array($disabledMobilePluginList)) {
				$disabledMobilePluginList = array();
			}

			$enabledMobilePluginList = @unserialize($postSettings['enabled_mobile_plugins']);
			if (!is_array($enabledMobilePluginList)) {
				$enabledMobilePluginList = array();
			}

			$disabledGroupList = @unserialize($postSettings['disabled_groups']);
			if (!is_array($disabledGroupList)) {
				$disabledGroupList = array();
			}

			$enabledGroupList = @unserialize($postSettings['enabled_groups']);
			if (!is_array($enabledGroupList)) {
				$enabledGroupList = array();
			}

			$disabledMobileGroupList = @unserialize($postSettings['disabled_mobile_groups']);
			if (!is_array($disabledMobileGroupList)) {
				$disabledMobileGroupList = array();
			}

			$enabledMobileGroupList = @unserialize($postSettings['enabled_mobile_groups']);
			if (!is_array($enabledMobileGroupList)) {
				$enabledMobileGroupList = array();
			}

			$permalinksQuery = "SELECT pl_id, permalink FROM ".$wpdb->prefix."po_plugins WHERE post_id = %d";
			$permalinksResult = $wpdb->get_results($wpdb->prepare($permalinksQuery, $post->ID), ARRAY_A);
			
			$permalinkFilters = array();
			foreach($permalinksResult as $permalink) {
				$permalinkFilters[] = array('pl_id'=>$permalink['pl_id'], 'permalink'=>$permalink['permalink']);
			}
			
			$secure = $postSettings['secure'];
			$postPriority = $postSettings['post_priority'];
			
			$postType = get_post_type($post->ID);
			$postTypeObject = get_post_type_object($postType);
			if (isset($postTypeObject->labels->name)) {
				$postTypeName = $postTypeObject->labels->singular_name;
			} else {
				$postTypeName = 'Post';
			}

			$duplicates = $this->PO->find_duplicate_permalinks($post->ID, $postSettings['permalink']);
			if (sizeOf($duplicates) > 0) {
				foreach($duplicates as $dup) {
					$errMsg .= 'There is a '.get_post_type($dup).' with the same permalink.  <a href="' . get_admin_url() . 'post.php?post=' . $dup . '&action=edit">Edit Duplicate</a><br />';
				}
			}
		} else {
			$filterName = "";
			$affectChildren = 0;
			$disabledPluginList = array();
			$enabledPluginList = array();
			$disabledMobilePluginList = array();
			$enabledMobilePluginList = array();
			$disabledGroupList = array();
			$enabledGroupList = array();
			$disabledMobileGroupList = array();
			$enabledMobileGroupList = array();
			$permalinkFilters = array('', '');
			$secure=0;
			$postPriority=0;
			$postTypeName = 'Post';
		}
		
		if ($post->post_type != 'plugin_filter') {
			$fuzzyPermalink = preg_replace('/^.{1,5}:\/\//', '', get_permalink($post->ID));
		
		
			#Find and apply parent settings
			if ($fuzzyPermalink != '' && get_option("PO_fuzzy_url_matching") == "1" && sizeof($disabledPluginList) == 0 && sizeof($enabledPluginList) == 0 && sizeof($disabledGroupList) == 0 && sizeof($enabledGroupList) == 0) {
				$fuzzyPluginList = $this->PO->find_parent_plugins($post->ID, $fuzzyPermalink, 0, $secure);
				$disabledPluginList = $fuzzyPluginList['plugins']['disabled_plugins'];
				$enabledPluginList = $fuzzyPluginList['plugins']['enabled_plugins'];
				$disabledGroupList = $fuzzyPluginList['plugins']['disabled_groups'];
				$enabledGroupList = $fuzzyPluginList['plugins']['enabled_groups'];
				if ($fuzzyPluginList['post_id'] > 0) {
					$errMsg .= 'There is a parent affecting the standard plugins on this '. $postTypeName . '.  To edit it click <a href="' . get_admin_url() . 'post.php?post=' . $fuzzyPluginList['post_id'] . '&action=edit">here</a>.<br />';
				}
			}



			#Find and apply parent settings to mobile plugins
			if ($fuzzyPermalink != '' && get_option('PO_disable_mobile_plugins') == '1' && get_option("PO_fuzzy_url_matching") == "1" && sizeof($disabledMobilePluginList) == 0 && sizeof($enabledMobilePluginList) == 0 && sizeof($disabledMobileGroupList) == 0 && sizeof($enabledMobileGroupList) == 0) {
				$fuzzyPluginList = $this->PO->find_parent_plugins($post->ID, $fuzzyPermalink, 1, $secure);
				$disabledMobilePluginList = $fuzzyPluginList['plugins']['disabled_plugins'];
				$enabledMobilePluginList = $fuzzyPluginList['plugins']['enabled_plugins'];
				$disabledMobileGroupList = $fuzzyPluginList['plugins']['disabled_groups'];
				$enabledMobileGroupList = $fuzzyPluginList['plugins']['enabled_groups'];
				if ($fuzzyPluginList['post_id'] > 0) {
					$errMsg .= 'There is a parent affecting the mobile plugins on this '. $postTypeName . '.  To edit it click <a href="' . get_admin_url() . 'post.php?post=' . $fuzzyPluginList['post_id'] . '&action=edit">here</a>.<br />';
				}
			}
		}




		
		$globalPlugins = get_option('PO_disabled_plugins');
		if (!is_array($globalPlugins)) {
			$globalPlugins = array();
		}

		$globalMobilePlugins = get_option('PO_disabled_mobile_plugins');
		if (!is_array($globalMobilePlugins)) {
			$globalMobilePlugins = array();
		}

		$globalGroups = get_option('PO_disabled_groups');
		if (!is_array($globalGroups)) {
			$globalGroups = array();
		}

		$globalMobileGroups = get_option('PO_disabled_mobile_groups');
		if (!is_array($globalMobileGroups)) {
			$globalMobileGroups = array();
		}
		
		
		$plugins = $this->PO->reorder_plugins(get_plugins());
		
		$activePlugins = $this->PO->get_active_plugins();
		$activeSitewidePlugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
		
		$groupList = get_posts(array('posts_per_page'=>-1, 'post_type'=>'plugin_group'));
		if (get_option("PO_disable_plugins") != 1) {
			$errMsg .= 'You currently have Selective Plugin Loading disabled.  None of the changes you make here will have any affect on what plugins are loaded until you enable it.  You can enable it by going to the <a href="' . get_admin_url() . 'admin.php?page=Plugin_Organizer">settings page</a> and clicking enable under Selective Plugin Loading.';
		}
		
		require_once($this->PO->absPath . "/tpl/postMetaBox.php");
	}
}
?>