<?php
/**
 * This file contains the overall HTML structure of the "Admin Bar Editor" admin page
 * and loads other templates.
 *
 * These templates variables are provided by the plugin:
 *
 * @var string $pageSlug The admin bar editor page slug.
 * @var array $currentConfiguration Current toolbar configuration.
 * @var array $defaultConfiguration The default toolbar configuration.
 * @var string $imagesUrl A fully qualified URL of the "images" subdirectory.
 * @var int $maxImportFileSize Maximum size of a menu import file (in bytes).
 * @var string $settingsPageUrl
 * @var array $actors A list of all roles and other actor types (e.g. Super Admin).
 */
?>
<script type="text/javascript">
	AbeData = (typeof AbeData === 'undefined') ? {} : AbeData;
	AbeData.currentConfiguration = <?php echo json_encode($currentConfiguration); ?>;
	AbeData.defaultConfiguration = <?php echo json_encode($defaultConfiguration); ?>;
	AbeData.actors = <?php echo json_encode($actors); ?>;
</script>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2>
		<?php echo Abe_AdminBarEditor::PLUGIN_NAME; ?>
		<a href="<?php echo esc_attr($settingsPageUrl); ?>" class="add-new-h2"
		   title="Configure plugin settings">Settings</a>
	</h2>

	<div id="ws_admin_bar_editor">

		<div id="abe-actor-list-container">
			<ul id="abe-actor-list" class="subsubsub" data-bind="foreach: actors">
				<li>
					<a href="#"
						data-bind="
							text: name,
							click: $root.selectedActor,
							css: {
								'current' : selected
							}"
					></a>
				</li>
			</ul>
			<div class="clear"></div>
		</div>

		<div id="abe-editor-content" class="abe-panel">

			<div class="abe-toolbar">
				<a id="abe-create-item" data-bind="click: createItem" class="abe-toolbar-button" title="Create item">
					<img src="<?php echo $imagesUrl . 'page_white_add.png'; ?>">
				</a>

				<a id="abe-create-group" class="abe-toolbar-button" title="Create group"
				   data-bind="
								click: createGroup,
								css: {
									'abe-disabled-button' : !canCreateGroup()
								}
							">
					<div class="abe-button-image"></div>
				</a>

				<span class="abe-toolbar-spacer"></span>

				<a id="abe-cut-node" class="abe-toolbar-button" title="Cut"
				   data-bind="click: cutNode, css: {'abe-disabled-button': !selectedNode()}">
					<div class="abe-button-image"></div>
				</a>

				<a id="abe-copy-node" class="abe-toolbar-button" title="Copy"
				   data-bind="click: copyNode, css: {'abe-disabled-button': !selectedNode()}">
					<div class="abe-button-image"></div>
				</a>

				<a id="abe-paste-node" class="abe-toolbar-button" title="Paste"
				   data-bind="click: pasteNode, css: {'abe-disabled-button': !nodeInClipboard()}">
					<div class="abe-button-image"></div>
				</a>

				<span class="abe-toolbar-spacer"></span>

				<a id="abe-expand-all" data-bind="click: expandAll" class="abe-toolbar-button" title="Expand all">
					<img src="<?php echo $imagesUrl . 'chevron-expand-16.png'; ?>">
				</a>
				<a id="abe-collapse-all" data-bind="click: collapseAll" class="abe-toolbar-button" title="Collapse all">
					<img src="<?php echo $imagesUrl . 'chevron-collapse-16.png'; ?>">
				</a>

				<span class="abe-toolbar-spacer"></span>

				<a id="abe-delete-node" class="abe-toolbar-button" title="Delete node"
				   data-bind="
								click: deleteSelectedNode,
								css: {
									'abe-disabled-button' : ! (selectedNode() && selectedNode().is_custom())
								}
							">
					<div class="abe-button-image"></div>
				</a>
			</div>

			<ol
				data-bind="
					nestedSortable: {
						data      : nodes,
						template  : 'abe-node-template',
						isAllowed : isAllowedMove,
						afterMove : onNodeMoved,
						options   : {
							handle: '.abe-node-header',
							items : 'li',
							toleranceElement: '> div',
							tabSize: 30,
							placeholder: 'abe-sort-placeholder',
							errorClass: 'abe-sort-error'
						}
					}"
				class="abe-primary-list abe-children"
				id="abe-primary-node-list">
			</ol>

		</div><!-- /abe-editor-content -->

        <!-- Important editor buttons - save, export and so on -->
        <div id="abe-editor-menu" class="abe-panel">

            <?php
            $formActionUrl = admin_url(
                add_query_arg(
                    array(
                        'page' => $pageSlug,
                        'noheader' => 1,
                    ),
                    'options-general.php'
                )
            );
            ?>

            <form
                method="post"
                action="<?php echo esc_attr($formActionUrl); ?>"
                data-bind="submit: saveMenu"
                >

                <?php wp_nonce_field('save_menu'); ?>
                <input type="hidden" name="action" value="save_menu">
                <input name="nodes" type="hidden" id="admin-bar-node-list">

                <?php submit_button('Save Changes', 'primary', 'abe-save-menu', false); ?>
            </form>

            <?php
            submit_button(
                'Load defaults',
                'secondary',
                'abe-load-default-config',
                false,
                array('data-bind' => 'click: loadDefaultConfiguration')
            );

            submit_button(
                'Undo changes',
                'secondary',
                'abe-load-current-config',
                false,
                array('data-bind' => 'click: loadCurrentConfiguration')
            );
            ?>

            <!-- Export form -->
            <form
                action="<?php echo esc_attr($formActionUrl); ?>"
                method="post"
                target="abe-export-frame"
                data-bind="submit: exportMenu"
                >

                <?php wp_nonce_field('export_menu'); ?>
                <input type="hidden" name="action" value="export_menu">
                <input type="hidden" name="export_data" id="abe-export-data" value="">

                <?php submit_button('Export', 'secondary', 'abe-export-menu', false); ?>
            </form>
            <!--suppress HtmlUnknownTarget -->
            <iframe name="abe-export-frame" src="about:blank" style="display:none;"></iframe>

            <!-- Import button -->
            <?php
            submit_button(
                'Import',
                'secondary',
                'abe-import-nodes',
                false,
                array('data-bind' => 'click: importMenu')
            );
            ?>

        </div>

		<div class="clear"></div>

    </div>

	<!-- Import form -->
	<div id="abe-import-dialog" title="Import">
		<div id="abe-import-progress-notice">
			<img src="<?php echo $imagesUrl . '/spinner.gif'; ?>" alt="wait">
			Importing file...
		</div>

		<div id="abe-import-complete-notice">
			Import complete.
		</div>

		<form
			action="<?php echo esc_attr($formActionUrl); ?>"
			method="post"
			enctype="multipart/form-data"
			id="abe-import-form"
			class="abe-hide-while-importing">

			<?php wp_nonce_field('import_menu'); ?>
			<input type="hidden" name="action" value="import_menu">

			<label>
				Choose a JSON (.json) file to import: <br>
				<input type="file" name="import_file" accept=".json" id="abe-import-file">
			</label>

			<?php
			submit_button('Import file', 'primary', 'abe-upload-file-button', false);
			?>
		</form>
	</div>
</div>

<script type="text/html" id="abe-node-template">
	<?php require WS_ADMIN_BAR_EDITOR_DIR . '/templates/abe-node-template.php'; ?>
</script>