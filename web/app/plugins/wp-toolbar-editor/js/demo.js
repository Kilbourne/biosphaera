jQuery(function($) {
	//Let the user hide the demo notice. It would be annoying to have it up all the time.
	$('#ws-abe-hide-demo-notice').click(function() {
		$('#abe-demo-notice').hide();
		$.cookie('abe_hide_demo_notice', 1);
		return false;
	});
});
