<?php

class ResponsiveMenuPro_Controllers_UpgradeController extends ResponsiveMenuPro_Controllers_BaseController {
    
      
    /**
     * Script that runs if the menu has been upgraded
     *
     * @return mixed
     * @added 2.0
     */
    
    static function upgrade() {
		
        if( self::needsUpgrade() ) :
            
            ResponsiveMenuPro_Factories_FileFolderFactory::create();
            
            /* Update Version */
            update_option( 'responsive_menu_pro_version', ResponsiveMenuPro_Registry::get( 'config', 'current_version' ) );
            
            /* Merge Changes */
            update_option( 'responsive_menu_pro_options', array_merge( ResponsiveMenuPro_Registry::get( 'defaults' ), ResponsiveMenuPro::getOptions() ) );
            
        endif;
            
    }
    
        
    /**
     * Determines whether or not the site needs upgrading
     *
     * @return boolean
     * @added 2.0
     */
    
    static function needsUpgrade() {
    	
		return version_compare( get_option( 'responsive_menu_pro_version' ), ResponsiveMenuPro_Registry::get( 'config', 'current_version' ), '<' );

        
    }
    
    
}