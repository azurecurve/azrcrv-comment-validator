<?php

/**
 * Declare the Namespace.
 */
namespace azurecurve\CommentValidator;

// Check that code was called from ClassicPress with uninstallation constant declared
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Options to remove
$options = array(
	'azrcrv-cv',
	'azrcrv-cv-weights',
	'azrcrv-cv-thresholds',
);

global $wpdb;

// Remove from single site
if ( ! is_multisite() ) {
	foreach ( $options as $option ) {
		delete_option( $option );
	}
	// Drop IP reputation table
	$table = $wpdb->prefix . 'azrcrv_cv_ip_rep';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
	// Clear scheduled cron
	wp_clear_scheduled_hook( 'azrcrv_cv_decay_ip_rep' );
}

// Remove from multi site
else {
	$site_ids         = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	$original_site_id = get_current_site_id();

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );

		foreach ( $options as $option ) {
			delete_option( $option );
		}

		$table = $wpdb->prefix . 'azrcrv_cv_ip_rep';
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore
		wp_clear_scheduled_hook( 'azrcrv_cv_decay_ip_rep' );
	}

	switch_to_blog( $original_site_id );

	foreach ( $options as $option ) {
		delete_site_option( $option );
	}
}
