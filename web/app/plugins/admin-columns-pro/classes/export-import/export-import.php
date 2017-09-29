<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAC_EI_URL', plugin_dir_url( __FILE__ ) );
define( 'CAC_EI_DIR', plugin_dir_path( __FILE__ ) );

// only run plugin in the admin interface
if ( ! is_admin() ) {
	return false;
}

require_once CAC_EI_DIR . 'functions.php';
require_once CAC_EI_DIR . 'classes/export_import.php';