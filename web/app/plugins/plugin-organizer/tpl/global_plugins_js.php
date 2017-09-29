<?php
global $wpdb;
if ( current_user_can( 'activate_plugins' ) ) {
	?>
	<script type="text/javascript" language="javascript">
		function PO_submit_global_plugins(){
			var disabledList = new Array();
			var disabledMobileList = new Array();
			var disabledGroupList = new Array();
			var disabledMobileGroupList = new Array();
			jQuery('.PO-disabled-std-plugin-list').each(function() {
				disabledList[disabledList.length] = jQuery(this).val();
			});

			jQuery('.PO-disabled-mobile-plugin-list').each(function() {
				disabledMobileList[disabledMobileList.length] = jQuery(this).val();
			});

			jQuery('.PO-disabled-std-group-list').each(function() {
				disabledGroupList[disabledGroupList.length] = jQuery(this).val();
			});

			jQuery('.PO-disabled-mobile-group-list').each(function() {
				disabledMobileGroupList[disabledMobileGroupList.length] = jQuery(this).val();
			});
			
			var postVars = { 'PO_disabled_std_plugin_list[]': disabledList, 'PO_disabled_mobile_plugin_list[]': disabledMobileList, 'PO_disabled_std_group_list[]': disabledGroupList, 'PO_disabled_mobile_group_list[]': disabledMobileGroupList, PO_nonce: '<?php print $this->PO->nonce; ?>' };
			PO_submit_ajax('PO_save_global_plugins', postVars, '#post-body-content', function(){});
		}
	</script>
	<?php
}
?>