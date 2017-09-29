<?php

class ResponsiveMenuPro_FileSystem_FolderCreator {

	public function create( $folderName ) {

        if( !file_exists( $folderName ) ) mkdir( $folderName, 0777 );

        if( !file_exists( $folderName ) )
            ResponsiveMenuPro_Status::set( 'error', __( 'Unable to create folders', 'responsive-menu-pro' ) );
		
	}
	
}
