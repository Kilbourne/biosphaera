<?php

class ameSuperUsers extends ameModule {
	protected $tabSlug = 'hidden-users';
	protected $tabTitle = 'Users';

	public function __construct($menuEditor) {
		parent::__construct($menuEditor);

		add_filter('users_list_table_query_args', array($this, 'filterUserQueryArgs'));
		add_filter('map_meta_cap', array($this, 'restrictUserEditing'), 10, 4);

		add_action('admin_menu_editor-header', array($this, 'handleFormSubmission'), 10, 2);
	}

	public function filterUserQueryArgs($args) {
		//Exclude superusers if the current user is not a superuser.
		$superUsers = $this->getSuperUserIDs();
		if ( empty($superUsers) ) {
			return $args;
		}

		if ( !$this->isCurrentUserSuper() ) {
			$args['exclude'] = array_merge(
				isset($args['exclude']) ? $args['exclude'] : array(),
				$superUsers
			);
		}

		return $args;
	}

	/**
	 * Prevent normal users from editing superusers.
	 *
	 * @param string[] $requiredCaps List of primitive capabilities (output).
	 * @param string $capability     The meta capability (input).
	 * @param int $thisUserId        The user that's trying to do something.
	 * @param array $args
	 * @return string[]
	 */
	public function restrictUserEditing($requiredCaps, $capability, $thisUserId, $args) {
		static $editUserCaps = array('edit_user', 'delete_user', 'promote_user', 'remove_user');
		if ( !in_array($capability, $editUserCaps) || !isset($args[0]) ) {
			return $requiredCaps;
		}

		/** @var int The user that might be edited or deleted. */
		$targetUserId = intval($args[0]);
		$thisUserId = intval($thisUserId);

		if ( $this->isSuperUser($targetUserId) && !$this->isSuperUser($thisUserId) ) {
			return array_merge($requiredCaps, array('do_not_allow'));
		}

		return $requiredCaps;
	}

	/**
	 * @return int[]
	 */
	private function getSuperUserIDs() {
		$result = $this->menuEditor->get_plugin_option('super_users');
		if ( $result === null ) {
			return array();
		}
		return $result;
	}

	/**
	 * @return WP_User[]
	 */
	private function getSuperUsers() {
		$ids = $this->getSuperUserIDs();
		if ( empty($ids) ) {
			return array();
		}

		//Caution: If you pass an empty array as "include", get_users() will return *all* users from the current site.
		return get_users( array('include' => $ids) );
	}

	/**
	 * Is the current user one of the superusers?
	 *
	 * @return bool
	 */
	private function isCurrentUserSuper() {
		$user = wp_get_current_user();
		return $user && $this->isSuperUser($user->ID);
	}

	private function isSuperUser($userId) {
		return in_array($userId, $this->getSuperUserIDs());
	}

	public function enqueueTabScripts() {
		parent::enqueueTabScripts();

		wp_enqueue_auto_versioned_script(
			'ame-super-users',
			plugins_url('super-users.js', __FILE__),
			array('knockout', 'jquery', 'ame-visible-users', 'ame-actor-manager', 'jquery-cookie')
		);

		//Pass users to JS.
		$users = array();
		foreach($this->getSuperUsers() as $user) {
			$properties = $this->menuEditor->user_to_property_map($user);
			$properties['avatar_html'] = get_avatar($user->ID, 32);
			$users[$user->user_login] = $properties;
		}

		$currentUser = wp_get_current_user();
		wp_localize_script(
			'ame-super-users',
			'wsAmeSuperUserSettings',
			array(
				'superUsers' => $users,
				'userEditUrl' => admin_url('user-edit.php'),
				'currentUserLogin' => $currentUser->get('user_login'),
			)
		);
	}

	public function enqueueTabStyles() {
		parent::enqueueTabStyles();

		wp_enqueue_auto_versioned_style(
			'ame-super-users-css',
			plugins_url('super-users.css', __FILE__)
		);
	}

	public function handleFormSubmission($action, $post = array()) {
		//Note: We don't need to check user permissions here because plugin core already did.
		if ( $action === 'ame_save_super_users' ) {
			check_admin_referer($action);

			$userIDs = array_map('intval', explode(',', $post['settings'], 100));
			$userIDs = array_unique(array_filter($userIDs));

			//Save settings.
			$this->menuEditor->set_plugin_option('super_users', $userIDs);

			wp_redirect($this->getTabUrl(array('updated' => 1)));
			exit;
		}
	}
}