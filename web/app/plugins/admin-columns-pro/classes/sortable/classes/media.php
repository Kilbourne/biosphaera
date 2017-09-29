<?php

/**
 * Addon class
 *
 * @since 1.0
 */
class CAC_Sortable_Model_Media extends CAC_Sortable_Model {

	public function init_hooks() {
		add_filter( 'request', array( $this, 'handle_sorting_request' ), 1 );
		add_filter( "manage_" . $this->storage_model->get_screen_id() . "_sortable_columns", array( $this, 'add_sortable_headings' ) );
		add_action( 'restrict_manage_posts', array( $this, 'add_reset_button' ) );
	}

	/**
	 * @since 3.7
	 */
	public function get_items( $args ) {
		return $this->get_posts( $args );
	}

	/**
	 * @see CAC_Sortable_Model::get_sortables()
	 * @since 1.0
	 */
	public function get_sortables() {

		$column_names = array(

			// WP default columns

			// Custom Columns
			'column-alternate_text',
			'column-available_sizes',
			'column-caption',
			'column-dimensions',
			'column-description',
			'column-exif_data',
			'column-file_name',
			'column-file_size',
			'column-height',
			'column-meta',
			'column-mediaid',
			'column-mime_type',
			'column-taxonomy',
			'column-width',

			// ACF Fields
			'column-acf_field',
		);

		return array_merge( $column_names, (array) $this->get_default_sortables() );
	}

	/**
	 * Columns that are sortable by WordPress core
	 *
	 * @since 3.8
	 */
	public function get_default_sortables() {
		$columns = array(
			'title',
			'author',
			'date',
			'parent',
			'comment'
		);

		return $columns;
	}

	/**
	 * Admin requests for orderby column
	 *
	 * Only works for WP_Query objects ( such as posts and media )
	 *
	 * @since 1.0
	 *
	 * @param array $vars
	 *
	 * @return array Vars
	 */
	public function handle_sorting_request( $vars ) {

		$vars = $this->apply_sorting_preference( $vars );

		if ( empty( $vars['orderby'] ) ) {
			return $vars;
		}

		$column = $this->get_column_by_orderby( $vars['orderby'] );
		if ( empty( $column ) ) {
			return $vars;
		}

		// unsorted Attachments
		$posts = array();

		$show_all_results = $this->get_show_all_results();

		switch ( $column->properties->type ) :

			// WP Default Columns

			// Custom Columns
			case 'column-mediaid' :
				$vars['orderby'] = 'ID';
				break;

			case 'column-width' :
			case 'column-height' :
				$sort_flag = SORT_NUMERIC;
				break;

			case 'column-height' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts() as $id ) {
					$meta = wp_get_attachment_metadata( $id );
					$height = ! empty( $meta['height'] ) ? $meta['height'] : 0;
					if ( $height || $show_all_results ) {
						$posts[ $id ] = $height;
					}
				}
				break;

			case 'column-dimensions' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts() as $id ) {
					$meta = wp_get_attachment_metadata( $id );
					$height = ! empty( $meta['height'] ) ? $meta['height'] : 0;
					$width = ! empty( $meta['width'] ) ? $meta['width'] : 0;
					$surface = $height * $width;

					if ( $surface || $show_all_results ) {
						$posts[ $id ] = $surface;
					}
				}
				break;

			case 'column-caption' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-description' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-mime_type' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-file_name' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts() as $id ) {
					$meta = get_post_meta( $id, '_wp_attached_file', true );
					$file = ! empty( $meta ) ? strtolower( basename( $meta ) ) : '';
					if ( $file || $show_all_results ) {
						$posts[ $id ] = $file;
					}
				}
				break;

			case 'column-alternate_text' :
				$sort_flag = SORT_STRING;
				break;

			case 'column-file_size' :
				$sort_flag = SORT_NUMERIC;
				foreach ( $this->get_posts() as $id ) {
					$value = false;
					if ( $file = wp_get_attachment_url( $id ) ) {
						$abs = str_replace( WP_CONTENT_URL, WP_CONTENT_DIR, $file );
						$value = file_exists( $abs ) ? filesize( $abs ) : false;
					}
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $this->prepare_sort_string_value( $value );
					}
				}
				break;

			case 'column-available_sizes' :
				$sort_flag = SORT_NUMERIC;
				$sizes = get_intermediate_image_sizes();
				foreach ( $this->get_posts() as $id ) {
					$meta = get_post_meta( $id, '_wp_attachment_metadata', true );
					$value = isset( $meta['sizes'] ) ? count( array_intersect( array_keys( $meta['sizes'] ), $sizes ) ) : false;

					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'column-taxonomy' :
				$sort_flag = SORT_STRING;
				$posts = $this->get_posts_sorted_by_taxonomy( $column->get_option( 'taxonomy' ) );
				break;

			// Custom Field
			case 'column-meta' :
				if ( $items = $this->get_meta_items_for_sorting( $column ) ) {
					$posts = $items['items'];
					$sort_flag = $items['sort_flag'];
				}
				else {
					$vars['meta_query'][] = array(
						'key'     => $column->get_field_key(),
						'type'    => in_array( $column->get_option( 'field_type' ), array( 'numeric', 'library_id', 'count' ) ) ? 'NUMERIC' : 'CHAR',
						'compare' => '!=',
						'value'   => ''
					);
					$vars['orderby'] = $column->get_field_key();
				}
				break;

			case 'column-exif_data' :
				$sort_flag = SORT_STRING;
				foreach ( $this->get_posts() as $id ) {
					$value = $column->get_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $value;
					}
				}
				break;

			case 'column-acf_field' :
				if ( method_exists( $column, 'get_field' ) ) {
					$is_sortable = $this->set_acf_sorting_vars( $column, $vars );
					if ( ! $is_sortable ) {
						$sort_flag = SORT_REGULAR;
						$posts = $this->get_acf_sorting_data( $column, $this->get_posts() );
					}
				}
				break;

		endswitch;

		// we will add the sorted post ids to vars['post__in'] and remove unused vars
		if ( isset( $sort_flag ) ) {
			if ( ! $posts ) {
				foreach ( $this->get_posts() as $id ) {
					$value = method_exists( $column, 'get_raw_value' ) ? $column->get_raw_value( $id ) : $column->get_value( $id );
					if ( $value || $show_all_results ) {
						$posts[ $id ] = $this->prepare_sort_string_value( $value );
					}
				}
			}
			$vars = $this->get_vars_post__in( $vars, $posts, $sort_flag );
		}

		/**
		 * Filters the sorting vars
		 *
		 * @since 3.2.1
		 *
		 * @param $vars array WP Query vars
		 * @param $column object Column instance
		 */
		return apply_filters( 'cac/addon/sortable/vars', $vars, $column );
	}
}