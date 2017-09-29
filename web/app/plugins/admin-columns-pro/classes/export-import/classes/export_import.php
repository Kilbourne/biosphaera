<?php

/**
 * CAC_Export_Import Class
 *
 * @since 1.4.6.5
 *
 */
class CAC_Export_Import {

	private $php_export_string = '';

	public $exported_columns;

	/**
	 * @since 1.4.6.5
	 */
	function __construct() {

		// Add UI
		add_filter( 'cac/settings/tabs', array( $this, 'settings_tabs' ) );
		add_action( 'cac/settings/tab_contents/tab=import-export', array( $this, 'tab_importexport_contents' ) );

		// styling & scripts
		add_action( "admin_print_styles-settings_page_codepress-admin-columns", array( $this, 'scripts' ) );

		// Handle requests
		add_action( 'admin_init', array( $this, 'handle_export' ) );
		add_action( 'admin_init', array( $this, 'handle_import' ) );

		// Load PHP exported columns & layouts
		add_filter( 'cpac/storage_model/stored_columns', array( $this, 'set_php_stored_columns' ), 10, 2 );
		add_filter( 'ac/layouts', array( $this, 'set_php_layouts' ), 10, 2 );
	}

	/**
	 * @since 3.8
	 */
	public function get_exported_columndata( $storage_model ) {

		if ( ! isset( $this->exported_columns[ $storage_model->key ] ) ) {
			return false;
		}

		$columndata = $this->exported_columns[ $storage_model->key ];

		// Convert old export formats to new layout format
		$old_format_columns = array();
		foreach ( $columndata as $k => $data ) {
			if ( ! isset( $data['columns'] ) ) {
				$old_format_columns[ $k ] = $data;
				unset( $columndata[ $k ] );
			}
		}

		if ( $old_format_columns ) {
			array_unshift( $columndata, array( 'columns' => $old_format_columns ) );
		}

		// Add layout if missing
		foreach ( $columndata as $k => $data ) {
			if ( ! isset( $data['layout'] ) ) {
				$columndata[ $k ] = array(
					'columns' => isset( $data['columns'] ) ? $data['columns'] : $data,
					'layout'  => $storage_model->get_default_layout_args( array(
						'id'   => sanitize_key( substr( md5( serialize( $data ) ), 0, 16 ) ), // uniqueid based on settings
						'name' => __( 'Imported' ) . ( $k ? ' #' . $k : '' ) // add suffix
					) )
				);
			}
		}

		return $columndata;
	}

	/**
	 * @since 3.8
	 */
	public function set_php_stored_columns( $columns, $storage_model ) {
		if ( $columndata = $this->get_exported_columndata( $storage_model ) ) {
			foreach ( $columndata as $data ) {
				if ( $storage_model->layout == $data['layout']['id'] ) {
					$columns = $data['columns'];
				}
			}
		}

		return $columns;
	}

	/**
	 * @since 3.8
	 */
	public function set_php_layouts( $layouts, $storage_model ) {
		if ( $columndata = $this->get_exported_columndata( $storage_model ) ) {
			foreach ( $columndata as $data ) {
				$layout = (object) $data['layout'];

				if ( isset( $layouts[ $layout->id ] ) ) {
					unset( $layouts[ $layout->id ] );
				}

				// flag layout as not editable
				$layout->not_editable = true;
				$layouts[ $layout->id ] = $layout;
			}
		}

		return $layouts;
	}

	/**
	 * @since 1.4.6.5
	 */
	public function handle_export() {
		if ( ! isset( $_REQUEST['_cpac_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_cpac_nonce'], 'export' ) ) {
			return;
		}

		if ( empty( $_REQUEST['export_types'] ) ) {
			cpac_admin_message( __( 'Export field is empty. Please select your types from the left column.', 'codepress-admin-columns' ), 'error' );

			return;
		}

		// PHP
		if ( ! empty( $_REQUEST['cpac-export-php'] ) ) {
			$this->php_export_string = $this->get_php_export_string( $_REQUEST['export_types'] );
		}

		// JSON
		else {

			$filename = 'admin-columns-export_' . date( 'Y-m-d', time() );

			// single name
			if ( 1 == count( $_REQUEST['export_types'] ) ) {
				$filename .= '_' . $_REQUEST['export_types'][0];
			}

			// generate json file
			header( 'Content-disposition: attachment; filename=' . $filename . '.json' );
			header( 'Content-type: application/json' );
			echo $this->get_json_export_string( $_REQUEST['export_types'] );
			exit;
		}
	}

