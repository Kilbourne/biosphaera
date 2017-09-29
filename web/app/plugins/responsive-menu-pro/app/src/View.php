<?php

class ResponsiveMenuPro_View {
    
        
    /**
     * Create a new view for display throughout the application
     * Users .phtml files found in the app/views folder
     *
     * @param  string  $page
     * @param mixed $data
     * @return null
     * @added 2.0
     */
    
    static function make( $page, $data ) {  
        
        require ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_dir' ) .  
        DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 
        str_replace( '.', DIRECTORY_SEPARATOR, $page ) . '.phtml';
           
    }

    
    /**
     * Function to format and display the status bar in the admin pages
     *
     * @param  array  $status
     * @return string
     * @added 2.0
     */
    
    static function statusBar( $status ) {

        
        $message = null;        
        
        foreach( $status as $stati ) :
            
            $message .= '<div id="message" class="' . $stati[0] . ' below-h2">';
            $message .= '<p>' . $stati[1] . '</p>';
            $message .= '</div>';

        endforeach;

        return $message;
                
        
    }
    
    
    /**
     * Function to format and display the search bar in the main menu
     *
     * @return string
     * @added 2.0
     */
    
    static function searchBar( $placeholder ) { 
        
        /* Added for WPML Compatibility in 2.2
         * Thanks to miguelcortereal for this */
        
        $action = function_exists( 'icl_get_home_url' ) ? icl_get_home_url() : get_home_url(); ?>

        <form action="<?php echo $action; ?>" id="responsive_menu_pro_search" method="get" role="search">
            <input type="search" name="s" value="" placeholder="<?php _e( $placeholder, 'responsive-menu-pro' ); ?>" id="responsive_menu_pro_search_input">            
        </form>                        
                        
   <?php 
   
    } 
    
    /**
     * Function to format and display the search bar in the header menu
     *
     * @return string
     * @added 1.0.8
     */
    
    static function searchBarHeader( $placeholder ) { 
        
        $action = function_exists( 'icl_get_home_url' ) ? icl_get_home_url() : get_home_url(); ?>

        <form action="<?php echo $action; ?>" id="responsive_menu_pro_header_search" method="get" role="search">
            <input type="search" name="s" value="" placeholder="<?php _e( $placeholder, 'responsive-menu-pro' ); ?>" id="responsive_menu_pro_header_search_input">            
        </form>                        
                        
   <?php 
   
    }
    
    
    /**
     * Function to format and display the additional content in the main menu
     *
     * @return string
     * @added 2.0
     */
    
    static function additionalContent( $html ) { ?>
        
        <div id="responsive_menu_pro_additional_content">
            <?php echo do_shortcode( $html ); ?>
        </div>
                                      
    <?php 
   
    }
    
    
}