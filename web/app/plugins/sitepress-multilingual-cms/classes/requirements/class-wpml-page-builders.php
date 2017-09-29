<?php

/**
 * @author OnTheGo Systems
 */
class WPML_Page_Builders {
	private $constants_checks = array(
		'page-builders' => array(
			'js_composer' => array(
				'name'     => 'Visual Composer',
				'constant' => 'WPB_VC_VERSION',
			),
			'divi'        => array(
				'name'     => 'Divi',
				'constant' => 'ET_BUILDER_DIR',
			),
			'layouts'     => array(
				'name'     => 'Toolset Layouts',
				'constant' => 'WPDDL_VERSION',
			),
		),
	);
	private $items = array();
	private $wpml_wp_api;

	/**
	 * WPML_Page_Builders constructor.
	 *
	 * @param WPML_WP_API $wpml_wp_api
	 */
	function __construct( WPML_WP_API $wpml_wp_api ) {
		$this->wpml_wp_api = $wpml_wp_api;
		$this->fetch_items();
	}

	private function fetch_items() {
		foreach ( $this->constants_checks as $type => $components ) {
			foreach ( (array) $components as $slug => $data ) {
				if ( $this->wpml_wp_api->defined( $data['constant'] ) ) {
					$this->items[ $slug ]         = array( 'name' => $data['name'] );
					$this->items[ $slug ]['type'] = $type;
				}
			}
		}
	}

	public function get_results() {
		return $this->items;
	}
}
