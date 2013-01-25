<?php
/**
 * Enable Image Scaling Uninstall
 *
 * @since 1.0
 */

// Delete options
delete_option( 'ww_scaling_enabled' );
delete_option( 'ww_scaling_width' );
delete_option( 'ww_scaling_height' );

if ( is_multisite() ) {
	// Reset upload_resize user option for all users author+
	$users = get_users( array( 'who' => 'authors' ) );
	foreach ( $users as $user ) {
		$settings = get_user_option( 'user-settings', $user->ID );
		$reset = str_replace( array( '&upload_resize=1', 'upload_resize=1' ), '', $settings );
		update_user_option( $user->ID, 'user-settings', $reset );
	}
}