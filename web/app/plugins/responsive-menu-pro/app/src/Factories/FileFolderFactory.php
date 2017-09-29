<?php

class ResponsiveMenuPro_Factories_FileFolderFactory {
	
	public static function create() {
		
		$FolderCreator = new ResponsiveMenuPro_FileSystem_FolderCreator;
		$FolderCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_dir' ) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'export' );
		
		if( ResponsiveMenuPro::getOption( 'create_external_scripts' ) ) : 
                        
			$FileCreator = new ResponsiveMenuPro_FileSystem_FileCreator;
			
			/* Create Folders */
			
			$FolderCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_dir' ) );
			$FolderCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_dir' ) . DIRECTORY_SEPARATOR . 'js' );
			$FolderCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_dir' ) . DIRECTORY_SEPARATOR . 'css' );
        
			/* Create JS Content */
            $js = ResponsiveMenuPro_Models_JS::getJs( ResponsiveMenuPro::getOptions() );        
            $js = ResponsiveMenuPro::getOption( 'minify' ) == 'minify' ? ResponsiveMenuPro_Models_JS::Minify( $js ) : $js = $js; 
            
			/* Create JS File */
			$FileCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_dir' ) . 
    				DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'responsive-menu-pro-' . get_current_blog_id() . '.js', $js );

			/* Create CSS Content */
            $css = ResponsiveMenuPro_Models_CSS::getCSS( ResponsiveMenuPro::getOptions() );
            $css = ResponsiveMenuPro::getOption( 'minify' ) == 'minify' ? ResponsiveMenuPro_Models_CSS::Minify( $css ) : $css = $css; 
            
			/* Create CSS File */
			$FileCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_data_dir' ) . 
    				DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'responsive-menu-pro-' . get_current_blog_id() . '.css', $css );
            
        endif;
			
	}
	
}
