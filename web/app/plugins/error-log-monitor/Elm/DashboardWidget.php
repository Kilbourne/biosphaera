<?php
class Elm_DashboardWidget {
	private $widgetId = 'ws_php_error_log';
	private $requiredCapability = 'manage_options';

	/**
	 * @var scbOptions $settings Plugin settings.
	 */
	private $settings;
	/**
	 * @var Elm_plugin $plugin A reference to the main plugin object.
	 */
	private $plugin;

	private function __construct($settings, $plugin) {
		$this->settings = $settings;
		$this->plugin = $plugin;

		ajaw_v1_CreateAction('elm-ignore-message')
			->handler(array($this, 'ajaxIgnoreMessage'))
			->requiredParam('message')
			->requiredCap($this->requiredCapability)
			->register();

		ajaw_v1_CreateAction('elm-unignore-message')
			->handler(array($this, 'ajaxIgnoreMessage'))
			->requiredParam('message')
			->requiredCap($this->requiredCapability)
			->register();

		add_action('wp_dashboard_setup', array($this, 'registerWidget'));
		add_action('admin_init', array($this, 'handleLogClearing'));
	}

	public function registerWidget() {
		if ( $this->userCanSeeWidget() ) {
			wp_add_dashboard_widget(
				$this->widgetId,
				/* translators: Dashboard widget name */
				__('PHP Error Log', 'error-log-monitor'),
				array($this, 'displayWidgetContents'),
				array($this, 'handleSettingsForm')
			);

			add_action('admin_enqueue_scripts', array($this, 'enqueueWidgetDependencies'));
		}
	}

	private function userCanSeeWidget() {
		return apply_filters('elm_show_dashboard_widget', current_user_can($this->requiredCapability));
	}

	public function enqueueWidgetDependencies($hook) {
		if ( $hook === 'index.php' ) {
			wp_enqueue_script(
				'elm-dashboard-widget',
				plugins_url('js/dashboard-widget.js', $this->plugin->getPluginFile()),
				array('jquery', 'ajaw-v1-ajax-action-wrapper'),
				'20170127'
			);

			wp_enqueue_style(
				'elm-dashboard-widget-styles',
				plugins_url('css/dashboard-widget.css', $this->plugin->getPluginFile()),
				array(),
				'20170120'
			);
		}
	}

	public function displayWidgetContents() {
		$log = Elm_PhpErrorLog::autodetect();

		if ( is_wp_error($log) ) {
			$this->displayConfigurationHelp($log->get_error_message());
			return;
		}

		if ( isset($_GET['elm-log-cleared']) && !empty($_GET['elm-log-cleared']) ) {
			printf('<p><strong>%s</strong></p>', __('Log cleared.', 'error-log-monitor'));
		}

		$filteredLog = $this->plugin->getWidgetEntries($log);
		if ( is_wp_error($filteredLog) ) {
			printf('<p>%s</p>', $filteredLog->get_error_message());
			return;
		}

		//$this->displaySummary();

		$startTime = microtime(true);
		$lines = $filteredLog->readLastEntries($this->settings->get('widget_line_count'));
		$elapsedTime = microtime(true) - $startTime;

		printf('<!-- Log file parse time: %.3f seconds -->', $elapsedTime);

		if ( empty($lines) ) {
			if ( $filteredLog->getSkippedEntryCount() > 0 ) {
				$message = __(
					'There are no recent log entries that match the filter settings.',
					'error-log-monitor'
				);
			} else {
				$message = __('The log file is empty.', 'error-log-monitor');
			}
			echo '<p>', $message, '</p>';

			if ( $filteredLog->getSkippedEntryCount() > 0 ) {
				$this->displaySkippedEntryCount($filteredLog);
			}
		} else {
			if ($this->settings->get('sort_order') === 'reverse-chronological') {
				$lines = array_reverse($lines);
			}

			/*
			 * Maybe show a notice if there are no errors or warnings in the last 24 hours / 7 days.
			 * Either just no messages, or only minor messages like notices or deprecation warnings.
			 * "No errors or warnings today."
			 * "No log entries in the last 24 hours."
			 * "No errors or warnings in the last 24 hours."
			 */

			if ($this->settings->get('dashboard_log_layout', 'list') === 'list') {
				$this->displayLogAsList($lines);
			} else {
				$this->displayLogAsTable($lines);
			}

			if ( $filteredLog->getSkippedEntryCount() > 0 ) {
				$this->displaySkippedEntryCount($filteredLog);
			}

			echo '<p>';
			printf(
				/* translators: 1: Log file name, 2: Log file size */
				__('Log file: %1$s (%2$s)', 'error-log-monitor') . ' ',
				esc_html($log->getFilename()),
				Elm_Plugin::formatByteCount($log->getFileSize(), 2)
			);
			/** @noinspection HtmlUnknownTarget */
			printf(
				'<a href="%s" class="button" onclick="return confirm(\'%s\');">%s</a>',
				wp_nonce_url(admin_url('/index.php?elm-action=clear-log&noheader=1'), 'clear-log'),
				esc_js(__('Are you sure you want to clear the error log?', 'error-log-monitor')),
				__('Clear Log', 'error-log-monitor')
			);

			echo '</p>';
		}
	}

