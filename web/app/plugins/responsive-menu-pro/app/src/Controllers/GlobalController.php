<?php


class ResponsiveMenuPro_Controllers_GlobalController extends ResponsiveMenuPro_Controllers_BaseController {
    
        
    /**
     * Prepare our Global Options
     *
     * @return null
     * @added 2.0
     */
    
    static function prepare() {
        
        
        add_action( 'plugins_loaded', array( 'ResponsiveMenuPro_Controllers_GlobalController', 'Internationalise' ) );
        add_action( 'wp_enqueue_scripts', array( 'ResponsiveMenuPro_Controllers_GlobalController', 'jQuery' ) );

         
    }
    

    /**
     * Makes sure jQuery is added to all pages as it is needed for the
     * system to work
     *
     * @return null
     * @added 1.0
     */
    
    static function jQuery() {
        
        
        wp_enqueue_script( 'jquery' ); 
        
        
    }
    
    
    /**
     * Loads our Translations for use throughout the program
     *
     * Current Translations:
     * 
     * hr_HR - Croatian - With thanks to Neverone Design - https://www.facebook.com/pages/Neverone-design/490262371018076
     * es_ES - Spanish - With thanks to Andrew @ WebHostingHub - http://www.webhostinghub.com
     * 
     * @return null 
     * @added 1.6
     */
    
    
    static function Internationalise() {

        
        __( 'Responsive Menu Pro', 'responsive-menu-pro' );
        
        load_plugin_textdomain( 'responsive-menu-pro', false, 'responsive-menu-pro/translations/' );

        
    }
    
    
}