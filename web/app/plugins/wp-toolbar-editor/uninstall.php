<?php
/**
 * @author Yahnis Elsts
 * @copyright 2013
 *
 * The uninstallation script.
 */

if( defined( 'ABSPATH') && defined('WP_UNINSTALL_PLUGIN') ) {

	//Remove plugin settings
	delete_option('ws_abe_admin_bar_settings');
	delete_option('ws_abe_admin_bar_nodes');
	delete_option('ws_abe_override_global_menu');

	if ( is_multisite() ){
		delete_site_option('ws_abe_admin_bar_settings');

		// Theoretically, we should also delete per-site options (ws_abe_override_global_menu,
		// ws_abe_admin_bar_nodes) from all sites in the network. There is, however, no easy way
		// to do that. Calling switch_to_blog() for every site is a bad idea performance-wise.
	}

	//Remove update metadata
	delete_site_option('ws_abe_external_updates');
}