/*
 *	Fires when the dom is ready
 *
 */
jQuery(document).ready(function() {

	if ( jQuery('#cpac').length === 0 )
		return false;

	cpac_export_multiselect();
	cpac_import();
});

/*
 * Export Multiselect
 *
 * @since 1.5
 */
function cpac_export_multiselect() {

	// init
	jQuery( '.cpac-export-multiselect' ).multiSelect();

	// click events
	jQuery( '.export-select-all' ).click( function( e ) {
		jQuery( this ).parents( 'form' ).find( '.cpac-export-multiselect' ).multiSelect( 'select_all' );
		e.preventDefault();
	} );
}

/*
 * Import
 *
 * @since 1.5
 */
function cpac_import() {
	var container = jQuery('#cpac_import_input');

	jQuery('#upload', container).change(function () {
		if ( jQuery(this).val() )
			jQuery('#import-submit', container).addClass('button-primary');
		else
			jQuery('#import-submit', container).removeClass('button-primary');
	});
}