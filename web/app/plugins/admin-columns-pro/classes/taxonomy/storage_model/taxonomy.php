<?php

class CPAC_Storage_Model_Taxonomy extends CPAC_Storage_Model {

	public $taxonomy;

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 */
	function __construct( $taxonomy ) {

		$this->key = 'wp-taxonomy_' . $taxonomy;
		$this->type = 'taxonomy';
		$this->page = 'edit-tags';
		$this->taxonomy = $taxonomy;
		$this->menu_type = __( 'Taxonomy', 'codepress-admin-columns' );

		$this->set_labels();

		parent::__construct();
	}

	/**
	 * @since 3.7
	 */
	public function init_manage_columns() {
		add_filter( "manage_edit-{$this->taxonomy}_columns", array( $this, 'add_headings' ) );
		add_filter( "manage_{$this->taxonomy}_custom_column", array( $this, 'manage_value' ), 10, 3 );
	}

	/**
	 * @since 2.7.3
	 */
	private function set_labels() {
		$taxonomy = get_taxonomy( $this->taxonomy );
		$this->label =$taxonomy->labels->name;
		$this->singular_label =$taxonomy->labels->singular_name;
	}

	/**
	 * @since 3.7.3
	 */
	public function is_current_screen() {
		$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : '';
		return ( $this->taxonomy === $taxonomy ) && parent::is_current_screen();
	}

	/**
	 * Get screen link
	 *
	 * @since 1.2.0
	 *
	 * @return string Link
	 */
	protected function get_screen_link() {

		return add_query_arg( array( 'taxonomy' => $this->taxonomy ), admin_url( $this->page . '.php' ) );
	}

	/**
	 * Get taxonomy
	 *
	 * @since 3.4
	 *
	 * @return string Taxonomy name
	 */
	public function get_taxonomy() {
		return $this->taxonomy;
	}

	/**
	 * Get WP default supported admin columns per post type.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_default_columns() {

		if ( ! function_exists( '_get_list_table' ) ) {
			return array();
		}

		// You can use this filter to add thirdparty columns by hooking into this.
		// See classes/third_party.php for an example.
		do_action( "cac/columns/default/storage_key={$this->key}" );

		// get columns
		$table = _get_list_table( 'WP_Terms_List_Table', array( 'screen' => 'edit-' . $this->taxonomy ) );
		$columns = $table->get_columns();

		return $columns;
	}

	/**
	 * Get original columns
	 *
	 * @since 3.5.1
	 */
	public function get_default_column_names() {
		return array( 'cb', 'name', 'description', 'slug', 'posts', 'links' );
	}

	/**
	 * Get Meta
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	public function get_meta() {
		global $wpdb;

		$meta = array();

		// Only works with ACF taxonomy fields
		if ( $results = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT option_name FROM {$wpdb->options} WHERE option_name LIKE '%s' ORDER BY 1", $this->taxonomy . '_%' ), ARRAY_N ) ) {
			foreach ( $results as $result ) {

				$option_name = $result[0];
				$underscore = strpos( $option_name, '_', strlen( $this->taxonomy ) + 1 );

				if ( false === $underscore ) {
					continue;
				}

				$key = substr( $option_name, $underscore + 1, strlen( $option_name ) );

				$meta[][0] = $key;
			}
		}

		return $meta;
	}

	/**
	 * Manage value
	 *
	 * @since 1.2.0
	 *
	 * @param string $column_name
	 * @param int $post_id
	 */
	public function manage_value( $content, $column_name, $term_id ) {

		$value = $content;

		// get column instance
		if ( $column = $this->get_column_by_name( $column_name ) ) {
			$value .= $column->get_value( $term_id );
		}

		// add hook
		$value = apply_filters( "cac/column/value", $value, $term_id, $column, $this->key );
		$value = apply_filters( "cac/column/value/{$this->type}", $value, $term_id, $column, $this->key );

		return $value;
	}
}