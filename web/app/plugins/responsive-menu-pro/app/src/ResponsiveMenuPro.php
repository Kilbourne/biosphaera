<?php


class ResponsiveMenuPro {
    
    
    /**
     * Main Construct for the Whole Application
     * Sets Registry and Default Values (if none present)
     *
     * @return null
     * @added 2.0
     */
    
    public function __construct() {
        
        
        if( !get_option( 'responsive_menu_pro_version' ) ) :
                add_option( 'responsive_menu_pro_version', ResponsiveMenuPro_Registry::get( 'config', 'current_version' ) );
        endif;
        
        if( !get_option( 'responsive_menu_pro_options' ) ) :
            add_option( 'responsive_menu_pro_options', ResponsiveMenuPro_Registry::get( 'defaults' ) );
        endif;
        
        ResponsiveMenuPro_Registry::set( 'options', get_option( 'responsive_menu_pro_options' ) );
        ResponsiveMenuPro_Registry::set( 'version', get_option( 'responsive_menu_pro_version' ) );

        
    }
    
        
    /**
     * The main application run function, this sets up all the magic and grunt
     * work of the application, firing off all the different controllers.
     *
     * @return null
     * @added 2.0
     */
    
    public function run() {
        

        ResponsiveMenuPro_Controllers_InstallController::prepare();
        ResponsiveMenuPro_Controllers_UpgradeController::upgrade();
        ResponsiveMenuPro_Controllers_GlobalController::prepare();
        ResponsiveMenuPro_Controllers_FrontController::prepare();
        ResponsiveMenuPro_Controllers_AdminController::prepare();
        ResponsiveMenuPro_Controllers_HTMLController::prepare();
        ResponsiveMenuPro_Controllers_CSSController::prepare();
        ResponsiveMenuPro_Controllers_JSController::prepare();
     
        ResponsiveMenuPro_Shortcode::prepare();
     
    }
    
  
    /**
     * Function to return all options throughout the site, it also
     * automatically mixes in any default options that don't exist
     * in the current version
     *
     * @return array
     * @added 2.1
     */
    
    static function getOptions() {
        
        return array_merge( (array) ResponsiveMenuPro_Registry::get( 'defaults' ), (array) get_option( 'responsive_menu_pro_options' ) );
        
    }
    
    
    /**
     * Function to return individual options throughout the site, it
     * automatically returns the default option if a current value
     * doesn't exist in the current version
     *
     * @return array
     * @added 2.1
     */
    
    static function getOption( $option ) {
        
        $options = self::getOptions();
        
        if( isset( $options[$option] ) )
            return $options[$option];
        
        return ResponsiveMenuPro_Registry::get( 'defaults', $option );
        
    }
    
    /**
     * Function to return if there are created menus in the system
     *
     * @return bool
     * @added 2.3
     */
    
    static function hasMenus() {
        
        if( count( get_terms( 'nav_menu' ) ) > 0 )
            return true;
        
        return false;
        
    }
    
    /**
     * Function to return currently created menus in the system
     *
     * @return object
     * @added 2.3
     */
    
    static function getMenus() {
        
        return get_terms( 'nav_menu' );
        
    }
    
    /*
     * Function to return current theme location menus in the system
     *
     * @return object
     * @added 2.6 Mkdgs
     */
    static function getMenusLocations() {
        
        $menus = get_registered_nav_menus();        
        $r = array();
        
        foreach ( $menus as $location => $description ) {
              $r[] = $o = new stdClass;
              $o->location = $location;
              $o->description = $description;
        }
        
        return $r;
        
    }
    
}