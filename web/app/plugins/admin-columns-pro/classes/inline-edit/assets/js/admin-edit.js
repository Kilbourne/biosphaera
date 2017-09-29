/**
 * Format a list of options from a storage model for use in X-editable
 *
 * @since 1.0
 *
 * @param array options List of options, can be nested (1 level max). Options have their key as the input value and their value as the input label. Parents have string 'label' and array 'options' of options.
 * @return array List of options with parents with 'text' and 'children' and options with 'id' and 'text'
 */
function cacie_options_format_editable( options ) {

	var foptions = [];

	if ( typeof options === "undefined" ) {
		return foptions;
	}

	for ( var i = 0; i < options.length; i++ ) {
		var parent;

		if ( typeof options[ i ].options !== 'undefined' ) {
			parent = {
				text : options[ i ].label,
				children : []
			};

			for ( var j in options[ i ].options ) {
				parent.children.push( {
					value : options[ i ].options[ j ].value,
					id : options[ i ].options[ j ].value,
					text : options[ i ].options[ j ].label
				} );
			}
		}
		else {
			parent = {
				value : options[ i ].value,
				id : options[ i ].value,
				text : options[ i ].label
			};
		}

		foptions.push( parent );
	}

	return foptions;
}

jQuery.fn.cacie_show_message = function( message, type ) {

	if ( typeof type == 'undefined' ) {
		type = '';
	}
	else if ( type != 'info' && type != 'error' && type != 'success' ) {
		type = '';
	}

	var el_alert = jQuery( '<div />' );

	el_alert.addClass( 'alert' );

	if ( type ) {
		el_alert.addClass( 'alert-' + type );
	}

	el_alert.append( '<button type="button" class="close" data-dismiss="alert">&times;</button>' );
	el_alert.append( message );

	jQuery( this ).after( el_alert );
};

jQuery.fn.cacie_after_save = function( column, item, newvalue ) {
	var el = jQuery( this );

	if ( typeof newvalue != 'undefined' ) {
		el.cacie_set_value( column, item, newvalue );
	}

	el.removeClass( 'bg-transition' );
	el.cacie_handle_value( column, item );
	el.cacie_handle_actions( column, item );
	el.cacie_remove_ajax_loading( column, item );

	// hook for addons
	jQuery( document ).trigger( 'cacie_after_save', column, item, newvalue );
};

jQuery.fn.cacie_handle_value = function( column, item ) {
	var el = jQuery( this );
	var currentvalue = el.cacie_get_value( column, item );
	var isempty;

	if ( currentvalue ) {
		isempty = false;
	}
	else {
		isempty = true;
	}

	if ( column.addon_cacie.editable.type == 'media' && currentvalue == 'false' ) {
		isempty = true;
	}

	if ( isempty ) {
		el.parents( 'td' ).removeClass( 'cacie-nonempty' );
		el.parents( 'td' ).addClass( 'cacie-empty' );
	}
	else {
		el.parents( 'td' ).addClass( 'cacie-nonempty' );
		el.parents( 'td' ).removeClass( 'cacie-empty' );
	}
};

jQuery.fn.cacie_add_ajax_loading = function( column, item ) {
	var el = jQuery( this );
	var editable_el = el.parents( 'td' ).find( '.cacie-editable' );

	switch ( column.type ) {
		case 'title':
		case 'name':
		case 'username':
			if ( editable_el.siblings( '.post-state' ).length ) {
				editable_el = editable_el.siblings( '.post-state' ).eq( 0 );
			}
			break;
	}

	editable_el.after( '<div class="spinner cacie-ajax-loading" />' );
};

jQuery.fn.cacie_remove_ajax_loading = function( column, item ) {
	var el = jQuery( this );

	el.parents( 'td' ).find( '.cacie-ajax-loading' ).remove();
};

jQuery.fn.cacie_restore_revision = function( column, item, revision ) {
	var el = jQuery( this );

	item.columndata[ column.column_name ].current_revision = revision;

	var revisions = CACIE_Items[ item.ID ].columndata[ column.column_name ].revisions;
	var current_revision = CACIE_Items[ item.ID ].columndata[ column.column_name ].current_revision;

	if ( 'is_xeditable' in column.addon_cacie.editable && column.addon_cacie.editable.is_xeditable ) {
		var target = el.cacie_get_target( column );

		el.cacie_add_ajax_loading( column, item );

		target.editable( 'setValue', revisions[ current_revision ], true );
		target.editable( 'submit' );
	}
	else {
		el.cacie_savecolumn( column, item, revisions[ current_revision ], false );
	}

	el.cacie_handle_actions( column, item );
};

