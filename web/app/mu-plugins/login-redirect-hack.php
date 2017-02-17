<?php
/*
Plugin Name:  Login Redirect HAck
Version:      1.0.0
Author:       Kilbourne
License:      MIT License
*/

function my_login_redirect( $redirect_to, $request, $user ) {
	//is there a user to check?
	if (strpos($request,'wp-admin') !== false) {
    	return home_url().'/wp/wp-admin/';
	}
	
	
}

add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );