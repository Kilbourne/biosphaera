<?php
$groups = get_posts(array('post_type'=>'plugin_group', 'posts_per_page'=>-1));
?>

<select id="PO-plugin-action" name="action" onchange="PO_action_change()">
	<option value="save_load_order">Save Load Order</option>
	<option value="reset_to_default_order">Reset To Default Order</option>
	<option value="create_new_group">Create New Group</option>
	<option value="delete_plugin_group">Delete Group</option>
	<option value="remove_plugins_from_group">Remove From Group</option>
	<option value="add_to_plugin_group">Add To Group</option>
	<option value="edit_plugin_group_name">Edit Group Name</option>
</select><br />
<div id="PO-groups-container">
	<div id="PO-group-select">
		Existing Group Name: <select id="PO-group-name" name="plugin_groups" multiple="multiple">
			<option value="" disabled>-- Select Group --</option>
			<?php foreach ($groups as $group) { ?>
				<option value="<?php print $group->ID; ?>"><?php print preg_replace("/'/", "\'", $group->post_title); ?></option>
			<?php } ?>
		</select>
	</div>
	<div id="PO-new-group-name-container">
		New Group Name: <input type="text" name="PO_new_group_name" id="PO-new-group-name" />
	</div>
</div>

<div id="PO-plugin-wrapper" class="PO-content-wrap">
	<div class="PO-loading-container">
		<div>
			<img src="<?php print $this->PO->urlPath . "/image/ajax-loader.gif"; ?>">
		</div>
	</div>
	<div class="inside">
		<div class="table-header">
			<div class="form-header">
				<input type="button" onmousedown="PO_submit_plugin_action()" value="Submit" />
			</div>
			<div class="info-header">
				Plugin Info
			</div>
			<div class="groups-header">
				Plugin Groups
			</div>
			<div style="clear: both;"></div>
		</div>
		<?php
		$count=0;
		$plugins = array(
			'network-active'=>array(),
			'active'=>array(),
			'inactive'=>array()
		);
		foreach ($this->PO->reorder_plugins(get_plugins()) as $pluginPath=>$plugin) {
			if (is_plugin_active_for_network($pluginPath)) {
				$plugins['network-active'][$pluginPath] = $plugin;
			} else if (is_plugin_active($pluginPath)) {
				$plugins['active'][$pluginPath] = $plugin;
			} else {
				$plugins['inactive'][$pluginPath] = $plugin;
			}
		}
		
		foreach($plugins as $pluginStatus=>$pluginList) {
			?>
			<div id="PO-<?php print $pluginStatus; ?>-plugins">
			<?php
			foreach ($pluginList as $pluginPath=>$plugin) {
				$pluginSelector = preg_replace('/[^A-Za-z0-9\-]/', '', preg_replace('/\s/', '-', $plugin['Name']));
				?>
				<div class="plugin <?php print $pluginStatus; ?>">
					<div id="PO-plugin-form-<?php print $pluginSelector; ?>" class="plugin-form">
						<input class="PO-plugin-action-checkbox" type="checkbox" name="plugins[]" value="<?php print $pluginPath; ?>" />
					</div>
					<div class="plugin-info">
						<input type="hidden" name="start_order[]" class="start-order" value="<?php print $count; ?>" />
						<?php print $plugin['Name']; ?>
					</div>
					<div id="<?php print $pluginSelector; ?>-plugin-groups" class="plugin-groups">
						<div id="PO-expand-info-<?php print $pluginSelector; ?>" class="dashicons PO-dashicon fa fa-plus-square-o expand-info-icon"></div>
						<div id="PO-info-container-<?php print $pluginSelector; ?>" class="PO-info-container">
							<div class="PO-info-inner">
								<?php
								$groups = get_posts(array('post_type'=>'plugin_group', 'posts_per_page'=>-1));
								$assignedGroups = "";
								foreach ($groups as $group) {
									$members = get_post_meta($group->ID, '_PO_group_members', $single=true);
									$members = stripslashes_deep($members);
									if (is_array($members) && array_search($pluginPath, $members) !== FALSE) {
										$assignedGroups .= '<a href="'.get_admin_url().'plugins.php?PO_group_view='.$group->ID.'">'.$group->post_title.'</a><br /><hr>';
									}
								}
								print rtrim($assignedGroups, ',');
								?>
							</div>
						</div>
					</div>
					<div style="clear: both;"></div>
				</div>
				<?php
				$count++;
			}
			?>
			</div>
			<?php
		}
		?>
	</div>
</div>