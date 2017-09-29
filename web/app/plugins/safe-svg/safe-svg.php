<?php
/*
Plugin Name: Safe SVG
Plugin URI:  https://wordpress.org/plugins/safe-svg/
Description: Allows SVG uploads into Wordpress and sanitizes the SVG before saving it
Version:     1.3.0
Author:      Daryll Doyle
Author URI:  http://enshrined.co.uk
Text Domain: safe-svg
Domain Path: /languages
 */

defined( 'ABSPATH' ) or die( 'Really?' );

require 'lib/vendor/autoload.php';

if ( ! class_exists( 'safe_svg' ) ) {

    /**
     * Class safe_svg
     */
    Class safe_svg {

        /**
         * The sanitizer
         *
         * @var \enshrined\svgSanitize\Sanitizer
         */
        protected $sanitizer;

        /**
         * Set up the class
         */
        function __construct() {
            $this->sanitizer = new enshrined\svgSanitize\Sanitizer();
            $this->sanitizer->minify(true);

            add_filter( 'upload_mimes', array( $this, 'allow_svg' ) );
            add_filter( 'wp_handle_upload_prefilter', array( $this, 'check_for_svg' ) );
        }

        /**
         * Allow SVG Uploads
         *
         * @param $mimes
         *
         * @return mixed
         */
        public function allow_svg( $mimes ) {
            $mimes['svg'] = 'image/svg+xml';

            return $mimes;
        }

        /**
         * Check if the file is an SVG, if so handle appropriately
         *
         * @param $file
         *
         * @return mixed
         */
        public function check_for_svg( $file ) {

            if ( $file['type'] === 'image/svg+xml' ) {
                if ( ! $this->sanitize( $file['tmp_name'] ) ) {
                    $file['error'] = __( "Sorry, this file couldn't be sanitized so for security reasons wasn't uploaded",
                        'safe-svg' );
                }
            }

            return $file;
        }

        /**
         * Sanitize the SVG
         *
         * @param $file
         *
         * @return bool|int
         */
        protected function sanitize( $file ) {
            $dirty = file_get_contents( $file );

            $clean = $this->sanitizer->sanitize( $dirty );

            if ( $clean === false ) {
                return false;
            }

            file_put_contents( $file, $clean );

            return true;
        }

    }
}

$safe_svg = new safe_svg();