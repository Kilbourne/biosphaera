<?php

class WPML_Page_Builders_Requirements_Scripts {

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'wpml-page-builders-requirements-scripts', ICL_PLUGIN_URL . '/res/js/page_builders_requirements.js', array( 'jquery' ) );
	}
}