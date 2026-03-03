<?php

/**
 * @link              https://previewai.app
 * @since             1.0.0
 * @package           Preview_Ai
 *
 * @wordpress-plugin
 * Plugin Name:       Virtual Try-On for WooCommerce – Preview AI
 * Plugin URI:        https://previewai.app/
 * Description:       Preview AI is a plugin that allows your customers to preview your products in real-time using AI image generation.
 * Version:           1.3.0
 * Author:            Preview AI
 * Author URI:        https://profiles.wordpress.org/previewai/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins:  woocommerce
 * Text Domain:       preview-ai
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'PREVIEW_AI_VERSION', '1.3.0' );
define( 'PREVIEW_AI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-preview-ai-activator.php
 */
function preview_ai_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-preview-ai-activator.php';
	PREVIEW_AI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-preview-ai-deactivator.php
 */
function preview_ai_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-preview-ai-deactivator.php';
	PREVIEW_AI_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'preview_ai_activate' );
register_deactivation_hook( __FILE__, 'preview_ai_deactivate' );

/**
 * Check if tracking table needs update on plugins_loaded.
 */
add_action( 'plugins_loaded', function() {
	if ( class_exists( 'PREVIEW_AI_Tracking' ) ) {
		PREVIEW_AI_Tracking::maybe_create_table();
	}
} );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-preview-ai.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function preview_ai_run() {

	$plugin = new Preview_Ai();
	$plugin->run();

}
preview_ai_run();
