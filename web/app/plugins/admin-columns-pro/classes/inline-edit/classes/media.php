<?php

/**
 * Media storage model for editability
 *
 * @since 1.0
 */
class CACIE_Editable_Model_Media extends CACIE_Editable_Model {

	/**
	 * @since 3.6.1
	 */
	public function get_ajax_options( $column, $search ) {
	}

	/**
	 * @see CACIE_Editable_Model::is_editable()
	 * @since 1.0
	 */
	public function is_editable( $column ) {

		// By default, inherit editability from parent
		$is_editable = parent::is_editable( $column );

		switch ( $column->get_type() ) {

			// Default columns
			case 'author':
			case 'date':
			case 'title':

				// Custom columns
			case 'column-alternate_text':
			case 'column-caption':
			case 'column-description':
			case 'column-mime_type':
			case 'column-taxonomy':
				$is_editable = true;
				break;
		}

		/**
		 * Filter the editability of a column
		 *
		 * @since 3.4
		 *
		 * @param bool $is_editable Whether the column is editable
		 * @param CPAC_Column $column Colum object
		 * @param CACIE_Editable_Model $model Editability storage model
		 */
		$is_editable = apply_filters( 'cac/editable/is_column_editable', $is_editable, $column, $this );
		$is_editable = apply_filters( 'cac/editable/is_column_editable/column=' . $column->get_type(), $is_editable, $column, $this );

		return $is_editable;
	}

	/**
	 * @see CACIE_Editable_Model::get_column_options()
	 * @since 1.0
	 */
	public function get_column_options( $column ) {

		$options = parent::get_column_options( $column );

		switch ( $column['type'] ) {

			// WP Default

			// Custom columns
			case 'column-taxonomy':
				$options = $this->get_term_options( $column['taxonomy'] );
				break;
			case 'column-mime_type':
				$mime_types = wp_get_mime_types();
				$options = array_combine( $mime_types, $mime_types );
				break;

		}

		return $options;
	}

	/**
	 * @see CACIE_Editable_Model::get_editables_data()
	 * @since 1.0
	 */
	public function get_editables_data() {

		$data = array(

			/**
			 * Default columns
			 *
			 */
			'author'                => array(
				'type'            => 'select2_dropdown',
				'property'        => 'post_author',
				'ajax_populate'   => true,
				'formatted_value' => 'user'
			),
			'date'                  => array(
				'type'     => 'date',
				'property' => 'post_date'
			),
			'title'                 => array(
				'type'         => 'text',
				'property'     => 'post_title',
				'js'           => array(
					'selector' => 'strong > a',
				),
				'display_ajax' => false
			),

			/**
			 * Custom columns
			 *
			 */
			'column-alternate_text' => array(
				'type' => 'text'
			),
			'column-caption'        => array(
				'type'     => 'textarea',
				'property' => 'post_excerpt'
			),
			'column-description'    => array(
				'type'     => 'textarea',
				'property' => 'post_content'
			),
			'column-mime_type'      => array(
				'type'     => 'select',
				'property' => 'post_mime_type'
			),
			'column-taxonomy'       => array(
				'type' => 'select2_tags'
			),
		);

		// Handle capabilities for editing post status
		$post_type_object = get_post_type_object( $this->storage_model->get_post_type() );

		if ( ! current_user_can( $post_type_object->cap->publish_posts ) ) {
			unset( $data['column-status'] );
		}

		/**
		 * Filter the editability settings for a column
		 *
		 * @since 3.4
		 *
		 * @param array $data {
		 *     Editability settings.
		 *
		 * @type string $type Editability type. Accepts 'text', 'select', 'textarea', 'media', 'float',
		 *                                    'togglable', 'select', 'select2_dropdown' and 'select2_tags'
		 * @type array $options Optional. Options for dropdown ([value] => [label]), only used when $type is "select"
		 * }
		 *
		 * @param CACIE_Editable_Model $model Editability storage model
		 */
		$data = apply_filters( 'cac/editable/editables_data', $data, $this );
		$data = apply_filters( 'cac/editable/editables_data/type=' . $this->storage_model->get_type(), $data, $this );

		return $data;
	}

