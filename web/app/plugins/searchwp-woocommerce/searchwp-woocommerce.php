<?php
/*
Plugin Name: SearchWP WooCommerce Integration
Plugin URI: https://searchwp.com/
Description: Integrate SearchWP with WooCommerce searches and Layered Navigation
Version: 1.1.10
Author: SearchWP, LLC
Author URI: https://searchwp.com/

Copyright 2014-2016 Jonathan Christopher

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SEARCHWP_WOOCOMMERCE_VERSION' ) ) {
	define( 'SEARCHWP_WOOCOMMERCE_VERSION', '1.1.10' );
}

/**
 * instantiate the updater
 */
if ( ! class_exists( 'SWP_WooCommerce_Updater' ) ) {
	// load our custom updater
	include_once( dirname( __FILE__ ) . '/vendor/updater.php' );
}

// set up the updater
function searchwp_woocommerce_update_check() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return false;
	}

	// environment check
	if ( ! defined( 'SEARCHWP_PREFIX' ) ) {
		return false;
	}

	if ( ! defined( 'SEARCHWP_EDD_STORE_URL' ) ) {
		return false;
	}

	if ( ! defined( 'SEARCHWP_WOOCOMMERCE_VERSION' ) ) {
		return false;
	}

	// retrieve stored license key
	$license_key = trim( get_option( SEARCHWP_PREFIX . 'license_key' ) );
	$license_key = sanitize_text_field( $license_key );

	// instantiate the updater to prep the environment
	$searchwp_woocommerce_updater = new SWP_WooCommerce_Updater( SEARCHWP_EDD_STORE_URL, __FILE__, array(
			'item_id' 	=> 33339,
			'version'   => SEARCHWP_WOOCOMMERCE_VERSION,
			'license'   => $license_key,
			'item_name' => 'WooCommerce Integration',
			'author'    => 'Jonathan Christopher',
			'url'       => site_url(),
		)
	);

	return $searchwp_woocommerce_updater;
}

add_action( 'admin_init', 'searchwp_woocommerce_update_check' );

class SearchWP_WooCommerce_Integration {

	private /** @noinspection PhpUnusedPrivateFieldInspection */
		$post_in = array();
	private $woocommerce_query = false;
	private $woocommerce;
	private $results = array();
	private $native_get_vars = array( 's', 'post_type', 'orderby' );
	private $post_type = 'product';
	private $ordering = array();
	private $original_query = '';
	private $filtered_posts = array();

	function __construct() {

		// always exclude hidden Products
		add_filter( 'searchwp_exclude', array( $this, 'exclude_hidden_products' ) );

		// maybe exclude out of stock products
		add_filter( 'searchwp_exclude', array( $this, 'maybe_exclude_out_of_stock_products' ) );

		$query = isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '';
		$this->original_query = $query;

		// all of this functionality is only necessary if we are in fact viewing a WooCommerce-spec'd results page
		if ( isset( $_GET['post_type'] ) && 'product' == $_GET['post_type'] ) {
			// WooCommerce hooks
			add_action( 'loop_shop_post_in', array( $this, 'post_in' ), 9999 );
			add_action( 'woocommerce_product_query', array( $this, 'product_query' ), 10, 2 );
			add_filter( 'the_posts', array( $this, 'the_posts' ), 15, 2 ); // Woo uses priority 11
			add_filter( 'woocommerce_get_filtered_term_product_counts_query', array( $this, 'get_filtered_term_product_counts_query' ) );

			// SearchWP hooks
			add_filter( 'searchwp_engine_settings_default', array( $this, 'limit_engine_to_products' ) );
			add_filter( 'searchwp_query_main_join', array( $this, 'query_main_join' ), 10, 2 );
			add_filter( 'searchwp_query_orderby', array( $this, 'query_orderby' ) );
			add_filter( 'searchwp_query_select_inject', array( $this, 'searchwp_query_inject' ) );
			add_filter( 'searchwp_where', array( $this, 'searchwp_query_where' ) );

			// WordPress hooks
			add_action( 'init', array( $this, 'get_woocommerce_ordering' ) );

			add_action( 'wp', array( $this, 'hijack_query_vars' ), 1 );
			add_action( 'wp', array( $this, 'replace_original_search_query' ), 3 );
		}
	}

