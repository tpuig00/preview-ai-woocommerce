<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package    Preview_Ai
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * 1. Delete plugin options.
 */
$preview_ai_options = array(
	'preview_ai_enabled',
	'preview_ai_api_key',
	'preview_ai_display_mode',
	'preview_ai_needs_onboarding',
	'preview_ai_needs_first_try',
	'preview_ai_activation_time',
	'preview_ai_api_endpoint',
	'preview_ai_account_status',
	'preview_ai_stats',
	'preview_ai_tracking_db_version',
	'preview_ai_catalog_analysis_status',
	'preview_ai_catalog_analysis_progress',
	'preview_ai_catalog_pending_products',
	'preview_ai_catalog_analysis_results',
	'preview_ai_store_compatibility',
	'preview_ai_accent_color',
	'preview_ai_button_text',
	'preview_ai_button_icon',
	'preview_ai_button_position',
	'preview_ai_button_shape',
	'preview_ai_button_height',
);

foreach ( $preview_ai_options as $preview_ai_option ) {
	delete_option( $preview_ai_option );
	delete_site_option( $preview_ai_option ); // For multisite
}

/**
 * 1.1. Delete product metadata related to the plugin.
 * This removes all _preview_ai_* meta keys from the postmeta table.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk delete is more efficient during uninstall.
$wpdb->query(
	"DELETE FROM {$wpdb->prefix}postmeta 
	WHERE meta_key LIKE '_preview_ai_%'"
);

/**
 * 1.2. Delete user metadata related to the plugin.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk delete is more efficient during uninstall.
$wpdb->query(
	"DELETE FROM {$wpdb->prefix}usermeta 
	WHERE meta_key LIKE 'preview_ai_%'"
);

/**
 * 2. Delete transients.
 */
delete_transient( 'preview_ai_account_status' );

/**
 * 2.1. Delete Action Scheduler actions related to the plugin.
 * This is critical to prevent pending actions from continuing to run.
 */
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	// Delete all actions for the plugin hook.
	as_unschedule_all_actions( 'preview_ai_process_catalog_batch' );
} else {
	// Fallback: delete directly from the database if Action Scheduler is not available.
	// First, delete the associated logs.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary fallback when Action Scheduler is not loaded.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE al FROM {$wpdb->prefix}actionscheduler_logs al
			INNER JOIN {$wpdb->prefix}actionscheduler_actions aa ON al.action_id = aa.action_id
			WHERE aa.hook = %s",
			'preview_ai_process_catalog_batch'
		)
	);

	// Then, delete the actions.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Necessary fallback when Action Scheduler is not loaded.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = %s",
			'preview_ai_process_catalog_batch'
		)
	);
}

/**
 * 3. Delete custom database table.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Required table cleanup during uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}preview_ai_events" );