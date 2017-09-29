<?php

class ResponsiveMenuPro_Filters_Float extends ResponsiveMenuPro_Filters_OptionFilter implements ResponsiveMenuPro_Filters_FilterInterface {

	
	public function apply() {
		
		return floatval( $this->option );	
		
	}
	
}
