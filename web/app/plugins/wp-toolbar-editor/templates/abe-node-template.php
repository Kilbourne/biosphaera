<li class="abe-node">
	<div class="abe-node-widget"
		 data-bind="
		 	css: {
		 		'abe-settings-visible' : settingsVisible,
		 		'abe-selected'         : selected,
		 		'abe-hidden'           : !is_visible(),
		 		'abe-group'            : group,
		  		'abe-has-children'     : (children().length > 0)
		 	}">

		<div class="abe-node-header">
			<div class="abe-node-handle" data-bind="click: $root.selectedNode">
				<span class="abe-expand-button"
					  data-bind="
					  	click: toggleExpand,
					  	css: {'abe-expanded': expanded},
					  	attr: {
					  		title: expanded() ? 'Collapse node. Hold [Shift] to collapse the entire tree.' : 'Expand node. Hold [Shift] to expand the entire tree.'
					  	}
					  ">
				</span>

				<label data-bind="stopBubble: true" class="abe-node-visibility" title="Show/hide node">
					<input type="checkbox" data-bind="checked: is_visible">
				</label>

				<span class="abe-node-title">
					<span data-bind="text: safeTitle"></span>
				</span>

				<div class="abe-node-controls">
					<!-- ko if: group -->
						<span class="abe-node-type">Group</span>
					<!-- /ko -->

					<span class="abe-flag abe-flag-custom" data-bind="visible: is_custom"
						  title="User-created item"></span>

					<div class="abe-node-edit" data-bind="click: toggleSettings" title="Edit node"></div>
				</div>
			</div>
		</div>

		<div class="abe-node-settings" data-bind="visible: settingsVisible">

			<div class="abe-field abe-field-id"
				 data-field-name="id">

				<div class="abe-field-value-wrap">
					<label>
						<span class="abe-field-title">ID</span>
						<a class="abe-tooltip-trigger" title="
							Each item must have a unique ID.
							Allowed characters: a-z, numbers, dashes and underscores.
							<br>You can't change the ID of default items."></a>
						<input
							type="text"
							class="abe-field-value"
							data-bind="value: effectiveId, attr: { readonly: is_custom() ? null : 'readonly' }"
							>
					</label>
				</div>
			</div>

			<?php
			$fields = array(
				'title' => array(
					//'tooltip' => 'Menu item title. All HTML tags are allowed.',
				),
				'href' => array(
					'title' => 'URL',
					'tooltip' => "Enter a fully-qualified URL, or leave empty to create an item that doesn't link anywhere.",
				),
				'class' => array(
					'applies_to_groups' => true,
					//'tooltip' => 'A space-separated list of CSS classes.',
				),
				'titleAttr' => array(
					'title' => 'Title attribute',
					'tooltip' => 'Most web browsers will display the title attribute as a tooltip when you mouse over the menu item. HTML tags are not allowed here.',
				),
				'target' => array(
					'title' => 'Target attribute',
					'tooltip' => 'You can set this field to &quot;_blank&quot; to make the menu link open in a new tab or window. Leave it empty to open the link in the same tab.',
				),
				'html' => array(
					'title' => 'HTML content',
					'tooltip' => 'Additional HTML content that will be displayed below the menu title. Usually it is best to leave this field blank.',
				),
				'tabindex' => array(
					'title' => 'Tabindex attribute',
					'tooltip' => 'The <code>tabindex</code> defines the sequence that users will follow when they use the Tab key to navigate through the Toolbar menu. Leave this field empty to use the default tabbing order.',
				),
				'onclick' => array(
					'title' => 'Onclick attribute',
					//'tooltip' => 'JavaScript code that will be executed when a user clicks this item.',
				),
			);

			$prevAppliesToGroups = true;
			foreach($fields as $name => $settings) :
				$appliesToGroups = isset($settings['applies_to_groups']) ? $settings['applies_to_groups'] : false;
				$title = isset($settings['title']) ? $settings['title'] : ucwords($name);
				$tooltip = isset($settings['tooltip']) ? $settings['tooltip'] : null;

				$observableName = 'effective' . ucfirst($name);

				//Put fields that don't apply to groups inside containerless "if" bindings.
				//This way they won't be displayed for group nodes.
				if ( $appliesToGroups !== $prevAppliesToGroups ) {
					if ( !$appliesToGroups ) {
						echo '<!-- ko if: !group() -->';
					} else {
						echo '<!-- /ko -->';
					}
					$prevAppliesToGroups = $appliesToGroups;
				}

				?>
				<div
					class="abe-field abe-field-<?php echo $name; ?>"
					data-field-name="<?php echo $name; ?>"
					>

					<div class="abe-field-value-wrap">
						<label>
							<span class="abe-field-title"><?php echo $title; ?></span>

							<?php
							if ( !empty($tooltip) ) {
								printf('<a class="abe-tooltip-trigger" title="%s"></a>', $tooltip);
							}
							?>

							<input
								class="abe-field-value"
								type="text"
								data-bind="
									value: <?php echo $observableName; ?>,
									css: {
										'abe-default-value': isDefault('<?php echo $name; ?>')
									}"
								>
						</label>
					</div>

					<div class="abe-reset-button"
						 data-bind="click: resetToDefault, visible: canBeReset('<?php echo $name; ?>')"
						 data-field-name="<?php echo $name; ?>"
						 title="Reset to default value">
					</div>
				</div>
			<?php
			endforeach;

			//Close any open "if" bindings. There will only be one
			//if the last field didn't apply to groups.
			if ( !$prevAppliesToGroups ) {
				echo '<!-- /ko -->';
			}
			?>

		</div>
	</div>

	<ol
		class="abe-children"
		data-bind="nestedSortable: children, visible: expanded"></ol>
</li>