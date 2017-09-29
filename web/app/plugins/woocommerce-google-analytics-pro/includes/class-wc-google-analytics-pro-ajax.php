<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Classes
 * @author    SkyVerge
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * AJAX class
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro_AJAX {


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wp_ajax_wc_google_analytics_pro_revoke_access', array( $this, 'revoke_access' ) );
	}


	/**
	 * Revoke access to a Google Account
	 *
	 * @since 1.0.0
	 */
	public function revoke_access() {

		check_ajax_referer( 'revoke-access', 'security' );

		$url = wc_google_analytics_pro()->get_integration()->get_access_token_revoke_url();

		$response = wp_safe_remote_get( $url );

		// log errors
		if ( is_wp_error( $response ) ) {

			wc_google_analytics_pro()->log( sprintf( 'Could not revoke access token: %s', json_encode( $response->errors ) ) );
		}

		delete_option( 'wc_google_analytics_pro_access_token' );
		delete_option( 'wc_google_analytics_pro_refresh_token' );
		delete_option( 'wc_google_analytics_pro_account_id' );
		delete_transient( 'wc_google_analytics_pro_profiles' );

		wp_die();
	}


}
