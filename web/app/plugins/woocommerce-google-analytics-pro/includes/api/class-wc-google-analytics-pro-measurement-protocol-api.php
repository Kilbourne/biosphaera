<?php
/**
 * WooCommerce Google Analytics Pro
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
 * Do not edit or add to this file if you wish to upgrade WooCommerce Google Analytics Pro to newer
 * versions in the future. If you wish to customize WooCommerce Google Analytics Pro for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-google-analytics-pro/ for more information.
 *
 * @package     WC-Google-Analytics-Pro/Measurement-Protocol-API
 * @author      SkyVerge
 * @copyright   Copyright (c) 2012-2014, SkyVerge, Inc.
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Google Analytics Pro Measurement Protocol API Wrapper class
 *
 * A basic wrapper around the GA Measurement Protocol HTTP API used for making
 * server-side API calls to track events
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro_Measurement_Protocol_API extends SV_WC_API_Base {


	/** @var string endpoint for GA API */
	public $ga_url = 'https://ssl.google-analytics.com/collect';

	/** @var string Google Analytics tracking ID */
	private $tracking_id;


	/**
	 * Constructor.
	 *
	 * @param int $tracking_id Google Tracking ID from settings
	 * @since 1.0.0
	 */
	public function __construct( $tracking_id ) {

		$this->tracking_id    = $tracking_id;
		$this->request_uri    = $this->ga_url;
		$this->request_method = 'POST';
	}


	/**
	 * Track an event via the Measurement Protocol
	 *
	 * @since 1.0.0
	 * @param string $event_name Event name, used from settings page.
	 * @param array $identities GA User Identity and the WP User ID, if available
	 * @param array $properties List of event properties such as eventCategory and eventAction.
	 * @param array $ec Additional Enhanced Ecommerce data to be sent with the event
	 */
	public function track_event( $event_name, $identities, $properties = array(), $ec = array() ) {

		try {

			// make sure tracking code exists
			if ( empty( $this->tracking_id ) ) {
				return;
			}

			$request = $this->get_new_request();

			$request->identify( $identities['cid'], isset( $identities['uid'] ) ? $identities['uid'] : null );
			$request->track_event( $event_name, $properties );

			// add enhanced ecommerce data to request
			if ( ! empty( $ec ) ) {

				if ( isset( $ec['purchase'] ) && $ec['purchase'] ) {
					$request->track_ec_purchase( $ec['purchase'] );
				}

				if ( isset( $ec['refund'] ) && $ec['refund'] ) {

					$order = $ec['refund'][0];
					$items = isset( $ec['refund'][1] ) ? $ec['refund'][1] : null;

					$request->track_ec_refund( $order, $items );
				}

				if ( isset( $ec['add_to_cart'] ) && $ec['add_to_cart'] ) {

					$product  = $ec['add_to_cart'][0];
					$quantity = isset( $ec['add_to_cart'][1] ) ? $ec['add_to_cart'][1] : 1;

					$request->track_ec_add_to_cart( $product, $quantity );
				}

				if ( isset( $ec['remove_from_cart'] ) && $ec['remove_from_cart'] ) {
					$request->track_ec_remove_from_cart( $ec['remove_from_cart'] );
				}
			}

			$this->set_response_handler( 'WC_Google_Analytics_Pro_Measurement_Protocol_API_Response' );

			$this->perform_request( $request );

		} catch ( SV_WC_API_Exception $e ) {

			/* translators: Placeholders: %s - error message */
			$error = sprintf( __( 'Error tracking event: %s', 'woocommerce-google-analytics-pro' ), $e->getMessage() );

			if ( wc_google_analytics_pro()->debug_mode_on() ) {
				wc_google_analytics_pro()->log( $error );
			}
		}
	}


	/**
	 * Builds and returns a new API request object
	 *
	 * @param string $type
	 * @return \WC_Google_Analytics_Pro_Measurement_Protocol_API_Request
	 */
	protected function get_new_request( $type = null ){

		return new WC_Google_Analytics_Pro_Measurement_Protocol_API_Request( $this->tracking_id );
	}


	/**
	 * Get the request user agent.
	 *
	 * Checks for the presence of a browser to send to Google Analytics.
	 *
	 * @see \SV_WC_API_Base::get_request_user_agent() for the default
	 * @since 1.0.2
	 * @return string
	 */
	protected function get_request_user_agent() {

		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
		} else {
			$user_agent = parent::get_request_user_agent();
		}

		return $user_agent;
	}


	/**
	 * Returns the main plugin class
	 *
	 * @since 1.0.0
	 * @see \SV_WC_API_Base::get_plugin()
	 * @return object
	 */
	protected function get_plugin() {

		return wc_google_analytics_pro();
	}


}
