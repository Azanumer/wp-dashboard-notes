<?php
/**
 * Uninstall WP Dashboard Notes.
 *
 * Removes shared notes and every user's personal notes.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wdn_shared_notes' );

global $wpdb;
$wpdb->delete(
	$wpdb->usermeta,
	array( 'meta_key' => 'wdn_personal_notes' ),
	array( '%s' )
);