	/**
	 * @uses wp_import_handle_upload()
	 * @since 2.0.0
	 */
	public function handle_import() {
		if ( ! isset( $_REQUEST['_cpac_nonce'] ) || ! wp_verify_nonce( $_REQUEST['_cpac_nonce'], 'file-import' ) || empty( $_FILES['import'] ) ) {
			return false;
		}

		// handles upload
		$file = wp_import_handle_upload();

		// any errors?
		$error = false;
		if ( isset( $file['error'] ) ) {
			$error = __( 'Sorry, there has been an error.', 'codepress-admin-columns' ) . '<br />' . esc_html( $file['error'] );
		}
		else if ( ! file_exists( $file['file'] ) ) {
			$error = __( 'Sorry, there has been an error.', 'codepress-admin-columns' ) . '<br />' . sprintf( __( 'The export file could not be found at %s. It is likely that this was caused by a permissions problem.', 'codepress-admin-columns' ), '<code>' . esc_html( $file['file'] ) . '</code>' );
		}

		if ( $error ) {
			cpac_admin_message( $error, 'error' );

			return false;
		}

		// read file contents and start the import
		$content = file_get_contents( $file['file'] );

		// cleanup
		wp_delete_attachment( $file['id'] );

		// decode file contents
		$columndata = $this->get_decoded_settings( $content );

		if ( empty( $columndata ) ) {
			cpac_admin_message( __( 'Import failed. File does not contain Admin Column settings.', 'codepress-admin-columns' ), 'error' );

			return false;
		}

		foreach ( $columndata as $type => $columndata ) {
			$storage_model = cpac()->get_storage_model( $type );

			if ( ! $storage_model ) {
				cpac_admin_message( sprintf( __( 'Screen %s does not exist.', 'codepress-admin-columns' ), "<strong>{$type}</strong>" ), 'error' );
				continue;
			}

			$created_layouts = array();

			// Create Original layout. The old column settings will not be overwritten but stored in an "Original" layout
			if ( ! $storage_model->get_layouts() && $storage_model->get_stored_columns() ) {
				$original_layout = array(
					'name' => __( 'Original', 'codepress-admin-columns' )
				);
				$layout_id = $storage_model->create_layout( $original_layout, true );
				$created_layouts[ $layout_id ] = $original_layout['name'];
			}

			$default_layout = array(
				'name' => __( 'Imported', 'codepress-admin-columns' )
			);

			// Determine the import format. New import has layouts, the old import doesn't
			$is_layout_format = isset( $columndata[0] );

			// New json format with layouts
			// $columndata contains [layouts] and [columns]
			if ( $is_layout_format ) {
				foreach ( $columndata as $data ) {
					$layout = isset( $data['layout'] ) ? $data['layout'] : $default_layout;

					$layout_id = $storage_model->create_layout( $layout );
					$storage_model->set_layout( $layout_id );
					$storage_model->store( $data['columns'] );

					$created_layouts[ $layout_id ] = $layout['name'];
				}
			}

			// Old json format without layouts
			else {
				$layout_id = $storage_model->create_layout( $default_layout );
				$storage_model->set_layout( $layout_id );
				$storage_model->store( $columndata );

				$created_layouts[ $layout_id ] = $default_layout['name'];
			}

			if ( ! $created_layouts ) {
				cpac_admin_message( __( 'Import failed.', 'codepress-admin-columns' ), 'error' );

				return;
			}

			$links = array();
			foreach ( $created_layouts as $id => $name ) {
				$links[] = '<a href="' . $storage_model->get_edit_link_by_layout( $id ) . '"><strong>' . esc_html( $name ) . '</strong></a>';
			}

			$message = sprintf(
				__( 'Succesfully created %s %s for %s.', 'codepress-admin-columns' ),
				str_replace( ', ' . end( $links ), ' and ' . end( $links ), implode( ', ', $links ) ),
				_n( 'set', 'sets', count( $links ), 'codepress-admin-columns' ),
				"<strong>{$storage_model->label}</strong>"
			);

			cpac_admin_message( $message, 'updated' );
		}
	}

