<?php

/**
 * Taxonomy storage model for editability
 *
 * @since 2.3.4
 */
class CACIE_Editable_Model_Taxonomy extends CACIE_Editable_Model {

	/**
	 * @see CACIE_Editable_Model::is_editable()
	 * @since 1.0
	 */
	public function is_editable( $column ) {

		// By default, inherit editability from parent
		$is_editable = parent::is_editable( $column );

		switch ( $column->properties->type ) {

			// Default columns
			case 'name':
			case 'slug':
			case 'description':

				// Custom columns
			case 'column-excerpt' :
			case 'column-term_parent' :
				$is_editable = true;
				break;

			case 'column-meta' :
				$is_editable = false;
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

		return parent::get_column_options( $column );
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
			'name'               => array(
				'type'         => 'text',
				'property'     => 'name',
				'js'           => array(
					'selector' => 'a.row-title',
				),
				'display_ajax' => false
			),
			'slug'               => array(
				'type'     => 'text',
				'property' => 'slug',
			),
			'description'        => array(
				'type'     => 'textarea',
				'property' => 'description',
			),

			/**
			 * Custom columns
			 *
			 */
			'column-term_parent' => array(
				'type'            => 'select2_dropdown',
				'property'        => 'parent',
				'ajax_populate'   => true,
				'formatted_value' => 'term',
				'multiple'        => false,
				'clear_button'    => true
			),
			'column-excerpt'     => array(
				'type'     => 'textarea',
				'property' => 'description',
			),
		);

		/**
		 * Filter the editability settings for a column
		 *
		 * @since 3.4
		 *
		 * @param array $data {
		 *     Editability settings.
		 *
		 * @type string $type Editability type. Accepts 'text', 'select', 'textarea', etc.
		 * @type array $options Optional. Options for dropdown ([value] => [label]), only used when $type is "select"
		 * }
		 *
		 * @param CACIE_Editable_Model $model Editability storage model
		 */
		$data = apply_filters( 'cac/editable/editables_data', $data, $this );
		$data = apply_filters( 'cac/editable/editables_data/type=' . $this->storage_model->get_type(), $data, $this );
		$data = apply_filters( 'cac/editable/editables_data/taxonomy=' . $this->storage_model->get_taxonomy(), $data, $this );

		return $data;
	}

	/**
	 * @see CACIE_Editable_Model::get_items()
	 * @since 1.0
	 */
	public function get_items() {

		// @todo: not working properly with categories
		// to set the correct callback_args, and onylget terms that are currently being displayed
		//$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );
		//$wp_list_table->prepare_items();
		//$terms = get_terms( $wp_list_table->screen->taxonomy, wp_parse_args( array( 'hide_empty' => false ), $wp_list_table->callback_args ) );

		// get terms
		$terms = get_terms( $this->storage_model->taxonomy, array( 'hide_empty' => false ) );

		$items = array();

		foreach ( (array) $terms as $term ) {
			if ( ! current_user_can( 'manage_categories' ) ) {
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
						case 'name':
							$value = $term->name;
							break;
						case 'slug':
							$value = $term->slug;
							break;
						case 'description':
							$value = $term->description;
							break;
					}
				} // Custom column
				else {
					$raw_value = $this->get_column_editability_value( $column, $term->term_id );;

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
				$value = apply_filters( 'cac/editable/column_value', $value, $column, $term->term_id, $this );
				$value = apply_filters( 'cac/editable/column_value/column=' . $column->get_type(), $value, $column, $term->term_id, $this );

				// Get item data
				$itemdata = array();

				if ( method_exists( $column, 'get_item_data' ) ) {
					$itemdata = $column->get_item_data( $term->term_id );
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

			$items[ $term->term_id ] = array(
				'ID'         => $term->term_id,
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
		$term = get_term_by( 'id', $id, $this->storage_model->taxonomy );

		switch ( $column->properties->type ) {
			case 'name' :
				echo $term->name;
				break;
			case 'slug' :
				echo $term->slug;
				break;
			case 'description' :
				echo $term->description;
				break;
		}
	}

	/**
	 * @see CACIE_Editable_Model::column_save()
	 * @since 1.0
	 */
	public function column_save( $id, $column, $value ) {

		$taxonomy = $this->storage_model->taxonomy;

		if ( ! ( $term = get_term_by( 'id', $id, $taxonomy ) ) ) {
			exit;
		}
		if ( ! current_user_can( 'manage_categories' ) ) {
			exit;
		}

		// Third party columns can use the save() method as a callback for inline-editing
		if ( method_exists( $column, 'save' ) ) {
			$column->save( $id, $value );

			return;
		}

		// Fetch data
		$editable = $this->get_editable( $column->get_name() );

		switch ( $column->get_type() ) {

			// Save basic property such as title or description (data that is available in WP_Post)
			default:
				if ( ! empty( $editable['property'] ) ) {
					$property = $editable['property'];

					if ( isset( $term->{$property} ) ) {
						wp_update_term( $id, $taxonomy, array(
							$property => $value
						) );
					}
				}
		}
	}

	/**
	 * @since 3.7
	 */
	public function get_ajax_options( $column, $search ) {
		$args = array(
			'number'     => 100,
			'hide_empty' => false,
			'search'     => $search,
			'fields'     => 'id=>name'
		);

		return get_terms( $this->storage_model->taxonomy, $args );
	}
}