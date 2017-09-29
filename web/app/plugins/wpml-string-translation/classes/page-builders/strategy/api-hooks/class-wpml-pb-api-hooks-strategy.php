<?php

class WPML_PB_API_Hooks_Strategy implements IWPML_PB_Strategy {

	/** @var  WPML_PB_Factory $factory */
	private $factory;
	private $name;

	public function __construct( $name ) {
		$this->name = $name;
	}

	/**
	 * @param $post
	 *
	 */
	public function register_strings( $post ) {
		// No need to do anything here because Plugins should already register their strings via wpml_register_string shortcode.
	}

	public function set_factory( $factory ) {
		$this->factory = $factory;
	}

	public function get_package_key( $page_id ) {
		// This is not needed because we are not registering strings
	}

	public function get_package_kind() {
		return $this->name;
	}

	public function get_update_post( $package_data) {
		return $this->factory->get_update_post( $package_data, $this );
	}

	public function get_content_updater() {
		return $this->factory->get_api_hooks_content_updater( $this );
	}

	public function get_package_strings( $package_data ) {
		$this->factory->get_string_translations( $this )->get_package_strings( $package_data );
	}
	public function remove_string( $string_data ) {
		$this->factory->get_string_translations( $this )->remove_string( $string_data );
	}
}
