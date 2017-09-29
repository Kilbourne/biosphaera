<?php
class CACIE_Roles {

	private function __construct() {}

	/**
	 * Get the highest hierarchy role for a user
	 *
	 * @param int $userid ID of the user to get the role for
	 * @return bool|string User role name or false if the user doesn't exist
	 */
	static public function get_user_role( $userid )	{
		$userid = intval( $userid );

		if ( !$userid ) {
			return false;
		}

		$user = get_user_by( 'id', $userid );

		if ( !$user ) {
			return false;
		}

		$role = array_shift( $user->roles );

		return $role;
	}
}