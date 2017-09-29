<?php

if ( ! class_exists( 'Codepress_Licence_Manager' ) ) {

	/**
	 * Addon update class
	 *
	 * Example usage:
	 * new CAC_Addon_Update( __FILE__ );
	 *
	 * @version 1.0
	 * @since 1.0
	 */
	class Codepress_Licence_Manager {

		/**
		 * Endpoint to access API over plain http
		 *
		 * @since 3.1.2
		 * @var $nossl_endpoint string
		 */
		private $nossl_endpoint;

		/**
		 * Licence Key
		 *
		 * @since 1.1
		 */
		public $licence_key;

		/**
		 * API object
		 *
		 * @since 1.1
		 * @var $api ACP_API
		 */
		public $api;

		/**
		 * Option key to store licence data
		 *
		 * @since 1.1
		 */
		protected $option_key;

		/**
		 * Plugin basename
		 *
		 * @since 1.1
		 */
		protected $basename;

		/**
		 * @since 1.0
		 *
		 * @param array $args [api_url, option_key, file, name, version]
		 */
		public function __construct( $file_path ) {

			$this->option_key = 'cpupdate_cac-pro';
			$this->basename   = plugin_basename( $file_path );

			// Init API
			$this->set_api();

			// reflect API settings within the update request
			add_filter( 'http_request_args', array( $this, 'use_api_http_request_args_for_plugin_update' ), 10, 2 );

			// Hook into WP update process
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );

			// Seen when the user clicks "view details" on the plugin listing page
			add_action( 'install_plugins_pre_plugin-information', array( $this, 'plugin_changelog' ) );

			// Activate licence on plugin install
			register_activation_hook( $file_path, array( $this, 'auto_activate_licence' ) );

			$this->register_nossl_endpoint();
		}

		/**
		 * Register endpoint to access API over plain http
		 *
		 * @since 3.1.2
		 */
		private function register_nossl_endpoint() {
			$this->nossl_endpoint = 'cac-api-nossl';

			add_rewrite_rule( '^' . $this->nossl_endpoint . '/?$', 'index.php', 'top' );
		}

		/**
		 * Overwrite by child class
		 *
		 * @since 1.0.0
		 */
		protected function get_addons_update_data() {
			return array();
		}

		/**
		 * Overwrite by child class
		 *
		 * @since 1.0.0
		 */
		protected function get_available_addons() {
			return array();
		}

		/**
		 * Setup an API object
		 *
		 * @since 1.1
		 */
		private function set_api() {
			include_once 'api.php';

			$this->api = new ACP_API();

			$url = apply_filters( 'cac/api/url', 'https://www.admincolumns.com' );

			// change the scheme to access the API via http
			if ( ! apply_filters( 'cac/api/secure', true ) ) {
				$url = set_url_scheme( $url, 'http' ) . '/' . $this->nossl_endpoint;
			}

			$this->api
				->set_url( $url )
				->set_request_arg( 'sslverify', $this->is_ssl_enabled() );
		}

		/**
		 * Tries to match API settings with the update request
		 *
		 * @since 3.1.2
		 *
		 * @param array $r
		 * @param string $url
		 *
		 * @return array
		 */
		public function use_api_http_request_args_for_plugin_update( $r, $url ) {

			// only applies to api URL domain
			if ( 0 === strpos( $url, $this->api->get_url() ) ) {
				$api_args = $this->api->get_request_args();

				if ( isset( $api_args['sslverify'] ) ) {
					$r['sslverify'] = $api_args['sslverify'];
				}
			}

			return $r;
		}

		/**
		 * @since 1.1
		 * @return object self
		 */
		public function set_licence_key( $licence_key ) {
			$this->licence_key = $licence_key;

			return $this;
		}

		/**
		 * @since 1.1
		 * @return object self
		 */
		public function set_option_key( $option_key ) {
			$this->option_key = $option_key;

			return $this;
		}

		/**
		 * Update expiration date & renewal discount
		 *
		 * @since 3.4.3
		 */
		public function update_license_details() {
			$response = $this->api->get_license_details( $this->get_licence_key() );

			if ( isset( $response->expiry_date ) ) {
				$this->store_license_expiry_date( $response->expiry_date );
			}
			if ( isset( $response->renewal_discount ) ) {
				$this->store_license_renewal_discount( $response->renewal_discount );
			}
		}

		/**
		 * @since 1.0
		 *
		 * @param string $licence_key Licence Key
		 *
		 * @return object Response
		 */
		public function activate_licence( $licence_key ) {

			$response = $this->api->activate_licence( $licence_key );

			$this->delete_licence_key();
			$this->delete_licence_status();

			if ( isset( $response->activated ) ) {
				$this->store_licence_key( $licence_key );
				$this->store_licence_status( 'active' );

				if ( isset( $response->expiry_date ) ) {
					$this->store_license_expiry_date( $response->expiry_date );
				}
				if ( isset( $response->renewal_discount ) ) {
					$this->store_license_renewal_discount( $response->renewal_discount );
				}

				$this->purge_plugin_transients();
			}

			return $response;
		}

		/**
		 * TODO add for other add-ons
		 */
		public function purge_plugin_transients() {

			delete_site_transient( 'update_plugins' );
			delete_site_transient( 'admin-columns-pro_acppluginupdate' );
			delete_site_transient( 'cac-addon-acf_acppluginupdate' );
		}

		/**
		 * @since 1.0
		 */
		public function deactivate_licence() {

			$response = $this->api->deactivate_licence( $this->get_licence_key() );

			$this->delete_licence_key();
			$this->delete_licence_status();
			$this->delete_license_expiry_date();

			return $response;
		}

		/**
		 * HTML changelog
		 *
		 * @since 1.0
		 * @return void
		 */
		public function plugin_changelog() {

			$plugins   = array_keys( $this->get_available_addons() ); // addons
			$plugins[] = dirname( $this->basename ); // pro version

			foreach ( $plugins as $name ) {
				if ( $name === $_GET['plugin'] ) {

					$changelog = $this->api->get_plugin_changelog( $name );

					if ( is_wp_error( $changelog ) ) {
						$changelog = $changelog->get_error_message();
					}

					echo $changelog;
					exit;
				}
			}
		}

		/**
		 * @see ACP_API::get_plugin_install_data()
		 * @since 1.1
		 * @return mixed
		 */
		public function get_plugin_install_data( $plugin_name, $clear_cache = false ) {

			if ( $clear_cache ) {
				delete_site_transient( $this->option_key . '_plugininstall' );
			}

			$plugin_install = get_site_transient( $this->option_key . '_plugininstall' );

			// no cache, get data
			if ( ! $plugin_install ) {
				$plugin_install = $this->api->get_plugin_install_data( $this->get_licence_key(), $plugin_name );

				// flatten wp_error object for transient storage
				if ( is_wp_error( $plugin_install ) ) {
					$plugin_install = $this->flatten_wp_error( $plugin_install );
				}
			}

			/*
				We need to set the transient even when there's an error,
				otherwise we'll end up making API requests over and over again
				and slowing things down big time.
			*/
			set_site_transient( $this->option_key . '_plugininstall', $plugin_install, 60 * 15 ); // 15 min.

			// Maybe create wp_error object
			$plugin_install = $this->maybe_unflatten_wp_error( $plugin_install );

			return $plugin_install;
		}

		/**
		 * @see ACP_API::get_plugin_update_data()
		 * @since 1.1
		 * @return
		 */
		public function get_plugin_update_data( $plugin_name, $version ) {
			$plugin_update = get_site_transient( $plugin_name . '_acppluginupdate' );

			// no cache, get data
			if ( ! $plugin_update ) {
				$plugin_update = $this->api->get_plugin_update_data( $this->get_licence_key(), $plugin_name, $version );

				// flatten wp_error object for transient storage
				if ( is_wp_error( $plugin_update ) ) {
					$plugin_update = $this->flatten_wp_error( $plugin_update );
				}
			}

			/*
				We need to set the transient even when there's an error,
				otherwise we'll end up making API requests over and over again
				and slowing things down big time.
			*/
			set_site_transient( $plugin_name . '_acppluginupdate', $plugin_update, 3600 * 1 ); // 1 hour

			$plugin_update = $this->maybe_unflatten_wp_error( $plugin_update );

			return $plugin_update;
		}

		/**
		 * @see ACP_API::get_plugin_details()
		 * @since 1.1
		 * @return
		 */
		public function get_plugin_details() {

			$plugin_details = get_site_transient( $this->option_key . '_plugindetails' );

			// no cache, get data
			if ( ! $plugin_details ) {
				$plugin_details = $this->api->get_plugin_details( $this->basename );

				// flatten wp_error object for transient storage
				if ( is_wp_error( $plugin_details ) ) {
					$plugin_details = $this->flatten_wp_error( $plugin_details );
				}
			}

			/*
				We need to set the transient even when there's an error,
				otherwise we'll end up making API requests over and over again
				and slowing things down big time.
			*/
			set_site_transient( $this->option_key . '_plugindetails', $plugin_details, 3600 * 24 ); // 24 hour

			$plugin_details = $this->maybe_unflatten_wp_error( $plugin_details );

			return $plugin_details;
		}

		/**
		 * Check for Updates at the defined API endpoint and modify the update array.
		 *
		 * @uses api_request()
		 *
		 * @param object $transient Update array build by Wordpress.
		 *
		 * @return array Modified update array with custom plugin data.
		 */
		public function update_check( $transient ) {

			// Addons
			if ( $addons = $this->get_addons_update_data() ) {
				foreach ( $addons as $addon ) {
					$plugin_data = $this->get_plugin_update_data( dirname( $addon['plugin'] ), $addon['version'] );
					if ( ! is_wp_error( $plugin_data ) && ! empty( $plugin_data->new_version ) && version_compare( $plugin_data->new_version, $addon['version'] ) > 0 ) {
						$transient->response[ $addon['plugin'] ] = $plugin_data;
					}
				}
			}

			// Main plugin
			$plugin_data = $this->get_plugin_update_data( dirname( $this->basename ), $this->get_version() );
			if ( ! is_wp_error( $plugin_data ) && ! empty( $plugin_data->new_version ) && version_compare( $plugin_data->new_version, $this->get_version() ) > 0 ) {
				$transient->response[ $this->basename ] = $plugin_data;
			}

			return $transient;
		}

		/**
		 * @since 1.0
		 * @return void
		 */
		public function auto_activate_licence() {
			if ( ! $this->is_license_active() && ( $licence = $this->get_licence_key() ) ) {
				$this->activate_licence( $licence );
			}
		}

		/**
		 * Get the plugin's header info from the installed plugins list.
		 *
		 * @since 1.1
		 */
		public function get_plugin_info( $field ) {
			if ( ! is_admin() ) {
				return false;
			}

			$plugins = get_plugins();

			if ( ! isset( $plugins[ $this->basename ][ $field ] ) ) {
				return false;
			}

			return $plugins[ $this->basename ][ $field ];
		}

		public function get_basename() {
			return $this->basename;
		}

		public function get_version() {
			return $this->get_plugin_info( 'Version' );
		}

		public function get_name() {
			return $this->get_plugin_info( 'Name' );
		}

		/**
		 * Check if the license for this plugin is managed per site or network
		 *
		 * @since 3.6
		 * @return boolean
		 */
		protected function is_network_managed_license() {
			return is_multisite() && is_plugin_active_for_network( plugin_basename( ACP_FILE ) );
		}

		protected function update_option( $option, $value, $autoload = false ) {
			return $this->is_network_managed_license()
				? update_site_option( $option, $value )
				: update_option( $option, $value, $autoload );
		}

		protected function get_option( $option, $default = false ) {
			return $this->is_network_managed_license()
				? get_site_option( $option, $default )
				: get_option( $option, $default );
		}

		protected function delete_option( $option ) {
			return $this->is_network_managed_license()
				? delete_site_option( $option )
				: delete_option( $option );
		}

		public function get_masked_licence_key() {
			return str_repeat( '*', 28 ) . substr( $this->get_licence_key(), - 4 );
		}

		public function get_licence_key() {
			return $this->licence_key ? $this->licence_key : trim( $this->get_option( $this->option_key ) );
		}

		public function get_licence_status() {
			return $this->get_option( $this->option_key . '_sts' );
		}

		public function is_license_active() {
			$status = $this->get_licence_status();

			return true === $status || '1' === $status || 'active' === $status;
		}

		public function store_licence_key( $licence_key ) {
			$this->update_option( $this->option_key, $licence_key );
		}

		public function delete_licence_key() {
			$this->delete_option( $this->option_key );
		}

		public function store_licence_status( $status ) {
			$this->update_option( $this->option_key . '_sts', $status ); // status is 'true' or 'expired'
		}

		public function delete_licence_status() {
			$this->delete_option( $this->option_key . '_sts' );
		}

		public function is_ssl_enabled() {
			return '1' === $this->get_option( $this->option_key . '_ssl' );
		}

		public function enable_ssl() {
			$this->update_option( $this->option_key . '_ssl', '1' );
			$this->purge_plugin_transients(); // for updater
		}

		public function disable_ssl() {
			$this->delete_option( $this->option_key . '_ssl' );
			$this->purge_plugin_transients(); // for updater
		}

		public function get_license_expiry_date() {
			$expiry_date = $this->get_option( $this->option_key . '_expiry_date' );

			if ( ! is_int( $expiry_date ) ) {
				$expiry_date = strtotime( $expiry_date );
			}

			return $expiry_date;
		}

		public function store_license_expiry_date( $renewal_date ) {
			$this->update_option( $this->option_key . '_expiry_date', $renewal_date );
		}

		public function delete_license_expiry_date() {
			$this->delete_option( $this->option_key . '_expiry_date' );
		}

		public function get_license_renewal_discount() {
			return $this->get_option( $this->option_key . '_renewal_discount' );
		}

		public function store_license_renewal_discount( $renewal_discount ) {
			$this->update_option( $this->option_key . '_renewal_discount', $renewal_discount );
		}

		public function delete_license_renewal_discount() {
			$this->delete_option( $this->option_key . '_renewal_discount' );
		}

		public function get_days_to_expiry() {
			$days = false;

			if ( $this->is_license_active() && ( $expiry_date = $this->get_license_expiry_date() ) ) {
				$days = floor( ( $expiry_date - time() ) / 86400 );
			}

			return $days;
		}

		public function is_license_expired() {
			$days = $this->get_days_to_expiry();

			return false !== $days && $days <= 0;
		}

		/**
		 * Flatten WP_Error object for storage in transient
		 *
		 * @param object $wp_error WP_Error object
		 *
		 * @return $error Error Object
		 */
		public function flatten_wp_error( $wp_error ) {
			$error = false;

			if ( is_wp_error( $wp_error ) ) {
				$error = (object) array(
					'error'   => 1,
					'time'    => time(),
					'code'    => $wp_error->get_error_code(),
					'message' => $wp_error->get_error_message(),
				);
			}

			return $error;
		}

		/**
		 * Maybe unflatten error
		 *
		 * @param mixed $maybe_error stdClass
		 *
		 * @return $wp_error WP_Error Object
		 */
		public function maybe_unflatten_wp_error( $maybe_error ) {
			if ( isset( $maybe_error->error ) && isset( $maybe_error->message ) ) {
				$maybe_error = new WP_Error( $maybe_error->code, $maybe_error->message );
			}

			return $maybe_error;
		}
	}
}