jQuery.fn.cacie_get_target = function( column ) {
	var el = jQuery( this );

	if ( typeof column.addon_cacie.editable.js != 'undefined' && typeof column.addon_cacie.editable.js.selector != 'undefined' && el.find( column.addon_cacie.editable.js.selector ).length ) {
		el = el.find( column.addon_cacie.editable.js.selector );
	}

	return el;
};

/**
 * Get value
 *
 * @since 1.0
 */
jQuery.fn.cacie_get_value = function( column, item ) {
	return item.columndata[ column.column_name ].value;
};

/**
 * Set value
 *
 * @since 1.0
 */
jQuery.fn.cacie_set_value = function( column, item, newvalue ) {

	item.columndata[ column.column_name ].value = newvalue;
};

/**
 * Edit type: Text
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_text = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'text',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: URL
 *
 * @since 3.6
 */
jQuery.fn.cacie_edit_url = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'url',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: Float
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_float = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'text',
		value : el.cacie_get_value( column, item ),
		validate : function( value ) {
			if ( value && !cacie_is_float( value ) ) {
				return qie_i18n.errors.invalid_float;
			}
		}
	}, column, item );
};

/**
 * Edit type: WooCommerce Price
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_wc_price = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'wc_price',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: WooCommerce Usage and Limit
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_wc_usage = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'wc_usage',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: WooCommerce Stock
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_wc_stock = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'wc_stock',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

function cacie_esc_regex( value ) {
	return value.replace( /[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&" );
}

function cacie_is_float( value ) {

	var decimal_point_regex = '';

	if ( 'decimal_point' in woocommerce_admin && woocommerce_admin.decimal_point ) {
		decimal_point_regex = '|' + cacie_esc_regex( woocommerce_admin.decimal_point );
	}

	var regex = new RegExp( "^[0-9]+((\." + decimal_point_regex + ")[0-9]+)?$" );

	return value.match( regex );

}
/**
 * Edit type: Dimensions
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_dimensions = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'dimensions',
		value : el.cacie_get_value( column, item ),
		validate : function( value ) {
			if ( !cacie_is_float( value.length ) || !cacie_is_float( value.width ) || !cacie_is_float( value.height ) ) {
				return qie_i18n.errors.invalid_floats;
			}
		}
	}, column, item );
};

/**
 * Edit type: Number
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_number = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'number',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: Password
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_password = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'password',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: Email
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_email = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'email',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: Checkbox list
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_checkboxlist = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'checklist'
	}, column, item );
};

/**
 * Edit type: Textarea
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_textarea = function( column, item ) {

	var el = jQuery( this );

	el.cacie_xeditable( {
		type : 'textarea',
		value : el.cacie_get_value( column, item )
	}, column, item );
};

/**
 * Edit type: Select
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_select = function( column, item ) {

	var el = jQuery( this );
	var value = el.cacie_get_value( column, item );
	var options = column.addon_cacie.editable.options;

	el.cacie_xeditable( {
		type : 'select',
		value : value,
		source : cacie_options_format_editable( options ),
	}, column, item );
};

/**
 * Edit type: Select2 - Dropdown
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_select2_dropdown = function( column, item ) {

	var options = column.addon_cacie.editable.options;
	var el = jQuery( this );

	// populate options from object
	var defaults = {
		type : 'select2',
		showbuttons : true,
		source : cacie_options_format_editable( options ),
		select2 : {
			width : '200px'
		}
	};

	var column_editable = item.columndata[ column.column_name ].editable;

	// populate options by ajax
	if ( typeof column.addon_cacie.editable.ajax_populate !== 'undefined' && column.addon_cacie.editable.ajax_populate ) {
		args = {
			source : '',
			showbuttons : false,
			select2 : {
				width : 200,
				minimumInputLength : 0,
				initSelection : function( element, callback ) {
					var data = [];

					if ( typeof column_editable.formattedvalue != 'undefined' ) {
						for ( var id in column_editable.formattedvalue ) {
							data.push( {
								id : id,
								text : column_editable.formattedvalue[ id ]
							} );
						}
					}

					if ( data.length == 1 ) {
						callback( data[ 0 ] );
					}
					else if ( data.length > 0 ) {
						callback( data );
					}
				},
				ajax : {
					url : ajaxurl,
					dataType : 'json',
					quietMillis : 100,
					data : function( searchterm, page ) {
						return {
							plugin_id : 'cpac',
							action : 'cacie_get_options',
							searchterm : searchterm,
							column : column.column_name,
							storage_model : CACIE.storage_model,
							layout : CACIE.layout,
							item_id : item.ID,
							_ajax_nonce : CACIE.nonce
						};
					},
					results : function( response, page ) {
						if ( response.success ) {
							return {
								results : cacie_options_format_editable( response.data )
							};
						}

						// Close Select2 dropdown
						el.data( 'editable' ).input.$input.select2( 'close' );

						// Output error
						el.data( 'editable' ).container.$form.editableform( 'error', response.data );

						return { results : [] };
					}
				}
			}
		};

		defaults = jQuery.extend( defaults, args );
	}

	if ( typeof column.addon_cacie.editable.multiple !== 'undefined' && column.addon_cacie.editable.multiple ) {
		defaults.select2.multiple = true;
		defaults.showbuttons = true;
	}

	jQuery( this ).cacie_xeditable( defaults, column, item );

	if ( typeof column.addon_cacie.editable.ajax_populate !== 'undefined' && column.addon_cacie.editable.ajax_populate ) {
		jQuery( this ).on( 'shown', function() {
			var inp = el.data( 'editable' ).input.$input;

			inp.on( 'change', function() {
				column_editable.formattedvalue = [];

				var currentdata = inp.select2( 'data' );

				if ( typeof currentdata.id != 'undefined' ) {
					column_editable.formattedvalue[ currentdata.id ] = currentdata.text;
				}
				else {
					for ( var i in currentdata ) {
						column_editable.formattedvalue[ currentdata[ i ].id ] = currentdata[ i ].text;
					}
				}
			} );
		} );
	}
};

/**
 * Edit type: Select2 - Tags
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_select2_tags = function( column, item ) {

	var el = jQuery( this );
	var options = column.addon_cacie.editable.options;
	var value = el.cacie_get_value( column, item );

	// e.g. no terms available
	if ( 'false' == value )
		value = '';

	el.cacie_xeditable( {
		type : 'select2',
		value : value,
		select2 : {
			width : 200,
			tags : cacie_options_format_editable( options )
		},
	}, column, item );
};

/**
 * Edit type: togglable
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_togglable = function( column, item ) {

	var el = jQuery( this );
	var options = column.addon_cacie.editable.options;

	// Toggle on click
	jQuery( this ).on( 'click', function() {
		if ( !window.cacie_edit_enabled || !options ) {
			return;
		}

		var currentvalue = el.cacie_get_value( column, item );
		var num_values = options.length;
		var current_index = 0;
		var newvalue;

		for ( var i in options ) {
			if ( currentvalue == options[ i ].label ) {
				current_index = options[ i ].value;
				break;
			}
		}

		if ( typeof column.addon_cacie.editable.required != 'undefined' && column.addon_cacie.editable.required ) {
			if ( current_index !== 0 ) {
				el.cacie_show_message( qie_i18n.errors.field_required );
				return;
			}
		}

		newvalue = options[ ( current_index + 1 ) % num_values ].label;

		// Save column
		el.cacie_savecolumn( column, item, newvalue );
	} );
};

jQuery.fn.cacie_edit_media = function( column, item ) {
	var el = jQuery( this );

	el.cacie_edit_attachment( column, item );
};

/**
 * Edit type: media
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_attachment = function( column, item ) {

	var el = jQuery( this );

	// Media upload
	el.on( 'click', function( e ) {
		e.preventDefault();

		if ( !window.cacie_edit_enabled ) {
			return;
		}

		var current_selection = el.cacie_get_value( column, item );

		if ( !jQuery.isArray( current_selection ) ) {
			current_selection = [ current_selection ];
		}

		var args = {
			title : 'Change image',
			button : {
				text : 'Set as image'
			},
			multiple : ( typeof column.addon_cacie.editable.multiple != 'undefined' && column.addon_cacie.editable.multiple )
		};

		if ( typeof column.addon_cacie.editable.attachment != 'undefined' && typeof column.addon_cacie.editable.attachment.library != 'undefined' ) {
			args.library = {};

			if ( typeof column.addon_cacie.editable.attachment.library.uploaded_to_post != 'undefined' ) {
				args.library.uploadedTo = item.ID;
			}

			if ( typeof column.addon_cacie.editable.attachment.library.type != 'undefined' ) {
				args.library.type = column.addon_cacie.editable.attachment.library.type;
			}
		}

		// Merge with column type-specific arguments
		if ( 'js' in column.addon_cacie.editable ) {
			args = jQuery.extend( args, column.addon_cacie.editable.js );
		}

		// Init
		var uploader = wp.media( args );

		// Add current selection
		uploader.on( 'open', function() {
			var selection = uploader.state().get( 'selection' );

			current_selection.forEach( function( id ) {
				attachment = wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment ? [ attachment ] : [] );
			} );
		} );

		// Store selection
		uploader.on( 'select', function() {
			var selection = uploader.state().get( 'selection' ).toJSON();
			var multiple = uploader.options.multiple;

			// multiple attachments
			var attachment_ids = [];

			for ( var k in selection ) {
				var attachment = selection[ k ];
				attachment_ids.push( attachment.id );
			}

			// Single attachment ( integer )
			if ( 1 === attachment_ids.length && !multiple ) {
				attachment_ids = attachment_ids[ 0 ];
			}

			// Save column
			el.cacie_savecolumn( column, item, attachment_ids );
		} );

		if ( typeof column.addon_cacie.editable.attachment !== 'undefined' ) {
			if ( typeof column.addon_cacie.editable.attachment.disable_select_current !== 'undefined' && column.addon_cacie.editable.attachment.disable_select_current ) {
				uploader.on( 'ready', function() {
					setTimeout( function() {
					}, 1 );
				} );
			}
		}

		uploader.open();
	} );
};

/**
 * Edit type: Date
 *
 * Uses bootrap datepicker; http://www.eyecon.ro/bootstrap-datepicker/
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_date = function( column, item ) {

	var el = jQuery( this );
	var value = el.cacie_get_value( column, item );

	// convert yyyymmdd to yyyy-mm-dd
	if ( value ) {
		value = [ value.slice( 0, 4 ), '-', value.slice( 4 ) ].join( '' );
		value = [ value.slice( 0, 7 ), '-', value.slice( 7 ) ].join( '' );
	}

	el.attr( 'data-date', value );
	el.attr( 'data-date-format', 'yyyy-mm-dd' );

	el.bdatepicker().on( 'changeDate', function( ev ) {
		var new_date = new Date( ev.date );
		var yyyymmdd = new_date.yyyymmdd();

		el.cacie_savecolumn( column, item, yyyymmdd );
	} );

	// Convert date object to yyyyddmm format
	// http://stackoverflow.com/questions/3066586/get-string-in-yyyymmdd-format-from-js-date-object
	Date.prototype.yyyymmdd = function() {
		var yyyy = this.getFullYear().toString();
		var mm = (this.getMonth() + 1).toString(); // getMonth() is zero-based
		var dd = this.getDate().toString();
		return yyyy + (mm[ 1 ] ? mm : "0" + mm[ 0 ]) + (dd[ 1 ] ? dd : "0" + dd[ 0 ]); // padding
	};
};

/**
 * Edit type: taxonomy
 *
 * @since 1.0
 */
