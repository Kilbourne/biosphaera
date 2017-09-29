=== tinyWYM Editor ===
Contributors: arickards
Tags: tinyMCE, WYSIWYM, WYSIWYG, WP Editor, visual editor, editor, tinywym
Requires at least: 4.2.0
Tested up to: 4.7.2
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Convert WordPress's WYSIWYG editor into a WYSIWYM editor. Add and edit any HTML tag and attribute from the visual editor.

== Description ==

tinyWYM Editor was created to help inexperienced WordPress users create cleaner, more semantic markup, and to avoid some of the pitfalls of WordPress's standard WYSIWYG editor. It does this by labelling and highlighting all HTML elements in the editor, creating a visual representation of the HTML being generated.

tinyWYM Editor also gives more experience users all the control and flexibility of the text editor without having to leave the visual editor. Create and edit any HTML element, add attributes, and wrap or unwrap elements all from the visual editor.

See the [Screenshots](https://wordpress.org/plugins/tinywym-editor/screenshots/ "Screenshots") and [FAQ](https://wordpress.org/plugins/tinywym-editor/faq/ "Frequently Asked Questions") sections for details on how to use tinyWYM Editor.

== Installation ==

There are two options for installing and setting up this plugin.

= Upload Manually =

1. Download and unzip the plugin
2. Upload the 'tinyWYM-editor' folder into the '/wp-content/plugins/' directory
3. Go to the Plugins admin page and activate the plugin

= Install Via Admin Area =

1. In the admin area go to Plugins > Add New and search for "tinyWYM Editor"
2. Click install and then click activate

== Frequently Asked Questions ==

= How do I create an HTML element from a selection? =

To create a new element from a selection first select some text. tinyWYM Editor plugin adds a new button to the toolbar. Click it and a dialogue will appear with a field for your new tag and any attributes you want to give it. Enter your new HTML tag and any desired attributes, then click the 'Okay' button. (You can also hit Ctrl+T instead of clicking the button to open the dialogue.)

= How do I wrap one HTML element in another HTML element? =

To wrap an element in another element, place the caret inside the element that you want to wrap. tinyWYM Editor plugin adds a new button to the toolbar. Click it and a dialogue will appear with a field for your new tag and any attributes you want to give it. Enter your new HTML tag and any desired attributes, then click the 'Okay' button. (You can also hit Ctrl+T instead of clicking the button to open the dialogue.)

= How do I edit or add attributes to an HTML element? =

Click any element while holding the Alt key and a dialogue will appear where you can edit the current HTML tag and any attributes it might have or you want to give it. Enter your new HTML tag and any desired attributes, then click the 'Okay' button.

= How do I unwrap an HTML element from its parent element or remove text from its containing element? =

Click any element while holding the Shift+Alt and that element will be removed from the markup preserving any inner text or child elements.

= Why does tinyWYM Editor remove my theme's editor styles? =

tinyWYM Editor removes the current theme's editor stylesheet by default, however, you can enable your theme's editor stylesheet by going to Settings - tinyWYM Editor and checking 'Allow theme editor styles'. tinyWYM Editor removes other editor styles partly in order to prevent conflicts, but also because it is assumed that if you are using tinyWYM editor it is because you want to see the _markup_ being posted to the front end of the site and not what it will eventually look like. After all, that is what the plugin is for.

= Can I disable tinyWYM Editor for certain users? =

tinyWYM Editor allows administrators to disable tinyWYM for particular user roles; Administrators, Editors, Authors, or Contributors. Go to Settings - tinyWYM Editor.

= How do I toggle the tinyWYM styles on and off? =

tinyWYM Editor adds a button to the editor toolbar. Click it to toggle tinyWYM styles on and off. You and also use the keyboard shortcut ctrl+W.

== Screenshots ==

1. Wordpress editor without tinyWYM installed: Everything looks okay, but…
2. With tinyWYM installed: empty tags and superfluous markup revealed.
3. tinyWYM Editor converts WordPress's WYSIWYG editor into a WYSIWYM editor.
4. Create any HTML tag from selection: Select text then click the button or hit Ctrl+T.
5. Wrap the current element in a new HTML element: Place caret in element you want to wrap, then click the button or hit Ctrl+T.
6. Wrap multiple elements in a new HTML element: Select elements you want to wrap, then click the button or hit Ctrl+T.
7. Enter the new HTML tag name and any attributes you want it to have, then click 'Okay'.
8. Elements are now wrapped in a new tag.
9. Edit any element: Alt+Click any element then edit its tag and attributes.
10. Toggle tinyWYM on and off with the button or ctrl+W.

== Changelog ==

= 1.3 =

* Fixed bug making header tags overlap images with captions.
* Changed attributes input to textarea and widened modal window.

= 1.2.6 =

* Fix bug with various embeds not always showing.

= 1.2.5 =

* Quick update for WordPress 4.6 compatability.

= 1.2.4 =

* Quick update for WordPress 4.5 compatability.

= 1.2.3 =

* Fixed: CSS bug causing video embeds to not show in some situations.

= 1.2.2 =

* Added keyboard shortcut (ctrl+W) for toggling tinyWYM styles on and off.
* Update readme.text.
* Update screenshots, banners and thumbnails.

= 1.2.1 =

* Fixed: Toggle button not showing in BeaverBuilder's front end editor.

= 1.2 =

* Added new menu button to allow users to toggle tinyWYM styles on and off.

= 1.1.1 =

* Fix compatibility issue with older versions of PHP.

= 1.1 =

* Added new settings page
* Added option for admins to disable tinyWYM for Administrators, Editors, Authors, or Contributors.
* Added option to re-enable the current theme's editor styles.
* Added option to increase tinyWYM priority when loading scripts. (This was mainly to allow tinyWYM to work with BeaverBuilder's front end editor.)

= 1.0.2 =

* CSS improvements
* Increase script loading priority to allow for use with BeaverBuilder

= 1.0.1 =

* Update readme.txt & banner images for WordPress.org page

= 1.0 =

* Initial release

== Upgrade Notice ==

= 1.3 =

* This update fixes a CSS bug and improves the usability of the modal pop-up for editing tags.

= 1.2.6 =

* This update fixes a bug that made video and other embeds not always show.

= 1.2.5 =

* Quick update for WordPress 4.6 compatability.

= 1.2.4 =

* Quick update for WordPress 4.5 compatability, plus new thumbnail and banner images.

= 1.2.3 =

* When embedding videos from YouTube/Vimeo etc. the video wouldn't show until you hit return or clicked in another paragraph. The video might also disapear in certain situations. Fixed now.

= 1.2.2 =

* Added keyboard shortcut (ctrl+W) for toggling tinyWYM styles on and off.

= 1.2.1 =

* Fixed: Toggle button not showing in BeaverBuilder's front end editor.
* May need to clear browser's cache to see changes.

= 1.2 =

* Added new menu button to allow users to toggle tinyWYM styles on and off.

= 1.1 =

* Fix compatibility issue with older versions of PHP.

= 1.1 =

* Added new setting page: Setting - tinyWYM Editor

= 1.0.2 =

* Improved CSS for images inside editor, plus tinyWYM is now works with BeaverBuilder

= 1.0 =

* This is the first version of the plugin.  No updates available yet.