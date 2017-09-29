<?php

/**
 * Filtering Model for Posts ánd Media!
 *
 * @since 1.0
 */
class CAC_Filtering_Model_Post_Object extends CAC_Filtering_Model {

	public function init_hooks() {
		add_filter( 'request', array( $this, 'handle_filter_requests' ), 2 );
		add_filter( 'request', array( $this, 'handle_filter_range_requests' ), 2 );
		add_action( 'restrict_manage_posts', array( $this, 'add_filtering_markup' ) );
	}

	public function get_filterables() {

	}

	public function get_dropdown_options_by_column( $column ) {
	}

	/**
	 * Handle filter request for ranges
	 *
	 * @since 3.7
	 */
	public function handle_filter_range_requests( $vars ) {
		if ( isset( $_REQUEST['cpac_filter-min'] ) ) {
			$vars['meta_query'][] = $this->get_meta_query_range( $_REQUEST['cpac_filter-min'], $_REQUEST['cpac_filter-max'] );
		}

		return $vars;
	}

	/**
	 * Get values by meta key
	 *
	 * @since 3.5
	 */
	public function get_values_by_meta_key( $meta_key, $operator = 'DISTINCT meta_value AS value' ) {

		$sql = "
			SELECT {$operator}
			FROM {$this->wpdb->postmeta} pm
			INNER JOIN {$this->wpdb->posts} p ON pm.post_id = p.ID
			WHERE p.post_type = %s
			AND pm.meta_key = %s
			AND pm.meta_value != ''
			ORDER BY 1
		";

		$values = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $this->storage_model->post_type, $meta_key ) );

		if ( is_wp_error( $values ) || ! $values ) {
			return array();
		}

		return $values;
	}

	/**
	 * Get values by post field
	 *
	 * @since 1.0
	 */
	public function get_post_fields( $post_field ) {

		$post_field = sanitize_key( $post_field );
		$sql = "
			SELECT DISTINCT {$post_field}
			FROM {$this->wpdb->posts}
			WHERE post_type = %s
			AND {$post_field} <> ''
			ORDER BY 1
		";

		$values = $this->wpdb->get_col( $this->wpdb->prepare( $sql, $this->storage_model->get_post_type() ) );
		if ( is_wp_error( $values ) || ! $values ) {
			return array();
		}

		return $values;
	}

	/**
	 * Get terms by taxonomy and post type
	 *
	 * @since 3.8
	 */
	protected function get_terms_by_post_type( $taxonomies, $post_types ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT t.term_id AS term_id, t.slug AS slug, t.name AS name, tt.taxonomy AS taxonomy, tt.parent AS parent
			FROM $wpdb->terms AS t
	        INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
	        INNER JOIN $wpdb->term_relationships AS r ON r.term_taxonomy_id = tt.term_taxonomy_id
	        INNER JOIN $wpdb->posts AS p ON p.ID = r.object_id
	        WHERE p.post_type IN('%s') AND tt.taxonomy IN('%s')
	        GROUP BY t.term_id",
			join( "', '", (array) $post_types ),
			join( "', '", (array) $taxonomies )
		);

		$results = $wpdb->get_results( $query );

		return $results;
	}
}