	private function getIgnoreLink() {
		static $html = null;
		if ( $html === null ) {
			$html = sprintf(
				'<a href="#" class="elm-ignore-message" title="%s">%s</a>',
				esc_attr(__(
					"Ignored messages stay in the log file but they don't show up in the widget and don't generate email notifications.",
					'error-log-monitor'
				)),
				_x('Ignore', 'action link', 'error-log-monitor')
			);
		}
		return $html;
	}

	private function displaySkippedEntryCount(Elm_SeverityFilter $filteredLog) {
		echo '<p><div class="dashicons dashicons-filter" style="color: #82878c;"></div> ';
		$filteredLog->formatSkippedEntryCount();
		echo '</p>';
	}

	private function displayLogAsTable($lines) {
		echo '<table class="widefat striped elm-log-entries elm-log-table">',
		'<colgroup><col style="width: 9em;"><col></colgroup>',
		'<tbody>';
		foreach ($lines as $line) {
			printf(
				'<tr data-raw-message="%s" class="elm-entry"><td style="white-space:nowrap;">
						%s
						<p class="elm-line-actions">%s</p>						
					</td>',
				esc_attr($line['message']),
				!empty($line['timestamp']) ? $this->plugin->formatTimestamp($line['timestamp']) : '',
				$this->getIgnoreLink()
			);

			echo '<td>';
			echo esc_html($this->plugin->formatLogMessage($line['message']));

			if ( !empty($line['stacktrace']) ) {
				$this->displayStackTrace($line['stacktrace']);
			}

			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	private function displayLogAsList($lines) {
		//Do any of the log entries have a stack trace?
		$hasStackTraces = false;
		foreach($lines as $line) {
			if ( !empty($line['stacktrace']) ) {
				$hasStackTraces = true;
				break;
			}
		}

		$listClasses = array('elm-log-entries', 'elm-wide-list');
		if ( $hasStackTraces ) {
			$listClasses[] = 'elm-list-with-stack-traces';
		}

		$actions = sprintf('<div class="elm-line-actions">%s</div>', $this->getIgnoreLink());

		echo '<ul class="', esc_attr(implode(' ', $listClasses)), '"><tbody>';
		foreach ($lines as $line) {
			printf(
				'<li class="elm-entry%s" data-raw-message="%s">%s <p class="elm-timestamp">%s</p>',
				!empty($line['stacktrace']) ? ' elm-has-stack-trace' : '',
				esc_attr($line['message']),
				$actions,
				!empty($line['timestamp']) ? $this->plugin->formatTimestamp($line['timestamp']) : ''
			);

			echo '<p class="elm-log-message">',
				esc_html($this->plugin->formatLogMessage($line['message'])),
			'</p>';

			if ( !empty($line['stacktrace']) ) {
				$this->displayStackTrace($line['stacktrace']);
			}

			echo '</li>';
		}
		echo '</ul>';
	}

	private function displayStackTrace($stackTrace) {
		if ( empty($stackTrace) ) {
			return;
		}

		echo '<ul class="elm-stacktrace">';
		foreach($stackTrace as $item) {
			//Remove the "PHP " prefix from trace items.
			$item = preg_replace('@^PHP @', '', $item, 1);
			printf('<li>%s</li>'. "\n", esc_html($this->plugin->formatLogMessage($item)));
		}
		echo '</ul>';
	}

	private function displaySummary() {
		//TODO: Finish summary generation.
		$summary = $this->plugin->getSummary(new Elm_PhpErrorLog('C:\xampp\htdocs\cbtool\blog\wp-content\adminmenueditor_com.php.error.log'));
		$maxBarHeight = 100;

		foreach($summary as $item) {
			printf(
				'<p>%s</p><p>Count: %d</p>',
				htmlentities($item->message),
				$item->count
			);

			$maxBinValue = max($item->chart);

			echo '<ol class="elm-chart">';
			foreach($item->chart as $eventsInBin) {
				printf(
					'<li class="elm-chart-point %s" style="height: %d%%; width: %.2f%%" title="%d"></li>',
					($eventsInBin === 0) ? 'elm-chart-zero-point' : '',
					max(min(($eventsInBin / $maxBinValue), 1) * $maxBarHeight, 1),
					1 / count($item->chart) * 100,
					$eventsInBin
				);
			}
			echo '</ol>';

			//var_dump($item);
		}
	}

	private function displayConfigurationHelp($problem) {

		$exampleCode = "ini_set('log_errors', 'On');\n" . "ini_set('error_log', '/full/path/to/php-errors.log');";
		printf('<p><strong>%s</strong></p>', $problem);

		echo '<p>';
		_e(
			'To enable error logging, create an empty file named "php-errors.log".
			Place it in a directory that is not publicly accessible (preferably outside
			your web root) and ensure it is writable by the web server.
			Then add the following code to <code>wp-config.php</code>:',
			'error-log-monitor'
		);
		echo '</p>';
		echo '<pre>', $exampleCode, '</pre>';

		echo '<p>';
		printf(
			__('For reference, the full path of the WordPress directory is:<br>%s', 'error-log-monitor'),
			'<code>' . htmlentities(ABSPATH) . '</code>'
		);
		echo '</p>';

		echo '<p>';
		printf(
			/* translators: Links to English-language articles about configuring error logging. */
			__('See also: %s', 'error-log-monitor'),
			'<a href="http://codex.wordpress.org/Editing_wp-config.php#Configure_Error_Log">Editing wp-config.php</a>,
			 <a href="http://digwp.com/2009/07/monitor-php-errors-wordpress/">3 Ways To Monitor PHP Errors</a>'
		);
		echo '</p>';
	}

	public function handleSettingsForm() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget_id']) && is_array($_POST[$this->widgetId]) ) {
			$formInputs = $_POST[$this->widgetId];

			$this->settings->set('widget_line_count', intval($formInputs['widget_line_count']));
			if ( $this->settings->get('widget_line_count') <= 0 ) {
				$this->settings->set('widget_line_count', $this->settings->get_defaults('widget_line_count'));
			}

			$this->settings->set('strip_wordpress_path', isset($formInputs['strip_wordpress_path']));
			$this->settings->set('send_errors_to_email', trim(strval($formInputs['send_errors_to_email'])));

			$this->settings->set('email_interval', intval($formInputs['email_interval']));
			if ( $this->settings->get('email_interval') <= 60 ) {
				$this->settings->set('email_interval', $this->settings->get_defaults('email_interval'));
			}

			if ( isset($formInputs['sort_order']) ) {
				$this->settings->set('sort_order', strval($formInputs['sort_order']));
				if ( !in_array($this->settings->get('sort_order'), array('chronological', 'reverse-chronological')) ) {
					$this->settings->set('sort_order', $this->settings->get_defaults('sort_order'));
				}
			}

			$enableLogSizeNotification = isset($formInputs['enable_log_size_notification']);
			//Reset the "notification sent" flag when the user turns notifications on/off.
			if ( $enableLogSizeNotification != $this->settings->get('enable_log_size_notification') ) {
				//This is useful for testing and situations where the flag was set and then notifications got
				//temporarily turned off, with the log file size changing in the meantime.
				$this->settings->set('log_size_notification_sent', false);
			}
			$this->settings->set('enable_log_size_notification', $enableLogSizeNotification);

			if (
				$this->settings->get('enable_log_size_notification')
				&& isset($formInputs['log_size_notification_threshold'])
			) {
				$this->settings->set(
					'log_size_notification_threshold',
					floatval($formInputs['log_size_notification_threshold']) * Elm_Plugin::MB_IN_BYTES
				);
			}

			$dashboardFilterMode = strval($formInputs['dashboard_message_filter']);
			if ( in_array($dashboardFilterMode, array('all', 'selected')) ) {
				$this->settings->set('dashboard_message_filter', $dashboardFilterMode);

				if ( $dashboardFilterMode === 'selected' ) {
					$this->settings->set(
						'dashboard_message_filter_groups',
						$this->parseSelectedSeverityOptions($formInputs, 'dashboard_severity_option')
					);
				}
			}

			$emailFilterMode = strval($formInputs['email_message_filter']);
			if ( in_array($emailFilterMode, array('same_as_dashboard', 'selected')) ) {
				$this->settings->set('email_message_filter', $emailFilterMode);
				if ( $emailFilterMode === 'selected' ) {
					$this->settings->set(
						'email_message_filter_groups',
						$this->parseSelectedSeverityOptions($formInputs, 'email_severity_option')
					);
				}
			}

			$logLayout = strval($formInputs['dashboard_log_layout']);
			if ( in_array($logLayout, array('list', 'table')) ) {
				$this->settings->set('dashboard_log_layout', $logLayout);
			}

			do_action('elm_settings_changed', $this->settings);
		}

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('General', 'configuration section heading', 'error-log-monitor')
		);

