<?php
/**
 * Post storage model for editability
 *
 * @since 1.0
 */
class CACIE_Editable_Model_Comment extends CACIE_Editable_Model {

	/**
	 * @since 3.6.1
	 */
	public function get_ajax_options( $column, $search ) {}

	/**
	 * @since 3.5
	 */
	public function get_list_selector() {
		return '#the-comment-list';
	}

	/**
	 * @see CACIE_Editable_Model::is_editable()
	 * @since 1.0
	 */
	public function is_editable( $column ) {

		// By default, inherit editability from parent
		$is_editable = parent::is_editable( $column );

		switch ( $column->properties->type ) {

			// Default columns
			//case 'name':

			// Custom columns
			case 'column-approved':
			case 'column-author_url':
			case 'column-author_email':
			case 'column-author_name':
			case 'column-excerpt':
			case 'column-type':
			case 'column-user':

				$is_editable = true;
				break;
		}

		/**
		 * Filter the editability of a column
		 *
		 * @since 3.4
		 *
		 * @param bool $is_editable Whether the column is editable
		 * @param CPAC_Column $column Column object
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
		return $options;
	}

	/**
	 * @see CACIE_Editable_Model::get_editables_data()
	 * @since 1.0
	 */
	public function get_editables_data() {

		$data = array(

			// WP Default columns

			// Custom columns
			'column-approved' => array(
				'type'		=> 'togglable',
				'property' 	=> 'comment_approved',
				'options' 	=> array( 0, 1 )
			),
			'column-author_url' => array(
				'type'		=> 'text',
				'property' 	=> 'comment_author_url',
				'js' => array(
					'selector' => 'a'
				),
			),
			'column-author_email' => array(
				'type'		=> 'text',
				'property' 	=> 'comment_author_email',
			),
			'column-author_name' => array(
				'type'		=> 'text',
				'property' 	=> 'comment_author',
			),
			'column-excerpt' => array(
				'type'		=> 'textarea',
				'property' 	=> 'comment_content',
			),
			'column-type' => array(
				'type'		=> 'text',
				'property' 	=> 'comment_type',
			),
			'column-user' => array(
				'type' 		=> 'select2_dropdown',
				'property' 	=> 'user_id',
				'ajax_populate' => true
			),
			'column-meta' => array(
				// settings are set in CACIE_Editable_Model::get_columns()
			)
		);


		// @todo
		//if ( ! current_user_can( 'edit-comments' ) ) {
		//	unset( $data['column-status'] );
		//}

		/**
		 * Filter the editability settings for a column
		 *
		 * @since 3.4
		 *
		 * @param array $data {
		 *     Editability settings.
		 *
		 *     @type string		$type		Editability type. Accepts 'text', 'select', 'textarea', etc.
		 *     @type array		$options	Optional. Options for dropdown ([value] => [label]), only used when $type is "select"
		 * }
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

		global $wp_list_table;
		$comments = $wp_list_table->items;

		if ( ! $comments ) {
			return array();
		}

		$items = array();

		foreach ( $comments as $comment ) {
			if ( ! current_user_can( 'edit_comment', $comment->comment_ID ) ) {
				continue;
			}

			$columndata = array();
			foreach ( $this->storage_model->get_columns() as $column_name => $column ) {
				if ( ! $this->is_edit_enabled( $column ) ) {
					continue;
				}

				$value = '';

				// WP Default column
				if ( $column->properties->default ) {

					switch ( $column_name ) {
						//case 'name':
							//$value = $post->post_author;
							//break;
					}
				}

				// Custom column
				else {
					$raw_value = $this->get_column_editability_value( $column, $comment->comment_ID );

					if ( $raw_value === NULL ) {
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
				$value = apply_filters( 'cac/editable/column_value', $value, $column, $comment->comment_ID, $this );
				$value = apply_filters( 'cac/editable/column_value/column=' . $column->get_type(), $value, $column, $comment->comment_ID, $this );

				// Get item data
				$itemdata = array();
				if ( method_exists( $column, 'get_item_data' ) ) {
					$itemdata = $column->get_item_data( $comment->comment_ID );
				}

				// Add data
				$columndata[ $column_name ] = array(
					'revisions' => array( $value ),
					'current_revision' => 0,
					'itemdata' => $itemdata,
					'editable' => array(
						'formattedvalue' => $this->get_formatted_value( $column, $value )
					)
				);
			}

			// Add comment to items list
			$items[ $comment->comment_ID ] = array(
				'ID' 			=> $comment->comment_ID,
				'columndata' 	=> $columndata
			);
		}

		return $items;
	}

	/**
	 * Get editability value for a column
	 *
	 * @since 3.4
	 *
	 * @param CPAC_Column $column Column
	 * @param integer $id Item ID
	 * @return mixed Raw value
	 */
	public function get_column_editability_value( $column, $id ) {

		return $column->get_raw_value( $id );
	}

	/**
	 * @see CACIE_Editable_Model::manage_value()
	 * @since 1.0
	 */
	public function manage_value( $column, $id ){

	}

	/**
	 * @see CACIE_Editable_Model::column_save()
	 * @since 1.0
	 */
	public function column_save( $id, $column, $value ) {

		if ( ! ( $comment = get_comment( $id ) ) ) {
			exit;
		}
		if ( ! current_user_can( 'edit_comment', $id ) ) {
			exit;
		}

		// Third party columns can use the save() method as a callback for inline-editing
		// If a column features a saving method itself, the "return" statement makes sure default behaviour is prevented
		if ( method_exists( $column, 'save' ) ) {
			$result = $column->save( $id, $value );

			// Return a possible WP_Error yielded by the column save method
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return;
		}

		$editable = $this->get_editable( $column->get_name() );

		switch ( $column->properties->type ) {

			// Default

			// Custom columns
			case 'column-meta':
				$this->update_meta( $id, $column->get_field_key(), $value );
				break;

			// Save basic property such as title or description (data that is available in WP_Post)
			default:
				if ( ! empty( $editable['property'] ) ) {
					$property = $editable['property'];
					if ( isset( $comment->{$property} ) ) {
						wp_update_comment( array(
							'comment_ID' => $id,
							$property => $value
						) );
					}
				}
				else {
					$result = null;

					/**
					 * Called when a column is saved, but the saving is not handled by Admin Columns core
					 * This should be used for saving columns that are editable but do not have their own CPAC_Column class
					 * The first parameter, $result, should only be used if an error occurs
					 *
					 * @since 3.4
					 *
					 * @param WP_Error $result Result of saving
					 * @param CPAC_Column $column Column object
					 * @param int $id ID of item to be saved
					 * @param mixed $value Value to be saved
					 * @param CACIE_Editable_Model $model Editability storage model
					 */
					$result = apply_filters( 'cac/editable/column_save', $result, $column, $id, $value, $this );
					$result = apply_filters( 'cac/editable/column_save/column=' . $column->get_type(), $result, $column, $id, $value, $this );

					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}
		}
	}
}