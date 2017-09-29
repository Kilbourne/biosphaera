<?php
$POAdminStyles = get_option('PO_admin_styles');
if (!is_array($POAdminStyles)) {
	$POAdminStyles = array();
}

?>

<style type="text/css">
	.plugin.network-active {
		background-color: <?php print (isset($POAdminStyles['network_plugins_bg_color']) && $POAdminStyles['network_plugins_bg_color'] != '')? $POAdminStyles['network_plugins_bg_color'] : '#D7DF9E'; ?> !important;
		color: <?php print (isset($POAdminStyles['network_plugins_font_color']) && $POAdminStyles['network_plugins_font_color'] != '')? $POAdminStyles['network_plugins_font_color'] : '#444'; ?> !important;
	}

	.plugin.active, .activePluginWrap, #PO-plugin-legend-active {
		background-color: <?php print (isset($POAdminStyles['active_plugins_bg_color']) && $POAdminStyles['active_plugins_bg_color'] != '')? $POAdminStyles['active_plugins_bg_color'] : '#fff'; ?> !important;
		color: <?php print (isset($POAdminStyles['active_plugins_font_color']) && $POAdminStyles['active_plugins_font_color'] != '')? $POAdminStyles['active_plugins_font_color'] : '#444'; ?> !important;
		border-bottom: 1px solid <?php print (isset($POAdminStyles['active_plugins_border_color']) && $POAdminStyles['active_plugins_border_color'] != '')? $POAdminStyles['active_plugins_border_color'] : '#ccc'; ?>;
	}

	.plugin.inactive, .inactivePluginWrap, #PO-plugin-legend-inactive {
		background-color: <?php print (isset($POAdminStyles['inactive_plugins_bg_color']) && $POAdminStyles['inactive_plugins_bg_color'] != '')? $POAdminStyles['inactive_plugins_bg_color'] : '#ddd'; ?> !important;
		color: <?php print (isset($POAdminStyles['inactive_plugins_font_color']) && $POAdminStyles['inactive_plugins_font_color'] != '')? $POAdminStyles['inactive_plugins_font_color'] : '#444'; ?> !important;
		border-bottom: 1px solid <?php print (isset($POAdminStyles['inactive_plugins_border_color']) && $POAdminStyles['inactive_plugins_border_color'] != '')? $POAdminStyles['inactive_plugins_border_color'] : '#fff'; ?>;
	}

	.groupWrap, #PO-plugin-legend-group {
		background-color: <?php print (isset($POAdminStyles['plugin_groups_bg_color']) && $POAdminStyles['plugin_groups_bg_color'] != '')? $POAdminStyles['plugin_groups_bg_color'] : '#fff'; ?> !important;
		color: <?php print (isset($POAdminStyles['plugin_groups_font_color']) && $POAdminStyles['plugin_groups_font_color'] != '')? $POAdminStyles['plugin_groups_font_color'] : '#444'; ?> !important;
		border-bottom: 1px solid <?php print (isset($POAdminStyles['plugin_groups_border_color']) && $POAdminStyles['plugin_groups_border_color'] != '')? $POAdminStyles['plugin_groups_border_color'] : '#ccc'; ?>;
	}
	
	.groupWrap a {
		color: <?php print (isset($POAdminStyles['plugin_groups_font_color']) && $POAdminStyles['plugin_groups_font_color'] != '')? $POAdminStyles['plugin_groups_font_color'] : '#444'; ?> !important;
	}
	
	.globalPluginWrap, .globalGroupWrap, #PO-plugin-legend-global {
		background-color: <?php print (isset($POAdminStyles['global_plugins_bg_color']) && $POAdminStyles['global_plugins_bg_color'] != '')? $POAdminStyles['global_plugins_bg_color'] : '#660011'; ?> !important;
		color: <?php print (isset($POAdminStyles['global_plugins_font_color']) && $POAdminStyles['global_plugins_font_color'] != '')? $POAdminStyles['global_plugins_font_color'] : '#fff'; ?> !important;
		border-bottom: 1px solid <?php print (isset($POAdminStyles['global_plugins_border_color']) && $POAdminStyles['global_plugins_border_color'] != '')? $POAdminStyles['global_plugins_border_color'] : '#ccc'; ?>;
	}

	.globalGroupWrap a {
		color: <?php print (isset($POAdminStyles['global_plugins_font_color']) && $POAdminStyles['global_plugins_font_color'] != '')? $POAdminStyles['global_plugins_font_color'] : '#fff'; ?> !important;
	}

	


	.toggle-button-on {
		background-color: <?php print (isset($POAdminStyles['on_btn_bg_color']) && $POAdminStyles['on_btn_bg_color'] != '')? $POAdminStyles['on_btn_bg_color'] : '#336600'; ?>;
		color: <?php print (isset($POAdminStyles['on_btn_font_color']) && $POAdminStyles['on_btn_font_color'] != '')? $POAdminStyles['on_btn_font_color'] : '#fff'; ?>;
	}

	.toggle-button-off {
		background-color: <?php print (isset($POAdminStyles['off_btn_bg_color']) && $POAdminStyles['off_btn_bg_color'] != '')? $POAdminStyles['off_btn_bg_color'] : '#990000'; ?>;
		color: <?php print (isset($POAdminStyles['off_btn_font_color']) && $POAdminStyles['off_btn_font_color'] != '')? $POAdminStyles['off_btn_font_color'] : '#fff'; ?>;
	}

	.toggle-button-yes {
		background-color: <?php print (isset($POAdminStyles['yes_btn_bg_color']) && $POAdminStyles['yes_btn_bg_color'] != '')? $POAdminStyles['yes_btn_bg_color'] : '#336600'; ?>;
		color: <?php print (isset($POAdminStyles['yes_btn_font_color']) && $POAdminStyles['yes_btn_font_color'] != '')? $POAdminStyles['yes_btn_font_color'] : '#fff'; ?>;
	}
	
	.toggle-button-no {
		background-color: <?php print (isset($POAdminStyles['no_btn_bg_color']) && $POAdminStyles['no_btn_bg_color'] != '')? $POAdminStyles['no_btn_bg_color'] : '#990000'; ?>;
		color: <?php print (isset($POAdminStyles['no_btn_font_color']) && $POAdminStyles['no_btn_font_color'] != '')? $POAdminStyles['no_btn_font_color'] : '#fff'; ?>;
	}
</style>