=== Safe SVG ===
Contributors: enshrined
Donate link: http://enshrined.co.uk
Tags: svg, sanitize, uploads, sanitise, security, svg upload
Requires at least: 4.0
Tested up to: 4.6.1
Stable tag: 1.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allow SVG uploads and sanitize them to stop XML/SVG vulnerabilities

== Description ==

Safe SVG gives you the ability to allow SVG uploads in WordPress and make sure that they're sanitized to stop SVG/XML vulnerabilities affecting your site.

This is more of a proof of concept for [#24251](https://core.trac.wordpress.org/ticket/24251) than anything but feel free to use it!

SVG Sanitization is done through the following library: [https://github.com/darylldoyle/svg-sanitizer](https://github.com/darylldoyle/svg-sanitizer)

== Installation ==

Install through the WordPress directory or download, unzip and upload the files to your `/wp-content/plugins/` directory

== Changelog ==

= 1.0.0 =
* Initial Release

= 1.1.0 =
* Added i18n
* Added da, de ,en, es, fr, nl and ru translations
* Fixed an issue with filename not being pulled over on failed uploads

= 1.1.1 =
* Fixed an issue with empty svg elements self-closing

= 1.2.0 =
* Added support for camel case attributes such as viewBox

= 1.3.0 =
* Minify SVGs after cleaning so they can be loaded correctly through file_get_contents
