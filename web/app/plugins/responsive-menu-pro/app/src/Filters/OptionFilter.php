<?php

class ResponsiveMenuPro_Filters_OptionFilter {
	
	protected $option;
	
	public function __construct( $option ) {
		
		if( is_array( $data ) )
			throw new Exception( 'Option must not be an array' );
		else
			$this->option = $option;
		
	}
	
}