	/**
	 * Even if it's not a WooCommerce search, we should exclude hidden WooCommerce product IDS
	 *
	 * @since 1.1.3
	 *
	 * @param $ids
	 *
	 * @return array
	 */
	function exclude_hidden_products( $ids ) {

		$proceed = apply_filters( 'searchwp_woocommerce_consider_visibility', true );

		if ( empty( $proceed ) ) {
			return $ids;
		}

		$args = array(
			'post_type'  => 'product',
			'nopaging'   => true,
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => '_visibility',
					'value'   => array( 'hidden', 'catalog' ),
					'compare' => 'IN',
				),
			),
		);

		$hidden = get_posts( $args );

		if ( ! empty( $hidden ) ) {
			$ids = array_merge( $ids, $hidden );
		}

		return $ids;
	}

	/**
	 * If out of stock options should be hidden from search, exclude them from search
	 *
	 * @since 1.1.8
	 *
	 * @param $ids
	 *
	 * @return array
	 */
	function maybe_exclude_out_of_stock_products( $ids ) {

		if ( 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			return $ids;
		}

		$args = array(
			'post_type'  => 'product',
			'nopaging'   => true,
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => '_stock_status',
					'value'   => 'instock',
					'compare' => '!=',
				),
			),
		);

		$out_of_stock = get_posts( $args );

		if ( ! empty( $out_of_stock ) ) {
			$ids = array_merge( $ids, $out_of_stock );
		}

		return $ids;
	}

	/**
	 * Since we're simply replacing WooCommerce search field results, we want to limit even
	 * the default search engine settings to only include Products
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	function limit_engine_to_products( $settings ) {
		if ( $this->woocommerce_query ) {
			foreach ( $settings as $engine_post_type => $options ) {
				if ( $this->post_type !== $engine_post_type ) {
					$settings[ $engine_post_type ]['enabled'] = false;
				}
			}
		}

		return $settings;
	}

	function include_filtered_posts( $include ) {
		$include = array_merge( (array) $include, $this->filtered_posts );

		return array_unique( $include );
	}

	/**
	 * Piggyback WooCommerce's Layered Navigation and inject SearchWP results where applicable
	 *
	 * @param $filtered_posts
	 *
	 * @return array
	 */
	function post_in( $filtered_posts ) {

		global /** @noinspection PhpUnusedLocalVariableInspection */
		$wp_query;

		// WooCommerce 2.6 introduced tax/meta query piggybacking that's much better
		if ( function_exists( 'WC' ) && ! empty( WC()->version ) && version_compare( WC()->version, '2.6', '<' ) ) {
			return $this->legacy_post_in( $filtered_posts );
		}

		if ( $this->is_woocommerce_search()
		     // && ! isset( $_GET['orderby'] )
		     && $query = get_search_query() ) {

			$searchwp_engine = 'default';
			$swppg = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

			// force SearchWP to only consider the filtered posts
			if ( ! empty( $filtered_posts ) ) {
				$this->filtered_posts = $filtered_posts;
				add_filter( 'searchwp_include', array( $this, 'include_filtered_posts' ) );
			}

			do_action( 'searchwp_woocommerce_before_search', $this );

			// don't log this search, it's redundant
			add_filter( 'searchwp_log_search', '__return_false' );

			$wc_query = new WC_Query;

			$args = array(
				's'                 => $query,
				'engine'            => $searchwp_engine,
				'page'              => $swppg,
				'fields'            => 'ids',
				'posts_per_page'    => -1,
				'tax_query'         => $wc_query->get_tax_query(),
				'meta_query'        => $wc_query->get_meta_query(),
			);

			$args = apply_filters( 'searchwp_woocommerce_query_args', $args );

			$results = new SWP_Query( $args );

			$this->results = $results->posts;

			remove_filter( 'searchwp_log_search', '__return_false' );

			return $this->results;
		}

		return (array) $filtered_posts;
	}

	/**
	 * Legacy post retrieval for WooCommerce <2.6
	 *
	 * @param $filtered_posts
	 *
	 * @return array
	 */
	function legacy_post_in( $filtered_posts ) {
		global /** @noinspection PhpUnusedLocalVariableInspection */
		$wp_query;

		if ( $this->is_woocommerce_search()
		     && function_exists( 'SWP' )
		     && ! isset( $_GET['orderby'] )
		     && $query = get_search_query() ) {

			$searchwp_engine = 'default';
			$searchwp = SWP();
			$swppg = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

			add_filter( 'searchwp_load_posts', '__return_false' );
			add_filter( 'searchwp_posts_per_page', array( $this, 'set_pagination' ) );

			// force SearchWP to only consider the filtered posts
			if ( ! empty( $filtered_posts ) ) {
				$this->filtered_posts = $filtered_posts;
				add_filter( 'searchwp_include', array( $this, 'include_filtered_posts' ) );
			}

			// don't log this search, it's redundant
			add_filter( 'searchwp_log_search', '__return_false' );
			$this->results = $searchwp->search( $searchwp_engine, $query, $swppg );
			remove_filter( 'searchwp_log_search', '__return_false' );

			remove_filter( 'searchwp_load_posts', '__return_false' );
			remove_filter( 'searchwp_posts_per_page', array( $this, 'set_pagination' ) );

			$filtered_posts = array_intersect( $this->results, (array) $filtered_posts );
			$filtered_posts = array_unique( $filtered_posts );

			// also set our WooCommerce Instance IDs
			WC()->query->unfiltered_product_ids = $this->results;
		}

		return (array) $filtered_posts;
	}

	/**
	 * Callback for the_posts so we can tell WC about our filtered IDs for Layered Nav Widgets
	 *
	 * @since 1.1.4
	 *
	 * @param $posts
	 * @param bool $query
	 *
	 * @return mixed
	 */
	public function the_posts( $posts, $query = false ) {
		WC()->query->unfiltered_product_ids = $this->results;
		WC()->query->filtered_product_ids = $this->results;
		WC()->query->layered_nav_product_ids = $this->results;

		return $posts;
	}

	/**
	 * WooCommerce stores products in view as a transient based on $wp_query but that falls apart
	 * with search terms that rely on SearchWP, WP_Query's s param returns nothing, and that gets used by WC
	 */
	function hijack_query_vars() {
		global $wp_query;

		if ( $this->is_woocommerce_search()
		     && function_exists( 'SWP' )
		     && ! isset( $_GET['orderby'] )
		     && $this->original_query ) {

			$wp_query->set( 'post__in', array() );
			$wp_query->set( 's', '' );

			if ( isset( $wp_query->query['s'] ) ) {
				unset( $wp_query->query['s'] );
			}
		}
	}

	/**
	 * Put back the search query once we've hijacked it to get around WooCommerce's products in view storage
	 */
	function replace_original_search_query() {
		global $wp_query;

		if ( ! empty( $this->original_query ) ) {
			$wp_query->set( 's', $this->original_query );
		}
	}

	/**
	 * Determines whether Layered Navigation is active right now
	 *
	 * @return bool
	 */
	function is_layered_navigation_active() {
		$active = false;

		if ( is_active_widget( false, false, 'woocommerce_layered_nav', true ) && ! is_admin() ) {
			if ( is_array( $_GET ) ) {
				foreach ( $_GET as $get_key => $get_var ) {
					// our 'flag' will be a GET variable present that isn't the basic search results page
					// as identified by the native WordPress search trigger 's' and Woo's 'post_type'
					if ( ! in_array( $get_key, apply_filters( 'searchwp_woocommerce_native_get_vars', $this->native_get_vars ) ) ) {
						$active = true;
						break;
					}
				}
			}
		}

		return $active;
	}

	/**
	 * Determine whether a WooCommerce search is taking place
	 * @return bool
	 */
	function is_woocommerce_search() {

		$woocommerce_search = false;

		if (
			( is_search()
			  || (
				  is_archive()
				  && isset( $_GET['s'] )
				  && ! empty( $_GET['s'] )
			  )
			)
			&& isset( $_GET['post_type'] )
			&& 'product' == $_GET['post_type']
		) {
			$woocommerce_search = true;
		}

		return $woocommerce_search;
	}

	/**
	 * Utilize WooCommerce's WC_Query object to retrieve information about any ordering that's going on
	 */
	function get_woocommerce_ordering() {
		if ( $this->is_woocommerce_search() && class_exists( 'WC_Query' ) ) {
			$woocommerce_query = new WC_Query();
			if ( method_exists( $woocommerce_query, 'get_catalog_ordering_args' ) ) {
				$this->ordering = $woocommerce_query->get_catalog_ordering_args();
				$this->ordering['wc_orderby'] = isset( $_GET['orderby'] ) ? $_GET['orderby'] : '';
			}
		}
	}


	/**
	 * Set our environment variables once a WooCommerce query is in progress
	 *
	 * @param $q
	 * @param $woocommerce
	 */
	function product_query( $q, $woocommerce ) {
		global $wp_query;

		$this->woocommerce_query = $q;
		$this->woocommerce = $woocommerce;

		// if SearchWP found search results we want the order of results to be returned by SearchWP weight in descending order
		if ( $this->is_woocommerce_search() && apply_filters( 'searchwp_woocommerce_force_weight_sort', true ) ) {
			$wp_query->set( 'order', 'DESC' );
			$wp_query->set( 'orderby', 'post__in' );

			// if it's not the main Search page, it's the WooCommerce Shop page
			if ( ! is_search() && wc_get_page_id( 'shop' ) == get_queried_object_id() ) {
				$wp_query->set( 's', '' );
			}
		}

	}

	/**
	 * WooCommerce Layered Nav Widgets fire a query to get term counts on each load, when SearchWP is in play
	 * these counts can be incorrect when searching for taxonomy terms that match the Layered Nav filters
	 * so we need to hijack this query entirely, run our own, and generate new SQL for WooCommerce to fire
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	function get_filtered_term_product_counts_query( $query ) {
		global $wpdb;

		if ( empty( $this->results ) ) {
			return $query;
		}

		// modify the WHERE clause to also include SearchWP-provided results
		$query['where'] .= " OR {$wpdb->posts}.ID IN (" . implode( ',', array_map( 'absint', $this->results ) ) . ")";

		return $query;
	}

	/**
	 * Depending on the sorting taking place we may need a custom JOIN in the main SearchWP query
	 *
	 * @param $sql
	 * @param $engine
	 *
	 * @return string
	 */
	function query_main_join( $sql, $engine ) {
		global $wpdb;

		if ( isset( $engine ) ) {
			$engine = null;
		}

		// if WooCommerce is sorting results we need to tell SearchWP to return them in that order
		if ( $this->is_woocommerce_search() ) {

			if ( ! isset( $this->ordering['wc_orderby'] ) ) {
				$this->get_woocommerce_ordering();
			}

			// depending on the sorting we need to do different things
			if ( isset( $this->ordering['wc_orderby'] ) ) {
				switch ( $this->ordering['wc_orderby'] ) {
					case 'price':
					case 'price-desc':
					case 'popularity':
						$meta_key = isset( $this->ordering['meta_key'] ) ? $this->ordering['meta_key'] : '';
						$sql = $sql . $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} AS swpwc ON {$wpdb->posts}.ID = swpwc.post_id AND swpwc.meta_key = %s", $meta_key );
						break;
					case 'rating':
						$sql = $sql . " LEFT OUTER JOIN {$wpdb->comments} ON({$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID) LEFT JOIN {$wpdb->commentmeta} ON({$wpdb->comments}.comment_ID = {$wpdb->commentmeta}.comment_id) ";
						break;
				}
			}

			// for visibility we always need to join postmeta
			$sql = $sql . " INNER JOIN {$wpdb->postmeta} as woovisibility ON {$wpdb->posts}.ID = woovisibility.post_id ";

		}

		return $sql;
	}

	/**
	 * Handle the varous sorting capabilities offered by WooCommerce by makikng sure SearchWP respects them since
	 * we are always ordering by post__in based on SearchWP's retrieved results
	 *
	 * @param $orderby
	 *
	 * @return string
	 */
	function query_orderby( $orderby ) {
		global $wpdb;

		if ( $this->is_woocommerce_search() && ! empty( $this->ordering ) ) {

			if ( ! isset( $this->ordering['wc_orderby'] ) ) {
				$this->get_woocommerce_ordering();
			}

			// depending on the sorting we need to do different things
			if ( isset( $this->ordering['wc_orderby'] ) ) {
				$order = isset( $this->ordering['order'] ) ? $this->ordering['order'] : 'ASC';
				switch ( $this->ordering['wc_orderby'] ) {
					case 'price':
					case 'price-desc':
					case 'popularity':
						$order = in_array( $this->ordering['wc_orderby'], array( 'popularity', 'price-desc' ) ) ? 'DESC' : $order;
						$orderby = "ORDER BY swpwc.meta_value+0 {$order}, " . str_replace( 'ORDER BY', '', $orderby );
						break;
					/* case 'price-desc':
						$orderby = "ORDER BY {$wpdb->postmeta}.meta_value+0 DESC, " . str_replace( 'ORDER BY', '', $orderby );
						break; */
					case 'date':
						$orderby = 'ORDER BY post_date DESC';
						break;
					case 'rating':
						$orderby = "ORDER BY average_rating DESC, {$wpdb->posts}.post_date DESC";
						break;
					case 'name':
						$orderby = 'ORDER BY post_title ASC';
						break;
				}
			}
		}

		return $orderby;
	}

	/**
	 * Callback for SearchWP's main query that facilitates integrating WooCommerce ratings
	 *
	 * @return string
	 */
	function searchwp_query_inject() {
		global $wpdb;

		$sql = '';

		if ( ! isset( $this->ordering['wc_orderby'] ) ) {
			$this->get_woocommerce_ordering();
		}

		if ( $this->is_woocommerce_search() && ! empty( $this->ordering ) ) {
			// ratings need moar SQL
			if ( 'rating' == $this->ordering['wc_orderby'] ) {
				$sql = " AVG( {$wpdb->commentmeta}.meta_value ) as average_rating ";
			}
		}

		return $sql;
	}

	/**
	 * Callback for SearchWP's main query that facilitates sorting by WooCommerce rating
	 *
	 * @return string
	 */
	function searchwp_query_where() {
		global $wpdb;

		$sql = '';

		if ( ! isset( $this->ordering['wc_orderby'] ) ) {
			$this->get_woocommerce_ordering();
		}

		if ( $this->is_woocommerce_search() && ! empty( $this->ordering ) ) {
			// ratings need moar SQL
			if ( 'rating' == $this->ordering['wc_orderby'] ) {
				$sql = " AND ( {$wpdb->commentmeta}.meta_key = 'rating' OR {$wpdb->commentmeta}.meta_key IS null ) ";
			}
		}

		if ( $this->is_woocommerce_search() ) {
			// visibility
			if ( apply_filters( 'searchwp_woocommerce_consider_visibility', true ) ) {
				$sql .= " AND ( ( woovisibility.meta_key = '_visibility' AND CAST( woovisibility.meta_value AS CHAR ) IN ( 'visible', 'search' ) ) ) ";
			}
		}

		return $sql;
	}

	/**
	 * Callback to set SearchWP pagination
	 *
	 * @return int
	 */
	function set_pagination() {
		global $wp_query;

		return (int) $wp_query->get( 'posts_per_page' );
	}
}

// kickoff
new SearchWP_WooCommerce_Integration();
