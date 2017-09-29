<?php
/**
 * @author OnTheGo Systems
 */
class WPML_Requirements {
	private $active_plugins       = array();
	private $missing_requirements = array();

	private $requirements = array(
		'page-builders' => array(
			'wpml-string-translation'     => array(
				'name' => 'WPML String Translation',
				'url'  => 'https://wpml.org/documentation/translating-your-contents/page-builders/',
			),
			'wpml-translation-management' => array(
				'name' => 'WPML Translation Management',
				'url'  => 'https://wpml.org/documentation/translating-your-contents/page-builders/',
			),
		),
	);

	/**
	 * WPML_Requirements constructor.
	 */
	public function __construct() {
		if ( function_exists( 'get_plugins' ) ) {
			$installed_plugins = get_plugins();
			foreach ( $installed_plugins as $plugin_file => $plugin_data ) {
				if ( is_plugin_active( $plugin_file ) ) {
					$plugin_slug                          = $this->get_plugin_slug( $plugin_data );
					$this->active_plugins[ $plugin_slug ] = $plugin_data;
				}
			}
		}
	}

	public function get_plugin_slug( array $plugin_data ) {
		$plugin_slug = null;
		if ( array_key_exists( 'Plugin Slug', $plugin_data ) && $plugin_data['Plugin Slug'] ) {
			$plugin_slug = $plugin_data['Plugin Slug'];
		} elseif ( array_key_exists( 'TextDomain', $plugin_data ) && $plugin_data['TextDomain'] ) {
			$plugin_slug = $plugin_data['TextDomain'];
		} elseif ( array_key_exists( 'Name', $plugin_data ) && $plugin_data['Name'] ) {
			$plugin_slug = $plugin_data['Name'];
		}

		return $plugin_slug;
	}

	public function get_missing_requirements() {
		return $this->missing_requirements;
	}

	/**
	 * @param $component_type
	 *
	 * @return array
	 */
	public function get_requirements( $component_type ) {
		$missing_plugins = $this->get_missing_plugins_for_type( $component_type );

		$requirements = array();

		if ( $missing_plugins ) {
			foreach ( (array) $this->requirements[ $component_type ] as $plugin_slug => $plugin_data ) {
				$requirement            = $plugin_data;
				$requirement['missing'] = false;
				if ( in_array( $plugin_slug, $missing_plugins, true ) ) {
					$requirement['missing']       = true;
					$this->missing_requirements[] = $requirement;
				}
				$requirements[] = $requirement;
			}
		}

		return $requirements;
	}

	private function get_missing_plugins_for_type( $plugin_type ) {
		$requirements_keys   = array_keys( $this->requirements[ $plugin_type ] );
		$active_plugins_keys = array_keys( $this->active_plugins );

		return array_diff( $requirements_keys, $active_plugins_keys );
	}
}
