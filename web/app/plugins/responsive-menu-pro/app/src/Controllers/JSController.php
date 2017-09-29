<?php


class ResponsiveMenuPro_Controllers_JSController extends ResponsiveMenuPro_Controllers_BaseController {
    
        
    /**
     * Prepare our JavaScript for inclusion throughout the site
     *
     * @return null
     * @added 1.0
     */
    
    static function prepare() {

        if( ResponsiveMenuPro::getOption( 'create_external_scripts' ) )
            add_action( 'wp_enqueue_scripts', array( 'ResponsiveMenuPro_Controllers_JSController', 'addExternal' ) );
        else
            add_action( self::inFooter() ? 'wp_footer' : 'wp_head', array( 'ResponsiveMenuPro_Controllers_JSController', 'addInline' ) ); 
                
    }
    
        
    /**
     * Creates and echos the inline styles if used
     *
     * @return string
     * @added 1.0
     */
    
    static function addInline() {
        
        $opt = ResponsiveMenuPro::getOptions();
        
        if( !wp_is_mobile() && $opt['use_only_on_mobile'] ) :
            return false;
        else :
        echo ResponsiveMenuPro::getOption( 'minify' ) == 'minify' ? 
        	ResponsiveMenuPro_Models_JS::Minify( ResponsiveMenuPro_Models_JS::getJs( $opt ) ) : 
        	ResponsiveMenuPro_Models_JS::getJs( $opt );
        endif;    
        
    }
    
        
    /**
     * Adds the external scripts to the site if required
     *
     * @return null
     * @added 1.4
     */
    
    static function addExternal() {
        
        $opt = ResponsiveMenuPro::getOptions();
        
        if( !wp_is_mobile() && $opt['use_only_on_mobile'] ) :
            return false;
        else :
            wp_enqueue_script( 

                'responsive-menu-pro', 
                ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_uri' ) . 'js/responsive-menu-pro-' . get_current_blog_id() . '.js', 
                'jquery.mobile', 
                '1.0', 
                self::inFooter() 

            );
        endif;
        
    }
    
}