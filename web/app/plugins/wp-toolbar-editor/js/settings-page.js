jQuery(function($) {
	//Enable the "Add exception..." box only if global scope is currently selected.
	$('input[type="radio"]', '#abe-menu-scope-settings').change(function() {
		$('#abe-override-scope').prop(
			'disabled',
			! $('#abe-menu-config-scope-global').prop('checked')
		);
	});
});
