jQuery( document ).ready( function( $ ) {

	// set sorting indicator
	if ( AC_SORTABLE.column_name ) {
		$heading = $( 'table.wp-list-table thead th.column-' + AC_SORTABLE.column_name );
		if ( $heading.length > 0 ) {
			$heading.removeClass( 'asc', 'desc' ).addClass( 'sorted ' + AC_SORTABLE.order );
		}
	}
} );