<?php

class WPML_PB_Update_Translated_Posts_From_Original {

	/** @var IWPML_PB_Strategy $strategy */
	private $strategy;
	/** @var  SitePress $sitepress */
	private $sitepress;

	public function __construct( SitePress $sitepress, IWPML_PB_Strategy $strategy ) {
		$this->sitepress = $sitepress;
		$this->strategy = $strategy;
	}

	public function update( $original_post ) {
		$original_post_id = $original_post->ID;
		$string_packages  = apply_filters( 'wpml_st_get_post_string_packages', false, $original_post_id );

		$element_type      = 'post_' . $original_post->post_type;
		$trid              = $this->sitepress->get_element_trid( $original_post_id, $element_type );
		$post_translations = $this->sitepress->get_element_translations( $trid, $element_type );

		foreach ( $string_packages as $package ) {
			$package_data = array( 'package'   => $package,
			                       'languages' => $this->get_target_languages( $post_translations )
			);
			$update_post  = $this->strategy->get_update_post( $package_data );
			$update_post->update();
		}
	}

	private function get_target_languages( $post_translations ) {
		$langs = array();

		foreach ( $post_translations as $post_translation ) {
			if ( ! $post_translation->original ) {
				$langs[] = $post_translation->language_code;
			}
		}

		return $langs;
	}

}
