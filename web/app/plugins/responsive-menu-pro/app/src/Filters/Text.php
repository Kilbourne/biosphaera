<?php

class ResponsiveMenuPro_Filters_Text extends ResponsiveMenuPro_Filters_OptionFilter implements ResponsiveMenuPro_Filters_FilterInterface {

	
	public function apply() {
		
		return stripslashes( strip_tags( trim( $this->option ) ) );

		
	}
	
}
