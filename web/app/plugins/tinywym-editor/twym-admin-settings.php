<?php
/**
 * Admin Settings Page
 *
 * @since 1.1
 */

//* If this file is called directly, abort ==================================== */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//* Register Admin Page
add_action( 'admin_menu', 'twym_add_options_page' );

function twym_add_options_page() {

	add_options_page(
		__( 'tinyWYM Editor Settings' ),
		__( 'tinyWYM Editor' ),
		'manage_options',
		'tinywym-settings',
		'twym_settings_form'
	);

	add_action( 'admin_init', 'twym_register_settings' );

}

function twym_register_settings() {

	register_setting( 'twym_settings_group', 'twym_settings', 'twym_sanitize_settings' );

}

function twym_settings_form() {

	$settings = get_option( 'twym_settings' );
	
	$user_roles = array(
		__( 'administrator', 'twym_editor' ),
		__( 'editor', 'twym_editor' ),
		__( 'author', 'twym_editor' ),
		__( 'contributor', 'twym_editor' ),
	);

	?>
	
	<div class="wrap">
		<h2><?php _e( 'tinyWYM Editor Settings', 'twym_editor' ); ?></h2>
		
		<form method="post" action="options.php">
			<?php settings_fields( 'twym_settings_group' ); ?>
			<?php $twym_settings = get_option( 'twym_settings' ); ?>
			<table class="form-table">
				
				<?php foreach ( $user_roles as $role ) : ?>
					
					<?php
						$checked_disabled = isset( $settings[ 'disable' ][ $role ] ) ? checked( $settings[ 'disable' ][ $role ], '1', false ) : '';
						$checked_force    = isset( $settings[ 'force_enable' ] )     ? checked( $settings[ 'force_enable' ],     '1', false ) : '';
						$checked_theme    = isset( $settings[ 'theme_styles' ] )     ? checked( $settings[ 'theme_styles' ],     '1', false ) : '';
					?>

					<tr valign="top">
						<th scope="row"><?php printf( __( '%s Settings', 'twym_editor' ), ucwords( $role ) ); ?></th>
						<td>
							<p>
								<input id="disable-<?php echo $role ?>" type="checkbox" name="twym_settings[disable][<?php echo $role ?>]" value="1" <?php echo $checked_disabled ?>>
								<label for="disable-<?php echo $role ?>"><?php printf( __( 'Disable for %ss', 'twym_editor' ), ucwords( $role ) ); ?></label><br>
							</p>
						</td>
					</tr>

				<?php endforeach; ?>

				<tr valign="top">
					<th scope="row"><?php _e( 'Compatibility', 'twym_editor' ); ?></th>
					<td>
						<p><?php _e( 'Some plugins, such as Beaver Builder, disable other editor plugins when using custom instances of the WordPress editor. Check the box below if tinyWYM Editor appears to be disabled in some editor instances.', 'twym_editor' ); ?>
						</p>
						<p>
							<input id="force-enable" type="checkbox" name="twym_settings[force_enable]" value="1" <?php echo $checked_force; ?>>
							<label for="force-enable"><?php _e( 'Force enable tinyWYM Editor', 'twym_editor' ); ?></label>
						</p>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row"><?php _e( 'Theme Editor Styles', 'twym_editor' ); ?></th>
					<td>
						<p><?php _e( 'Many themes include their own editor stylesheet which may cause conflicts with tinyWYM Editor\'s own stylesheet. If you would like to allow your theme\'s editor stylesheet to load anyway, check the box below.', 'twym_editor' ); ?>
						</p>
						<p>
							<input id="theme-styles" type="checkbox" name="twym_settings[theme_styles]" value="1" <?php echo $checked_theme; ?>>
							<label for="theme-styles"><?php _e( 'Allow theme editor styles', 'twym_editor' ); ?></label>
						</p>
					</td>
				</tr>
				
			</table>
			
			<p class="submit">
				<input type="submit" class="button button-primary" value="<?php _e( 'Save Settings', 'twym_editor' ); ?>" />
			</p>
			
		</form>
	<pre>
		<?php var_dump($twym_settings);?></pre>
	</div>
	
	<?php

}

function twym_sanitize_settings( $input ) {

	// Loop through settings and set to 1 if anything is sent
	if ( is_array( $input ) ) {

		foreach ( $input as $setting => $value ) {

			// Check if user role disable setting group
			if ( is_array( $input[$setting] ) ) {

				foreach ( $input[$setting] as $user => $disable ) {

					if ( $user ) {
						$input[$setting][$user] = 1;
					}

				}

			} else {

				// Sanitise other settings
				if ( $input[$setting] ) {
					$input[$setting] = 1;
				}

			}
		}
	}

	return $input;

}