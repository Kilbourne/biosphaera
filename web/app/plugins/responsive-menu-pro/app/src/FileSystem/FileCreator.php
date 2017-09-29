<?php

class ResponsiveMenuPro_FileSystem_FileCreator {
	
	public function create( $fileName, $data ) {
		
        $file = fopen( $fileName, 'w' );
        fwrite( $file, $data );
        
        fclose( $file );
        
        if( !$file ) :
            ResponsiveMenuPro_Status::set( 'error', __( 'Unable to create file', 'responsive-menu-pro' ) );
			return false;
		endif;
		
		return true;
		
	}
	
	
}
