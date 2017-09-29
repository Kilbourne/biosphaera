<?php

class ResponsiveMenuPro_Controllers_InstallController extends ResponsiveMenuPro_Controllers_BaseController {
    
    
    /**
     * Prepare our Installation Options
     *
     * @return null
     * @added 2.0
     */
    
    static function prepare() {
        
        
        register_activation_hook( __FILE__, array( 'ResponsiveMenuPro_InstallController', 'install' ) );
        
        
    }
    
        
    /**
     * Sets our initial default options when menu
     * is first installed
     *
     * @return null
     * @added 1.0
     */
    
    static function install() {

        
        add_option( 'responsive_menu_pro_version', ResponsiveMenuPro_Registry::get( 'config', 'current_version' ) );
        add_option( 'responsive_menu_pro_options', ResponsiveMenuPro_Registry::get( 'defaults' ) );

        
    }
    
    
}