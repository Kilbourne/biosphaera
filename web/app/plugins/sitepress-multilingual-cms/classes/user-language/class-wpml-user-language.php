<?php

/**
 * @package wpml-core
 * @subpackage wpml-user-language
 */
class WPML_User_Language {
	/** @var  SitePress $sitepress */
	protected $sitepress;

	private $language_changes_history       = array();
	private $admin_language_changes_history = array();
	private $language_switched              = false;

	public function __construct( SitePress $sitepress, wpdb $wpdb = null ) {
		$this->sitepress = $sitepress;

		if ( ! $wpdb ) {
			global $wpdb;
		}
		$this->wpdb = $wpdb;

		$this->language_changes_history[] = $sitepress->get_current_language();
		$this->admin_language_changes_history[] = $this->sitepress->get_admin_language();

		$this->register_hooks();
	}

	public function register_hooks() {
		add_action( 'wpml_switch_language_for_email', array( $this, 'switch_language_for_email_action' ), 10, 1 );
		add_action( 'wpml_restore_language_from_email', array( $this, 'restore_language_from_email_action' ), 10, 0 );
		add_action( 'profile_update', array( $this, 'sync_admin_user_language_action' ), 10, 2 );
	}

	public function switch_language_for_email_action( $email ) {
		$this->switch_language_for_email( $email );
	}

	private function switch_language_for_email( $email ) {
		$language = apply_filters( 'wpml_user_language', null, $email );

		if ( $language ) {
			$this->language_switched                = true;
			$this->language_changes_history[]       = $language;
			$this->admin_language_changes_history[] = $language;

			$this->sitepress->switch_lang( $language, true );

			$this->sitepress->set_admin_language( $language );
		}
	}

	public function restore_language_from_email_action() {
		$this->wpml_restore_language_from_email();
	}

	private function wpml_restore_language_from_email() {
		if ( $this->language_switched ) {
			$this->language_switched = false;

			$this->sitepress->switch_lang( $this->language_changes_history[0], true );

			$this->sitepress->set_admin_language( $this->admin_language_changes_history[0] );
		}
	}

	public function sync_admin_user_language_action( $user_id ) {
		if ( $this->user_needs_sync_admin_lang() ) {
			$this->sync_admin_user_language( $user_id );
		}
	}

	public function sync_default_admin_user_languages() {
		$sql_users   = 'SELECT user_id FROM ' . $this->wpdb->usermeta . ' WHERE meta_key = %s AND meta_value = %s';
		$query_users = $this->wpdb->prepare( $sql_users, array( 'locale', '' ) );
		$user_ids    = $this->wpdb->get_col( $query_users );

		if ( $user_ids ) {
			$language = $this->sitepress->get_default_language();

			$sql   = 'UPDATE ' . $this->wpdb->usermeta . ' SET meta_value = %s WHERE meta_key = %s and user_id IN (' . wpml_prepare_in( $user_ids ) . ')';
			$query = $this->wpdb->prepare( $sql, array( $language, 'icl_admin_language' ) );

			$this->wpdb->query( $query );
		}
	}

	private function sync_admin_user_language( $user_id ) {
		$wp_language = get_user_meta( $user_id, 'locale', true );

		if ( $wp_language ) {
			$user_language = $this->sitepress->get_language_code_from_locale( $wp_language );
		} else {
			$user_language = $this->sitepress->get_default_language();
		}
		update_user_meta( $user_id, 'icl_admin_language', $user_language );
	}

	private function user_needs_sync_admin_lang() {
		$wp_api = $this->sitepress->get_wp_api();
		return $wp_api->version_compare_naked( get_bloginfo( 'version' ), '4.7', '>=' );
	}
}
