<div id="wrap">
    <div class="po-setting-icon fa fa-globe" id="icon-po-global"> <br /> </div>

    <h2 class="po-setting-title">Global Plugins</h2>
    <div style="clear: both;"></div>
	<p>Select the plugins you would like to disable site wide.  This will allow you to not load the plugin on any page unless it is specifically allowed in the post or page.</p>
	
	<div id="poststuff" class="metabox-holder">
      <div id="post-body">
        <div id="post-body-content">
	      <div class="PO-loading-container">
			<div>
				<img src="<?php print $this->PO->urlPath . "/image/ajax-loader.gif"; ?>">
			</div>
		  </div>
		  <div id="pluginListdiv" class="stuffbox inside" style="width: 98%">
              <?php
			  $ajaxSaveFunction = "PO_submit_global_plugins();";
			  require_once('postMetaBox.php');
			  ?>
		  </div>
	    </div>
      </div>
    </div>
</div>

