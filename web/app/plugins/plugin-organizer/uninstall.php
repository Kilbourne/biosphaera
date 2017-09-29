<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit();
}

if (is_multisite()) {
	$networkSites = wp_get_sites();
	if (sizeof($networkSites) > 0) {
		$originalBlogID = get_current_blog_id();
		foreach($networkSites as $site) {
			switch_to_blog($site['blog_id']);
			PO_delete_site_data();
		}
		switch_to_blog($originalBlogID);
	}
} else {
	PO_delete_site_data();
}

if (file_exists(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php")) {
	@unlink(WPMU_PLUGIN_DIR . "/PluginOrganizerMU.class.php");
}

function PO_delete_site_data() {
	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_url_plugins");
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_post_plugins");
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_groups");
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."PO_plugins");
	$wpdb->query("DROP TABLE IF EXISTS `".$wpdb->prefix."po_plugins");

	delete_option("PO_mobile_user_agents");
	delete_option("PO_disabled_plugins");
	delete_option("PO_disabled_mobile_plugins");
	delete_option("PO_disabled_groups");
	delete_option("PO_disabled_mobile_groups");
	delete_option("PO_ignore_arguments");
	delete_option("PO_ignore_protocol");
	delete_option("PO_plugin_order");
	delete_option("PO_default_group");
	delete_option("PO_preserve_settings");
	delete_option("PO_alternate_admin");
	delete_option("PO_fuzzy_url_matching");
	delete_option("PO_version_num");
	delete_option("PO_custom_post_type_support");
	delete_option("PO_disable_plugins");
	delete_option("PO_disable_mobile_plugins");
	delete_option("PO_admin_disable_plugins");
	delete_option("PO_auto_trailing_slash");
	delete_option("PO_group_members_corrected");
	delete_option("PO_network_active_plugins_color");
	delete_option("PO_order_access_net_admin");

	delete_option("PO_enabled_search_plugins");
	delete_option("PO_disabled_search_plugins");
	delete_option("PO_enabled_mobile_search_plugins");
	delete_option("PO_disabled_mobile_search_plugins");
	delete_option("PO_enabled_search_groups");
	delete_option("PO_disabled_search_groups");
	delete_option("PO_enabled_mobile_search_groups");
	delete_option("PO_disabled_mobile_search_groups");

	##Delete CPT settings
	$cptSettings = get_option('PO_pt_stored');

	foreach($cptSettings as $cptSetting) {
		delete_option('PO_disabled_pt_plugins_'.$cptSetting);
		delete_option('PO_enabled_pt_plugins_'.$cptSetting);
		delete_option('PO_disabled_mobile_pt_plugins_'.$cptSetting);
		delete_option('PO_enabled_mobile_pt_plugins_'.$cptSetting);
		delete_option('PO_disabled_pt_groups_'.$cptSetting);
		delete_option('PO_enabled_pt_groups_'.$cptSetting);
		delete_option('PO_disabled_mobile_pt_groups_'.$cptSetting);
		delete_option('PO_enabled_mobile_pt_groups_'.$cptSetting);
	}

	delete_option("PO_pt_stored");

	$customPosts = get_posts(array('post_type'=>array('plugin_filter', 'plugin_group'), 'posts_per_page'=>-1));
	foreach($customPosts as $customPost) {
		wp_delete_post( $customPost->ID, true);
	}
}
?>