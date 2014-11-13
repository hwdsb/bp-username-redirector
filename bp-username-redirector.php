<?php
/*
Plugin Name: BP Username Redirector
Description: Redirects old user accounts to their new URL.  Requires the BuddyPress Username Changer plugin.
Author: r-a-y
Author URI: http://profiles.wordpress.org/r-a-y
Version: 0.1
License: GPLv2 or later
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Logs username changes that the BuddyPress Username Changer plugin makes.
 *
 * @param string $new_user_login The new user_login for the user
 * @param WP_User $old_user_data The old userdata for the user
 */
function bp_ur_log_username_changes( $new_user_login, $old_user_data ) {
	$log = bp_get_option( 'bp_cu_log' );

	// make sure we grab the right username b/c of user compat mode
	$username = bp_is_username_compatibility_mode() ? rawurlencode( $old_user_data->user_login ) : $old_user_data->user_nicename;

	// save updated log if username isn't already logged
	if ( empty( $log[$username] ) ) {
		$log[$username] = $old_user_data->ID;
		bp_update_option( 'bp_cu_log', $log );
	}
}
add_action( 'bp_username_changed', 'bp_ur_log_username_changes', 10, 2 );

/**
 * If we land on an invalid user page, attempt to redirect to new user's page.
 *
 * When BP introduces rewrite rules, some logic will need to be rewritten.
 */
function bp_ur_user_redirector() {
	global $bp_unfiltered_uri;

	// BP Username Changer plugin doesn't exist, so stop!
	if ( ! function_exists( 'bpdev_bpcu_nav_setup' ) ) {
		return;
	}

	// if we're on a valid user page, stop now!
	if ( bp_is_user() ) {
		return;
	}

	// if we're on a spammer's page, let BP do its thang, so stop!
	if ( bp_displayed_user_id() && bp_is_user_spammer( bp_displayed_user_id() ) ) {
		return;
	}

	// sanity check!
	if ( empty( $bp_unfiltered_uri ) ) {
		return;
	}

	// check if we're attempting to find a member page
	if ( ! empty( $bp_unfiltered_uri[0] ) && 'members' == $bp_unfiltered_uri[0] ) {
		// see if we have a username
		if ( empty( $bp_unfiltered_uri[1] ) ) {
			return;
		}

		// grab the username
		$username = $bp_unfiltered_uri[1];

		// grab our username change log
		$old_usernames = bp_get_option( 'bp_cu_log' );

		// found a match in our log!
		// let's redirect the old username to the new one
		if ( ! empty( $old_usernames[$username] ) ) {
			$url = bp_get_requested_url();
			$url = str_replace( "/{$username}/", bp_core_get_username( $old_usernames[$username] ), $url );

			bp_core_redirect( $url );
			die();
		}
	}
}
add_action( 'bp_do_404', 'bp_ur_user_redirector' );