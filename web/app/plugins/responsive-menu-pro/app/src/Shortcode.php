<?php

class ResponsiveMenuPro_Shortcode {
    
    /**
     * Function to set a new status in the system
     *
     * @param  string  $type
     * @param string $text
     * @return null
     * @added 2.0
     */
    
    static function prepare() {
        
        
        if( ResponsiveMenuPro::getOption( 'use_shortcode' ) )
            add_shortcode( 'responsive_menu_pro', array( 'ResponsiveMenuPro_Controllers_HTMLController', 'display' ) );

        
    }
    
    
}