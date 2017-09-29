<div id="wrap">
    <div class="po-setting-icon fa fa-asterisk" id="icon-po-pt"> <br /> </div>

    <h2 class="po-setting-title">Post Type Plugins</h2>
    
	<div style="clear: both;"></div>
	<p>Select the plugins you would like to disable/enable on the selected post type.
	  <a href="#" onclick="PO_display_ui_dialog('Post Type Plugins', 'This will overwrite any settings you have applied to any posts matching this post type.  The settings for individual posts can not be restored once this is done.  You can override these settings on each individual post by checking a checkbox.');return false;">
	    <span class="dashicons PO-dashicon dashicons-editor-help"></span>
	  </a>
	</p>
	<div id="PO-progress-message">
	</div>
	<div id="PO-pt-settings" class="metabox-holder">
      <div class="PO-loading-container">
		<div>
			<img src="<?php print $this->PO->urlPath . "/image/ajax-loader.gif"; ?>">
		</div>
	  </div>
	  <div id="pluginListdiv" class="stuffbox inside" style="width: 98%">
		<?php
	    $ajaxSaveFunction = "PO_submit_pt_plugins(0, 0);";
	    require_once('postMetaBox.php');
	    ?>
	  </div>
    </div>
</div>

