<?php

class ResponsiveMenuPro_Controllers_BaseController {
    
    /**
     * Determines wether to display scripts in footer
     *
     * @return boolean
     * @added 2.0
     */
    
    static function inFooter() {
           
        return ResponsiveMenuPro::getOption( 'scripts_in_footer' ) && ResponsiveMenuPro::getOption( 'scripts_in_footer' ) == 'footer' ?  true : false;
        
    }
    
}