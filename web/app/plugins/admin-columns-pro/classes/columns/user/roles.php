<?php

class CPAC_Column_User_Roles extends CPAC_Column {

	public function init() {

		parent::init();

		// Properties
		$this->properties['type'] = 'column-roles';
		$this->properties['label'] = __( 'Roles', 'codepress-admin-columns' );
	}

	public function get_value( $user_id ) {
		global $wp_roles;
		$user = new WP_User( $user_id );
		$roles = $user->roles;

		$roles_labels = array();
		foreach ( $roles as $key ) {
			$label = translate_user_role( $wp_roles->roles[ $key ]['name'] );
			$label = '<div class="order cpac-tip" data-tip="' . $key . '">' . $label . '</div>';
			$roles_labels[] = $label;
		}

		return implode( ', ', $roles_labels );
	}

	public function get_raw_value( $user_id ) {
		$user = new WP_User( $user_id );

		return implode( ', ', $user->roles );
	}
}