jQuery.fn.cacie_edit_checklist = function( column, item ) {

	var el = jQuery( this );
	var value = el.cacie_get_value( column, item );
	var options = column.addon_cacie.editable.options;

	// e.g. no terms available
	if ( 'false' == value ) {
		value = '';
	}

	el.cacie_xeditable( {
		type : 'checklist',
		value : value,
		source : cacie_options_format_editable( options ),
	}, column, item );
};

/**
 * Make column editable via x-editable for a certain item
 *
 * @since 1.0
 *
 * @param object args Arguments to be used for the x-editable call. Arguments that are passed will overwrite the default values for these arguments
 * @param object editable Information about this type of editable column
 * @param object column Information about this specific editable column
 * @param object item The current item (row) that is being made editable (e.g. a post or user object)
 */
jQuery.fn.cacie_xeditable = function( args, column, item ) {

	var el = jQuery( this );

	var defaults = {
		url : ajaxurl,
		params : {
			plugin_id : 'cpac',
			action : 'cacie_column_save',
			storage_model : CACIE.storage_model,
			layout : CACIE.layout,
			column : column.column_name,
			_ajax_nonce : CACIE.nonce
		},
		pk : item.ID,
		value : jQuery( this ).cacie_get_value( column, item ),
		placement : 'bottom',
		mode : 'popup', // or inline
		emptytext : '',
	};

	// Merge with edit type-specific arguments
	args = jQuery.extend( defaults, args );

	// Merge with column type-specific arguments
	if ( typeof column.addon_cacie.editable.js != 'undefined' ) {
		args = jQuery.extend( args, column.addon_cacie.editable.js );
	}

	// Placeholder
	if ( typeof column.addon_cacie.editable.placeholder != 'undefined' ) {
		args.placeholder = column.addon_cacie.editable.placeholder;
	}

	var htmlatts = [];

	// Max length
	if ( typeof column.addon_cacie.editable.maxlength != 'undefined' ) {
		htmlatts.push( {
			key : 'maxlength',
			value : parseInt( column.addon_cacie.editable.maxlength, 10 )
		} );
	}

	// Number range
	if ( typeof column.addon_cacie.editable.range_min != 'undefined' ) {
		htmlatts.push( {
			key : 'min',
			value : parseFloat( column.addon_cacie.editable.range_min, 10 )
		} );
	}

	if ( typeof column.addon_cacie.editable.range_max != 'undefined' ) {
		htmlatts.push( {
			key : 'max',
			value : parseFloat( column.addon_cacie.editable.range_max, 10 )
		} );
	}

	if ( typeof column.addon_cacie.editable.range_step != 'undefined' ) {
		var step = 'any';

		if ( column.addon_cacie.editable.range_step.length > 0 && column.addon_cacie.editable.range_step != 'any' ) {
			step = parseFloat( column.addon_cacie.editable.range_step, 10 );
		}

		htmlatts.push( {
			key : 'step',
			value : step
		} );
	}

	if ( htmlatts.length ) {
		var htmlatts_html = '';

		for ( var i in htmlatts ) {
			htmlatts_html += htmlatts[ i ].key + '="' + jQuery( '<div />' ).text( htmlatts[ i ].value ).html() + '"';
		}

		switch ( column.addon_cacie.editable.type ) {
			case 'number':
				args.tpl = '<input type="number" ' + htmlatts_html + '>';
				break;
			case 'text':
				args.tpl = '<input type="text" ' + htmlatts_html + '>';
				break;
			case 'textarea':
				args.tpl = '<textarea ' + htmlatts_html + '></textarea>';
				break;
		}
	}

	// Required
	if ( typeof column.addon_cacie.editable.required != 'undefined' && column.addon_cacie.editable.required ) {
		args.validate = function( value ) {
			var valid = true;

			switch ( column.addon_cacie.editable.type ) {
				case 'select':
					if ( !value || value == 'null' ) {
						valid = false;
					}
					break;
				default:
					if ( !value.length ) {
						valid = false;
					}
					break;
			}

			if ( !valid ) {
				return qie_i18n.errors.field_required;
			}
		};
	}

	var target = el.cacie_get_target( column );

	target.on( 'save', function( e, params ) {
		el.cacie_store_revision( column, item, params.newValue );
		el.cacie_after_save( column, item, params.newValue );
	} );

	// Display ajax returned value
	if ( typeof column.addon_cacie.editable.display_ajax == 'undefined' || column.addon_cacie.editable.display_ajax ) {
		args.display = function() {}; // should be left empty, do not remove
		args.success = function( response ) { // replace display with ajax value
			if ( response.success ) {
				var $val = jQuery('<div>' ).html( response.data.value ).append( el.find('.row-actions') );
				el.html( $val.html() );
			}

			el.cacie_after_save( column, item );

			if ( typeof response.data !== 'undefined' && typeof response.data.rawvalue !== 'undefined' ) {
				return { newValue : response.data.rawvalue };
			}
		};
	}
	else if ( typeof column.addon_cacie.editable.js !== 'undefined' && typeof column.addon_cacie.editable.js.selector !== 'undefined' ) {
		args.success = function( response ) {
			el.cacie_after_save( column, item );
		}
	}
	else {
		args.success = function( response ) {
			el.cacie_after_save( column, item );

			if ( typeof response.data !== 'undefined' && typeof response.data.rawvalue !== 'undefined' ) {
				return { newValue : response.data.rawvalue };
			}
		};
	}

	// Add marker to editable that XEditable is in use
	column.addon_cacie.editable.is_xeditable = true;

	// Reference to xeditables
	var xeditable = el;

	if ( typeof column.addon_cacie.editable.js !== 'undefined' && typeof column.addon_cacie.editable.js.selector !== 'undefined' ) {
		xeditable = el.find( column.addon_cacie.editable.js.selector );
		el.removeClass( 'cacie-editable' );
		xeditable.addClass( 'cacie-editable' );
	}

	// Add for reference
	window.xeditables.push( xeditable );

	// XEditable
	el.editable( args );
};

