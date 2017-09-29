=== WP Edit ===
Contributors: josh401
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=A9E5VNRBMVBCS
Tags: wpedit, wp edit, editor, buttons, button, add, font, font style, font select, table, tables, visual editor, search, replace, colors, color, anchor, advance, advanced, links, link, popup, javascript, upgrade, update, admin, image, images, citations, preview, html, custom css, borders, pages, posts, colorful, php, php widget, shortcode, shortcodes, style, styles, plugin, login, excerpt, id, post, page, youtube, tinymce
Requires at least: 3.9
Tested up to: 4.7
Stable tag: 4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Take complete control over the WordPress content editor.

== Description ==

= NEW Custom Buttons API =
WP Edit now uses a custom buttons API which allows other plugin/theme developers to add their editor buttons into the WP Edit button configuration; allowing a WP Edit user to place the plugin/theme buttons into any desired location.

Refer your favorite plugin/theme developers to the [WP Edit Custom Buttons API](http://learn.wpeditpro.com/custom-buttons-api/) documentation to get your favorite buttons added to WP Edit.

= Introduction =
For a riveting video introduction into the possibilities available with WP Edit; please visit [Jupiter Jim's Marketing Team](http://jupiterjim.club/wordpress/tutorials/change-font-family-font-size-wordpress-4-4-1/).

= Description =

WP Edit is built around three years of custom WordPress development. WP Edit adds extensive, additional editing functionality to the default WordPress editor. Begin creating content like the pros; without knowing a single bit of HTML or CSS.

[Subscribe to our Feedblitz List](http://www.feedblitz.com/f/?Sub=950320), and receive news, update notices and more.
[<img title="Subscribe to get updates by email and more!" border="0" src="http://assets.feedblitz.com/chicklets/email/i/25/950320.bmp">](http://www.feedblitz.com/f/?Sub=950320)

= Most Powerful Features =
WP Edit will provide new buttons, additional options, and extended formatting abilities to the exisiting content editor.

* Easily insert images, media, YouTube videos, and clip art.
* Create tables via a graphical interface.
* Adjust table cell border and background colors.
* No need to learn HTML and CSS (although the basics can certainly help); use buttons with visual interfaces instead!
* Easily access all shortcodes available to your WordPress environment; and insert them into the content editor.
* Use shortcodes to insert columns.. similar to "magazine" style layouts, in your content areas.

= Why should you use this plugin? =
Because WP Edit is the culmination of three years development in the WordPress content editor. You can begin creating content (with advanced layouts); easily insert all types of external media (YouTube, Vimeo, etc.); adjust fonts, styles, colors, and sizes; and much more!

= What is included in the free version? =
* Drag and drop functionality for custom creation of the top row of editor buttons.
* Adds additional editor buttons such as subscript, superscript, insert media, emoticons, search and replace, html editor, preview.. and many more.
* Add your custom editor to excerpt areas and profile descriptions.
* Allow shortcodes in excerpt and widget areas.
* Highlight admin posts/pages based on status (green = published, yellow = draft, etc.)
* Easily import/export plugin options.

= Why should you upgrade to WP Edit Pro? =
* Drag and drop functionality for custom creation of all rows of editor buttons.
* Powerful network installation functionality; WP Network Ready.
* User roles for custom button arrangements; allow different user roles access to different editor buttons.
* Extreme Custom Widget Builder - create custom widgets just like posts or pages.. and insert them into any widget area or the content editor.

= Translations =
* Spanish - Provided by Andrew Kurtis with ["WebHostingHub"](http://www.webhostinghub.com).

= Notes =
* This plugin is provided "as-is"; within the scope of WordPress.  We will update this plugin to remain secure, and to follow WP coding standards.
* If you prefer more "dedicated" support, with more advanced and powerful plugin features, please consider upgrading to ["WP Edit Pro"](http://wpeditpro.com). 

= Resources =
* ["Complete Guide to WP Edit Buttons"](http://learn.wpeditpro.com/wp-edit-buttons-guide/)


== Installation ==

* From the WP admin panel, click "Plugins" -> "Add new".
* In the browser input box, type "WP Edit".
* Select the "WP Edit" plugin (authored by "josh401"), and click "Install".
* Activate the plugin.

OR...

* Download the plugin from this page.
* Save the .zip file to a location on your computer.
* Open the WP admin panel, and click "Plugins" -> "Add new".
* Click "upload".. then browse to the .zip file downloaded from this page.
* Click "Install".. and then "Activate plugin".

OR...

* Download the plugin from this page.
* Extract the .zip file to a location on your computer.
* Use either FTP or your hosts cPanel to gain access to your website file directories.
* Browse to the `wp-content/plugins` directory.
* Upload the extracted `wp_edit` folder to this directory location.
* Open the WP admin panel.. click the "Plugins" page.. and click "Activate" under the newly added "WP Edit" plugin.



== Frequently asked questions ==

* Nothing at the moment.

== Screenshots ==

1. Create custom button arrangements from a friendly drag and drop interface.
2. The custom button arrangement will be loaded in the content editor.
3. Eight tabs packed with options.

== Changelog ==

= 4.0 =
* 10/03/2016
* Added Custom Buttons API; other plugins/themes can add buttons to WP Edit.
* Added dismissable admin notice for Custom Buttons API (help spread the word!).
* Added plugin rating statistics to sidebar (Please rate and review).
* Added nonce fields for every form submission used to save database options.
* Moved plugins.php page styles to properly enqueue (used for notices).

= 3.9 =
* 09/05/2016
* Added functionality to enable visual editor on BBPress forums (Editor tab).
* Fixed strict standards error on wp_widget_rss_output() function (final fix will be done upstream when WordPress 4.7 is released).
* Adjusted plugin css file.

= 3.8.1 =
* 05/11/2016
* Removed a stray var_dump() function.

= 3.8 =
* 05/11/2016
* Added support for WP Edit toolbars in custom post types excerpt areas.
* Fixed deprecated function.  (htmledit_pre changed to format_for_editor) (main.php ~line 115).
* Updated introduction video link.
* Minor changes to ensure WordPress 4.6 compatibility.
* Increased stable tag version.

= 3.7 =
* 01/11/2016

* Fixed Feedblitz image loading insecure over https.
* Fixed WP_PLUGIN_URL constant; switched to using plugins_url() function.
* Fixed profile biography editor.
* Updated compatibility version.

= 3.6 = 
* 12/16/2015
* Update to be stable with WordPress 4.4.

= 3.5 =
* 10/02/2015
* Updated plugin translation code to handle new WordPress language packs.
* Fixed call to jquery theme only loading over http.  Will now load over https when applicable.

= 3.4 =
* 08/21/2015
* Removed PHP Widget option for two reasons:
* 1 - It presented a potential security risk.  It is recommended to use a plugin dedicated to this functionality.
* 2 - It used the older PHP construct method.  I could have updated the class; but I think it's better to remove the option entirely.

= 3.3 =
* 08/10/2015
* Minor code enhancements to prepare for the WordPress 4.3 release.

= 3.2 =

* 06/12/2015
* Fixed issue with OptimizePress theme.
* Added "title" attribute to "Insert/Edit Advanced Link" button.
* Made minor changes to plugin update message and styling.

= 3.1 =

* 02/25/2015
* Checked for compatibility with latest WP development version.
* Added WP Pointer to content editor informing of "Toolbar Toggle" button to expand/collapse additional editor button rows.
* Added NonBreaking Space button to editor (remember to "Save Buttons" to get new buttons to appear).
* Removed Editor Font Size (Editor tab) option... and replaced with a better radio selection for choosing Font Size Type in the Font Size dropdown menu.
* Added input field for defining available font-size values in the Font Size dropdown menu.
* Added CodeCogs Equation Editor button and editor functionality.

= 3.0.1 =

* 01/05/2015
* Fixed security risk with Post/Page Title option.

= 3.0 =

* 12/28/2014
* Many code optimizations.
* New drag and drop interface code (bug fixes).
* New admin styling.
* Buttons can now be "multi-selected"; and moved as a group.
* Updated the "disable wpautop" functionality.
* Added "Column Shortcodes" button for easily inserting column shortcodes.
* Added "Abbreviation" button for inserting abbreviations into content area.
* Added fancy tooltips to buttons (adds additional button info on hover).
* Added functions to check if new buttons have been added; or old buttons have been removed (click "Save Buttons" to check).

= 2.4 =

* 10/11/2014
* Added Row 2 to the drag and drop panel.
* Fixed "featured image" not being displayed properly on custom post types (such as woocommerce).

= 2.3 =

* 09/04/2014
* Updated "Table" and "Colorpicker" buttons to TinyMCE 4.1.4 versions.
* Removed "Save Editor Scrollbar" option (not needed after WP 4.0).
* Fixed "foreach" error in php log.


= 2.2 =

* 08/02/2014
* Reverted table plugin back to old version; until WordPress 4.0 is released.  The new table plugin will only work with the updated TinyMCE (which will be included in WordPress 4.0).  Please see [This Topic](http://wordpress.org/support/topic/table-properties-not-working) for more information.  I will release another update right after WordPress updates to 4.0.

= 2.1 =

* 07/30/2014
* Adjusted Predefined Styles and added "style_formats_merge" argument.  This will prevent overwriting other plugin/theme styles, if present.
* Updated Table plugin (provided my MoxieCode) to version 4.1.3.  This version includes background and border color selections for the entire table; table rows; and table cells.

= 2.0 =

* 06/27/2014
* Fixed function naming conflict with Custom Styles.
* Fixed Predefined Styles filter argument (may have clashed with other plugins adding custom style formats).
* Added button to check for new buttons added to the drag and drop interface.
* Added Accessibility Checker plugin button.
* Added Spanish translation - Provided by Andrew Kurtis with ["WebHostingHub"](http://www.webhostinghub.com).

= 1.9 =

* 06/01/2014
* Added background color and border color to "table" button (table cell properties).
* Fixed RSS widget feed.
* Removed WP Edit metabox from "Edit Post" admin pages.
* Added assortment of Predefined Styles (more styles coming soon in the Pro version) under the Editor tab.

= 1.8 =

* 05/03/2014
* Added font 'px' ability (Editor tab).
* Updated TinyMCE core "table" button (supports vertical align on table cells).
* Fixed loading path of admin page .js and .css files, props ["@Fencer04"](http://wordpress.org/support/profile/fencer04).

= 1.7 =

* 04/23/2014
* Introducing the only available Advanced Link button!
* I have coded this from scratch.
* It does not resemble the old Tinymce3 Advanced Link button; but it does have some MUCH requested functionality.
* I decided to code this immediately, when I saw the reaction to the original button being removed.
* Please report any bugs or features requests (or to say, "Hey, thanks!") ["Here"](http://wordpress.org/support/plugin/wp-edit).
* A complete description and tutorial of the button can be found on our ["Knowledge Base"](http://learn.wpeditpro.com/insertedit-advanced-link-button/).

= 1.6 =

* 04/20/2014
* Fixed signoff shortcode adding slashes when saving to database.

= 1.5 =

* 04/18/2014
* Fixed image button.

= 1.4 =

* 04/17/2014
* Fixed post revisions and page revisions input fields.
* Added additional font sizes back into font size dropdown selection box.

= 1.3 =

* 04/17/2014
* Fixed links pointing to old site.

= 1.2 =

* 04/14/2014
* Change naming syntax to prevent addon errors.

= 1.1 =

* 04/10/2014
* Fixed styling on different screen resolutions.
* Updated readme.txt file.

= 1.0 =

* 03/23/2014
* Plugin created

== Upgrade Notice ==

Nothing at the moment.