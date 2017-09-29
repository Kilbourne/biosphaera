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
 * Do not edit or add to this file if you wish to upgrade WooCommerce PayPal Express to newer
 * versions in the future. If you wish to customize WooCommerce PayPal Express for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-PayPal Express/
 *
 * @package   WC-Google-Analytics-Pro/API-Request
 * @author    SkyVerge
 * @copyright Copyright (c) 2015-2016, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;


/**
 * Google Analytics Pro Measurement Protocol API Request Class
 *
 * Generates query string required by API specs to perform an API request
 *
 * @since 1.0.0
 */
class WC_Google_Analytics_Pro_Measurement_Protocol_API_Request implements SV_WC_API_Request {


	/** @var array the request parameters */
	private $parameters = array();

	/** @var string Google Analytics tracking ID */
	private $tracking_id;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 * @param string $tracking_id Google Analytics tracking ID
	 */
	public function __construct( $tracking_id ) {

		$this->tracking_id = $tracking_id;

		// set default params for all requests
		$this->add_parameters( array(
			'v'   => '1',                // API version
			'tid' => $this->tracking_id, // tracking ID
			'z'   => time(),             // request time
		) );
	}


	/**
	 * Add identity params to the request
	 *
	 * @since 1.0.0
	 * @param string $client_id Anonymous GA client ID, usually from GA cookie (cid)
	 * @param string $user_id Optional. Identified user ID (uid)
	 */
	public function identify( $client_id, $user_id = null ) {

		$this->add_parameter( 'cid', $client_id );

		if ( $user_id ) {
			$this->add_parameter( 'uid', $user_id );
		}
	}

	/**
	 * Add parameters to track an event
	 *
	 * @since 1.0.0
	 * @param string $event_name
	 * @param array $properties
	 */
	public function track_event( $event_name, $properties ) {

		/**
		 * Filters the event parameters
		 *
		 * @since 1.1.1
		 * @param array $parameters An associative array of event parameters
		 * @param string $event_name The event name
		 */
		$this->add_parameters( apply_filters( 'wc_google_analytics_pro_api_event_parameters', array(
			't'   => 'event',
			'ec'  => isset( $properties['eventCategory'] ) ? $properties['eventCategory'] : 'general',
			'ea'  => isset( $properties['eventAction'] )   ? $properties['eventAction']   : $event_name,
			'el'  => isset( $properties['eventLabel'] )    ? $properties['eventLabel']    : null,
			'ev'  => isset( $properties['eventValue'] )    ? $properties['eventValue']    : null,
			'cos' => isset( $properties['checkoutStep'] )  ? $properties['checkoutStep']  : null,
		), $event_name ) );
	}


	/**
	 * Add parameters to track enhanced ecommerce product impression
	 *
	 * @since 1.0.0
	 * @param WC_Product $product
	 */
	public function track_ec_impression( $product ) {

		$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );
		$category_hierarchy = wc_google_analytics_pro()->get_integration()->get_category_hierarchy( $product );

