<?php

class ResponsiveMenuPro_Filters_Final implements ResponsiveMenuPro_Filters_FilterInterface {
	
	protected $data;
	
	public function __construct( $data ) {
		
		if( is_array( $data ) )
			$this->data = $data;
		else
			throw new Exception( 'Data must be an array' );
		
	}
	
	public function apply() {
		
		/* 
		 * Turn ON shortcode 
		 * Turn OFF Use only on mobile
		 * IF single site menu is on
		 */
		if( isset( $this->data['use_single_site_menu'] ) && $this->data['use_single_site_menu'] == 'use_single_site_menu' ) :
			$this->data['use_shortcode'] = 'use_shortcode';
			$this->data['use_only_on_mobile'] = false;
		endif;
		
		return $this->data;	
		
	}
	
}