/**
 * Store Revision
 *
 * @since 1.0
 *
 * @param object editable Information about this type of editable column
 * @param object column Information about this specific editable column
 * @param object item The current item (row) that is being made editable (e.g. a post or user object)
 * @param mixed value Revised value
 */
jQuery.fn.cacie_store_revision = function( column, item, value ) {

	var revisions = CACIE_Items[ item.ID ].columndata[ column.column_name ].revisions;
	var current_revision = CACIE_Items[ item.ID ].columndata[ column.column_name ].current_revision;
	var num_deletes = revisions.length - current_revision - 1;

	for ( var i = 0; i < num_deletes; i++ ) {
		CACIE_Items[ item.ID ].columndata[ column.column_name ].revisions.pop();
	}

	CACIE_Items[ item.ID ].columndata[ column.column_name ].revisions.push( value );
	CACIE_Items[ item.ID ].columndata[ column.column_name ].current_revision++;

	jQuery( this ).cacie_handle_actions( column, item );
};

/**
 * Save a column by initiating an AJAX request, setting the item column HTML based on the return value from the AJAX call
 *
 * @since 1.0
 *
 * @param object editable Information about this type of editable column
 * @param object column Information about this specific editable column
 * @param object item The current item (row) that is being made editable (e.g. a post or user object)
 * @param mixed newvalue New value for this item and column
 * @param bool storerevision Optional. Whether to store the change as a revision
 */