	/**
	 * @since 3.8
	 */
	private function export_single_layouts() {
		return apply_filters( 'ac/export_import/export_single_sets', true );
	}

	/**
	 * @since 3.8
	 */
	private function get_export_data( $types ) {
		$data = array();

		foreach ( cpac()->get_storage_models() as $storage_model ) {

			// All layouts
			if ( in_array( $storage_model->key, $types ) ) {
				foreach ( $storage_model->get_layouts() as $layout ) {
					$storage_model->set_layout( $layout->id );
					if ( $columns = $storage_model->get_stored_columns() ) {
						$data[ $storage_model->key ][] = array(
							'columns' => $columns,
							'layout'  => $layout
						);
					}
				}
			}

			// Individual layouts
			else if ( $layouts = $storage_model->get_layouts() ) {
				foreach ( $layouts as $layout ) {
					if ( in_array( $storage_model->key . $layout->id, $types ) ) {
						$storage_model->set_layout( $layout->id );
						if ( $columns = $storage_model->get_stored_columns() ) {
							$data[ $storage_model->key ][] = array(
								'columns' => $columns,
								'layout'  => $layout
							);
						}
					}
				}
			}

			// No layout
			if ( empty( $data[ $storage_model->key ] ) && in_array( $storage_model->key, $types ) && ( $columns = $storage_model->get_stored_columns() ) ) {
				$data[ $storage_model->key ][] = array(
					'columns' => $columns,
				);
			}
		}

		return array_filter( $data );
	}

	/**
	 * Gets multi select options to use in a HTML select element
	 *
	 * @since 2.0.0
	 * @return array Multiselect options
	 */
	private function get_export_multiselect_options() {
		$options = array();

		$export_single_layouts = $this->export_single_layouts();

		foreach ( cpac()->get_storage_models() as $storage_model ) {

			// Individual layouts
			if ( $export_single_layouts ) {
				if ( $layouts = $storage_model->get_layouts() ) {
					foreach ( $layouts as $layout ) {
						$storage_model->set_layout( $layout->id );
						if ( $storage_model->get_stored_columns() ) {
							$options[ $storage_model->get_menu_type() ][ $storage_model->key . $storage_model->layout ] = $storage_model->label . ' - ' . $layout->name;
						}
					}
				}

				else if ( $storage_model->get_stored_columns() ) {
					$options[ $storage_model->get_menu_type() ][ $storage_model->key ] = $storage_model->label;
				}
			}

			// All layouts
			else {
				$has_stored_columns = false;

				// Layouts
				if ( $layouts = $storage_model->get_layouts() ) {
					foreach ( $layouts as $layout ) {
						$storage_model->set_layout( $layout->id );
						if ( $storage_model->get_stored_columns() ) {
							$has_stored_columns = true;
							break;
						}
					}
				}

				// Single
				else if ( $storage_model->get_stored_columns() ) {
					$has_stored_columns = true;
				}

				// Add menu type
				if ( $has_stored_columns ) {
					$options[ $storage_model->get_menu_type() ][ $storage_model->key ] = $storage_model->label;
				}
			}
		}

		return $options;
	}

	/**
	 * @since 2.0.0
	 */
	private function get_json_export_string( $types = array() ) {
		if ( empty( $types ) ) {
			return false;
		}

		$data = $this->get_export_data( $types );

		if ( empty( $data ) ) {
			return false;
		}

		// PHP 5.4 <
		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
			$json = json_encode( $data, JSON_PRETTY_PRINT );
		}

		// Older versions of PHP
		else {
			$json = $this->get_pretty_json( $data );
		}

