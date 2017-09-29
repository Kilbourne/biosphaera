<?php


class ResponsiveMenuPro_Controllers_CSSController extends ResponsiveMenuPro_Controllers_BaseController {
    
    
    /**
     * Prepare our CSS Outputs
     *
     * @return null
     * @added 2.0
     */
    
    static function prepare() {
        
        if( ResponsiveMenuPro::getOption( 'create_external_scripts' ) )
            add_action( 'wp_enqueue_scripts', array( 'ResponsiveMenuPro_Controllers_CSSController', 'addExternal' ) );
        else
            add_action( 'wp_head', array( 'ResponsiveMenuPro_Controllers_CSSController', 'addInline' ) ); 

    }
    
    
    /**
     * Create and echos the Inline Styles
     *
     * @return string
     * @added 2.0
     */
    
    static function addInline() {
        
        $opt = ResponsiveMenuPro::getOptions();
        
        if( !wp_is_mobile() && $opt['use_only_on_mobile'] ) :
            return false;
        else :
            echo ResponsiveMenuPro::getOption( 'minify' ) == 'minify' ? ResponsiveMenuPro_Models_CSS::Minify( ResponsiveMenuPro_Models_CSS::getCSS( $opt ) ) : ResponsiveMenuPro_Models_CSS::getCSS( $opt ); 
        endif;
        
    }
    
    
    /**
     * Adds External Styles to Header
     *
     * @return null
     * @added 2.0
     */
    
    static function addExternal() {
        
        $opt = ResponsiveMenuPro::getOptions();
        
        if( !wp_is_mobile() && $opt['use_only_on_mobile'] ) :
            return false;
        else :

            wp_enqueue_style( 
                'responsive-menu-pro', 
                ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_uri' ) . 'css/responsive-menu-pro-' . get_current_blog_id() . '.css', 
                array(), 
                '1.0', 
                'all' 
            ); 
            
        endif;   
        
    }
    

}