jQuery.fn.cacie_savecolumn = function( column, item, newvalue, storerevision ) {

	var el = jQuery( this );

	// Update value for element
	el.cacie_set_value( column, item, newvalue );

	// Css transition
	el.addClass( 'bg-transition' );

	// Handle storing the revision
	storerevision = ( typeof storerevision == 'undefined' || storerevision );

	if ( storerevision ) {
		jQuery( this ).cacie_store_revision( column, item, newvalue );
	}

	// Do AJAX request
	el.cacie_add_ajax_loading( column, item );

	jQuery.post( ajaxurl, {
		plugin_id : 'cpac',
		action : 'cacie_column_save',
		storage_model : CACIE.storage_model,
		layout : CACIE.layout,
		column : column.column_name,
		pk : item.ID,
		value : newvalue,
		_ajax_nonce : CACIE.nonce
	}, function( response ) {
		if ( response.success ) {
			// update data even when empty, in case field is cleared
			el.html( response.data.value );

			if ( typeof response.data.itemdata !== 'undefined' ) {
				item.columndata[ column.column_name ].itemdata = response.data.itemdata;
			}
		}

		el.cacie_after_save( column, item );
	}, 'json' );
};

/*
 * Handle cell actions
 */
jQuery.fn.cacie_handle_actions = function( column, item ) {

	var el, el_actions, el_undo, el_redo, el_clear, el_download;

	el = jQuery( this );
	el_actions = el.parent().find( '.cacie-cell-actions' );

	el_actions.remove();

	el.parent().find( '.cacie-edit, .cacie-undo, .cacie-redo, .cacie-clear' ).remove();

	el_actions = jQuery( '<div class="cacie-cell-actions" />' );
	el_edit = jQuery( '<a href="#" class="cacie-cell-action cacie-edit" title="' + qie_i18n.edit + '" />' );
	el_undo = jQuery( '<a href="#" class="cacie-cell-action cacie-undo" title="' + qie_i18n.undo + '" />' );
	el_redo = jQuery( '<a href="#" class="cacie-cell-action cacie-redo" title="' + qie_i18n.redo + '" />' );
	el_clear = jQuery( '<a href="#" class="cacie-cell-action cacie-clear" title="' + qie_i18n.delete + '"/>' );

	el_undo.hide();
	el_redo.hide();
	el_clear.hide();

	el_actions.append( el_redo );
	el_actions.append( el_undo );

	if ( column.addon_cacie.editable.clear_button ) {
		el_actions.prepend( el_clear );
	}

	switch ( column.type ) {
		case 'title':
		case 'name':
		case 'username':
		case 'coupon_code':
			el_actions.prepend( el_edit );
			el.parents( 'td' ).find( column.addon_cacie.editable.js.selector ).after( el_actions );
			//el.parents( 'td' ).find( '.row-actions a' ).click( function( e ) {
			//e.stopPropagation();
			//} );
			break;
		default:
			switch ( column.addon_cacie.editable.type ) {
				case 'attachment':
					if ( item.columndata[ column.column_name ].itemdata.url ) {
						el_download = jQuery( '<a href="' + item.columndata[ column.column_name ].itemdata.url + '" class="cacie-cell-action cacie-download" target="_blank" title="' + qie_i18n.download + '"/>' );
						el_actions.prepend( el_download );
					}

					el_actions.prepend( el_edit );
					el.parents( 'td' ).find( '.cacie-editable' ).after( el_actions );
					break;
				case 'media':
					el_actions.prepend( el_edit );
					el_undo.before( '<div class="cacie-separator" />' );
					el_redo.before( '<div class="cacie-separator" />' );

					if ( typeof column.addon_cacie.editable.multiple !== 'undefined' && column.addon_cacie.editable.multiple ) {
						el.parents( 'td' ).addClass( 'cacie-multiple' );

						el_clear.remove();

						el.parents( 'td' ).find( '.cacie-item' ).each( function() {
							var el_item_actions = jQuery( '<div class="cacie-item-actions" />' );
							var el_delete = jQuery( '<a href="#" class="cacie-cell-action cacie-delete" title="' + qie_i18n.delete + '"/>' );

							el_item_actions.append( el_delete );

							el_delete.on( 'click', ( function( column, item ) {
								return function() {
									var item_id = jQuery( this ).parents( '.cacie-item' ).attr( 'data-cacie-id' );
									var val = el.cacie_get_value( column, item );

									if ( jQuery.isArray( val ) ) {
										val = jQuery.grep( val, function( value ) {
											return value != item_id;
										} );
									}

									el.cacie_savecolumn( column, item, val );

									return false;
								};
							}( column, item ) ) );

							jQuery( this ).append( el_item_actions );
						} );

						el.parents( 'td' ).find( '.cacie-editable' ).append( el_actions );
					}
					else {
						el.parents( 'td' ).find( '.cacie-editable' ).append( el_actions );
					}
					break;
				default:
					el_actions.prepend( el_edit );
					el.parents( 'td' ).find( '.cacie-editable' ).after( el_actions );
					break;
			}
			break;
	}

	// Edit: Click event
	el_edit.on( 'click', ( function( column, item ) {
		return function( e ) {
			e.preventDefault();
			e.stopPropagation();

			el.cacie_get_target( column ).trigger( 'click' );
		};
	}( column, item ) ) );

	// Undo: Click Event
	el_undo.on( 'click', ( function( column, item ) {
		return function( e ) {
			e.preventDefault();
			e.stopPropagation();

			el.cacie_restore_revision( column, item, item.columndata[ column.column_name ].current_revision - 1 );
		};
	}( column, item ) ) );

	// Redo: Click Event
	el_redo.on( 'click', ( function( column, item ) {
		return function( e ) {
			e.preventDefault();
			e.stopPropagation();

			el.cacie_restore_revision( column, item, item.columndata[ column.column_name ].current_revision + 1 );
		};
	}( column, item ) ) );

	// Clear: Click Event
	el_clear.on( 'click', ( function( column, item ) {
		return function( e ) {
			el.cacie_savecolumn( column, item, '' );
			e.stopPropagation();
		};
	}( column, item ) ) );

	var revisions = CACIE_Items[ item.ID ].columndata[ column.column_name ].revisions;
	var current_revision = CACIE_Items[ item.ID ].columndata[ column.column_name ].current_revision;
	var current_value = revisions[ current_revision ];

	if ( current_revision < revisions.length - 1 ) {
		el_redo.show();
	}
	else {
		el_redo.hide();
	}

	if ( current_revision > 0 ) {
		el_undo.show();
	}
	else {
		el_undo.hide();
	}

	if ( current_value ) {
		el_clear.show();
	}
	else {
		el_clear.hide();
	}
};