		printf(
			'<p><label>%s <br><input type="text" name="%s[widget_line_count]" value="%s" size="5"></label></p>',
			__('Number of entries to show:', 'error-log-monitor'),
			esc_attr($this->widgetId),
			esc_attr($this->settings->get('widget_line_count'))
		);

		printf(
			'<p><label><input type="checkbox" name="%s[strip_wordpress_path]" %s> %s</label></p>',
			esc_attr($this->widgetId),
			$this->settings->get('strip_wordpress_path') ? ' checked="checked"' : '',
			__('Strip WordPress root directory from log messages', 'error-log-monitor')
		);

		printf(
			'<p><label><input type="checkbox" name="%s[sort_order]" value="reverse-chronological" %s> %s</label></p>',
			esc_attr($this->widgetId),
			$this->settings->get('sort_order') === 'reverse-chronological' ? ' checked="checked"' : '',
			__('Reverse line order (most recent on top)', 'error-log-monitor')
		);

		printf('<p class="hidden">%s <br>', __('Widget layout:', 'error-log-monitor'));
		$layouts = array(
			_x('Table', 'widget layout option', 'error-log-monitor') => 'table',
			_x('List', 'widget layout option', 'error-log-monitor') => 'list',
		);
		foreach($layouts as $name => $value) {
			printf(
				'<label><input type="radio" name="%s[dashboard_log_layout]" value="%s" %s> %s</label><br>',
				esc_attr($this->widgetId),
				esc_attr($value),
				($value == $this->settings->get('dashboard_log_layout')) ? ' checked="checked"' : '',
				$name
			);
		}
		echo '</p>';

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('Notifications', 'configuration section heading', 'error-log-monitor')
		);

		printf(
			'<p>
				<label for="%1$s-send_errors_to_email">%2$s</label>
				<input type="text" class="widefat" name="%1$s[send_errors_to_email]" id="%1$s-send_errors_to_email" value="%3$s">
			</p>',
			esc_attr($this->widgetId),
			__('Periodically email logged errors to:', 'error-log-monitor'),
			$this->settings->get('send_errors_to_email')
		);

		printf(
			'<p><label>%s <br><select name="%s[email_interval]">',
			__('How often to send email (max):', 'error-log-monitor'),
			esc_attr($this->widgetId)
		);
		$intervals = array(
			__('Every 10 minutes', 'error-log-monitor') => 10*60,
			__('Every 15 minutes', 'error-log-monitor') => 15*60,
			__('Every 30 minutes', 'error-log-monitor') => 30*60,
			__('Hourly', 'error-log-monitor')           => 60*60,
			__('Daily', 'error-log-monitor')            => 24*60*60,
			__('Weekly', 'error-log-monitor')           => 7*24*60*60,
		);
		foreach($intervals as $name => $interval) {
			printf(
				'<option value="%d" %s>%s</option>',
				$interval,
				($interval == $this->settings->get('email_interval')) ? ' selected="selected"' : '',
				$name
			);
		}
		echo '</select></label></p>';

		printf(
			'<p><label><input type="checkbox" name="%s[enable_log_size_notification]" 
		                      id="elm_enable_log_size_notification" %s> %s</label><br>',
			esc_attr($this->widgetId),
			$this->settings->get('enable_log_size_notification') ? ' checked="checked"' : '',
			__('Send an email notification when the log file size exceeds this limit:', 'error-log-monitor')
		);
		printf(
			'<input type="number" name="%s[log_size_notification_threshold]" value="%s" 
			        size="1" min="1" max="10240" style="max-width: 80px;" 
			        id="elm_log_size_notification_threshold" %s> MiB',
			esc_attr($this->widgetId),
			$this->settings->get('log_size_notification_threshold') / Elm_Plugin::MB_IN_BYTES,
			$this->settings->get('enable_log_size_notification') ? '' : ' disabled="disabled"'
		);
		echo '</p>';

		//This script is too short to be worth placing in a separate file. Lets just inline it.
		?>
		<script type="text/javascript">
			jQuery(function($) {
				var sizeNotificationEnabled = $('#elm_enable_log_size_notification');
				sizeNotificationEnabled.change(function() {
					$('#elm_log_size_notification_threshold').prop('disabled', !sizeNotificationEnabled.is(':checked'));
				});
			});
		</script>
		<?php

		printf(
			'<h3 class="elm-config-section-heading"><strong>%s</strong></h3>',
			_x('Filters', 'configuration section heading', 'error-log-monitor')
		);

		echo '<h4>', __('Dashboard widget filter', 'error-log-monitor'), '</h4>';

		printf(
			'<label><input type="radio" name="%s[dashboard_message_filter]" value="all"
			               id="elm_dashboard_message_filter_all" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('dashboard_message_filter', 'all') === 'all') ? ' checked="checked"' : '',
			__('Show all messages', 'error-log-monitor')
		);
		printf(
			'<label><input type="radio" name="%s[dashboard_message_filter]" value="selected"
			               id="elm_dashboard_message_filter_selected" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('dashboard_message_filter') === 'selected') ? ' checked="checked"' : '',
			__('Show only selected types', 'error-log-monitor')
		);

		$this->printSeverityFilterOptions(
			'dashboard_severity_option',
			$this->settings->get('dashboard_message_filter_groups', Elm_SeverityFilter::getAvailableOptions())
		);

		echo '<h4>', __('Email notification filter', 'error-log-monitor'), '</h4>';

		printf(
			'<label><input type="radio" name="%s[email_message_filter]" value="same_as_dashboard"
			               id="elm_email_message_filter_same" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('email_message_filter', 'same_as_dashboard') === 'same_as_dashboard') ? ' checked="checked"' : '',
			__('Same as the dashboard widget', 'error-log-monitor')
		);
		printf(
			'<label><input type="radio" name="%s[email_message_filter]" value="selected"
			               id="elm_email_message_filter_selected" %s> %s</label><br>',
			esc_attr($this->widgetId),
			($this->settings->get('email_message_filter') === 'selected') ? ' checked="checked"' : '',
			__('Notify only about selected types', 'error-log-monitor')
		);

		$this->printSeverityFilterOptions(
			'email_severity_option',
			$this->settings->get('email_message_filter_groups', Elm_SeverityFilter::getAvailableOptions())
		);

		//Ignored message list.
		printf('<h4>%s</h4>', __('Ignored messages', 'error-log-monitor'));

		$ignoredMessages = $this->settings->get('ignored_messages', array());
		if ( empty($ignoredMessages) ) {
			echo '<p>', __('There are no ignored messages.', 'error-log-monitor'), '</p>';
		} else {
			echo '<table class="widefat striped elm-ignored-messages">';
			foreach(array_keys($ignoredMessages) as $message) {
				printf('<tr data-raw-message="%s">', esc_attr($message));
				printf('<td>%s</td>', htmlentities($message));
				printf(
					'<td><p class="elm-line-actions"><a href="#" class="elm-unignore-message">%s</a></p></td>',
					_x('Unignore', 'action link', 'error-log-monitor')
				);
				echo '</tr>';
			}
			echo '</table>';
		}

	}

	private function printSeverityFilterOptions($fieldNamePrefix, $selectedOptions) {
		echo '<div class="elm_error_type_list" style="margin-left: 18px; margin-bottom: 1em;">';

		foreach(Elm_SeverityFilter::getAvailableOptions() as $errorGroup) {
			$label = ucwords($errorGroup);
			if ($errorGroup === Elm_SeverityFilter::UNKNOWN_LEVEL_GROUP) {
				$label = _x('Other', 'error type', 'error-log-monitor');
			}

			printf(
				'<label><input type="checkbox" name="%s[%s-%s]" %s> %s</label><br>',
				esc_attr($this->widgetId),
				esc_attr($fieldNamePrefix),
				$this->slugifyErrorLevel($errorGroup),
				in_array($errorGroup, $selectedOptions) ? ' checked="checked"' : '',
				$label
			);
		}

		echo '</div>';
	}

	private function slugifyErrorLevel($type) {
		return str_replace(' ', '_', $type);
	}

	private function parseSelectedSeverityOptions($formInputs, $fieldPrefix) {
		$validGroups = Elm_SeverityFilter::getAvailableOptions();
		$includedGroups = array();

		foreach($validGroups as $group) {
			$fieldName = $fieldPrefix . '-' . $this->slugifyErrorLevel($group);
			if ( isset($formInputs[$fieldName]) && !empty($formInputs[$fieldName]) ) {
				$includedGroups[] = $group;
			}
		}

		return $includedGroups;
	}

	public function handleLogClearing() {
		$doClearLog =  isset($_GET['elm-action']) && ($_GET['elm-action'] === 'clear-log')
			&& check_admin_referer('clear-log') && $this->userCanSeeWidget();

		if ( $doClearLog ) {
			$log = Elm_PhpErrorLog::autodetect();
			if ( is_wp_error($log) ) {
				return;
			}

			$log->clear();

			//Since the log is empty now, we can reset the file size notification.
			$this->settings->set('log_size_notification_sent', false);

			wp_redirect(admin_url('index.php?elm-log-cleared=1'));
			exit();
		}
	}

	/**
	 * Note: This handler processes both "ignore" and "unignore" operations.
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function ajaxIgnoreMessage($params) {
		//Avoid race conditions.
		//Step 1: Use locks.
		$handle = fopen(__FILE__, 'r');
		flock($handle, LOCK_EX);
		//Step 2: Force WP to reload options from the database. Normally this would be
		//bad for performance, but it's acceptable in a rarely used AJAX handler.
		wp_cache_delete('alloptions', 'options');

		$ignoredMessages = $this->settings->get('ignored_messages', array());

		if ( $params['action'] === 'elm-ignore-message' ) {
			//Add the message to the list.
			$ignoredMessages[$params['message']] = true;
		} else {
			//Remove the message from the list.
			unset($ignoredMessages[$params['message']]);
		}

		$this->settings->set('ignored_messages', $ignoredMessages);

		flock($handle, LOCK_UN);
		return $ignoredMessages;
	}

	public static function getInstance($settings, $plugin) {
		static $instance = null;
		if ( $instance === null ) {
			$instance = new self($settings, $plugin);
		}
		return $instance;
	}
}