<?php

class ResponsiveMenuPro_Transient {
    
    /**
     * Function to get named cached transient menu
     *
     * @param  string  $name
     * @return string
     * @added 2.3
     * @edited 2.4 - Added option to use transient caching
     */
    
    static function getTransientMenu( $data ) {

        $Transient = ResponsiveMenuPro::getOption( 'use_transient_caching' );

        if( $Transient ) :

            $cachedKey = 'responsive_menu_pro_' . $data['menu'] . '_' . get_current_blog_id();
            $cachedMenu = get_transient( $cachedKey );
            
        else :
            
            $cachedMenu = false;
        
        endif;

        if( $cachedMenu === false ) :

            $cachedMenu = self::createTransientMenu( $data );
			
            if( $Transient )
                set_transient( $cachedKey, $cachedMenu );
        
        endif;
        
        return $cachedMenu;
        
    }
    
     /**
     * Function to create named cached transient menu
     *
     * @param  string  $name
     * @return array
     * @added 2.3
     */
    
    static function createTransientMenu( $data ) {
        	
        if ( $data['theme_location'] ) { // if theme_location is used, menu is no used 
            $data['menu'] = null;
        }
		
        $cachedMenu = wp_nav_menu( array(
                'theme_location' => $data['theme_location'], 
                'menu' => $data['menu'],
                'container_class' => 'responsive_menu_pro_container',
				'container_id'    => 'responsive_menu_pro_container',
                'menu_class' => 'responsive_menu_pro_menu',
                'menu_id' => 'responsive_menu_pro_menu',
                'depth' => $data['depth'] ,
                'walker' => ( !empty( $data['walker'] ) ) ? new $data['walker'] : '', // Add by Mkdgs
                'echo' => false 
                )
            );
        
        return $cachedMenu;
        
    }
    
    /**
     * Function to clear all transient menus
     *
     * @return null
     * @added 2.3
     */
    
    static function clearTransientMenus() {
        
        if( ResponsiveMenuPro::hasMenus() ) :

            foreach( ResponsiveMenuPro::getMenus() as $menu ) :

                delete_transient( 'responsive_menu_pro_' . $menu->slug . '_' . get_current_blog_id() );

            endforeach;

        endif;
        
    }
    
}