		/**
		 * Filters the enhanced ecommerce product impression parameters
		 *
		 * @since 1.1.1
		 * @param array $parameters An associative array of enhanced ecommerce product impression parameters
		 * @param \WC_Product $product The product
		 */
		$this->add_parameters( apply_filters( 'wc_google_analytics_pro_api_ec_impression_parameters', array(
			'il1nm'     => '',                                                  // Impression list 1. Required.
			'il1pi1id'  => $product_identifier,                                 // Product Impression 1 ID. Either ID or name must be set.
			'il1pi1nm'  => $product->get_title(),                               // Product Impression 1 name. Either ID or name must be set.
			'il1pi1ca'  => $category_hierarchy,                                 // Product Impression 1 category.
			'il1pi1pr'  => $product->get_price(),                               // Product Impression 1 price.
			'il1pi1br'  => '',                                                  // Product Impression 1 brand.
			'il1pi1va'  => '',                                                  // Product Impression 1 variant.
			'il1pi1ps'  => '',                                                  // Product Impression 1 position.
			'il1pi1cd1' => '',                                                  // Custom dimension
		), $product ) );
	}


	/**
	 * Add parameters to track enhanced ecommerce add to cart
	 *
	 * @since 1.0.0
	 * @param WC_Product $product
	 * @param int $quantity
	 */
	public function track_ec_add_to_cart( $product, $quantity ) {

		$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );
		$category_hierarchy = wc_google_analytics_pro()->get_integration()->get_category_hierarchy( $product );

		/**
		 * Filters the enhanced ecommerce add to cart event parameters
		 *
		 * @since 1.1.1
		 * @param array $parameters An associative array of enhanced ecommerce add to cart event parameters
		 * @param \WC_Product $product The product
		 * @param int $quantity The item quantity
		 */
		$this->add_parameters( apply_filters( 'wc_google_analytics_pro_api_ec_add_to_cart_parameters', array(
			'pa'    => 'add',                                                   // Product action
			'pal'   => '',                                                      // Product list
			'pr1id' => $product_identifier,                                     // Product id
			'pr1nm' => $product->get_title(),                                   // Product name
			'pr1ca' => $category_hierarchy,                                     // Product category
			'pr1pr' => $product->get_price(),                                   // Product price
			'pr1qt' => $quantity,                                               // Product Quantity
		), $product, $quantity ) );
	}


	/**
	 * Add parameters to track enhanced ecommerce remove from cart
	 *
	 * @since 1.0.0
	 * @param WC_Product $product
	 */
	public function track_ec_remove_from_cart( $product ) {

		$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );
		$category_hierarchy = wc_google_analytics_pro()->get_integration()->get_category_hierarchy( $product );

		/**
		 * Filters the enhanced ecommerce remove from cart event parameters
		 *
		 * @since 1.1.1
		 * @param array $parameters An associative array of enhanced ecommerce remove from cart event parameters
		 * @param \WC_Product $product The product
		 */
		$this->add_parameters( apply_filters( 'wc_google_analytics_pro_api_ec_remove_from_cart_parameters', array(
			'pa'    => 'remove',                                                // Product action
			'pal'   => '',                                                      // Product list
			'pr1id' => $product_identifier,                                     // Product id
			'pr1nm' => $product->get_title(),                                   // Product name
			'pr1ca' => $category_hierarchy,                                     // Product category
			'pr1pr' => $product->get_price(),                                   // Product price
			'pr1qt' => '1',                                                     // Product Quantity
		), $product ) );
	}


	/**
	 * Add parameters to track enhanced ecommerce completed purchase
	 *
	 * @since 1.0.0
	 * @param WC_Order $order
	 */
	public function track_ec_purchase( $order ) {

		// Set general data about the purchase
		$params = array(
			'pa'  => 'purchase',                                                // Product action
			'ti'  => $order->get_order_number(),                                // Transaction ID. Required.
			'tr'  => $order->get_total(),                                       // Revenue.
			'tt'  => $order->get_total_tax(),                                   // Tax.
			'ts'  => $order->get_total_shipping(),                              // Shipping.
			'tcc' => implode( ',', $order->get_used_coupons() ),                // Coupon code
			'cu'  => $order->get_order_currency(),                              // order currency
		);

		$c = 0;

		// add the purchased products
		foreach ( $order->get_items() as $item ) {

			$c++;
			$product = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );
			$variant = ( 'variation' === $product->product_type ) ? implode( ',', array_values( $product->get_variation_attributes() ) ) : '';

			$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );
			$category_hierarchy = wc_google_analytics_pro()->get_integration()->get_category_hierarchy( $product );

			$params["pr{$c}id"] = $product_identifier;                          // Product ID
			$params["pr{$c}nm"] = $item['name'];                                // Product name
			$params["pr{$c}ca"] = $category_hierarchy;                          // Product category
			$params["pr{$c}br"] = '';                                           // Product brand
			$params["pr{$c}pr"] = $order->get_item_total( $item );              // Product price
			$params["pr{$c}qt"] = $item['qty'];                                 // Product Quantity
			$params["pr{$c}va"] = $variant;                                     // Product variant
		}

		/**
		 * Filters the enhanced ecommerce completed purchase event parameters
		 *
		 * @since 1.1.1
		 * @param array $parameters An associative array of enhanced ecommerce completed purchase event parameters
		 * @param \WC_Order $order The order
		 */
		$this->add_parameters( apply_filters( 'wc_google_analytics_pro_api_ec_purchase_parameters', $params, $order ) );
	}


	/**
	 * Add parameters to track enhanced ecommerce order refund
	 *
	 * @since 1.0.0
	 * @param WC_Order $order
	 * @param array $items Optional. Refunded items. If not provided, a full refund is tracked.
	 */
	public function track_ec_refund( $order, $items = array() ) {

		$params = array(
			'ni'  => '1',                                                       // Non-interaction parameter.
			'ti'  => $order->get_order_number(),                                // Transaction ID. Required.
			'pa'  => 'refund',                                                  // Product action. Required
		);

		// if this is a partial refund, indicate which products were refunded
		if ( ! empty( $items ) ) {

			$c = 0;

			foreach ( $items as $item_id => $item ) {

				$c++;
				/** @var WC_Product_Variable $product because we use get_variation_attributes */
				$product = wc_get_product( ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'] );

				$product_identifier = wc_google_analytics_pro()->get_product_identifier( $product );

				$params["pr{$c}id"] = $product_identifier;                      // Product ID
				$params["pr{$c}qt"] = $item['qty'];                             // Product Quantity

				$c++;
			}
		}

		/**
		 * Filters the enhanced ecommerce order refund event parameters
		 *
		 * @since 1.1.1
		 * @param array $parameters An associative array of enhanced ecommerce order refund event parameters
		 * @param \WC_Order $order The order
		 * @param array $items Refunded items
		 */
		$this->add_parameters( apply_filters( 'wc_google_analytics_pro_api_ec_refund_parameters', $params, $order, $items ) );
	}


	/** Helper Methods ******************************************************/


	/**
	 * Add a parameter
	 *
	 * @since 1.0.0
	 * @param string $key
	 * @param string|int $value
	 */
	private function add_parameter( $key, $value ) {

		$this->parameters[ $key ] = $value;
	}


	/**
	 * Add multiple parameters
	 *
	 * @since 1.0.0
	 * @param array $params
	 */
	private function add_parameters( array $params ) {

		foreach ( $params as $key => $value ) {
			$this->add_parameter( $key, $value );
		}
	}


	/**
	 * Returns the string representation of this request
	 *
	 * @since 1.0.0
	 * @see SV_WC_API_Request::to_string()
	 * @return string the request query string
	 */
	public function to_string() {

		return 'payload_data&' . http_build_query( $this->get_parameters(), '', '&' );
	}


	/**
	 * Returns the string representation of this request with any and all
	 * sensitive elements masked or removed
	 *
	 * @since 1.0.0
	 * @see SV_WC_API_Request::to_string_safe()
	 * @return string the pretty-printed request array string representation, safe for logging
	 */
	public function to_string_safe() {

		$request = $this->get_parameters();

		$sensitive_fields = array( 'USER', 'PWD', 'SIGNATURE' );

		foreach ( $sensitive_fields as $field ) {

			if ( isset( $request[ $field ] ) ) {

				$request[ $field ] = str_repeat( '*', strlen( $request[ $field ] ) );
			}
		}

		return print_r( $request, true );
	}


	/**
	 * Returns the request parameters
	 *
	 * @since 1.0.0
	 * @return array request parameters
	 */
	public function get_parameters() {

		// validate parameters
		foreach ( $this->parameters as $key => $value ) {

			// remove unused params
			if ( null === $value || '' === $value ) {
				unset( $this->parameters[ $key ] );
			}
		}

		return $this->parameters;
	}


	/**
	 * Returns the method for this request: one of HEAD, GET, PUT, PATCH, POST, DELETE
	 *
	 * @since 1.0.0
	 * @return string the request method, or null to use the API default
	 */
	public function get_method() {
		return null;
	}


	/**
	 * Returns the request path
	 *
	 * @since 1.0.0
	 * @return string the request path, or '' if none
	 */
	public function get_path() {
		return '';
	}


}
