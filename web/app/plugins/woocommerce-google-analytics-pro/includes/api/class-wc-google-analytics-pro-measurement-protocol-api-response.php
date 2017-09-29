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
 * needs please refer to http://docs.woothemes.com/document/woocommerce-google-analytics-pro/
 *
 * @package   WC-Google-Analytics-Pro/API/Response
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
defined( 'ABSPATH' ) or exit;

/**
 * Google Analytics Pro Measurement Protocol API Response Class
 *
 * Parses response string received from Google Analytics which will just be 2xx status code if it was received.
 *
 * @link https://developers.google.com/analytics/devguides/collection/protocol/v1/reference
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro_Measurement_Protocol_API_Response implements SV_WC_API_Response  {


	/** @var array URL-decoded and parsed parameters */
	protected $parameters = array();

	/** @var \WC_Order optional order object if this request was associated with an order */
	protected $order;


	/**
	 * Parse the response parameters from the raw URL-encoded response string
	 *
	 * @since 1.0.0
	 * @param string $response the raw URL-encoded response string
	 * @param WC_Order $order the order object associated with this response
	 */
	public function __construct( $response, WC_Order $order = null ) {

		// URL decode the response string and parse it
		parse_str( urldecode( $response ), $this->parameters );
	}


	/**
	 * Returns true if the parameter is not empty
	 *
	 * @since 1.0.0
	 * @param string $name parameter name
	 * @return bool
	 */
	protected function has_parameter( $name ) {
		return ! empty( $this->parameters[ $name ] );
	}


	/**
	 * Gets the parameter value, or null if parameter is not set or empty
	 *
	 * @since 1.0.0
	 * @param string $name parameter name
	 * @return string|null
	 */
	protected function get_parameter( $name ) {
		return $this->has_parameter( $name ) ? $this->parameters[ $name ] : null;
	}


	/**
	 * Returns the string representation of this response
	 *
	 * @since 1.0.0
	 * @see SV_WC_API_Response::to_string()
	 * @return string response
	 */
	public function to_string() {

		return print_r( $this->parameters, true );
	}


	/**
	 * Returns the string representation of this response with any and all
	 * sensitive elements masked or removed
	 *
	 * @since 1.0.0
	 * @see SV_WC_API_Response::to_string_safe()
	 * @return string response safe for logging/displaying
	 */
	public function to_string_safe() {

		// no sensitive data to mask
		return $this->to_string();
	}


}
