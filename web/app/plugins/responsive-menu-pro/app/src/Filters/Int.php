<?php

class ResponsiveMenuPro_Filters_Int extends ResponsiveMenuPro_Filters_OptionFilter implements ResponsiveMenuPro_Filters_FilterInterface {

	
	public function apply() {
		
		return intval( $this->option );

	}
	
}
