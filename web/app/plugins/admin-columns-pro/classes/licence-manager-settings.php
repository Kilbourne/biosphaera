<?php

include_once 'licence-manager.php';

/**
 * Settings screen for Licence activation/deactivation
 *
 * @since 3.0
 */
if ( ! class_exists( 'Codepress_Licence_Manager_Settings' ) ) {

	/**
	 * @since 3.0
	 */
	class Codepress_Licence_Manager_Settings extends Codepress_Licence_Manager {

		/**
		 * CPAC instance
		 *
		 * @var CPAC
		 */
		public $cpac;

		/**
		 * CAC_Pro instance
		 */
		public $pro;

		/**
		 * @since 1.0
		 *
		 * @param array $args Arguments; This must contain: api_url, option_key, version, file, secret_key, product_name
		 */
		function __construct( $file_path, $cpac, $pro ) {

			parent::__construct( $file_path );

			$this->cpac = $cpac;
			$this->pro = $pro;

			// Add UI
			add_filter( 'cac/settings/groups', array( $this, 'settings_group' ) );
			add_filter( 'cac/network_settings/groups', array( $this, 'settings_group' ) );
			add_action( 'cac/settings/groups/row=addons', array( $this, 'display' ) );

			// licence Requests
			add_action( 'admin_init', array( $this, 'handle_request' ) );

			// Hook into the plugin install process, inject addon download url
			add_action( 'plugins_api', array( $this, 'inject_addon_install_resource' ), 10, 3 );

			// Do check before installing add-on
			add_filter( 'cac/addons/install_request/maybe_error', array( $this, 'maybe_install_error' ), 10, 2 );

			// Add notifications to the plugin screen
			add_action( 'after_plugin_row_' . $this->basename, array( $this, 'display_plugin_row_notices' ), 11 );

			// Add notice for license expiry
			add_action( 'all_admin_notices', array( $this, 'display_license_expiry_notices' ) );

			// Check for notice hide request
			add_action( 'wp_ajax_cpac_hide_license_expiry_notice', array( $this, 'ajax_hide_license_expiry_notice' ) );

			// Adds notice to update message that a licence is needed
			add_action( 'in_plugin_update_message-' . $this->basename, array( $this, 'need_license_message' ), 10, 2 );

			// add scripts, after settings page is set.
			add_action( 'admin_menu', array( $this, 'scripts' ), 20 ); // low prio, after $settings_page has been set by CPAC_Settings.
			add_action( 'network_admin_menu', array( $this, 'network_scripts' ), 20 );

			// check for a secure connection
			add_action( 'wp_ajax_cpac_check_connection', array( $this, 'ajax_check_connection' ) );

			// check license has been renewed
			add_action( 'wp_ajax_cpac_check_license_renewed', array( $this, 'ajax_check_license_renewed' ) );

			// check subscription renewal status once every week
			add_action( 'shutdown', array( $this, 'do_weekly_renewal_check' ) );
		}

		/**
		 * @since 3.4.3
		 */
		public function ajax_check_license_renewed() {
			$this->update_license_details(); // update reneweal date

			$phases = $this->get_hide_license_notice_thresholds();

			echo ( $this->get_days_to_expiry() <= $phases[ count( $phases ) - 1 ] ) ? '0' : '1'; // check for renewal threshold
			exit;
		}

		/**
		 * @since 3.4.3
		 */
		public function do_weekly_renewal_check() {
			if ( get_transient( '_cpac_renewal_check' ) ) {
				return;
			}
			$this->update_license_details();
			set_transient( '_cpac_renewal_check', 1, 3600 * 24 * 7 ); // 7 day interval
		}

		/**
		 * @since 3.1.2
		 */
		public function ajax_check_connection() {
			echo $this->api->test_request( $this->basename ) ? '1' : '0';
			exit;
		}

		/**
		 * @since 3.1.2
		 */
		public function scripts() {
			$settings_page = $this->cpac->settings()->get_settings_page();
			add_action( "admin_print_scripts-" . $settings_page, array( $this, 'admin_scripts' ) );
		}

		public function network_scripts() {
			$settings_page = $this->pro->get_network_settings_page();
			add_action( "admin_print_scripts-" . $settings_page, array( $this, 'admin_scripts' ) );
		}

		/**
		 * @since 3.1.2
		 */
		public function admin_scripts() {
			wp_enqueue_script( 'cac-connection', CAC_PRO_URL . "assets/js/check-connection.js", array( 'jquery' ), CAC_PRO_VERSION );
		}

		/**
		 * @since 1.0
		 */
		public function maybe_install_error( $error, $plugin_name ) {

			if ( ! $this->is_license_active() ) {
				$error = sprintf( __( "Licence not active. Enter your licence key on <a href='%s'>the settings page</a>.", 'codepress-admin-columns' ), $this->get_license_page_url() );
			}

			$install_data = $this->get_plugin_install_data( $plugin_name, $clear_cache = true ); // get remote add-on info

			if ( is_wp_error( $install_data ) ) {
				$error = $install_data->get_error_message();
			}

			return $error;
		}

		/**
		 * @since 1.2
		 */
		public function get_available_addons() {
			return $this->cpac->addons()->get_available_addons();
		}

		/**
		 * Get addons data for the update process
		 *
		 * @since 1.0.0
		 */
		public function get_addons_update_data() {
			$addons_update_data = array();

			$addons = $this->cpac->addons()->get_available_addons();
			foreach ( $addons as $plugin => $data ) {
				$basename = $this->cpac->addons()->get_installed_addon_plugin_basename( $plugin );
				$version = $this->cpac->addons()->get_installed_addon_plugin_version( $plugin );

				if ( ! $basename ) {
					continue;
				}

				$addons_update_data[] = array(
					'plugin'  => $basename,
					'version' => $version
				);
			}

			return $addons_update_data;
		}

		/**
		 * Add addons to install process, not the update process.
		 *
		 * @since 1.0
		 */
		public function inject_addon_install_resource( $result, $action, $args ) {

			if ( 'plugin_information' != $action || empty( $args->slug ) ) {
				return $result;
			}

			$addons = $this->cpac->addons()->get_available_addons();

			if ( ! isset( $addons[ $args->slug ] ) ) {
				return $result;
			}

			$install_data = $this->get_plugin_install_data( $args->slug, true );

			if ( ! $install_data ) {
				return $result;
			}

			return $install_data;
		}

		/**
		 * Handle requests for license activation and deactivation
		 *
		 * @since 1.0
		 */
		public function handle_request() {

			// Activation
			if ( isset( $_POST['_wpnonce_addon_activate'] ) && wp_verify_nonce( $_POST['_wpnonce_addon_activate'], $this->option_key ) ) {

				$licence_key = isset( $_POST[ $this->option_key ] ) ? sanitize_text_field( $_POST[ $this->option_key ] ) : '';

				if ( empty( $licence_key ) ) {
					cpac_admin_message( __( 'Empty licence.', 'codepress-admin-columns' ), 'error' );

					return;
				}

				$response = $this->activate_licence( $licence_key );

				if ( is_wp_error( $response ) ) {
					cpac_admin_message( $response->get_error_message(), 'error' );
				}
				elseif ( isset( $response->activated ) ) {
					cpac_admin_message( $response->message, 'updated' );
				}
				else {
					cpac_admin_message( __( 'Wrong response from API.', 'codepress-admin-columns' ), 'error' );
				}
			}

			// Deactivation
			if ( isset( $_POST['_wpnonce_addon_deactivate'] ) && wp_verify_nonce( $_POST['_wpnonce_addon_deactivate'], $this->option_key ) ) {

				$response = $this->deactivate_licence();

				if ( is_wp_error( $response ) ) {
					cpac_admin_message( __( 'Wrong response from API.', 'codepress-admin-columns' ) . ' ' . $response->get_error_message(), 'error' );
				}
				elseif ( isset( $response->deactivated ) ) {
					cpac_admin_message( $response->message, 'updated' );
				}
				else {
					cpac_admin_message( __( 'Wrong response from API.', 'codepress-admin-columns' ), 'error' );
				}
			}

			// Toggle SSL
			if ( isset( $_POST['_wpnonce_addon_toggle_ssl'] ) && wp_verify_nonce( $_POST['_wpnonce_addon_toggle_ssl'], $this->option_key ) ) {

				// disable ssl
				if ( '0' == $_POST['ssl'] ) {
					$this->disable_ssl();
				}
				else {
					$this->enable_ssl();
				}
			}

		}

		/**
		 * Add settings group to Admin Columns settings page
		 *
		 * @since 1.0
		 *
		 * @param array $groups Add group to ACP settings screen
		 *
		 * @return array Settings group for ACP
		 */
		public function settings_group( $groups ) {

			if ( isset( $groups['addons'] ) ) {
				return $groups;
			}

			$groups['addons'] = array(
				'title'       => __( 'Updates', 'codepress-admin-columns' ),
				'description' => __( 'Enter your licence code to receive automatic updates.', 'codepress-admin-columns' )
			);

			return $groups;
		}

		/**
		 * Get the URL to manage your license based on network or site managed license
		 *
		 * @return string
		 */
		public function get_license_page_url() {
			$urls = $this->cpac->settings()->get_settings_urls();
			$key = $this->is_network_managed_license() ? 'network_settings' : 'settings';

			return $urls[ $key ];
		}

		/**
		 * Display licence field
		 *
		 * @since 1.0
		 * @return void
		 */
		public function display() {

			// When the plugin is network activated, the license is managed globally
			if ( $this->is_network_managed_license() && ! is_network_admin() ) {
				?>
				<p>
					<?php
					$page = __( 'network settings page', 'codepress-admin-columns' );

					if ( current_user_can( 'manage_network_options' ) ) {
						$page = sprintf( '<a href="%s">%s</a>', network_admin_url( 'settings.php?page=codepress-admin-columns' ), $page );
					}

					printf( __( 'The license can be managed on the %s.', 'codepress-admin-columns' ), $page );
					?>
				</p>
				<?php
				return;
			}

			// Use this hook when you want to hide to licence form
			if ( ! apply_filters( 'cac/display_licence/addon=' . $this->option_key, true ) ) {
				return;
			}

			$licence = $this->get_licence_key();

			// on submit
			if ( ! empty( $_POST[ $this->option_key ] ) ) {
				$licence = $_POST[ $this->option_key ];
			}

			?>

			<style type="text/css">
				#licence_activation .dashicons-no-alt,
				#licence_activation .dashicons-yes {
					font-size: 25px;
					color: #46b450;
					vertical-align: -28%;
					margin-left: -6px;
					width: 22px;
				}

				#licence_activation .dashicons-no-alt {
					color: #dc3232;
				}
			</style>

			<form id="licence_activation" action="" method="post">
				<label for="<?php echo $this->option_key; ?>">
					<strong><?php echo $this->get_name(); ?></strong>
				</label>
				<br/>

				<?php if ( $this->is_license_active() ) : ?>

					<?php wp_nonce_field( $this->option_key, '_wpnonce_addon_deactivate' ); ?>

					<p>
						<span class="dashicons dashicons-yes"></span>
						<?php _e( 'Automatic updates are enabled.', 'codepress-admin-columns' ); ?> <?php //echo $this->get_masked_licence_key(); ?>
						<input type="submit" class="button" value="<?php _e( 'Deactivate licence', 'codepress-admin-columns' ); ?>">
					</p>

				<?php else : ?>

					<?php wp_nonce_field( $this->option_key, '_wpnonce_addon_activate' ); ?>

					<input type="password" value="<?php echo $licence; ?>" id="<?php echo $this->option_key; ?>" name="<?php echo $this->option_key; ?>" size="30" placeholder="<?php _e( 'Enter your licence code', 'codepress-admin-columns' ) ?>">
					<input type="submit" class="button" value="<?php _e( 'Update licence', 'codepress-admin-columns' ); ?>">
					<p class="description">
						<?php _e( 'Enter your licence code to receive automatic updates.', 'codepress-admin-columns' ); ?>
						<br/>
						<?php printf( __( 'You can find your license key on your %s.', 'codepress-admin-columns' ), '<a href="' . ac_get_site_url( 'my-account' ) . '" target="_blank">' . __( 'account page', 'codepress-admin-columns' ) . '</a>' ); ?>
					</p>

				<?php endif; ?>

			</form>

			<form id="toggle_ssl" action="" method="post" style="display:none; background: white;">
				<?php wp_nonce_field( $this->option_key, '_wpnonce_addon_toggle_ssl' ); ?>

				<p style="padding: 20px;">
					<?php printf( __( 'Could not connect to %s — You will not receive update notifications or be able to activate your license until this is fixed. This issue is often caused by an improperly configured SSL server (https). We recommend fixing the SSL configuration on your server, but if you need a quick fix you can:', 'codepress-admin-columns' ), ac_get_site_url() ); ?>
					<br/><br/>

					<?php
					$ssl_value = 1;
					$ssl_label = __( 'Enable SSL', 'codepress-admin-columns' );

					if ( $this->is_ssl_enabled() ) {
						$ssl_value = 0;
						$ssl_label = __( 'Disable SSL', 'codepress-admin-columns' );
					}
					?>

					<input type="hidden" name="ssl" value="<?php echo $ssl_value; ?>">
					<input type="submit" class="button" value="<?php echo $ssl_label; ?>">

				</p>
			</form>
			<?php
		}

		/**
		 * Get renewal message
		 *
		 * @since 3.4.3
		 */
		private function get_renewal_message() {

			$message = false;

			$days_to_expiry = $this->get_days_to_expiry();

			// renewal date has been set?
			if ( $days_to_expiry !== false ) {
				if ( $days_to_expiry > 0 ) {

					if ( $days_to_expiry < 28 ) { // for plugin page
						$days = sprintf( _n( '1 day', '%s days', $days_to_expiry, 'codepress-admin-columns' ), $days_to_expiry );
						if ( $discount = $this->get_license_renewal_discount() ) {
							$message = sprintf(
								__( "Your Admin Columns Pro license will expire in %s. %s now and get a %d%% discount!", 'codepress-admin-columns' ),
								'<strong>' . $days . '</strong>',
								'<a href="' . ac_get_site_url( 'my-account' ) . '">' . __( 'Renew your license', 'codepress-admin-columns' ) . '</a>',
								$discount
							);
						}

						else {
							$message = sprintf(
								__( "Your Admin Columns Pro license will expire in %s. %s now and get a discount!", 'codepress-admin-columns' ),
								'<strong>' . $days . '</strong>',
								'<a href="' . ac_get_site_url( 'my-account' ) . '">' . __( 'Renew your license', 'codepress-admin-columns' ) . '</a>'
							);
						}
					}
				}
				else {
					$message = sprintf(
						__( 'Your Admin Columns Pro license has expired on %s! Renew your license now by going to your %s.', 'codepress-admin-columns' ),
						date_i18n( get_option( 'date_format' ), $this->get_license_expiry_date() ),
						'<a href="' . ac_get_site_url( 'my-account' ) . '">' . __( 'My Account page', 'codepress-admin-columns' ) . '</a>'
					);
				}
			}

			return $message;
		}

		/**
		 * Get the button HTML for re-checking a license
		 *
		 * @since 3.4.3
		 */
		private function get_check_license_button() {

			$check_message_success = esc_js( __( 'Your license was successfully renewed!', 'codepress-admin-columns' ) );
			$check_message_error = esc_js( __( 'Your license has not been renewed yet.', 'codepress-admin-columns' ) );
			?>
			<a href="#" class="button cpac-check-license"><?php _e( 'Check my license', 'codepress-admin-columns' ); ?></a>
			<script type="text/javascript">
				if (typeof cpac_license_check_js == 'undefined') {
					var cpac_license_check_js = true;

					jQuery(document).ready(function ($) {
						$('body').on('click', '.cpac-check-license', function (e) {
							if (!$(this).hasClass('disabled')) {
								var el = $(this).parents('p');

								if (el.length == 0) {
									el = $(this).parents('.update-message');
								}

								$(this).after('<div class="spinner inline"></div>').show();
								$(this).addClass('disabled');

								$.post(ajaxurl, {
									'action': 'cpac_check_license_renewed'
								}, function (data) {
									el.find('.spinner').hide();

									if ('1' === data) {
										el.parent().removeClass('error').addClass('updated');
										el.html('<?php echo $check_message_success; ?>');
									}
									else {
										el.parent().removeClass('warning');
										var msg = '<?php echo $check_message_error; ?> ';
										el.find('a.cpac-check-license').replaceWith('<strong><?php echo $check_message_error; ?></strong>');
									}

								});
							}

							return false;
						});
					});
				}
			</script>
			<?php
		}

		/**
		 * Shows a message below the plugin on the plugins page
		 *
		 * @since 1.0.3
		 */
		public function display_plugin_row_notices() {

			if ( $this->is_license_active() ) {
				if ( $message = $this->get_renewal_message() ) {
					?>
					<tr class="plugin-update-tr">
						<td colspan="3" class="plugin-update cac-plugin-update">
							<div class="update-message">
								<?php echo $message; ?>
								<?php echo $this->get_check_license_button(); ?>

								<style type="text/css">
									.cac-plugin-update .spinner.right {
										display: block;
										right: 8px;
										text-decoration: none;
										text-align: right;
										position: absolute;
										top: 50%;
										margin-top: -10px;
									}

									.cac-plugin-update .spinner.inline {
										display: inline-block;
										position: absolute;
										margin: 4px 0 0 4px;
										padding: 0;
										float: none;
									}

									.plugin-update-tr .cac-plugin-update {
										border-left: 4px solid #2EA2CC;
									}

									.plugin-update-tr .cac-plugin-update .update-message {
										margin-top: 6px;
										line-height: 28px;
									}

									.plugin-update-tr .cac-plugin-update .update-message:before {
										content: "\f348";
										margin-top: 3px;
									}
								</style>
							</div>
						</td>
					</tr>
					<?php
				}
			}

			// needs to validate license
			else {
				$plugin_details = $this->get_plugin_details();

				$message = __( 'To finish activating Admin Columns Pro, please ', 'codepress-admin-columns' );
				if ( isset( $plugin_details->version ) && version_compare( $this->get_version(), $plugin_details->version, '<' ) ) {
					$message = __( 'To update, ', 'codepress-admin-columns' );
				}

				$message .= sprintf(
					__( "go to %s and enter your licence key. If you don't have a licence key, you may <a href='%s' target='_blank'>purchase one</a>.", 'codepress-admin-columns' ),
					sprintf(
						'<a href="%s">%s</a>',
						$this->get_license_page_url(),
						__( 'Settings', 'codepress-admin-columns' )
					),
					ac_get_site_url()
				);

				?>
				<tr class="plugin-update-tr">
					<td colspan="3" class="plugin-update cac-plugin-update">
						<div class="update-message">

							<?php echo $message; ?>

							<style type="text/css">
								.plugin-update-tr .cac-plugin-update {
									border-left: 4px solid #2EA2CC;
								}

								.plugin-update-tr .cac-plugin-update .update-message {
									margin-top: 6px;
								}

								.plugin-update-tr .cac-plugin-update .update-message:before {
									content: "\f348";
								}
							</style>
						</div>
					</td>
				</tr>
				<?php
			}
		}

		/**
		 * Whether the license expiry notice should be displayed, regardless of the license timeout
		 *
		 * @since 3.4.3
		 */
		public function is_license_expiry_notice_hideable() {
			return ( ! $this->cpac->is_settings_screen( 'settings' ) );
		}

		/**
		 * Display notice for license expiry
		 *
		 * @since 3.4.3
		 */
		public function display_license_expiry_notices() {
			global $pagenow;

			/**
			 * Filter the visibility of the Admin Columns renewal notice
			 *
			 * @since 3.4.3
			 *
			 * @param bool $hide Whether to hide the renewal notice. Defaults to false.
			 */
			if ( apply_filters( 'cac/hide_renewal_notice', false ) || cpac()->suppress_site_wide_notices() ) {
				return;
			}

			// Check visibility based on screen
			if ( ! $this->cpac->is_cac_screen() && $pagenow === 'plugins.php' ) {
				return;
			}

			// Permissions check
			if ( ! current_user_can( 'manage_admin_columns' ) ) {
				return;
			}

			$is_settings_screen = $this->cpac->is_settings_screen();
			$hide_license_timeout = get_user_meta( get_current_user_id(), 'cpac_hide_license_notice_timeout', true );
			$hide_license_phase = get_user_meta( get_current_user_id(), 'cpac_hide_license_notice_phase', true );

			if ( $this->is_license_expiry_notice_hideable() ) {
				// Notice was blocked the final time
				if ( $hide_license_phase == 'completed' ) {
					return;
				}

				// Notice was blocked, and timeout hasn't been reached yet
				if ( time() < $hide_license_timeout ) {
					return;
				}
			}

			// First license expiry threshold passed
			$phases = $this->get_hide_license_notice_thresholds();

			if ( $this->get_days_to_expiry() > $phases[ count( $phases ) - 1 ] ) {
				return;
			}

			// Show a renewal message if the license needs renewal
			if ( $message = $this->get_renewal_message() ) {
				?>
				<div class="cpac_message error warning">
					<?php if ( $this->is_license_expiry_notice_hideable() ) : ?>
						<a href="#" class="hide-notice"></a>
					<?php endif; ?>
					<p>
						<?php echo $message; ?>
						<?php echo $this->get_check_license_button(); ?>
					</p>
					<div class="clear"></div>

					<style type="text/css">
						body .wrap .cpac_message {
							position: relative;
							padding-right: 40px;
						}

						.cpac_message.error.warning {
							border-left: 4px solid #ffba00;
						}

						.cpac_message .spinner.right {
							display: block;
							right: 8px;
							text-decoration: none;
							text-align: right;
							position: absolute;
							top: 50%;
							margin-top: -10px;
						}

						.cpac_message .spinner.inline {
							display: inline-block;
							position: absolute;
							margin: 4px 0 0 4px;
							padding: 0;
							float: none;
						}

						.cpac_message .hide-notice {
							right: 8px;
							text-decoration: none;
							width: 32px;
							text-align: right;
							position: absolute;
							top: 50%;
							height: 32px;
							margin-top: -16px;
						}

						.cpac_message .hide-notice:before {
							display: block;
							content: '\f335';
							font-family: 'Dashicons';
							margin: .5em 0;
							padding: 2px;
						}
					</style>

					<script type="text/javascript">
						jQuery(function ($) {
							$(document).ready(function () {
								$('.cpac_message .hide-notice').click(function (e) {
									var el = $(this).parents('.cpac_message');

									$(this).after('<div class="spinner right"></div>').show();
									$(this).hide();

									$.post(ajaxurl, {
										'action': 'cpac_hide_license_expiry_notice'
									}, function (data) {
										el.find('.spinner').hide();
										el.slideUp();
									});

									return false;
								});
							});
						});
					</script>
				</div>
				<?php
			}
		}

		public function get_hide_license_notice_thresholds() {

			return array( 0, 7, 21 );
		}

		/**
		 * Handle an AJAX request for hiding license expiry notices
		 *
		 * @since 3.4.3
		 */
		public function ajax_hide_license_expiry_notice() {

			$hide_license_timeout = get_user_meta( get_current_user_id(), 'cpac_hide_license_notice_timeout', true );
			$hide_license_phase = get_user_meta( get_current_user_id(), 'cpac_hide_license_notice_phase', true );

			if ( $hide_license_phase != 'completed' ) {
				$expiry_date = $this->get_license_expiry_date();
				$phases = $this->get_hide_license_notice_thresholds();
				$days = $this->get_days_to_expiry();

				foreach ( $phases as $phase => $threshold ) {
					if ( $days <= $threshold ) {
						break;
					}
				}

				$new_phase = $phase - 1;

				if ( $new_phase == - 1 ) {
					update_user_meta( get_current_user_id(), 'cpac_hide_license_notice_timeout', 0 );
					update_user_meta( get_current_user_id(), 'cpac_hide_license_notice_phase', 'completed' );
				}
				else {
					update_user_meta(
						get_current_user_id(),
						'cpac_hide_license_notice_timeout',
						$expiry_date - $phases[ $new_phase ] * 86400 // Expiry date minus x days
					);
					update_user_meta( get_current_user_id(), 'cpac_hide_license_notice_phase', $new_phase );
				}
			}

			wp_send_json_success();
		}

		/**
		 * Message displayed on plugin page if license not activated
		 *
		 * @param  array $plugin_data
		 * @param  object $r
		 *
		 * @return void
		 */
		public function need_license_message( $plugin_data, $r ) {
			if ( empty( $r->package ) ) {
				printf( ' ' . __( "To enable updates for this product, please <a href='%s'>activate your license</a>.", 'codepress-admin-columns' ), $this->cpac->settings()->get_settings_url( 'settings' ) );
			}
		}
	}
}