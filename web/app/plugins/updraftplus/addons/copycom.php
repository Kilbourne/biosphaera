<?php
/*
UpdraftPlus Addon: copycom:Copy.Com Support
Description: Allows UpdraftPlus to back up to Copy.Com cloud storage
Version: 1.4
Shop: /shop/copycom/
IncludePHP: methods/addon-base.php
Latest Change: 1.12.6
*/

# https://developers.copy.com/documentation
# Undocumented (amongst much else): Paths are case-sensitive (your specified folder must match the case), but clashes are prevented (two paths which differ only by case are not allowed).

if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed');

/*
do_bootstrap($possible_options_array, $connect = true) # Return a WP_Error object if something goes wrong
do_upload($file) # Return true/false
do_listfiles($match)
do_delete($file) - return true/false
do_download($file, $fullpath, $start_offset) - return true/false
do_config_print()
do_credentials_test_parameters() - return an array: keys = required _POST parameters; values = description of each
do_credentials_test($testfile, $posted_settings) - return true/false
do_credentials_test_deletefile($testfile, $posted_settings)
*/

if (!class_exists('UpdraftPlus_RemoteStorage_Addons_Base')) require_once(UPDRAFTPLUS_DIR.'/methods/addon-base.php');

class UpdraftPlus_Addons_RemoteStorage_copycom extends UpdraftPlus_RemoteStorage_Addons_Base {

	public function __construct() {
		# 3rd parameter: chunking? 4th: Test button?
		parent::__construct('copycom', 'Copy.Com', false, false);
	}

	public function do_upload($file, $from) {
		$this->log_errors();
	}
	
	private function log_errors() {
		global $updraftplus;
		$updraftplus->log(__('Barracuda have closed down Copy.Com, as of May 1st, 2016. See:', 'updraftplus').' https://techlib.barracuda.com/CudaDrive/EOL', 'error', 'copycom_going_away');
		$updraftplus->log("Barracuda have closed down Copy.Com, as of May 1st, 2016.");
	}
	
	private function wp_error() {
		return new WP_Error('no_bootstrap', "Barracuda have closed down Copy.Com, as of May 1st, 2016.");
	}

	public function do_download($file, $fullpath, $start_offset) {
		$this->log_errors();
		return false;
	}

	public function do_delete($file) {
		return $this->wp_error();
	}

	public function do_listfiles($match = 'backup_') {
		return array();
	}

	public function do_bootstrap($opts, $connect = true) {
	}

	public function do_config_print($opts) {
		global $updraftplus_admin;

		$folder = (empty($opts['folder'])) ? '' : untrailingslashit($opts['folder']);

		$updraftplus_admin->storagemethod_row(
			'copycom',
			'',
			'<img alt="Copy.Com" src="'.UPDRAFTPLUS_URL.'/images/copycom.png">'
		);

		$updraftplus_admin->storagemethod_row(
			'copycom',
			'',
			'<strong>'.__('Barracuda have closed down Copy.Com, as of May 1st, 2016. See:', 'updraftplus').' <a href="https://techlib.barracuda.com/CudaDrive/EOL">https://techlib.barracuda.com/CudaDrive/EOL</a></strong>'
		);
	}
}

$updraftplus_addons_copycom = new UpdraftPlus_Addons_RemoteStorage_copycom;
