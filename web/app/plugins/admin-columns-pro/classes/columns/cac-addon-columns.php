<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAC_ADDON_COLUMNS_DIR', plugin_dir_path( __FILE__ ) );

/**
 * @since 3.6
 */
class CACIE_Addon_Columns {

	public function __construct() {
		add_filter( 'cac/columns/custom/type=post', array( $this, 'set_post_columns' ), 10 );
		add_filter( 'cac/columns/custom/type=user', array( $this, 'set_user_columns' ), 10 );
	}

	public function set_post_columns( $columns ) {
		$columns['CPAC_Column_Post_Child_Pages'] = CAC_ADDON_COLUMNS_DIR . 'post/child-pages.php';
		$columns['CPAC_Column_Related_Posts'] = CAC_ADDON_COLUMNS_DIR . 'post/related-posts.php';

		return $columns;
	}

	public function set_user_columns( $columns ) {
		$columns['CPAC_Column_User_Roles'] = CAC_ADDON_COLUMNS_DIR . 'user/roles.php';

		return $columns;
	}

}

new CACIE_Addon_Columns();
