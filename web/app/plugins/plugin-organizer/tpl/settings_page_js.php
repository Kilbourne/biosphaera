<?php
if ( current_user_can( 'activate_plugins' ) ) {
	?>
	<script type="text/javascript" language="javascript">
		jQuery(function() {
			jQuery('#PO-custom-post-type-container .PO-post-type-container').sortable();
			jQuery('#PO-tab-menu li').click(function() {
				PO_show_tab(this);
			});


			jQuery('.PO-colorpicker').each(function() {
				jQuery(this).parent().find('.PO-color-preview').css('backgroundColor', jQuery(this).val());
				
				jQuery(this).parent().find('.PO-color-preview').ColorPicker({
					onSubmit: function(hsb, hex, rgb, el) {
						jQuery(el).css('backgroundColor', 'rgb('+rgb.r+', '+rgb.g+', '+rgb.b+')');
						jQuery(el).parent().find('.PO-colorpicker').val('#'+hex);
						jQuery(el).ColorPickerHide();
					},
					onBeforeShow: function () {
						jQuery(this).ColorPickerSetColor(jQuery(this).parent().find('.PO-colorpicker').val());
					}
				});
					
				jQuery(this).ColorPicker({
					onSubmit: function(hsb, hex, rgb, el) {
						jQuery(el).parent().find('.PO-color-preview').css('backgroundColor', 'rgb('+rgb.r+', '+rgb.g+', '+rgb.b+')');
						jQuery(el).val('#'+hex);
						jQuery(el).ColorPickerHide();
					},
					onBeforeShow: function () {
						jQuery(this).ColorPickerSetColor(jQuery(this).val());
					}
				});
			});
		});
		
		function PO_show_tab(tab) {
			jQuery('#PO-tab-menu li').removeClass('active');
			jQuery(tab).addClass('active');
			jQuery('#PO-tab-content .PO-tab-content').removeClass('active');
			jQuery('#'+jQuery(tab).prop('id')+'-content').addClass('active');

		}
		
		function PO_submit_mobile_user_agents() {
			var mobileUserAgents = jQuery('#PO-mobile-user-agents').val();
			var postVars = { 'PO_mobile_user_agents': mobileUserAgents, PO_nonce: '<?php print $this->PO->nonce; ?>' };
			PO_submit_ajax('PO_submit_mobile_user_agents', postVars, '#PO-browser-string-div', function(){});
		}
	
		function PO_submit_gen_settings() {
			var disable_plugins = 0;
			var disable_mobile_plugins = 0;
			var admin_disable_plugins = 0;
			if (jQuery('#PO-disable-plugins').prop('checked')) {
				disable_plugins = 1;
			}

			if (jQuery('#PO-disable-mobile-plugins').prop('checked')) {
				disable_mobile_plugins = 1;
			}

			if (jQuery('#PO-admin-disable-plugins').prop('checked')) {
				admin_disable_plugins = 1;
			}

			
			//Fuzzy URL
			var fuzzy_url_matching = 0;
			if (jQuery('#PO-fuzzy-url-matching').prop('checked')) {
				fuzzy_url_matching = 1;
			}
			
			
			//Ignore Protocol
			var ignore_protocol = 0;
			if (jQuery('#PO-ignore-protocol').prop('checked')) {
				ignore_protocol = 1;
			}
			
			//Ignore Arguments
			var ignore_arguments = 0;
			if (jQuery('#PO-ignore-arguments').prop('checked')) {
				ignore_arguments = 1;
			}


			//Supported Post Types
			var PO_cutom_post_type = new Array();
			jQuery('.PO-cutom-post-type').each(function() {
				if (this.checked) {
					PO_cutom_post_type[PO_cutom_post_type.length] = this.value;
				}
			});

			//Network Admin Access
			var order_access_net_admin = 0;
			if (jQuery('#PO-order-access-net-admin').prop('checked')) {
				order_access_net_admin = 1;
			}

			//Auto Trailing Slash
			var auto_trailing_slash = 0;
			if (jQuery('#PO-auto-trailing-slash').prop('checked')) {
				auto_trailing_slash = 1;
			}

			var postVars = {
				'PO_disable_plugins': disable_plugins,
				'PO_disable_mobile_plugins': disable_mobile_plugins,
				'PO_admin_disable_plugins': admin_disable_plugins,
				'PO_fuzzy_url_matching': fuzzy_url_matching,
				'PO_ignore_protocol': ignore_protocol,
				'PO_ignore_arguments': ignore_arguments,
				'PO_cutom_post_type[]': PO_cutom_post_type,
				'PO_order_access_net_admin': order_access_net_admin,
				'PO_auto_trailing_slash': auto_trailing_slash,
				'PO_nonce': '<?php print $this->PO->nonce; ?>'
			};
			
			PO_submit_ajax('PO_submit_gen_settings', postVars, '#PO-gen-settings-div', PO_reorder_post_types);
		}
		
		function PO_submit_redo_permalinks() {
			var old_site_address = jQuery('#PO-old-site-address').val();
			var new_site_address = jQuery('#PO-new-site-address').val();
			var postVars = { PO_nonce: '<?php print $this->PO->nonce; ?>', 'old_site_address': old_site_address, 'new_site_address': new_site_address };
			PO_submit_ajax('PO_redo_permalinks', postVars, '#PO-redo-permalinks-div', function(){});
		}

		function PO_manage_mu_plugin_file(selected_action) {
			if (selected_action != '') {
				var postVars = { 'selected_action': selected_action, PO_nonce: '<?php print $this->PO->nonce; ?>' };
				PO_submit_ajax('PO_manage_mu_plugin', postVars, '#PO-manage-mu-div', function(){});
			}
		}

		function PO_submit_admin_css_settings() {
			var postVars = {
				'PO_network_plugins_bg_color': jQuery('#PO-network-plugins-bg-color').val(),
				'PO_network_plugins_font_color': jQuery('#PO-network-plugins-font-color').val(),
				'PO_active_plugins_bg_color': jQuery('#PO-active-plugins-bg-color').val(),
				'PO_active_plugins_font_color': jQuery('#PO-active-plugins-font-color').val(),
				'PO_active_plugins_border_color': jQuery('#PO-active-plugins-border-color').val(),
				'PO_inactive_plugins_bg_color': jQuery('#PO-inactive-plugins-bg-color').val(),
				'PO_inactive_plugins_font_color': jQuery('#PO-inactive-plugins-font-color').val(),
				'PO_inactive_plugins_border_color': jQuery('#PO-inactive-plugins-border-color').val(),
				'PO_global_plugins_bg_color': jQuery('#PO-global-plugins-bg-color').val(),
				'PO_global_plugins_font_color': jQuery('#PO-global-plugins-font-color').val(),
				'PO_global_plugins_border_color': jQuery('#PO-global-plugins-border-color').val(),
				'PO_plugin_groups_bg_color': jQuery('#PO-plugin-groups-bg-color').val(),
				'PO_plugin_groups_font_color': jQuery('#PO-plugin-groups-font-color').val(),
				'PO_plugin_groups_border_color': jQuery('#PO-plugin-groups-border-color').val(),
				'PO_on_btn_bg_color': jQuery('#PO-on-btn-bg-color').val(),
				'PO_on_btn_font_color': jQuery('#PO-on-btn-font-color').val(),
				'PO_off_btn_bg_color': jQuery('#PO-off-btn-bg-color').val(),
				'PO_off_btn_font_color': jQuery('#PO-off-btn-font-color').val(),
				'PO_yes_btn_bg_color': jQuery('#PO-yes-btn-bg-color').val(),
				'PO_yes_btn_font_color': jQuery('#PO-yes-btn-font-color').val(),
				'PO_no_btn_bg_color': jQuery('#PO-no-btn-bg-color').val(),
				'PO_no_btn_font_color': jQuery('#PO-no-btn-font-color').val(),
				'PO_nonce': '<?php print $this->PO->nonce; ?>'
			};
			PO_submit_ajax('PO_submit_admin_css_settings', postVars, '#PO-manage-css-div', function(){});
		}

		function PO_reorder_post_types() {
			jQuery(jQuery('#PO-custom-post-type-container .PO-post-type-container .PO-post-type-row').get().reverse()).each(function() {
				if (jQuery(this).find('.PO-cutom-post-type').is(':checked')) {
					var clonedRow = jQuery(this).clone();
					jQuery(this).remove();
					jQuery('#PO-custom-post-type-container .PO-post-type-container').prepend(clonedRow);
				}

			});
		}

	</script>
	<?php
}
?>