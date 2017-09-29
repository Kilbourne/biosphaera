<?php


class ResponsiveMenuPro_Controllers_AdminController extends ResponsiveMenuPro_Controllers_BaseController {
    
        
    /**
     * Prepare our Admin Options
     *
     * @return null
     * @added 2.0
     */
    
    static function prepare() {
        
        // Check that we are in the admin area
        if( is_admin() ) : 
            
            add_filter( 'plugin_action_links', array( 'ResponsiveMenuPro_Controllers_AdminController', 'addSettingsLink' ), 10, 2 );
            add_action( 'admin_menu', array( 'ResponsiveMenuPro_Controllers_AdminController', 'addMenus' ) );
        
            // Clear Transients on Saving/Updating Menus/Posts 
            // Added 2.4
            
            if( ResponsiveMenuPro::getOption( 'use_transient_caching' ) ) :
                add_action( 'wp_update_nav_menu', array( 'ResponsiveMenuPro_Transient', 'clearTransientMenus' ) );
                add_action( 'save_post', array( 'ResponsiveMenuPro_Transient', 'clearTransientMenus' ) );
            endif;
            
            // Specifically for Responsive Menu Page
            if( isset( $_GET['page'] ) && $_GET['page'] == 'responsive-menu-pro' ) :

                add_action( 'admin_enqueue_scripts', array( 'ResponsiveMenuPro_Controllers_AdminController', 'colorpicker' ) );
				add_action( 'admin_enqueue_scripts', array( 'ResponsiveMenuPro_Controllers_AdminController', 'addJS' ) );
				add_action( 'admin_enqueue_scripts', array( 'ResponsiveMenuPro_Controllers_AdminController', 'addCSS' ) );
				
            endif;
        
            
        endif;
        

    }

	static function addCSS() {
		
        wp_register_style( 'responsive_menu_pro_css', ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_uri' ) . 'public/css/admin.css' );
        wp_enqueue_style( 'responsive_menu_pro_css' );
	
	}

	static function addJS() {
		
        wp_register_script( 'responsive_menu_pro_js', ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_uri' ) . 'public/js/admin.js', 'jquery' );
        wp_enqueue_script( 'responsive_menu_pro_js' );
	
	}

    
    
    /**
     * Create our admin menus.
     *
     * @return null
     * @added 1.0
     */
    
    static function addMenus() {

        
        add_menu_page( 

            __( 'Responsive Menu Pro', 'responsive-menu-pro' ), 
            __( 'Responsive Menu Pro', 'responsive-menu-pro' ), 
            'manage_options', 
            'responsive-menu-pro', 
            array( 'ResponsiveMenuPro_Controllers_AdminController', 'adminPage' ), 
            ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_uri' ) . 'public/imgs/icon.png' 

        );

        
    }
    
    /**
     * Creates the main admin page and saves the data if submitted
     *
     * @return null
     * @added 1.0
     */
    
    static function adminPage() {
        
		/* Is Export */
        if( ResponsiveMenuPro_Input::post( 'responsive_menu_pro_export' ) )            
            ResponsiveMenuPro_Export::export();

		/* Is Import, Normal Save or Reset */
        if( ResponsiveMenuPro_Input::post( 'responsive_menu_pro_submit' ) 
        || ResponsiveMenuPro_Input::post( 'responsive_menu_pro_import' ) 
		|| ResponsiveMenuPro_Input::post( 'responsive_menu_pro_reset' ) 
		|| ResponsiveMenuPro_Input::post( 'responsive_menu_pro_update_theme' ) ) :
                    
			/* Get our Data Array */
            $data = ResponsiveMenuPro_Input::post( 'responsive_menu_pro_import' ) ? 
            	ResponsiveMenuPro_Import::getData( ResponsiveMenuPro_Input::file( 'responsive_menu_pro_import_file' ) ) : 
            	ResponsiveMenuPro_Input::post();

			/* Reset to defaults */
			if( ResponsiveMenuPro_Input::post( 'responsive_menu_pro_reset' ) ) :
				$data = ResponsiveMenuPro_Registry::get( 'defaults' );
				$data['arrow_shape_active'] = json_decode( $data['arrow_shape_active'] );
				$data['arrow_shape_inactive'] = json_decode( $data['arrow_shape_inactive'] );
			endif;

			/* Apply Theme Options if Required */
			if( ResponsiveMenuPro_Input::post( 'responsive_menu_pro_update_theme' ) ) :
				$Theme = new ResponsiveMenuPro_Theme( ResponsiveMenuPro_Input::post() );
				$data = $Theme->apply( ResponsiveMenuPro_Input::post( 'theme' ) );
			endif;
			
			/* Apply Final Filters to avoid setting conflicts */
			$FinalFilters = new ResponsiveMenuPro_Filters_Final( $data );
			$data = $FinalFilters->apply();
			
			/* Save Details to database */
            ResponsiveMenuPro_Models_Admin::save( $data );
     
	 		/* Clear Transient Menus */
	 		if( ResponsiveMenuPro::getOption( 'use_transient_caching' ) )
	 			ResponsiveMenuPro_Transient::clearTransientMenus();
	 		
			/* Create all our Files and Folders */
			ResponsiveMenuPro_Factories_FileFolderFactory::create();
                
        endif;    
	
		/* Return with Admin Page */
        ResponsiveMenuPro_View::make( 'admin.page', ResponsiveMenuPro::getOptions() );
        
    }
    
    /**
     * Adds the WordPress Colour Picker to the admin options page
     *
     * @return null
     * @added 1.0
     */
    
    static function colorpicker(){ 
    
        
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        
    }
    
        
    /**
     * Adds the settings link on the WordPress Plugins Page
     *
     * @param array $links
     * @param string $file
     * @return array
     * @added 2.0
     */
    
    static function addSettingsLink( $links, $file ) {
        
        
        if ( $file == 'responsive-menu-pro/responsive-menu-pro.php' ) :

            $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=responsive-menu-pro">';
            $settings_link .= __( 'Settings', 'responsive-menu-pro' );
            $settings_link .= '</a>';
            
            array_unshift( $links, $settings_link );

        endif;

        return $links;

    
    }

    
}