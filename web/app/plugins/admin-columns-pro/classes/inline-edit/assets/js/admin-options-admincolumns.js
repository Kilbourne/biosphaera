/*
 * Refresh column events when ACF-field-type or Custom-Field-field-type is changed.
 *
 */
jQuery( document ).bind('column_init column_change column_add', function( e, column ){
	if ( 'column-acf_field' === jQuery( column ).find( '.column_type select' ).val() ) {
		jQuery( column ).find( '.column_field select, .column-sub_field select' ).change( function() {
			jQuery( column ).cpac_column_refresh();
		} );
	}
});