/*
 * Init
 */
function cacie_init() {

	// List of xeditable elements for global reference
	window.xeditables = [];

	// this will allow non xeditable fields with click events to be disabled / enabled
	window.cacie_edit_enabled = 1;

	// Items table/container to apply editability on
	var list = jQuery( CACIE_List_Selector );

	// Readability
	var columns = CACIE_Columns;
	var items = CACIE_Items;

	// Loop through columns
	for ( var column_name in columns ) {
		var column = columns[ column_name ];

		// Set column name
		column.column_name = column_name;

		// Make column editable
		if ( column.addon_cacie.editable ) {
			if ( typeof column.addon_cacie.editable.type === 'undefined' || column.addon_cacie.editable.type === '' ) {
				continue;
			}

			var type = column.addon_cacie.editable.type;
			var fn;
			var options;

			// Function for editability of items in this column
			fn = 'cacie_edit_' + type;

			// Loop through items for current column
			jQuery( '.column-' + column_name, list ).each( function() {
				if( jQuery(this ).hasClass('cacie-editable-container' ) ){
					// in case we run init again
					return true;
				}

				// Get corresponding item for row
				var item;
				var id_attr = jQuery( this ).parents( 'tr' ).attr( 'id' );
				var id = parseInt( id_attr.substr( id_attr.lastIndexOf( '-' ) + 1 ), 10 );

				if ( !( id in items ) ) {
					// Skip to the next element (equivalent to "continue" in a for-loop)
					return true;
				}

				item = items[ id ];

				// Current value
				var currentvalue;

				if ( column_name in item.columndata ) {
					currentvalue = item.columndata[ column_name ].revisions[ 0 ];
				}

				// Value must be defined and must no be a WP Error object
				if ( currentvalue == null || ( typeof currentvalue === 'undefined' ) || ( typeof currentvalue.errors !== 'undefined' ) ) {
					// Skip to the next element (equivalent to "continue" in a for-loop)
					return true;
				}

				// Save value to item
				if ( type == 'select2_dropdown' || type == 'select2_tags' ) {
					if ( currentvalue.length === 0 ) {
						currentvalue = [];
					}
				}

				item.columndata[ column_name ].value = currentvalue;

				// Add classes to table cell
				jQuery( this ).addClass( 'cacie-editable-container cacie-editable-' + type );

				// Wrap the editable content for better control
				jQuery( this ).wrapInner( '<span class="inner cacie-editable"></span>' );
				var el = jQuery( this ).find( '.inner' );

				// set attributes
				el.cacie_handle_value( column, item );
				el.cacie_handle_actions( column, item );

				// Make editable
				el[ fn ]( column, item );
			} );
		}
	}
}

