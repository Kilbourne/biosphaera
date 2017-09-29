/*
 * Filtering ajax caching
 *
 */
jQuery( document ).ready( function( $ ) {

	$( '.tablenav .actions:eq(1) select:not(.cpac_filter)' ).each( function() {
		if ( $( this ).val() != '' ) {
			$( this ).addClass( 'active' );
		}
		if ( $( this ).val() == 0 ) {
			$( this ).removeClass( 'active' );
		}
	} );

	// Date ranges
	$( '.cpac_range.date input.min' ).on( 'keydown', function( e ) {
		var keyCode = e.keyCode || e.which;
		if ( keyCode == 9 ) {
			e.preventDefault();
		}
	} ).datepicker( {
		dateFormat : "yy-mm-dd",
		changeYear : true,
		beforeShow : function() {
			$( 'body' ).addClass( 'cpac_ui' )
		},
		onClose : function( selectedDate ) {
			$( this ).parent( '.input_group' ).find( '.max' ).datepicker( "option", "minDate", selectedDate ).focus();
		}
	} );
	$( '.cpac_range.date input.max' ).datepicker( {
		dateFormat : "yy-mm-dd",
		changeYear : true,
		beforeShow : function() {
			$( 'body' ).addClass( 'cpac_ui' )
		},
		onClose : function( selectedDate ) {
			$( this ).parent( '.input_group' ).find( '.min' ).datepicker( "option", "maxDate", selectedDate );
		}
	} );

	// Number ranges
	$( ".cpac_range.number input.min" ).on( 'blur change', function() {
		var minvalue = parseInt( $( this ).val() );
		var $maxinput = $( this ).parent().find( 'input.max' );
		var maxvalue = parseInt( $maxinput.val() );

		if ( maxvalue < minvalue ) {
			$maxinput.val( minvalue );
		}
	} );

	$( ".cpac_range.number input.max" ).on( 'change', function() {
		var maxvalue = parseInt( $( this ).val() );
		var $mininput = $( this ).parent().find( 'input.min' );
		var minvalue = parseInt( $mininput.val() );

		if ( minvalue > maxvalue ) {
			$mininput.val( maxvalue );
		}

	} );


	// Multisite Users
	if( $( 'body.cp-wp-ms_users' ).length ){
		var $form = $('<form method="get" id="cpac_form-user-list"></form>');
		$form.insertBefore('#form-user-list' );

		$( '#wpfooter select.cpac_filter, #wpfooter input[name=cpac_filter_action]' ).appendTo( '#cpac_form-user-list' );
	}

	$.post( ajaxurl, {
			plugin_id : 'cpac',
			action : 'cac_update_filtering_cache',
			storage_model : CAC_Filtering.storage_model,
			layout : CAC_Filtering.layout,
			_ajax_nonce : CAC_Filtering.nonce
		},
		function( response ) {

			if ( response.success ) {

				var $select_boxes = $( 'select.cpac_filter' );

				// populate select options with new data
				if ( response.data ) {

					var $data = $( '<div>' ).html( response.data );

					$select_boxes.each( function() {
						var $el = $( this );
						var name = $( this ).attr( 'name' );
						var $select = $data.find( 'select[name="' + name + '"]' );

						if ( $select.length > 0 ) {

							$el.html( '' ).html( $select.html() );

							var current = $el.data( 'current' );
							if ( current ) {
								$el.find( 'option[value="' + current + '"]' ).attr( 'selected', 'selected' );
							}
						}

						// No filter values found
						else {
							$el.remove();
						}
					} );
				}

				// there are no select options, we can remove the "loading values" messages
				else {
					$select_boxes.remove();
				}
			}

			// Error
			else {
				// do nothing
			}
		},
		'json'
	);
} );