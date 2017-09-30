<?php
/*
Plugin Name: SearchWP WPML Integration
Plugin URI: https://searchwp.com/
Description: Integrate SearchWP with WPML
Version: 1.2.1
Author: Jonathan Christopher
Author URI: https://searchwp.com/

Copyright 2013-2014 Jonathan Christopher

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

if ( ! defined( 'SEARCHWP_WPML_VERSION' ) ) {
	define( 'SEARCHWP_WPML_VERSION', '1.2.1' );
}

/**
 * instantiate the updater
 */
if ( ! class_exists( 'SWP_WPML_Updater' ) ) {
	// load our custom updater
	include_once( dirname( __FILE__ ) . '/vendor/updater.php' );
}

// set up the updater
function searchwp_wpml_update_check() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	// environment check
	if ( ! defined( 'SEARCHWP_PREFIX' ) ) {
		return;
	}

	if ( ! defined( 'SEARCHWP_EDD_STORE_URL' ) ) {
		return;
	}

	if ( ! defined( 'SEARCHWP_WPML_VERSION' ) ) {
		return;
	}

	// retrieve stored license key
	$license_key = trim( get_option( SEARCHWP_PREFIX . 'license_key' ) );

	// instantiate the updater to prep the environment
	$searchwp_wpml_updater = new SWP_WPML_Updater( SEARCHWP_EDD_STORE_URL, __FILE__, array(
			'item_id' 	=> 33645,
			'version'   => SEARCHWP_WPML_VERSION,
			'license'   => $license_key,
			'item_name' => 'WPML Integration',
			'author'    => 'Jonathan Christopher',
			'url'       => site_url(),
		)
	);
}

add_action( 'admin_init', 'searchwp_wpml_update_check' );

class SearchWP_WPML {

	function __construct() {
		add_action( 'after_plugin_row_' . plugin_basename( __FILE__ ), array( $this, 'plugin_row' ), 11 );

		add_filter( 'searchwp_query_join', array( $this, 'join_wpml' ), 10, 2 );
		add_filter( 'searchwp_query_conditions', array( $this, 'force_current_language' ) );

		// prevent interference with the indexer
		add_action( 'searchwp_indexer_pre', array( $this, 'remove_all_unwanted_filters' ) );
	}

	function remove_all_unwanted_filters() {
		remove_all_filters( 'posts_join' );
		remove_all_filters( 'posts_where' );
		remove_all_filters( 'pre_get_posts' );
	}

	function join_wpml( $sql, $postType ) {
		global $wpdb, $sitepress;

		if( !empty( $sitepress ) && method_exists( $sitepress, 'get_current_language' ) && method_exists( $sitepress, 'get_default_language' ) && post_type_exists( $postType ) ) {
			$prefix = $wpdb->prefix;

			$sql .= " LEFT JOIN {$prefix}icl_translations t ON {$prefix}posts.ID = t.element_id ";
			$sql .= " AND t.element_type LIKE %s LEFT JOIN {$prefix}icl_languages l ON t.language_code=l.code AND l.active=1 ";

			$sql = $wpdb->prepare( $sql, 'post_' . $postType );
		}

		return $sql;
	}

	function force_current_language( $sql ) {
		global $wpdb, $sitepress;

		if( !empty( $sitepress ) && method_exists( $sitepress, 'get_current_language' ) && method_exists( $sitepress, 'get_default_language' ) ) {
			$currentLanguage = $sitepress->get_current_language();
			$defaultLanguage = $sitepress->get_default_language();

			if( $currentLanguage == $defaultLanguage ) {
				$sql .= " AND (t.language_code='%s' OR t.language_code IS NULL) ";
			} else {
				$sql .= " AND (t.language_code='%s') ";
			}

			$sql = $wpdb->prepare( $sql, $currentLanguage );
		}

		return $sql;
	}

	function plugin_row() {
		if( ! class_exists( 'SearchWP' ) ) { ?>
			<tr class="plugin-update-tr searchwp">
				<td colspan="3" class="plugin-update">
					<div class="update-message">
						<?php _e( 'SearchWP must be active to use this Extension' ); ?>
					</div>
				</td>
			</tr>
		<?php }
		else {
			$searchwp = SearchWP::instance();
			if( version_compare( $searchwp->version, '1.1', '<' ) ) { ?>
				<tr class="plugin-update-tr searchwp">
					<td colspan="3" class="plugin-update">
						<div class="update-message">
							<?php _e( 'SearchWP WPML Integration requires SearchWP 1.1 or greater', $searchwp->textDomain ); ?>
						</div>
					</td>
				</tr>
			<?php }
		}
	}

}

new SearchWP_WPML();