/*
 * Enable inline editing
 */
function cacie_enable() {

	jQuery( '#cacie-toggle-edit' ).addClass( 'active' );

	if ( typeof window.xeditables === 'undefined' ) {
		cacie_init();
	}

	// enable cacie when init has run once
	else {
		jQuery( window.xeditables ).editable( 'enable' );
	}

	jQuery( CACIE_List_Selector ).addClass( 'cacie-enabled' );
	window.cacie_edit_enabled = 1;
}

/*
 * Enable inline editing
 */
function cacie_disable() {
	jQuery( '#cacie-toggle-edit' ).removeClass( 'active' );
	jQuery( window.xeditables ).editable( 'disable' );
	window.cacie_edit_enabled = 0; // disable click events
	jQuery( CACIE_List_Selector ).removeClass( 'cacie-enabled' );
}

/*
 * Onload functions
 */
jQuery( document ).ready( function( $ ) {


	$( '.wp-list-table tbody').on( 'updated', 'tr', function(){

		//jQuery( window.xeditables ).editable( 'enable' );
		cacie_init();
	});

	// Columns and items are available
	if ( typeof CACIE_Columns === 'undefined' || typeof CACIE_Items === 'undefined' || CACIE_Items == null ) {
		return;
	}

	// Any editable columns and items
	if ( CACIE_Columns.length === 0 || CACIE_Items.length === 0 ) {
		return;
	}

	// Add button
	jQuery( '.tablenav.top .actions:last' ).append( '<a href="javascript:;" id="cacie-toggle-edit" class="cpac-edit add-new-h2">' + qie_i18n.inline_edit + '</a>' );

	// Toggle Inline Edit
	$( '#cacie-toggle-edit' ).on( 'click', function( e ) {

		// disable
		if ( window.cacie_edit_enabled ) {
			cacie_disable();
		}

		// enable
		else {
			cacie_enable();
		}

		// store preference
		jQuery.post( ajaxurl, {
			plugin_id : 'cpac',
			action : 'cacie_editability_state_save',
			value : window.cacie_edit_enabled,
			storage_model : CACIE.storage_model,
			_ajax_nonce : CACIE.nonce
		} );

		e.preventDefault();
	} );

	if ( CACIE.inline_edit.active === true ) {
		cacie_enable();
	}

	$( '.wp-list-table' ).on( 'click', '.cacie-editable a', function( e ) {
		e.stopPropagation();
		var $link = $( this );

		$link.parent().editable( 'destroy' );
		$link.parents( 'td:first' ).find( '.popover' ).remove();
		window.location.href = $link.attr( 'href' );
	} );
} );