<?php

class ResponsiveMenuPro_Filters_Html extends ResponsiveMenuPro_Filters_OptionFilter implements ResponsiveMenuPro_Filters_FilterInterface {

	
	public function apply() {
		
		return trim( stripslashes( $this->option  ) );

		
	}
	
}
