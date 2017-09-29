<?php

/**
 * Addon class
 *
 * @since 3.5
 */
class CAC_Filtering_Model_MS_User extends CAC_Filtering_Model_User {

	public function init_hooks() {
		add_action( 'pre_get_users', array( $this, 'handle_filter_requests' ), 1 );
		add_action( 'pre_get_users', array( $this, 'handle_filter_range_requests' ), 1 );

		add_action( 'in_admin_footer', array( $this, 'add_filtering_markup' ) );
		add_action( 'in_admin_footer', array( $this, 'add_filtering_button' ), 11 ); // placement after dropdowns
	}

	public function get_filterables() {
		$column_types = array(

			// WP default columns
			'email',
			'username',

			// Custom columns
			'column-first_name',
			'column-last_name',
			'column-rich_editing',
			'column-user_registered',
			'column-user_url',
		);

		return $column_types;
	}
}