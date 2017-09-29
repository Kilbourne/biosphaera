<?php


class ResponsiveMenuPro_Controllers_HTMLController extends ResponsiveMenuPro_Controllers_BaseController {
    
    
    /**
     * Prepare the HTML for display on the front end
     *
     * @return null
     * @added 1.0
     */
    
    static function prepare() {
        
        if( !is_admin() ) :
        	if( !ResponsiveMenuPro::getOption( 'use_shortcode' ) )
            	add_action( 'wp_footer', array( 'ResponsiveMenuPro_Controllers_HTMLController', 'display' ) );
		endif;
        
    }
    
    
    /**
     * Creates the view for the menu and echos it out
     *
     * @return string
     * @added 1.0
     */
    
    static function display( $args = null ) {
        
        $options = ResponsiveMenuPro::getOptions();
        
        if( !wp_is_mobile() && $options['use_only_on_mobile'] ) :
            return false;
        else :
            ResponsiveMenuPro_View::make( 'menu', $args ? array_merge( $options, $args ) : $options );
            ResponsiveMenuPro_View::make( 'button', $args ? array_merge( $options, $args ) : $options );
        endif;
    }
    
    
}