<?php
/*
Plugin Name: Plugin Organizer MU
Plugin URI: http://www.jsterup.com
Description: A plugin for specifying the load order of your plugins.
Version: 8.1
Author: Jeff Sterup
Author URI: http://www.jsterup.com
License: GPL2
*/

class PluginOrganizerMU {
	var $ignoreProtocol, $ignoreArguments, $requestedPermalink, $postTypeSupport;
	var $protocol, $mobile, $detectMobile, $requestedPermalinkHash, $permalinkSearchField, $secure;
	function __construct() {
		$this->ignoreProtocol = get_option('PO_ignore_protocol');
		$this->ignoreArguments = get_option('PO_ignore_arguments');
		$this->postTypeSupport = get_option('PO_custom_post_type_support');
		$this->postTypeSupport[] = 'plugin_filter';
		$this->detectMobile = get_option('PO_disable_mobile_plugins');
		$this->secure=0;
		if ($this->detectMobile == 1) {
			$this->detect_mobile();
		}
	}
	
	function disable_plugins($pluginList, $networkPlugin=0) {
		global $wpdb, $pagenow;
		$newPluginList = array();
		if (is_array($pluginList) && get_option("PO_disable_plugins") == "1" && ((get_option('PO_admin_disable_plugins') != "1" && !is_admin()) || (get_option('PO_admin_disable_plugins') == "1" && !in_array($pagenow, array("plugins.php", "update-core.php", "update.php")) && (!isset($_REQUEST['page']) || $_REQUEST['page'] != 'PO_group_and_order_plugins')))) {
				
			if (isset($GLOBALS["PO_CACHED_PLUGIN_LIST"]) && is_array($GLOBALS["PO_CACHED_PLUGIN_LIST"]) && $networkPlugin == 0) {
				$newPluginList = $GLOBALS["PO_CACHED_PLUGIN_LIST"];
			} else {
				$this->set_requested_permalink();
				if (get_option("PO_version_num") != "8.1" && !is_admin()) {
					$newPluginList = $pluginList;
					update_option("PO_disable_plugins", "0");
					update_option("PO_admin_disable_plugins", "0");
				} else {
					if ($this->detectMobile == 1 && $this->mobile) {
						$globalPlugins = get_option("PO_disabled_mobile_plugins");
						$globalGroups = get_option("PO_disabled_mobile_groups");
					} else {
						$globalPlugins = get_option("PO_disabled_plugins");
						$globalGroups = get_option("PO_disabled_groups");
					}

					##Search page
					if (!is_admin() && isset($_REQUEST['s'])) {
						if ($this->detectMobile == 1 && $this->mobile) {
							$disabledPlugins = get_option('PO_disabled_mobile_search_plugins');
							$enabledPlugins = get_option('PO_enabled_mobile_search_plugins');
							$disabledGroups = get_option('PO_disabled_mobile_search_groups');
							$enabledGroups = get_option('PO_enabled_mobile_search_groups');
						} else {
							$disabledPlugins = get_option('PO_disabled_search_plugins');
							$enabledPlugins = get_option('PO_enabled_search_plugins');
							$disabledGroups = get_option('PO_disabled_search_groups');
							$enabledGroups = get_option('PO_enabled_search_groups');
						}
							
					}

					$disabledPlugins = (isset($disabledPlugins) && is_array($disabledPlugins))? $disabledPlugins : array();
					$enabledPlugins = (isset($enabledPlugins) && is_array($enabledPlugins))? $enabledPlugins : array();
					$disabledGroups = (isset($disabledGroups) && is_array($disabledGroups))? $disabledGroups : array();
					$enabledGroups = (isset($enabledGroups) && is_array($enabledGroups))? $enabledGroups : array();
					
					if (sizeof($disabledPlugins) == 0 && sizeof($enabledPlugins) == 0 && sizeof($disabledGroups) == 0 && sizeof($enabledGroups) == 0) {
						
						if ($this->ignoreProtocol == '0') {
							$requestedPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE ".$this->permalinkSearchField." = %s AND status IN ('publish','private') AND secure = %d AND post_type IN ([IN]) ORDER BY FIELD(post_type, [IN]), post_priority DESC";
							$requestedPostQuery = $wpdb->prepare($requestedPostQuery, $this->requestedPermalinkHash, $this->secure);
							$requestedPost = $wpdb->get_results($this->prepare_in($requestedPostQuery, $this->postTypeSupport), ARRAY_A);
						} else {
							$requestedPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE ".$this->permalinkSearchField." = %s AND status IN ('publish','private') AND post_type IN ([IN]) ORDER BY FIELD(post_type, [IN]), post_priority DESC";
							$requestedPostQuery = $wpdb->prepare($requestedPostQuery, $this->requestedPermalinkHash);
							$requestedPost = $wpdb->get_results($this->prepare_in($requestedPostQuery, $this->postTypeSupport), ARRAY_A);
						}
						if (!is_array($requestedPost)) {
							$requestedPost = array();
						}
						
						$disabledPlugins = array();
						$enabledPlugins = array();
						$disabledGroups = array();
						$enabledGroups = array();
						foreach($requestedPost as $currPost) {
							if ($this->detectMobile == 1 && $this->mobile) {
								$disabledPlugins = @unserialize($currPost['disabled_mobile_plugins']);
								$enabledPlugins = @unserialize($currPost['enabled_mobile_plugins']);
								$disabledGroups = @unserialize($currPost['disabled_mobile_groups']);
								$enabledGroups = @unserialize($currPost['enabled_mobile_groups']);
							} else {
								$disabledPlugins = @unserialize($currPost['disabled_plugins']);
								$enabledPlugins = @unserialize($currPost['enabled_plugins']);
								$disabledGroups = @unserialize($currPost['disabled_groups']);
								$enabledGroups = @unserialize($currPost['enabled_groups']);
							}
							if ((is_array($disabledPlugins) && sizeof($disabledPlugins) > 0) || (is_array($enabledPlugins) && sizeof($enabledPlugins) > 0) || (is_array($disabledGroups) && sizeof($disabledGroups) > 0) || (is_array($enabledGroups) && sizeof($enabledGroups) > 0)) {
								break;
							}
						}
					}
					
					$disabledPlugins = (!is_array($disabledPlugins))? array() : $disabledPlugins;
					$enabledPlugins = (!is_array($enabledPlugins))? array() : $enabledPlugins;
					$disabledGroups = (!is_array($disabledGroups))? array() : $disabledGroups;
					$enabledGroups = (!is_array($enabledGroups))? array() : $enabledGroups;
					
					if (get_option("PO_fuzzy_url_matching") == "1" && sizeof($disabledPlugins) == 0 && sizeof($enabledPlugins) == 0 && sizeof($disabledGroups) == 0 && sizeof($enabledGroups) == 0) {
						$endChar = (preg_match('/\/$/', get_option('permalink_structure')) || is_admin())? '/':'';
						$lastUrl = $_SERVER['HTTP_HOST'].$endChar;
						
						$fuzzyPost = array();
						//Dont allow an endless loop
						$loopCount = 0;
		
						$permalinkHashes = array();
						$previousIndex = 8;
						$lastOcc = strrpos($this->requestedPermalink, "/");
						while ($loopCount < 25 && $previousIndex < $lastOcc) {
							$startReplace = strpos($this->requestedPermalink, '/', $previousIndex);
							$endReplace = strpos($this->requestedPermalink, '/', $startReplace+1);
							if ($endReplace === false) {
								$endReplace = strlen($this->requestedPermalink);
							}
							$permalinkHashes[] = $wpdb->prepare('%s', md5(substr_replace($this->requestedPermalink, "/*/", $startReplace, ($endReplace-$startReplace)+1)));
							$previousIndex = $endReplace;
							$loopCount++;
						}

						if (sizeof($permalinkHashes) > 0) {
							if ($this->ignoreProtocol == '0') {
								$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$this->permalinkSearchField." = ".implode(" OR ".$this->permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND secure = %d AND post_type IN ([IN]) ORDER BY dir_count DESC, FIELD(post_type, [IN]), post_priority DESC";
								$fuzzyPostQuery = $wpdb->prepare($fuzzyPostQuery, $this->secure);
								$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $this->postTypeSupport), ARRAY_A);
								
							} else {
								$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$this->permalinkSearchField." = ".implode(" OR ".$this->permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND post_type IN ([IN]) ORDER BY dir_count DESC, FIELD(post_type, [IN]), post_priority DESC";
								$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $this->postTypeSupport), ARRAY_A);
							}
						}
						
						#print $this->prepare_in($fuzzyPostQuery, $this->postTypeSupport);
						if (sizeof($fuzzyPost) == 0) {
							$permalinkHashes = array();
							$loopCount = 0;
							while ($loopCount < 25 && $this->requestedPermalink != $lastUrl && ($this->requestedPermalink = preg_replace('/\/[^\/]+\/?$/', $endChar, $this->requestedPermalink))) {
								$loopCount++;
								$this->requestedPermalinkHash = $wpdb->prepare('%s', md5($this->requestedPermalink));
								$permalinkHashes[] = $this->requestedPermalinkHash;
							}
							
							if (sizeof($permalinkHashes) > 0) {
								if ($this->ignoreProtocol == '0') {
									$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$this->permalinkSearchField." = ".implode(" OR ".$this->permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND secure = %d AND children = 1 AND post_type IN ([IN]) ORDER BY dir_count DESC, FIELD(post_type, [IN]), post_priority DESC";
									$fuzzyPostQuery = $wpdb->prepare($fuzzyPostQuery, $this->secure);
									$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $this->postTypeSupport), ARRAY_A);
									
								} else {
									$fuzzyPostQuery = "SELECT * FROM ".$wpdb->prefix."po_plugins WHERE (".$this->permalinkSearchField." = ".implode(" OR ".$this->permalinkSearchField." = ", $permalinkHashes).") AND status IN ('publish','private') AND children = 1 AND post_type IN ([IN]) ORDER BY dir_count DESC, FIELD(post_type, [IN]), post_priority DESC";
									$fuzzyPost = $wpdb->get_results($this->prepare_in($fuzzyPostQuery, $this->postTypeSupport), ARRAY_A);
								}
							}
						}

							
						#print $this->prepare_in($fuzzyPostQuery, $this->postTypeSupport);
						#print_r($fuzzyPost);
						$matchFound = 0;
						if (sizeof($fuzzyPost) > 0) {
							foreach($fuzzyPost as $currPost) {
								if ($this->detectMobile == 1 && $this->mobile) {
									$disabledFuzzyPlugins = @unserialize($currPost['disabled_mobile_plugins']);
									$enabledFuzzyPlugins = @unserialize($currPost['enabled_mobile_plugins']);
									$disabledFuzzyGroups = @unserialize($currPost['disabled_mobile_groups']);
									$enabledFuzzyGroups = @unserialize($currPost['enabled_mobile_groups']);
								} else {
									$disabledFuzzyPlugins = @unserialize($currPost['disabled_plugins']);
									$enabledFuzzyPlugins = @unserialize($currPost['enabled_plugins']);
									$disabledFuzzyGroups = @unserialize($currPost['disabled_groups']);
									$enabledFuzzyGroups = @unserialize($currPost['enabled_groups']);
								}
								if ((is_array($disabledFuzzyPlugins) && sizeof($disabledFuzzyPlugins) > 0) || (is_array($enabledFuzzyPlugins) && sizeof($enabledFuzzyPlugins) > 0) || (is_array($disabledFuzzyGroups) && sizeof($disabledFuzzyGroups) > 0) || (is_array($enabledFuzzyGroups) && sizeof($enabledFuzzyGroups) > 0)) {
									$matchFound = 1;
									break;
								}
							}
							
							if ($matchFound > 0) {
								if (!is_array($disabledFuzzyPlugins)) {
									$disabledFuzzyPlugins = array();
								}

								if (!is_array($enabledFuzzyPlugins)) {
									$enabledFuzzyPlugins = array();
								}

								if (!is_array($disabledFuzzyGroups)) {
									$disabledFuzzyGroups = array();
								}

								if (!is_array($enabledFuzzyGroups)) {
									$enabledFuzzyGroups = array();
								}

								$disabledPlugins = $disabledFuzzyPlugins;
								$enabledPlugins = $enabledFuzzyPlugins;
								$disabledGroups = $disabledFuzzyGroups;
								$enabledGroups = $enabledFuzzyGroups;
							}
						}
					}

					$disabledGroupMembers = array();
					$enabledGroupMembers = array();
					if (is_array($disabledGroups)) {
						foreach($disabledGroups as $group) {
							$groupMembers = get_post_meta($group, '_PO_group_members', $single=true);
							if (!is_array($groupMembers)) {
								$groupMembers = array();
							}
							$disabledGroupMembers = array_merge($disabledGroupMembers, $groupMembers);
						}
					}

					if (is_array($enabledGroups)) {
						foreach($enabledGroups as $group) {
							$groupMembers = get_post_meta($group, '_PO_group_members', $single=true);
							if (!is_array($groupMembers)) {
								$groupMembers = array();
							}
							$enabledGroupMembers = array_merge($enabledGroupMembers, $groupMembers);
						}
					}
					$disabledGroupMembers = array_unique($disabledGroupMembers);
					$enabledGroupMembers = array_unique($enabledGroupMembers);
					

					foreach($disabledGroupMembers as $groupMember) {
						if (!in_array($groupMember, $disabledPlugins)) {
							$disabledPlugins[] = $groupMember;
						}
					}
					
					foreach($enabledGroupMembers as $groupMember) {
						if (!in_array($groupMember, $enabledPlugins)) {
							$enabledPlugins[] = $groupMember;
						}
					}


					if (is_array($globalPlugins) && sizeOf($globalPlugins) > 0) {
						foreach ($pluginList as $plugin) {
							if (in_array($plugin, $globalPlugins) && (!preg_match('/plugin-organizer.php$/', $plugin) || !is_admin())) {
								if (in_array($plugin, $enabledPlugins)) {
									$newPluginList[] = $plugin;
								}
							} else {
								$newPluginList[] = $plugin;
							}
						}
						$pluginList = $newPluginList;
						$newPluginList = array();
					}

					if (is_array($globalGroups) && sizeOf($globalGroups) > 0) {
						foreach($globalGroups as $group) {
							$groupMembers = get_post_meta($group, '_PO_group_members', $single=true);
							if (!is_array($groupMembers)) {
								$groupMembers = array();
							}
							
							foreach ($pluginList as $plugin) {
								if (in_array($plugin, $groupMembers) && (!preg_match('/plugin-organizer.php$/', $plugin) || !is_admin())) {
									if (in_array($plugin, $enabledPlugins)) {
										$newPluginList[] = $plugin;
									}
								} else {
									$newPluginList[] = $plugin;
								}
							}
							$pluginList = $newPluginList;
							$newPluginList = array();
						}
					}

					
					
					if (is_array($disabledPlugins)) {
						if (is_admin()) {
							foreach ($disabledPlugins as $key=>$plugin) {
								if (preg_match('/plugin-organizer.php$/', $plugin)) {
									unset($disabledPlugins[$key]);
								}
							}
						}
						foreach ($pluginList as $plugin) {
							if (!in_array($plugin, $disabledPlugins)) {
								$newPluginList[] = $plugin;
							}
						}
					} else {
						$newPluginList = $pluginList;
					}
				}
				if ($networkPlugin == 0) {
					$GLOBALS["PO_CACHED_PLUGIN_LIST"] = $newPluginList;
				}
			}
		} else {
			$newPluginList = $pluginList;
		}
		return $newPluginList;
	}
	
	function disable_network_plugins($pluginList) {
		if (isset($GLOBALS["PO_CACHED_NET_PLUGINS"]) && is_array($GLOBALS["PO_CACHED_NET_PLUGINS"])) {
			$newPluginList = $GLOBALS["PO_CACHED_NET_PLUGINS"];
		} else {
			$newPluginList = array();
			if (is_array($pluginList) && sizeOf($pluginList) > 0) {
				remove_filter('option_active_plugins', array($this, 'disable_plugins'), 10, 1);
				$activePlugins = get_option('active_plugins');
				add_filter('option_active_plugins', array($this, 'disable_plugins'), 10, 1);
				$tempPluginList = array_keys($pluginList);
				$tempPluginList = $this->disable_plugins($tempPluginList, 1);
				$newPluginList = array();
				$newPluginListOrder = array();
				foreach($tempPluginList as $pluginFile) {
					$newPluginList[$pluginFile] = $pluginList[$pluginFile];
					$newPluginListOrder[] = array_search($pluginFile, $activePlugins);
				}
				array_multisort($newPluginListOrder, $newPluginList);
			}
			$GLOBALS["PO_CACHED_NET_PLUGINS"] = $newPluginList;
		}
		
		return $newPluginList;
	}

	function set_requested_permalink() {
		if ($this->ignoreArguments == '1') {
			$splitPath = explode('?', $_SERVER['REQUEST_URI']);
			$requestedPath = $splitPath[0];
			$this->permalinkSearchField = 'permalink_hash';
		} else {
			$requestedPath = $_SERVER['REQUEST_URI'];
			$this->permalinkSearchField = 'permalink_hash_args';
		}
		
		$this->requestedPermalink = $_SERVER['HTTP_HOST'].$requestedPath;
		$this->requestedPermalinkHash = md5($this->requestedPermalink);

		if ($this->ignoreProtocol == '0') {
			$this->secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 1 : 0;
		} else {
			$this->secure = 0;
		}


	}

	function detect_mobile() {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$mobileAgents = get_option('PO_mobile_user_agents');
		if (!is_array($mobileAgents)) {
			$mobileAgents = array();
		}
		$this->mobile = false;

		foreach ( $mobileAgents as $agent ) {
			if ( $agent != "" && stripos($userAgent, $agent) !== FALSE ) {
				$this->mobile = true;
				break;
			}
		}
	}

	function prepare_in($sql, $vals){
		global $wpdb;
		$in_count = substr_count($sql, '[IN]');
		if ( $in_count > 0 ){
			$args = array( str_replace('[IN]', implode(', ', array_fill(0, count($vals), '%s')), str_replace('%', '%%', $sql)));
			// This will populate ALL the [IN]'s with the $vals, assuming you have more than one [IN] in the sql
			for ($i=0; $i < substr_count($sql, '[IN]'); $i++) {
				$args = array_merge($args, $vals);
			}
			$sql = call_user_func_array(array($wpdb, 'prepare'), array_merge($args));
		}
		return $sql;
	}
}
$PluginOrganizerMU = new PluginOrganizerMU();

add_filter('option_active_plugins', array($PluginOrganizerMU, 'disable_plugins'), 10, 1);

add_filter('site_option_active_sitewide_plugins', array($PluginOrganizerMU, 'disable_network_plugins'), 10, 1);

?>