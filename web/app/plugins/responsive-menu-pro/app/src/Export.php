<?php

class ResponsiveMenuPro_Export {
    
  
    /**
     * Function to create export XML file
     *
     * @return file xml
     * @added 2.2
     */
    
    static function export() {
        
        if( !is_admin() ) exit();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<responsive_menu_pro_options>';
        
        foreach( ResponsiveMenuPro::getOptions() as $option_key => $option_val ) :
            
            $xml .= '<' . $option_key . '>' . base64_encode( $option_val ) . '</' . $option_key . '>';
                
        endforeach;
        
        $xml .= '</responsive_menu_pro_options>';
        
		$FileCreator = new ResponsiveMenuPro_FileSystem_FileCreator();
		$NewFile = $FileCreator->create( ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_dir' ) . 
        DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR  . 'export' . DIRECTORY_SEPARATOR  . 'export.xml', $xml );

        $link = ResponsiveMenuPro_Registry::get( 'config', 'plugin_base_uri' ) . 'public/export/export.xml';
        
		if( $NewFile )
        	ResponsiveMenuPro_Status::set( 'updated', '<a href="' . $link . '">' . __( 'You can download your exported file by clicking here', 'responsive-menu-pro' ) . '</a>' );
        
        
    }
    
    
}