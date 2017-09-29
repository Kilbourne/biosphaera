<?php
/**
 * Register Storage Model: MS User ( Multsite USer )
 *
 */
function cpac_register_storage_model_ms_user( $storage_models, $cac ) {

	// Network Users on a multisite, only available on the first subsite
	if ( is_multisite() && is_main_site() ) {

		include_once "storage_model/ms-user.php";
		$storage_model = new CPAC_Storage_Model_MS_User();
		$storage_models[ $storage_model->key ] = $storage_model;
	}

	return $storage_models;
}
add_filter( 'cac/storage_models', 'cpac_register_storage_model_ms_user', 10, 2 );