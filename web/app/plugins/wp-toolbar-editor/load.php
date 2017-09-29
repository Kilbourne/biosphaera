<?php
/*
Plugin Name: WordPress Toolbar Editor
Plugin URI: http://adminmenueditor.com/
Description: Lets you edit the WordPress Toolbar (a.k.a. Admin Bar) - the horizontal menu at the top of your page that shows up when you're logged in. You can hide, move, rename and edit existing items, as well as create new menu items. Requires <em>Admin Menu Editor Pro</em>.
Version: 1.2
Author: Janis Elsts
Author URI: http://w-shadow.com/
*/

define('WS_ADMIN_BAR_EDITOR_FILE', __FILE__);
define('WS_ADMIN_BAR_EDITOR_DIR', dirname(__FILE__));

require WS_ADMIN_BAR_EDITOR_DIR . '/includes/auto-versioning.php';
require WS_ADMIN_BAR_EDITOR_DIR . '/Abe/AdminBarEditor.php';
require WS_ADMIN_BAR_EDITOR_DIR . '/Abe/Node.php';

add_action('plugins_loaded', 'abe_init_plugin');
function abe_init_plugin() {
	global $wsAdminBarEditor;

	$isAmeProLoaded = class_exists('WPMenuEditor') && apply_filters('admin_menu_editor_is_pro', false);
	if ( $isAmeProLoaded ) {
		$wsAdminBarEditor = new Abe_AdminBarEditor();
	} else {
		add_action('admin_notices', 'abe_display_dependency_error');
	}
}

function abe_display_dependency_error() {
	//TODO: See how WooCommerce add-ons phrase their error messages and update ours accordingly.
	printf(
		'<div class="error"><p>
			<strong>%1$s is disabled.</strong>
			Please install and activate Admin Menu Editor Pro to use this add-on.
		</p></div>',
		Abe_AdminBarEditor::PLUGIN_NAME
	);
}


