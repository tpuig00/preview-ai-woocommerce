<?php

/**
 * Fired during plugin activation
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 * @author     Preview AI <hello@previewai.app>
 */
class PREVIEW_AI_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * Sets up the initial state for the plugin:
	 * - Creates tracking database table
	 * - Marks that onboarding is needed (if no API key exists)
	 * - Sets default options
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Create tracking table.
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-tracking.php';
		PREVIEW_AI_Tracking::create_table();

		// Check if this is a fresh install (no API key configured).
		$existing_api_key = get_option( 'preview_ai_api_key', '' );

		if ( empty( $existing_api_key ) ) {
			// Mark that onboarding is needed.
			update_option( 'preview_ai_needs_onboarding', true );
			update_option( 'preview_ai_activation_time', time() );
		}

		// Set default options if they don't exist.
		if ( false === get_option( 'preview_ai_enabled' ) ) {
			update_option( 'preview_ai_enabled', 1 );
		}

		if ( false === get_option( 'preview_ai_api_endpoint' ) ) {
			update_option( 'preview_ai_api_endpoint', 'https://api.previewai.app' );
		}

		// Clear any cached API status.
		delete_transient( 'preview_ai_account_status' );
	}
}
