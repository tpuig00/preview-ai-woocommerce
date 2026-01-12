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
			update_option( 'preview_ai_api_endpoint', 'https://api.previewai.app/api/' );
		}

		// Set accent color based on theme primary color if possible.
		if ( false === get_option( 'preview_ai_accent_color' ) ) {
			$detected_color = self::detect_store_primary_color();
			if ( $detected_color ) {
				update_option( 'preview_ai_accent_color', $detected_color );
			} else {
				update_option( 'preview_ai_accent_color', '#3b82f6' ); // Default blue.
			}
		}

		// Clear any cached API status.
		delete_transient( 'preview_ai_account_status' );
	}

	/**
	 * Try to detect the store's primary/accent color.
	 *
	 * @return string|null Hex color or null if not detected.
	 */
	private static function detect_store_primary_color() {
		// 1. Storefront theme.
		$storefront_accent = get_theme_mod( 'storefront_accent_color' );
		if ( $storefront_accent ) {
			return $storefront_accent;
		}

		// 2. Astra theme.
		$astra_settings = get_option( 'astra-settings' );
		if ( is_array( $astra_settings ) && ! empty( $astra_settings['theme-color-1'] ) ) {
			return $astra_settings['theme-color-1'];
		}

		// 3. OceanWP theme.
		$ocean_primary = get_theme_mod( 'ocean_primary_color' );
		if ( $ocean_primary ) {
			return $ocean_primary;
		}

		// 4. GeneratePress theme.
		$gp_settings = get_option( 'generate_settings' );
		if ( is_array( $gp_settings ) && ! empty( $gp_settings['global_colors'] ) ) {
			foreach ( $gp_settings['global_colors'] as $color ) {
				if ( isset( $color['slug'] ) && 'primary' === $color['slug'] ) {
					return $color['color'];
				}
			}
		}

		// 5. Divi theme.
		$divi_accent = get_option( 'et_divi_accent_color' );
		if ( $divi_accent ) {
			return $divi_accent;
		}

		return null;
	}
}
