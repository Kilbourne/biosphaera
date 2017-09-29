<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Page_Builders_Requirements {
	const NOTICE_GROUP = 'requirements';
	const MISSING_REQ_NOTICE_ID = 'missing-requirements';
	const EDITOR_NOTICE_ID = 'enable-translation-editor';
	const DOCUMENTATION_LINK = 'https://wpml.org/documentation/plugins-compatibility/page-builders/';

	private $issues = array();
	private $tm_settings = array();
	private $should_create_editor_notice = false;
	private $notice_model;
	private $sitepress;
	private $page_builders;

	/**
	 * Page_Builders_Requirements constructor.
	 *
	 * @param SitePress $sitepress
	 */
	public function __construct( SitePress $sitepress ) {
		$this->sitepress     = $sitepress;
		$this->tm_settings   = $this->sitepress->get_setting( 'translation-management' );
		$this->page_builders = $this->get_page_builders();
	}

	public function init_hooks() {
		if ( $this->sitepress->get_setting( 'setup_complete' ) ) {
			add_action( 'admin_init', array( $this, 'init' ) );
			add_action( 'wp_ajax_wpml_set_translation_editor', array( $this, 'set_translation_editor_callback' ) );
		}
	}

	public function init() {
		if ( $this->sitepress->get_wp_api()->is_back_end() ) {
			$this->update_issues();
			$this->update_notices();
		}
	}

	private function update_notices() {
		$wpml_admin_notices = wpml_get_admin_notices();

		if ( ! $this->issues ) {
			$wpml_admin_notices->remove_notice( self::NOTICE_GROUP, self::MISSING_REQ_NOTICE_ID );
		}
		if ( ! $this->should_create_editor_notice ) {
			$wpml_admin_notices->remove_notice( self::NOTICE_GROUP, self::EDITOR_NOTICE_ID );
		}


		if ( $this->issues || $this->should_create_editor_notice ) {

			$notice_model = $this->get_notice_model();
			$wp_api       = $this->sitepress->get_wp_api();

			$this->add_requirements_notice( $notice_model, $wpml_admin_notices, $wp_api );
			$this->add_tm_editor_notice( $notice_model, $wpml_admin_notices, $wp_api );
		}

	}

	private function update_issues() {
		$page_builders = new WPML_Page_Builders( $this->sitepress->get_wp_api() );
		$requirements  = new WPML_Requirements();
		$tpd           = new WPML_Third_Party_Dependencies( $page_builders, $requirements );

		$this->issues = $tpd->get_issues();

		$this->update_should_create_editor_notice();
	}

	private function update_should_create_editor_notice() {
		$editor_translation_set = ( 1 === (int) $this->tm_settings['doc_translation_method'] );

		$this->should_create_editor_notice = ! $editor_translation_set && ! $this->issues && $this->page_builders;
	}

	public function set_translation_editor_callback() {
		if ( ! $this->is_valid_request() ) {
			wp_send_json_error( __( 'This action is not allowed', 'sitepress' ) );
		} else {
			$wpml_admin_notices = wpml_get_admin_notices();

			$this->tm_settings['doc_translation_method'] = 1;
			$this->sitepress->set_setting( 'translation-management', $this->tm_settings, true );
			$this->sitepress->set_setting( 'doc_translation_method', 1, true );

			$wpml_admin_notices->remove_notice( self::NOTICE_GROUP, self::EDITOR_NOTICE_ID );

			wp_send_json_success();
		}
	}

	private function is_valid_request() {
		$valid_request = true;
		if ( ! array_key_exists( 'nonce', $_POST ) ) {
			$valid_request = false;
		}
		if ( $valid_request ) {
			$nonce = $_POST['nonce'];

			$nonce_is_valid = wp_verify_nonce( $nonce, 'wpml_set_translation_editor' );
			if ( ! $nonce_is_valid ) {
				$valid_request = false;
			}
		}

		return $valid_request;
	}

	private function get_page_builders() {
		$page_builders       = new WPML_Page_Builders( $this->sitepress->get_wp_api() );
		$page_builders_items = $page_builders->get_results();

		return $page_builders_items;
	}

	/**
	 * @return array
	 */
	private function get_page_builder_names() {
		$page_builders = $this->page_builders;
		$page_builder_names  = array_values( wp_list_pluck( $page_builders, 'name' ) );

		return $page_builder_names;
	}

	/**
	 * @return WPML_Requirements_Notification
	 */
	private function get_notice_model() {
		if ( ! $this->notice_model ) {
			$template_paths   = array(
				ICL_PLUGIN_PATH . '/templates/warnings/',
			);
			$twig_loader      = new Twig_Loader_Filesystem( $template_paths );
			$environment_args = array();
			if ( WP_DEBUG ) {
				$environment_args['debug'] = true;
			}
			$twig         = new Twig_Environment( $twig_loader, $environment_args );
			$twig_service = new WPML_Twig_Template( $twig );

			$this->notice_model = new WPML_Requirements_Notification( $twig_service );
		}

		return $this->notice_model;
	}

	/**
	 * @param WPML_Notice $notice
	 */
	private function add_actions_to_notice( WPML_Notice $notice ) {
		$dismiss_action = new WPML_Notice_Action( __( 'Dismiss', 'sitepress' ), '#', true, false, true );
		$notice->add_action( $dismiss_action );
		$document_action = new WPML_Notice_Action( __( 'Translating content created with page builders', 'sitepress' ), self::DOCUMENTATION_LINK );
		$notice->add_action( $document_action );
	}

	/**
	 * @param WPML_Notice $notice
	 * @param WPML_WP_API $wp_api
	 */
	private function add_callbacks( WPML_Notice $notice, WPML_WP_API $wp_api ) {
		$notice->add_display_callback( array( $wp_api, 'is_core_page' ) );
		$notice->add_display_callback( array( $wp_api, 'is_plugins_page' ) );
		$notice->add_display_callback( array( $wp_api, 'is_themes_page' ) );
	}

	/**
	 * @param WPML_Requirements_Notification $notice_model
	 * @param WPML_Notices $wpml_admin_notices
	 * @param WPML_WP_API $wp_api
	 */
	private function add_requirements_notice( WPML_Requirements_Notification $notice_model, WPML_Notices $wpml_admin_notices, WPML_WP_API $wp_api ) {
		if ( $this->issues ) {
			$warning = $notice_model->get_dependencies( $this->issues, 1 );

			$requirements_notice = new WPML_Notice( self::MISSING_REQ_NOTICE_ID, $warning, self::NOTICE_GROUP );

			$this->add_actions_to_notice( $requirements_notice );
			$this->add_callbacks( $requirements_notice, $wp_api );
			$wpml_admin_notices->add_notice( $requirements_notice, true );
		}
	}

	/**
	 * @param WPML_Requirements_Notification $notice_model
	 * @param WPML_Notices $wpml_admin_notices
	 * @param WPML_WP_API $wp_api
	 */
	private function add_tm_editor_notice( WPML_Requirements_Notification $notice_model, WPML_Notices $wpml_admin_notices, WPML_WP_API $wp_api ) {
		if ( $this->should_create_editor_notice ) {
			$wpml_page_builders_requirements_scripts = new WPML_Page_Builders_Requirements_Scripts();
			$wpml_page_builders_requirements_scripts->init();

			$text   = $notice_model->get_settings( $this->get_page_builder_names() );
			$notice = new WPML_Notice( self::EDITOR_NOTICE_ID, $text, self::NOTICE_GROUP );
			$notice->set_css_class_types( 'info' );

			$enable_action = new WPML_Notice_Action( _x( 'Enable it now', 'Page builder notice title for translation editor: enable action', 'sitepress' ), '#', false, false, true );
			$enable_action->set_js_callback( 'js-set-translation-editor' );
			$notice->add_action( $enable_action );

			$this->add_callbacks( $notice, $wp_api );
			$this->add_actions_to_notice( $notice );
			$wpml_admin_notices->add_notice( $notice );
		}
	}
}
