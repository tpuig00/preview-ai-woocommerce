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
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/**
 * 1. Eliminar opciones del plugin.
 */
$options = array(
	'preview_ai_api_key',
	'preview_ai_enabled',
	'preview_ai_product_type',
	'preview_ai_clothing_subtype',
	'preview_ai_display_mode',
	'preview_ai_button_text',
	'preview_ai_button_icon',
	'preview_ai_button_position',
	'preview_ai_accent_color',
	'preview_ai_needs_onboarding',
	'preview_ai_activation_time',
	'preview_ai_api_endpoint',
	'preview_ai_account_status',
	'preview_ai_stats',
	'preview_ai_tracking_db_version',
);

foreach ( $options as $option ) {
	delete_option( $option );
	delete_site_option( $option ); // Para multisite
}

/**
 * 2. Eliminar transitorios.
 */
delete_transient( 'preview_ai_account_status' );

/**
 * 3. Eliminar la tabla personalizada de base de datos.
 */
$table_name = $wpdb->prefix . 'preview_ai_events';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

/**
 * 4. Eliminar metadatos de posts (productos, variaciones y pedidos).
 */
$post_meta_keys = array(
	'_preview_ai_enabled',
	'_preview_ai_recommended_subtype',
	'_preview_ai_image_analysis',
	'_preview_ai_session_id',
	'_preview_ai_converted',
	'_preview_ai_refunded',
	'_preview_ai_clothing_subtype',
);

foreach ( $post_meta_keys as $meta_key ) {
	$wpdb->delete(
		$wpdb->postmeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}

/**
 * 5. Eliminar metadatos de usuario.
 */
$user_meta_keys = array(
	'preview_ai_dismissed_low_tokens',
);

foreach ( $user_meta_keys as $meta_key ) {
	$wpdb->delete(
		$wpdb->usermeta,
		array( 'meta_key' => $meta_key ),
		array( '%s' )
	);
}