		return $json;
	}

	private function get_columns_part( $columns ) {
		$columns_parts = array();

		foreach ( $columns as $column_name => $column ) {
			$properties_parts = array();

			foreach ( $column as $property => $value ) {
				$properties_parts[] = "\t\t\t\t\t\t'{$property}' => '{$value}'";
			}

			$columns_string = '';
			$columns_string .= "\t\t\t'{$column_name}' => array(\n";
			$columns_string .= implode( ",\n", $properties_parts ) . "\n";
			$columns_string .= "\t\t\t\t\t)";

			$columns_parts[] = $columns_string;
		}

		return implode( ",\n", $columns_parts );
	}

	private function get_layout_part( $layout ) {
		$columns_parts = array();
		foreach ( $layout as $k => $value ) {
			if ( is_array( $value ) ) {
				$columns_parts[] = "\t\t\t\t\t'{$k}' => array(\n\t\t\t\t\t\t'" . implode( "',\n\t\t\t\t\t\t'", $value ) . "'\n\t\t\t\t\t)";
			}
			else {
				$columns_parts[] = "\t\t\t\t\t'{$k}' => '{$value}'";
			}
		}

		return implode( ",\n", $columns_parts );
	}

	/**
	 * @since 2.0.0
	 */
	private function get_php_export_string( $types = array() ) {

		if ( empty( $types ) ) {
			return false;
		}

		$exported = $this->get_export_data( $types );

		if ( empty( $exported ) ) {
			return false;
		}

		// callback has to be unique
		$function_id = substr( md5( serialize( $exported ) ), - 8 );

		$exportstring = "function ac_custom_column_settings_{$function_id}() {\n";
		$exportstring .= "\n\tif ( function_exists( 'ac_register_columns' ) ) {";

		foreach ( $exported as $storage_model => $columndata ) {
			$exportstring .= "\n\t\tac_register_columns( '{$storage_model}', array(\n";

			// Layouts
			if ( isset( $columndata[0] ) ) {

				$layout_parts = array();
				foreach ( $columndata as $data ) {

					$layoutstring = '';
					$layoutstring .= "\t\t\t\t'columns' => array(";
					$layoutstring .= "\n\t\t" . $this->get_columns_part( $data['columns'] );
					$layoutstring .= "\n\t\t\t\t),\n";

					if ( isset( $data['layout'] ) ) {
						$layoutstring .= "\t\t\t\t'layout' => array(";
						$layoutstring .= "\n" . $this->get_layout_part( $data['layout'] );
						$layoutstring .= "\n\t\t\t\t)\n";
					}
					$layout_parts[] = $layoutstring;
				}
				$exportstring .= "\t\t\tarray(\n" . implode( "\n\t\t\t),\n\t\t\tarray(\n", $layout_parts ) . "\t\t\t)";
			}

			// Single
			else {
				$exportstring .= $this->get_columns_part( $columndata );
			}

			$exportstring .= "\n\t\t) );";
		}

		$exportstring .= "\n\t}";
		$exportstring .= "\n}";
		$exportstring .= "\nadd_action( 'init', 'ac_custom_column_settings_{$function_id}' );";

		return $exportstring;
	}

	/**
	 * @since 1.4.6.5
	 */
	public function settings_tabs( $tabs ) {
		$tabs['import-export'] = __( 'Export/Import', 'codepress-admin-columns' );

		return $tabs;
	}

	/**
	 * @since 1.4.6.5
	 */
	public function tab_importexport_contents( $content ) {
		$export_types = ( ! empty( $_REQUEST['export_types'] ) && is_array( $_REQUEST['export_types'] ) ) ? $_REQUEST['export_types'] : array();
		?>
		<table class="form-table cpac-form-table">
			<tbody>
			<?php if ( $this->php_export_string ) : ?>
				<tr>
					<th scope="row">
						<h3><?php _e( 'Results', 'codepress-admin-columns' ); ?></h3>
						<p>
							<a href="javascript:;" class="cpac-pointer" rel="cpac-php-export-instructions-html" data-pos="right"><?php _e( 'Instructions', 'codepress-admin-columns' ); ?></a>
						</p>
						<div id="cpac-php-export-instructions-html" style="display:none;">
							<h3><?php _e( 'Using the PHP export', 'codepress-admin-columns' ); ?></h3>
							<ol>
								<li><?php _e( 'Copy the generated PHP code in the right column', 'codepress-admin-columns' ); ?></li>
								<li><?php _e( 'Insert the code in your themes functions.php or in your plugin (on the init action)', 'codepress-admin-columns' ); ?></li>
								<li><?php _e( 'Your columns settings are now loaded from your PHP code instead of from your stored settings!', 'codepress-admin-columns' ); ?></li>
							</ol>
						</div>
					</th>
					<td class="padding-22">
						<form action="" method="post" id="php-export-results">
							<textarea class="widefat" rows="20"><?php echo $this->php_export_string; ?></textarea>
						</form>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row">
					<h3><?php _e( 'Columns', 'codepress-admin-columns' ); ?></h3>
					<p><?php _e( 'Select the columns to be exported.', 'codepress-admin-columns' ); ?></p>
				</th>
				<td class="padding-22">
					<div class="cpac_export">

						<?php if ( $groups = $this->get_export_multiselect_options() ) : ?>
							<form method="post" action="" class="<?php echo $this->export_single_layouts() ? 'large' : ''; ?>">
								<?php wp_nonce_field( 'export', '_cpac_nonce' ); ?>
								<select name="export_types[]" multiple="multiple" class="select cpac-export-multiselect" id="cpac_export_types">
									<?php foreach ( $groups as $group_key => $group ) : ?>
										<optgroup label="<?php echo esc_attr( $group_key ); ?>">
											<?php foreach ( $group as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"<?php selected( array_search( $key, $export_types ) !== false ); ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
								<a class="export-select export-select-all" href="javascript:;"><?php _e( 'select all', 'codepress-admin-columns' ); ?></a>
								<div class="submit">
									<input type="submit" class="button button-primary alignright" name="cpac-export-php" value="<?php _e( 'Export PHP', 'codepress-admin-columns' ); ?>">
									<input type="submit" class="button button-primary alignright" name="cpac-export-text" value="<?php _e( 'Download export file', 'codepress-admin-columns' ); ?>">
								</div>
							</form>
						<?php else : ?>
							<p><?php _e( 'No stored column settings are found.', 'codepress-admin-columns' ); ?></p>
						<?php endif; ?>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
		<table class="form-table cpac-form-table">
			<tbody>
			<tr>
				<td>
					<h3><?php _e( 'Download export file', 'codepress-admin-columns' ); ?></h3>
					<p><?php _e( 'Admin Columns will export to a format compatible with the Admin Columns import functionality.', 'codepress-admin-columns' ); ?></p>
					<ol>
						<li><?php _e( 'Select the columns you which to export from the list in the left column', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Click the &quot;Download export file&quot; button', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Save the .json-file when prompted', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Go to the Admin Columns import/export page in your other installation', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Select the export .json-file', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Click the &quot;Start import&quot; button', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( "That's it!", 'codepress-admin-columns' ); ?></li>
					</ol>
				</td>
				<td>
					<h3><?php _e( 'Export to PHP', 'codepress-admin-columns' ); ?></h3>
					<p><?php _e( 'Admin Columns will export PHP code you can directly insert in your plugin or theme.', 'codepress-admin-columns' ); ?></p>
					<ol>
						<li><?php _e( 'Select the columns you which to export from the list in the left column', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Click the &quot;Export to PHP&quot; button', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Copy the generated PHP code in the right column', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Insert the code in your themes functions.php or in your plugin (on the init action)', 'codepress-admin-columns' ); ?></li>
						<li><?php _e( 'Your columns settings are now loaded from your PHP code instead of from your stored settings!', 'codepress-admin-columns' ); ?></li>
					</ol>
				</td>
			</tr>
			</tbody>
		</table>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<h3><?php _e( 'Import', 'codepress-admin-columns' ); ?></h3>
					<p><?php _e( 'Import your Admin Column settings here.', 'codepress-admin-columns' ); ?></p>
					<p>
						<a href="javascript:;" class="cpac-pointer" rel="cpac-import-instructions-html" data-pos="right"><?php _e( 'Instructions', 'codepress-admin-columns' ); ?></a>
					</p>
					<div id="cpac-import-instructions-html" style="display:none;">
						<h3><?php _e( 'Import Columns Types', 'codepress-admin-columns' ); ?></h3>
						<ol>
							<li><?php _e( 'Choose a Admin Columns Export file to upload.', 'codepress-admin-columns' ); ?></li>
							<li><?php _e( 'Click upload file and import.', 'codepress-admin-columns' ); ?></li>
							<li><?php _e( "That's it! You imported settings are now active.", 'codepress-admin-columns' ); ?></li>
						</ol>
					</div>
				</th>
				<td class="padding-22">
					<div id="cpac_import_input">
						<form method="post" action="" enctype="multipart/form-data">
							<input type="file" size="25" name="import" id="upload">
							<?php wp_nonce_field( 'file-import', '_cpac_nonce' ); ?>
							<input type="submit" value="<?php _e( 'Upload file and import', 'codepress-admin-columns' ); ?>" class="button" id="import-submit" name="file-submit">
						</form>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @since 1.0
	 */
	public function scripts() {
		wp_enqueue_style( 'cac-ei-css', CAC_EI_URL . 'assets/css/export-import.min.css', array(), CAC_PRO_VERSION, 'all' );
		wp_enqueue_script( 'cac-ei-js', CAC_EI_URL . 'assets/js/export-import.min.js', array( 'jquery' ), CAC_PRO_VERSION );
		wp_enqueue_script( 'cac-ei-multi-select-js', CAC_EI_URL . 'assets/js/jquery.multi-select.min.js', array( 'jquery' ), CAC_PRO_VERSION );
	}

	/**
	 * Indents JSON
	 *
	 * Only needed for PHP less that 5.4
	 * Props to http://snipplr.com/view.php?codeview&id=60559
	 *
	 * @since 3.2.2
	 */
	private function get_pretty_json( $json ) {

		$json = json_encode( $json );

		$result = '';
		$pos = 0;
		$strLen = strlen( $json );
		$indentStr = '  ';
		$newLine = "\n";
		$prevChar = '';
		$outOfQuotes = true;

		for ( $i = 0; $i <= $strLen; $i ++ ) {

			// Grab the next character in the string.
			$char = substr( $json, $i, 1 );

			// Are we inside a quoted string?
			if ( $char == '"' && $prevChar != '\\' ) {
				$outOfQuotes = ! $outOfQuotes;

				// If this character is the end of an element,
				// output a new line and indent the next line.
			}
			else if ( ( $char == '}' || $char == ']' ) && $outOfQuotes ) {
				$result .= $newLine;
				$pos --;
				for ( $j = 0; $j < $pos; $j ++ ) {
					$result .= $indentStr;
				}
			}

			// Add the character to the result string.
			$result .= $char;

			// If the last character was the beginning of an element,
			// output a new line and indent the next line.
			if ( ( $char == ',' || $char == '{' || $char == '[' ) && $outOfQuotes ) {
				$result .= $newLine;
				if ( $char == '{' || $char == '[' ) {
					$pos ++;
				}

				for ( $j = 0; $j < $pos; $j ++ ) {
					$result .= $indentStr;
				}
			}

			$prevChar = $char;
		}

		return $result;
	}

	/**
	 * @since 2.0.0
	 *
	 * @param string $encoded_string
	 *
	 * @return array Columns
	 */
	private function get_decoded_settings( $encoded_string = '' ) {
		if ( ! $encoded_string || ! is_string( $encoded_string ) ) {
			return false;
		}

		// TXT File
		// is deprecated
		if ( strpos( $encoded_string, '<!-- START: Admin Columns export -->' ) !== false ) {
			$encoded_string = str_replace( "<!-- START: Admin Columns export -->\n", "", $encoded_string );
			$encoded_string = str_replace( "\n<!-- END: Admin Columns export -->", "", $encoded_string );
			$decoded = maybe_unserialize( base64_decode( trim( $encoded_string ) ) );
		}
		elseif ( ( $result = json_decode( $encoded_string, true ) ) && is_array( $result ) ) {
			$decoded = $result;
		}

		if ( empty( $decoded ) || ! is_array( $decoded ) ) {
			return false;
		}

		return $decoded;
	}
}