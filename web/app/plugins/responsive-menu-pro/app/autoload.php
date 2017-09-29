<?php

/*
|--------------------------------------------------------------------------
| Autoload our application
|--------------------------------------------------------------------------
|
| Here we include all our required files for the application to run correctly.
| At the moment this is a big mess of require_once calls and needs to be 
| tidied up with an autoloader function
|
*/

function responsive_menu_pro_autoload( $class ) {
	
	// Remove prepended ResponsiveMenuPro 
	// and change underscore for directory seperator
	$resolution = trim( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace( 
		array( 'ResponsiveMenuPro_', '_' ), 
		array( '', DIRECTORY_SEPARATOR ), 
		$class 
	) . '.php' );
	
	if( file_exists( $resolution ) )
    	include $resolution;	
	
}

spl_autoload_register( 'responsive_menu_pro_autoload' );