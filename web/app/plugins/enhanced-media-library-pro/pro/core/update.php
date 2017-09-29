<?php

if ( ! defined( 'ABSPATH' ) )
	exit;



/**
 *  wpuxss_eml_pro_site_transient_update_plugins
 *
 *  @since    2.0
 *  @created  13/10/14
 */

add_filter ('pre_set_site_transient_update_plugins', 'wpuxss_eml_pro_site_transient_update_plugins');

if ( ! function_exists( 'wpuxss_eml_pro_site_transient_update_plugins' ) ) {

    function wpuxss_eml_pro_site_transient_update_plugins( $transient ) {

        $transient = wpuxss_eml_pro_update_transient( $transient, get_option( 'wpuxss_eml_pro_license_key', '' ) );

        return $transient;
    }
}



/**
 *  wpuxss_eml_pro_update_transient
 *
 *  @since    2.1.5
 *  @created  13/01/16
 */

if ( ! function_exists( 'wpuxss_eml_pro_update_transient' ) ) {

    function wpuxss_eml_pro_update_transient( $transient, $license_key ) {

        global $wpuxss_eml_version;


        $new_version = wpuxss_eml_pro_remote_info( 'get-version' );


        if ( empty( $new_version ) || version_compare( $new_version, $wpuxss_eml_version, '<=' ) ) {
            return $transient;
        }


        $wpuxss_eml_pro_basename = wpuxss_get_eml_basename();
        $wpuxss_eml_pro_slug = wpuxss_get_eml_slug();

        $args = array(
            'action' => 'update',
            'key' => $license_key
        );

        $info = new stdClass();

        $info->slug = $wpuxss_eml_pro_slug;
        $info->plugin = $wpuxss_eml_pro_basename;
        $info->new_version = $new_version;
        $info->url = 'https://www.wpuxsolutions.com/enhanced-media-library/';
        $info->package = wpuxss_eml_pro_remote_info( 'is-license-active', $license_key ) ? wpuxss_eml_pro_get_url() . '?' . build_query( $args ) : '';

        $transient->response[$wpuxss_eml_pro_basename] = $info;

        return $transient;
    }
}



/**
 *  wpuxss_eml_pro_plugins_api
 *
 *  @since    2.0
 *  @created  13/10/14
 */

add_filter( 'plugins_api', 'wpuxss_eml_pro_plugins_api', 10, 3 );

if ( ! function_exists( 'wpuxss_eml_pro_plugins_api' ) ) {

    function wpuxss_eml_pro_plugins_api( $res, $action, $args ) {

        $wpuxss_eml_pro_slug = wpuxss_get_eml_slug();

        if ( ! isset( $args->slug ) || $args->slug != $wpuxss_eml_pro_slug )
            return $res;


        // getting info from the free version
        $args->slug = 'enhanced-media-library';

        $url = $http_url = 'http://api.wordpress.org/plugins/info/1.0/';
        if ( $ssl = wp_http_supports( array( 'ssl' ) ) ) {
            $url = set_url_scheme( $url, 'https' );
        }

        $http_args = array(
			'timeout' => 15,
			'body' => array(
				'action' => $action,
				'request' => serialize( $args )
			)
		);
		$request = wp_remote_post( $url, $http_args );

        if ( $ssl && is_wp_error( $request ) ) {

            trigger_error( __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.' ) . ' ' . __( '(WordPress could not establish a secure connection to WordPress.org. Please contact your server administrator.)','enhanced-media-library' ), headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE );
            $request = wp_remote_post( $http_url, $http_args );
        }

        if ( is_wp_error($request) ) {

            $res = new WP_Error('plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.','enhanced-media-library' ), $request->get_error_message() );
        }
        else {

            $res = maybe_unserialize( wp_remote_retrieve_body( $request ) );
            if ( ! is_object( $res ) && ! is_array( $res ) )
            $res = new WP_Error('plugins_api_failed', __( 'An unexpected error occurred. Something may be wrong with WordPress.org or this server&#8217;s configuration. If you continue to have problems, please try the <a href="https://wordpress.org/support/">support forums</a>.','enhanced-media-library' ), wp_remote_retrieve_body( $request ) );
        }


        // getting info from the PRO version
        $body = wpuxss_eml_pro_remote_info( 'get-info' );


        if ( ! empty( $body ) ) {

            $license_key = get_option( 'wpuxss_eml_pro_license_key', '' );

            $wpuxss_eml_args = array(
                'action' => 'update',
                'key' => $license_key
            );

            $res->name          = $body->name;
            $res->slug          = $body->slug;
            $res->version       = $body->version;
            $res->requires      = $body->requires;
            $res->tested        = $body->tested;
            $res->added         = $body->added;
            $res->last_updated  = $body->last_updated;

            $res->download_link = wpuxss_eml_pro_remote_info( 'is-license-active', $license_key ) ? wpuxss_eml_pro_get_url() . '?' . build_query( $wpuxss_eml_args ) : '';

        }

        return $res;
    }
}



/**
 *  wpuxss_eml_pro_in_plugin_update_message
 *
 *  @since    2.0
 *  @created  13/10/14
 */

add_action( 'in_plugin_update_message-' . wpuxss_get_eml_basename(), 'wpuxss_eml_pro_in_plugin_update_message', 10, 2 );

if ( ! function_exists( 'wpuxss_eml_pro_in_plugin_update_message' ) ) {

    function wpuxss_eml_pro_in_plugin_update_message( $plugin_data, $r ) {

        $license_key = get_option( 'wpuxss_eml_pro_license_key', '' );

        if ( wpuxss_eml_pro_remote_info( 'is-license-active', $license_key ) )
            return;


        echo '<br />' . sprintf(
            __('To unlock updates, please <a href="%s">activate your license</a>. You can get your license key in <a href="%s">Your Account</a>. If you do not have a license, you are welcome to <a href="%s">purchase it</a>.', 'enhanced-media-library'),
            admin_url('options-general.php?page=eml-settings#eml-license-key-section'),
            'http://www.wpuxsolutions.com/account/',
            'http://www.wpuxsolutions.com/pricing/'
        );
    }
}



/**
 *  wpuxss_eml_pro_remote_info
 *
 *  @since    2.1
 *  @created  28/10/15
 */

if ( ! function_exists( 'wpuxss_eml_pro_remote_info' ) ) {

    function wpuxss_eml_pro_remote_info( $action, $license_key = '' ) {

        global $wpuxss_eml_version;

        $url = wpuxss_eml_pro_get_url();

        $args = array(
            'timeout' => 15,
            'body' => array(
                'action' => $action,
                'key' => $license_key
            )
        );

        if ( 'get-version' == $action ) {
            $args['body']['version'] = $wpuxss_eml_version;
        }

        $request = wp_remote_post( $url, $args );
        $response = maybe_unserialize( wp_remote_retrieve_body( $request ) );

        return $response;
    }
}



/**
 *  wpuxss_eml_pro_get_url
 *
 *  @since    2.1
 *  @created  28/10/15
 */

if ( ! function_exists( 'wpuxss_eml_pro_get_url' ) ) {

    function wpuxss_eml_pro_get_url() {

        return 'https://www.wpuxsolutions.com/downloads/plugins/enhanced-media-library-pro/';
    }
}

?>
