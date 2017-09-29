<?php
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-droppable');
wp_enqueue_script('jquery-ui-draggable');
wp_enqueue_script('jquery-ui-tooltip');
wp_enqueue_script('jquery-ui-dialog');
?>
<script language="javascript" src="<?php print $this->PO->urlPath; ?>/js/validation.js"></script>
<script language="javascript" type="text/javascript">
	var tmpObjectCount = 0;
	<?php
	$globalPluginLists = array(
		'std_plugins'=>(is_array(get_option('PO_disabled_plugins')))? get_option('PO_disabled_plugins') : array(), 
		'mobile_plugins'=>(is_array(get_option('PO_disabled_mobile_plugins')))? get_option('PO_disabled_mobile_plugins') : array(),
		'std_groups'=>(is_array(get_option('PO_disabled_groups')))? get_option('PO_disabled_groups') : array(),
		'mobile_groups'=>(is_array(get_option('PO_disabled_mobile_groups')))? get_option('PO_disabled_mobile_groups') : array()
	);
	?>
	var globalPlugins = <?php print json_encode($globalPluginLists); ?>;

	var toggleButtonOptions = [['Off','On'], ['No','Yes']];
	function PO_reverse_toggle_buttons() {
		toggleButtonOptions = [['On','Off'], ['Yes','No']];
	}
	
	jQuery(function() {
		jQuery('#PO-activate-pt-override').change(function() {
			PO_activate_pt_override();
		});

		jQuery('#PO-pt-override').change(function() {
			PO_deactivate_pt_override();
		});
		
		PO_set_expand_info_action();
		
		PO_attach_ui_handlers();

		jQuery('.outerPluginWrap.disabledPlugins').droppable({
			accept: '.pluginWrap, .groupWrap',
			drop: function(event, ui) {
				var newElement = ui.draggable.clone();
				if (newElement.hasClass('pluginWrap')) {
					if (jQuery(this).find(':hidden[value="'+newElement.find('.PO-plugin-id').val()+'"]').length == 0) {
						var targetType = '';
						if (jQuery(this).prop('id') == 'PO-disabled-mobile-plugin-wrap') {
							targetType = 'mobile';
							newElement.find('.PO-disabled-item-id').remove();
							newElement.append('<input type="hidden" class="PO-disabled-item-id PO-disabled-mobile-plugin-list" name="PO_disabled_mobile_plugin_list[]" value="'+newElement.find('.PO-plugin-id').val()+'" />');
							if (jQuery.inArray(newElement.find('.PO-plugin-id').val(), globalPlugins['mobile_plugins']) > -1) {
								newElement.addClass('globalPluginWrap');
							}
							PO_activate_indicator('plugin', 'Mobile', newElement.find('.PO-plugin-id').val());
						} else if (jQuery(this).prop('id') == 'PO-disabled-std-plugin-wrap') {
							targetType = 'std';
							newElement.find('.PO-disabled-item-id').remove();
							newElement.append('<input type="hidden" class="PO-disabled-item-id PO-disabled-std-plugin-list" name="PO_disabled_std_plugin_list[]" value="'+newElement.find('.PO-plugin-id').val()+'" />');
							if (jQuery.inArray(newElement.find('.PO-plugin-id').val(), globalPlugins['std_plugins']) > -1) {
								newElement.addClass('globalPluginWrap');
							}
							PO_activate_indicator('plugin', 'Standard', newElement.find('.PO-plugin-id').val());
						}
						var orderPosition = parseInt(jQuery(newElement).find('.PO-plugin-order').val(), 10);
						var itemAdded = 0;
						jQuery('#PO-disabled-'+targetType+'-plugin-wrap .PO-plugin-order').each(function() {
							if (parseInt(jQuery(this).val()) < orderPosition) {
								jQuery(this).closest('.pluginWrap').after(newElement);
								itemAdded = 1;
							}
						});

						if (itemAdded == 0) {
							jQuery(this).find('.pluginListSubHead.plugins').after(newElement);
						}
						PO_attach_ui_handlers();
					} else {
						var pluginWrapper = jQuery(this).find(':hidden[value="'+newElement.find('.PO-plugin-id').val()+'"]').closest('.pluginWrap');
						pluginWrapper.fadeOut(100);
						pluginWrapper.fadeIn(100);
						pluginWrapper.fadeOut(100);
						pluginWrapper.fadeIn(100);
					}
				} else if (newElement.hasClass('groupWrap')) {
					if (jQuery(this).find(':hidden[value="'+newElement.find('.PO-group-id').val()+'"]').length == 0) {
						var targetType = '';
						if (jQuery(this).prop('id') == 'PO-disabled-mobile-plugin-wrap') {
							targetType = 'mobile';
							newElement.find('.PO-disabled-item-id').remove();
							newElement.append('<input type="hidden" class="PO-disabled-item-id PO-disabled-mobile-group-list" name="PO_disabled_mobile_group_list[]" value="'+newElement.find('.PO-group-id').val()+'" />');
							if (jQuery.inArray(newElement.find('.PO-group-id').val(), globalPlugins['mobile_groups']) > -1) {
								newElement.addClass('globalGroupWrap');
							}
							PO_activate_indicator('group', 'Mobile', newElement.find('.PO-group-id').val());
						} else if (jQuery(this).prop('id') == 'PO-disabled-std-plugin-wrap') {
							targetType = 'std';
							newElement.find('.PO-disabled-item-id').remove();
							newElement.append('<input type="hidden" class="PO-disabled-item-id PO-disabled-std-group-list" name="PO_disabled_std_group_list[]" value="'+newElement.find('.PO-group-id').val()+'" />');
							if (jQuery.inArray(newElement.find('.PO-group-id').val(), globalPlugins['std_groups']) > -1) {
								newElement.addClass('globalGroupWrap');
							}
							PO_activate_indicator('group', 'Standard', newElement.find('.PO-group-id').val());
						}
						var orderPosition = parseInt(jQuery(newElement).find('.PO-group-order').val(), 10);
						var itemAdded = 0;
						jQuery('#PO-disabled-'+targetType+'-plugin-wrap .PO-group-order').each(function() {
							if (parseInt(jQuery(this).val()) < orderPosition) {
								jQuery(this).closest('.groupWrap').after(newElement);
								itemAdded = 1;
							}
						});

						if (itemAdded == 0) {
							jQuery(this).find('.pluginListSubHead.groups').after(newElement);
						}
						
						PO_attach_ui_handlers();
					} else {
						var pluginWrapper = jQuery(this).find(':hidden[value="'+newElement.find('.PO-group-id').val()+'"]').closest('.groupWrap');
						pluginWrapper.fadeOut(100);
						pluginWrapper.fadeIn(100);
						pluginWrapper.fadeOut(100);
						pluginWrapper.fadeIn(100);
					}
				}
			}
		});

		jQuery('#PO-all-plugin-wrap.outerPluginWrap').droppable({
			accept: '.pluginWrap, .groupWrap',
			drop: function(event, ui) {
				if (ui.draggable.closest('#PO-all-plugin-wrap').length < 1) {
					if (ui.draggable.find('.PO-disabled-item-id').hasClass('PO-disabled-mobile-plugin-list')) {
						PO_deactivate_indicator('plugin', 'Mobile', ui.draggable.find('.PO-plugin-id').val());
					} else if (ui.draggable.find('.PO-disabled-item-id').hasClass('PO-disabled-std-plugin-list')) {
						PO_deactivate_indicator('plugin', 'Standard', ui.draggable.find('.PO-plugin-id').val());
					} else if (ui.draggable.find('.PO-disabled-item-id').hasClass('PO-disabled-mobile-group-list')) {
						PO_deactivate_indicator('group', 'Mobile', ui.draggable.find('.PO-group-id').val());
					} else if (ui.draggable.find('.PO-disabled-item-id').hasClass('PO-disabled-std-group-list')) {
						PO_deactivate_indicator('group', 'Standard', ui.draggable.find('.PO-group-id').val());
					}
					
					ui.draggable.detach();
				}
				
			}
		});


		jQuery('#PO-ui-notices').dialog({
			dialogClass: 'PO-ui-dialog',
			closeText: 'X',
			autoOpen: false,
			resizable: false,
			height: "auto",
			width: (jQuery(window).width() > 400)?'400':jQuery(window).width()-20,
			modal: true,
			position: {within: '.PO-content-wrap'},
			buttons: {
				"Ok": function() {
					jQuery(this).dialog("close");
				}
			},
			open: function(event, ui) {
				jQuery('.ui-widget-overlay.ui-front').css('position', 'fixed');
				jQuery('.ui-widget-overlay.ui-front').css('left', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('right', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('top', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('bottom', '0px');
				jQuery('.ui-widget-overlay.ui-front').css('background', '#000');
				jQuery('.ui-widget-overlay.ui-front').css('opacity', '.5');
				jQuery('.ui-widget-overlay.ui-front').css('zIndex', '9998');
			}
		});


		jQuery('.outerPluginWrap.disabledPlugins .pluginListHead').click(function() {
			var disableToggle = jQuery(this).find('.disabledListToggle');
			if (disableToggle.hasClass('fa-plus-square-o')) {
				disableToggle.removeClass('fa-plus-square-o');
				disableToggle.addClass('fa-minus-square-o');
				jQuery(this).closest('.outerPluginWrap').find('.disabledList').slideDown(300);
			} else {
				disableToggle.removeClass('fa-minus-square-o');
				disableToggle.addClass('fa-plus-square-o');
				jQuery(this).closest('.outerPluginWrap').find('.disabledList').slideUp(300);
			}
		});

		jQuery('.move-all-button').tooltip({
			content: function() {
				return jQuery(this).attr('title').replace(/__nl__/g, '<br />');
			},
			tooltipClass: "PO-ui-button-tooltip"
		});

		jQuery('.outerPluginWrap.disabledPlugins .PO-plugin-id').each(function() {
			jQuery(this).closest('.pluginNameContainer').find('.PO-plugin-order').val(jQuery('#PO-all-plugin-wrap .PO-plugin-id[value="'+jQuery(this).val()+'"]').closest('.pluginNameContainer').find('.PO-plugin-order').val());
		});

		jQuery('.outerPluginWrap.disabledPlugins .PO-group-id').each(function() {
			jQuery(this).closest('.pluginNameContainer').find('.PO-group-order').val(jQuery('#PO-all-plugin-wrap .PO-group-id[value="'+jQuery(this).val()+'"]').closest('.pluginNameContainer').find('.PO-group-order').val());
		});

		jQuery('#PO-add-permalink').click(function() {
			PO_add_permalink();
		});
	});
	
	function PO_add_permalink() {
		jQuery('#PO-permalink-container').append('<div class="PO-permalink-wrapper"><input type="hidden" name="PO_pl_id[]" value="tmp_'+tmpObjectCount+'"><input type="text" class="PO-permalink-input" size="25" name="PO_permalink_filter_tmp_'+tmpObjectCount+'" value=""><input type="button" class="PO-delete-permalink" value="X"></div>');
		tmpObjectCount++;
		PO_attach_ui_handlers();
	}
	
	function PO_activate_indicator(itemType, itemPlatform, itemID) {
		jQuery('#PO-all-plugin-wrap .PO-'+itemType+'-id[value="'+itemID+'"]').closest('.'+itemType+'Wrap').addClass('disabled'+itemPlatform);
	}
	
	function PO_deactivate_indicator(itemType, itemPlatform, itemID) {
		jQuery('#PO-all-plugin-wrap .PO-'+itemType+'-id[value="'+itemID+'"]').closest('.'+itemType+'Wrap').removeClass('disabled'+itemPlatform);
	}
	
	function PO_display_ui_dialog(dialogTitle, dialogText) {
		jQuery('.PO-ui-dialog .ui-dialog-title').html(dialogTitle);
		jQuery('#PO-ajax-notices-container').html(dialogText);
		jQuery('#PO-ui-notices').dialog('open');
	}
	
	function PO_attach_ui_handlers() {
		jQuery('.PO-group-members').tooltip({
			content: function() {
				return jQuery(this).attr('title').replace(/__nl__/g, '<br />');
			},
			tooltipClass: "PO-ui-tooltip"
		});
		
		jQuery( ".pluginWrap, .groupWrap" ).draggable({
			cursor: 'move',
			cursorAt: { top: 10, left: 10 },
			helper:'clone',
			containment:'document',
			start: function( event, ui ) {
				ui.helper.addClass('PO-ui-draggable-dragging'); 
			}
		});

		jQuery('.outerPluginWrap .pluginWrap, .outerPluginWrap .groupWrap').off('click.pluginOrganizer');
		jQuery('.outerPluginWrap .pluginWrap, .outerPluginWrap .groupWrap').on('click.pluginOrganizer', function() {
			if (jQuery(this).hasClass('selected')) {
				jQuery(this).removeClass('selected');
			} else {
				jQuery(this).addClass('selected');
			}
		});

		jQuery('.PO-delete-permalink').off('click.pluginOrganizer');
		jQuery('.PO-delete-permalink').on('click.pluginOrganizer', function() {
			jQuery(this).closest('.PO-permalink-wrapper').remove();
		});
	}
	
	
	function PO_activate_pt_override() {
		jQuery('#PO-pt-override-msg-container').hide();
		jQuery('#PO-post-meta-box-wrapper').show();
		jQuery('#PO-pt-override').prop('checked', true);
	}

	function PO_deactivate_pt_override() {
		if (jQuery('#PO-activate-pt-override').prop('checked')) {
			jQuery('#PO-pt-override-msg-container').show();
			jQuery('#PO-post-meta-box-wrapper').hide();
			jQuery('#PO-pt-override').prop('checked', false);
			jQuery('#PO-activate-pt-override').prop('checked', false);
		}
	}
	
	function PO_set_expand_info_action() {
		jQuery('.expand-info-icon').each(function() {
			jQuery(this).unbind();
			var targetID = jQuery(this).prop('id').replace('PO-expand-info-', '');
			var infoContainer = jQuery('#PO-info-container-' + targetID);
			if (!jQuery(infoContainer).find('.PO-info-inner').html().match(/^\s*$/)) {
				jQuery(this).click(function() {
					if (jQuery(this).hasClass('fa-plus-square-o')) {
						jQuery(this).removeClass('fa-plus-square-o');
						jQuery(this).addClass('fa-minus-square-o');
						infoContainer.slideDown(300);
					} else {
						jQuery(this).removeClass('fa-minus-square-o');
						jQuery(this).addClass('fa-plus-square-o');
						infoContainer.slideUp(300);
					}
				});
			}
		});
	}
	
	function PO_toggle_loading(container) {
		jQuery(container+' .PO-loading-container').toggle();
		jQuery(container+' .inside').toggle();
	}
	
	function PO_add_all(sourceType, targetType, targetClass) {
		if (targetClass == '') {
			jQuery('#PO-disabled-'+targetType+'-plugin-wrap .'+sourceType+'Wrap').remove();
		}

		jQuery(jQuery('#PO-all-plugin-wrap .'+sourceType+'Wrap'+targetClass).get().reverse()).each(function() {
			var newElement = jQuery(this).clone();
			if (targetClass != '') {
				newElement.removeClass(targetClass.replace('.', ''));
			}
			if (jQuery('#PO-disabled-'+targetType+'-plugin-wrap').find(':hidden[value="'+newElement.find('.PO-'+sourceType+'-id').val()+'"]').length == 0) {
				newElement.append('<input type="hidden" class="PO-disabled-item-id PO-disabled-'+targetType+'-'+sourceType+'-list" name="PO_disabled_'+targetType+'_'+sourceType+'_list[]" value="'+newElement.find('.PO-'+sourceType+'-id').val()+'" />');
				if (jQuery.inArray(newElement.find('.PO-'+sourceType+'-id').val(), globalPlugins[targetType+'_'+sourceType+'s']) > -1) {
					newElement.addClass('global'+sourceType.charAt(0).toUpperCase()+sourceType.slice(1)+'Wrap');
				}
				if (targetClass != '') {
					var orderPosition = parseInt(jQuery(this).find('.PO-'+sourceType+'-order').val());
					var itemAdded = 0;
					jQuery('#PO-disabled-'+targetType+'-plugin-wrap .PO-'+sourceType+'-order').each(function() {
						if (parseInt(jQuery(this).val()) < orderPosition) {
							jQuery(this).closest('.'+sourceType+'Wrap').after(newElement);
							itemAdded = 1;
						}
					});

					if (itemAdded == 0) {
						jQuery('#PO-disabled-'+targetType+'-plugin-wrap').find('.pluginListSubHead.'+sourceType+'s').after(newElement);
					}
				} else {
					jQuery('#PO-disabled-'+targetType+'-plugin-wrap').find('.pluginListSubHead.'+sourceType+'s').after(newElement);
				}
				if (targetType == 'std') {
					PO_activate_indicator(sourceType, 'Standard', newElement.find('.PO-'+sourceType+'-id').val());
				} else {
					PO_activate_indicator(sourceType, 'Mobile', newElement.find('.PO-'+sourceType+'-id').val());
				}
			}
		});
		PO_attach_ui_handlers();
	}

	function PO_remove_all(sourceType, targetType, targetClass) {
		jQuery('#PO-disabled-'+targetType+'-plugin-wrap .'+sourceType+'Wrap'+targetClass).each(function() {
			if (targetType == 'std') {
				PO_deactivate_indicator(sourceType, 'Standard', jQuery(this).find('.PO-'+sourceType+'-id').val());
			} else {
				PO_deactivate_indicator(sourceType, 'Mobile', jQuery(this).find('.PO-'+sourceType+'-id').val());
			}
			jQuery(this).remove();
		});
	}

	function PO_toggle_button(checkboxID, buttonPrefix, optionIndex) {
		if (jQuery('#'+checkboxID).prop('checked') == false) {
			PO_set_button(jQuery('#'+checkboxID), 1, buttonPrefix, optionIndex);
		} else {
			PO_set_button(jQuery('#'+checkboxID), 0, buttonPrefix, optionIndex);
		}
	}
	
	function PO_set_button(checkbox, onOff, buttonPrefix, optionIndex) {
		if (onOff == 1) {
			jQuery(checkbox).prop('checked', true);
		} else {
			jQuery(checkbox).prop('checked', false);
		}
		jQuery(checkbox).parent().find("input[type='button']").removeClass();
		jQuery(checkbox).parent().find("input[type='button']").addClass(buttonPrefix+'toggle-button-'+toggleButtonOptions[optionIndex][onOff].toLowerCase());
		jQuery(checkbox).parent().find("input[type='button']").attr('value',toggleButtonOptions[optionIndex][onOff]);
	}
	
	function PO_reset_post_settings(postID) {
		jQuery.post(encodeURI(ajaxurl + '?action=PO_reset_post_settings'), { 'postID': postID, PO_nonce: '<?php print $this->PO->nonce; ?>' }, function (result) {
			if (result == '1') {
				PO_display_ui_dialog('Submission Result', 'The settings were successfully reset.');
				location.reload(true);
			} else if (result == '-1') {
				PO_display_ui_dialog('Submission Result', 'There were no settings found in the database.');
			} else {
				PO_display_ui_dialog('Submission Result', 'There was an issue removing the settings.');
			}
		});
	}

	function PO_submit_ajax(action, postVars, container, callback) {
		PO_toggle_loading(container);
		jQuery.post(encodeURI(ajaxurl + '?action='+action), postVars, function (result) {

			PO_toggle_loading(container);
			PO_display_ui_dialog('Submission Result', result);
			
			if (typeof(callback) == 'function') {
				callback();
			}
		});
	}
	
	<?php
	print "var regex = new Array();\n";
	foreach ($this->PO->regex as $key=>$val) {
		print "regex['$key'] = $val;\n";
	}
	?>
</script>