	/**
	 * @see CACIE_Editable_Model::get_items()
	 * @since 1.0
	 */
	public function get_items() {

		global $wp_query;

		$items = array();

		foreach ( (array) $wp_query->posts as $post ) {
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				continue;
			}

			$columndata = array();

			foreach ( $this->storage_model->get_columns() as $column_name => $column ) {

				// Edit enabled for this column?
				if ( ! $this->is_edit_enabled( $column ) ) {
					continue;
				}

				// Set current value
				$value = '';

				// WP Default column
				if ( $column->properties->default ) {

					switch ( $column_name ) {
						case 'author':
							$value = $post->post_author;
							break;
						case 'date':
							$value = date( 'Ymd', strtotime( $post->post_date ) );
							break;
						case 'title':
							$value = $post->post_title;
							break;
					}
				}

				// Custom column
				else {
					$raw_value = $this->get_column_editability_value( $column, $post->ID );

					if ( $raw_value === null ) {
						continue;
					}

					$value = $raw_value;
				}

				/**
				 * Filter the raw value, used for editability, for a column
				 *
				 * @since 3.4
				 *
				 * @param mixed $value Column value used for editability
				 * @param CPAC_Column $column Colum object
				 * @param int $id Post ID to get the column editability for
				 * @param CACIE_Editable_Model $model Editability storage model
				 */
				$value = apply_filters( 'cac/editable/column_value', $value, $column, $post->ID, $this );
				$value = apply_filters( 'cac/editable/column_value/column=' . $column->get_type(), $value, $column, $post->ID, $this );

				// Get item data
				$itemdata = array();

				if ( method_exists( $column, 'get_item_data' ) ) {
					$itemdata = $column->get_item_data( $post->ID );
				}

				// Add data
				$columndata[ $column_name ] = array(
					'revisions'        => array( $value ),
					'current_revision' => 0,
					'itemdata'         => $itemdata,
					'editable'         => array(
						'formattedvalue' => $this->get_formatted_value( $column, $value )
					)
				);
			}

			$items[ $post->ID ] = array(
				'ID'         => $post->ID,
				'columndata' => $columndata
			);
		}

		return $items;
	}

	/**
	 * @see CACIE_Editable_Model::manage_value()
	 * @since 1.0
	 */
	public function manage_value( $column, $id ) {

		global $post;

		$post = get_post( $id );
		setup_postdata( $post );

		switch ( $column->get_type() ) {

			case 'author':
				printf( '<a href="%s">%s</a>',
					esc_url( add_query_arg( array( 'author' => get_the_author_meta( 'ID' ) ), 'upload.php' ) ),
					get_the_author()
				);
				break;
			case 'date':
				// copied from class-wp-media-list-table.php
				if ( '0000-00-00 00:00:00' == $post->post_date ) {
					$h_time = __( 'Unpublished' );
				}
				else {
					$m_time = $post->post_date;
					$time = get_post_time( 'G', true, $post, false );
					if ( ( abs( $t_diff = time() - $time ) ) < DAY_IN_SECONDS ) {
						if ( $t_diff < 0 ) {
							$h_time = sprintf( __( '%s from now' ), human_time_diff( $time ) );
						}
						else {
							$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
						}
					}
					else {
						$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
					}
				}
				echo $h_time;
				break;

			case 'title':
				// Set in DOM
				break;
		}

		wp_reset_postdata();
	}

	/**
	 * @see CACIE_Editable_Model::column_save()
	 * @since 1.0
	 */
	public function column_save( $id, $column, $value ) {
		if ( ! ( $post = get_post( $id ) ) ) {
			exit;
		}
		if ( ! current_user_can( 'edit_post', $id ) ) {
			exit;
		}

		// Third party columns can use the save() method as a callback for inline-editing
		if ( method_exists( $column, 'save' ) ) {
			$column->save( $id, $value );

			return;
		}

		$editable = $this->get_editable( $column->get_name() );

		switch ( $column->get_type() ) {

			/**
			 * Default Columns
			 *
			 */
			case 'date':
				// preserve the original time
				$time = strtotime( "1970-01-01 " . date( 'H:i:s', strtotime( $post->post_date ) ) );
				$date = date( 'Y-m-d H:i:s', strtotime( $value ) + $time );

				wp_update_post( array(
					'ID'            => $post->ID,
					'post_date'     => $date,
					'post_date_gmt' => get_gmt_from_date( $date )
				) );
				break;

			/**
			 * Custom Columns
			 *
			 */
			case 'column-alternate_text':
				$this->update_meta( $post->ID, '_wp_attachment_image_alt', $value );
				break;
			case 'column-meta':
				$this->update_meta( $post->ID, $column->get_field_key(), $value );
				break;
			case 'column-taxonomy':
				$taxonomy = $column->get_option( 'taxonomy' );
				if ( $taxonomy && taxonomy_exists( $taxonomy ) ) {
					$this->set_post_terms( $id, $value, $taxonomy );
				}
				break;

			// Save basic property such as title or description (data that is available in WP_Post)
			default:
				if ( ! empty( $editable['property'] ) ) {
					$property = $editable['property'];

					if ( isset( $post->{$property} ) ) {
						wp_update_post( array(
							'ID'      => $post->ID,
							$property => $value
						) );
					}
				}
		}
	}
}