<script type="text/javascript" language="javascript">
	jQuery(function() {
		jQuery('#PO-active-plugins').sortable();
		jQuery('#PO-network-active-plugins').sortable();

		jQuery('.expand-info-icon').each(function() {
			var targetID = jQuery(this).prop('id').replace('PO-expand-info-', '');
			var groupContainer = jQuery('#PO-info-container-' + targetID);
			if (!jQuery(groupContainer).find('.PO-info-inner').html().match(/^\s*$/)) {
				jQuery('#' + targetID + '-plugin-groups').show();
			}
		});
	});

	function PO_action_change() {
		if (jQuery('#PO-plugin-action').val().match(/^(delete_plugin_group|remove_plugins_from_group|add_to_plugin_group)$/)) {
			if (!jQuery('#PO-group-name').prop('multiple')) {
				jQuery('#PO-group-select').slideUp();
			}
			jQuery('#PO-group-name').prop('multiple',true);
			jQuery('#PO-new-group-name-container').slideUp();
			jQuery('#PO-group-select').slideDown(300);
		} else if (jQuery('#PO-plugin-action').val() == 'create_new_group') {
			jQuery('#PO-group-select').slideUp();
			jQuery('#PO-new-group-name-container').slideDown(300);
		} else if (jQuery('#PO-plugin-action').val() == 'edit_plugin_group_name') {
			if (jQuery('#PO-group-name').prop('multiple')) {
				jQuery('#PO-group-select').slideUp(function() {
					jQuery('#PO-group-name').prop('multiple',false);
					jQuery('#PO-group-select').slideDown(300);
					jQuery('#PO-new-group-name-container').slideDown(300);
					var selectOptions = jQuery('#PO-group-select option');
					if (jQuery('#PO-group-select option:selected').length == 0) {
						if (selectOptions.length > 1) {
							jQuery(selectOptions[1]).prop('selected', 'selected');
						} else {
							jQuery('#PO-group-select option:first').prop('selected', 'selected');
						}
					}
				});
			} else {
				jQuery('#PO-group-select').slideDown(300);
				jQuery('#PO-new-group-name-container').slideDown(300);
			}
		} else {
			jQuery('#PO-group-select').slideUp();
			jQuery('#PO-new-group-name-container').slideUp();
		}
	}
	
	function PO_submit_plugin_action() {
		var returnStatus = true;
		switch(jQuery('#PO-plugin-action').val()) {
			case "save_load_order":
				returnStatus = PO_save_draggable_plugin_order();
				break;
			case "create_new_group":
				returnStatus = PO_create_new_group();
				break;
			case "add_to_plugin_group":
				returnStatus = PO_add_plugins_to_group();
				break;
			case "edit_plugin_group_name":
				returnStatus = PO_edit_plugin_group_name();
				break;
			case "delete_plugin_group":
				returnStatus = PO_delete_plugin_group();
				break;
			case "remove_plugins_from_group":
				returnStatus = PO_remove_plugins_from_group();
				break;
			case "reset_to_default_order":
				returnStatus = PO_reset_to_default_order();
				break;
		}
		return returnStatus;
	}
	
	function PO_create_new_group() {
		var groupList = new Array();
		var newGroupName = jQuery('input[name=PO_new_group_name]:first').val();
		jQuery('.plugin input:checkbox[name*=plugins]').each(function() {
			if (this.checked) {
				groupList[groupList.length] = jQuery(this).val();
			}
		});
		if (newGroupName == '') {
			PO_display_ui_dialog('Alert', 'You must enter a name for the new plugin group.');
		} else if (groupList.length == 0) {
			PO_display_ui_dialog('Alert', 'You must select at least one plugin to add to the group.');
		} else {
			var postVars = { 'PO_group_list[]': groupList, 'PO_group_name': newGroupName, PO_nonce: '<?php print $this->PO->nonce; ?>' };
			PO_submit_ajax('PO_create_new_group', postVars, '#PO-plugin-wrapper', PO_update_group_controls);
		}
		return false;
	}
	
	function PO_save_draggable_plugin_order() {
		var orderList = new Array();
		var startOrderList = new Array();
		var count=0;
		jQuery('.plugin.network-active, .plugin.active').each(function () {
			orderList[orderList.length] = count;
			startOrderList[startOrderList.length] = jQuery(this).find('.start-order').val();
			count++;
		});
		var callback = function() {
			var count=0;
			jQuery('.plugin.network-active, .plugin.active').each(function () {
				jQuery(this).find('.start-order').val(count);
				count++;
			});
		}
		
		var postVars = { 'orderList[]': orderList, 'startOrder[]': startOrderList, PO_nonce: '<?php print $this->PO->nonce; ?>' };
		PO_submit_ajax('PO_plugin_organizer', postVars, '#PO-plugin-wrapper', callback);
		
		
		return false;
	}




	function PO_add_plugins_to_group() {
		var groupIds = new Array();
		jQuery('#PO-group-name option:selected').each(function() {
			groupIds[groupIds.length] = jQuery(this).val();
		});
		
		var groupList = new Array();
		jQuery('.plugin input:checkbox[name*=plugins]').each(function() {
			if (this.checked) {
				groupList[groupList.length] = jQuery(this).val();
			}
		});
		if (groupList.length == 0) {
			PO_display_ui_dialog('Alert', 'You must select at least one plugin to add to the group.');
		} else {
			var postVars = { 'PO_group_list[]': groupList, 'PO_group_ids[]': groupIds, PO_nonce: '<?php print $this->PO->nonce; ?>' };
			PO_submit_ajax('PO_add_to_group', postVars, '#PO-plugin-wrapper', PO_update_group_containers);
		}
		return false;
	}


	function PO_remove_plugins_from_group() {
		var groupIds = new Array();
		jQuery('#PO-group-name option:selected').each(function() {
			groupIds[groupIds.length] = jQuery(this).val();
		});
		
		var groupList = new Array();
		jQuery('.plugin input:checkbox[name*=plugins]').each(function() {
			if (this.checked) {
				groupList[groupList.length] = jQuery(this).val();
			}
		});
		
		if (groupList.length == 0) {
			PO_display_ui_dialog('Alert', 'You must select at least one plugin to remove from the group.');
		} else {
			if (confirm('Are you sure you wish to remove the selected plugins from the selected groups?')) {
				var postVars = { 'PO_group_list[]': groupList, 'PO_group_ids[]': groupIds, PO_nonce: '<?php print $this->PO->nonce; ?>' };
				PO_submit_ajax('PO_remove_plugins_from_group', postVars, '#PO-plugin-wrapper', PO_update_group_containers);
			}
		}
		return false;
	}


	function PO_delete_plugin_group() {
		var groupIds = new Array();
		jQuery('#PO-group-name option:selected').each(function() {
			groupIds[groupIds.length] = jQuery(this).val();
		});
		
		if (confirm('Are you sure you wish to delete the selected groups?')) {
			var postVars = { 'PO_group_ids[]': groupIds, PO_nonce: '<?php print $this->PO->nonce; ?>' };
			PO_submit_ajax('PO_delete_group', postVars, '#PO-plugin-wrapper', PO_update_group_controls);
		}
		return false;
	}


	function PO_edit_plugin_group_name(selectedGroup) {
		var newGroupName = jQuery('input[name=PO_new_group_name]:first').val();
		var group_id = jQuery('#PO-group-name option:selected').val();
		if (group_id == '') {
			PO_display_ui_dialog('Alert', 'The group you have selected can\'t be edited.');
		} else if (newGroupName == '') {
			PO_display_ui_dialog('Alert', 'You must enter a new name for the group if you want to change it.');
		} else {
			var postVars = { PO_nonce: '<?php print $this->PO->nonce; ?>', PO_group_id: group_id, PO_group_name: newGroupName };
			PO_submit_ajax('PO_edit_plugin_group_name', postVars, '#PO-plugin-wrapper', PO_update_group_controls);
		}
		return false;
	}
		
	function PO_reset_to_default_order() {
		if (confirm('Are you sure you want to reset the plugin load order back to default?')) {
			var postVars = { PO_nonce: '<?php print $this->PO->nonce; ?>' };
			PO_submit_ajax('PO_reset_to_default_order', postVars, '#PO-plugin-wrapper', function() {location.reload()});
		}
		return false;
	}

	function PO_update_group_containers() {
		jQuery('.plugin-form').each(function() {
			var pluginSelector = jQuery(this).prop('id').replace('PO-plugin-form-', '');
			jQuery(this).find('.PO-plugin-action-checkbox').each(function() {
				var postVars = { 'PO_plugin_path': jQuery(this).val(), PO_nonce: '<?php print $this->PO->nonce; ?>' };
					
				jQuery.post(encodeURI(ajaxurl + '?action=PO_get_plugin_group_container'), postVars, function (result) {
					jQuery('#PO-info-container-'+pluginSelector+' .PO-info-inner').html(result);
					if (result.match(/^\s*$/)) {
						jQuery('#'+pluginSelector+'-plugin-groups').hide();
					} else {
						jQuery('#'+pluginSelector+'-plugin-groups').show();
					}
					
					PO_set_expand_info_action();
				});
			});
			

		});
	}



	function PO_update_group_select() {
		var postVars = { PO_nonce: '<?php print $this->PO->nonce; ?>' };
		
		jQuery.post(encodeURI(ajaxurl + '?action=PO_get_group_list'), postVars, function (result) {
			var groupList = jQuery.parseJSON(result);
			if (typeof(groupList) == 'object') {
				jQuery('#PO-group-name').find('option').remove().end();
				jQuery('#PO-group-name').append('<option value="" disabled="disabled">-- Select Group --</option>');
				for(var i=0; i<groupList.length; i++) {
					jQuery('#PO-group-name').append('<option value="'+groupList[i][0]+'">'+groupList[i][1]+'</option>');
				}
			}
		});
	}

	function PO_update_group_controls() {
		PO_update_group_containers();
		PO_update_group_select();
	}
</script>