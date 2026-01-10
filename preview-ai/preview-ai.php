<?php

/**
 * @link              https://previewai.app
 * @since             1.0.0
 * @package           Preview_Ai
 *
 * @wordpress-plugin
 * Plugin Name:       Preview AI
 * Plugin URI:        https://previewai.app/
 * Description:       Preview AI is a plugin that allows your customers to preview your products in real-time using AI image generation.
 * Version:           1.0.0
 * Author:            Preview AI
 * Author URI:        https://previewai.app/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
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
define( 'PREVIEW_AI_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-preview-ai-activator.php
 */
function activate_Preview_Ai() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-preview-ai-activator.php';
	PREVIEW_AI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-preview-ai-deactivator.php
 */
function deactivate_Preview_Ai() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-preview-ai-deactivator.php';
	PREVIEW_AI_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_Preview_Ai' );
register_deactivation_hook( __FILE__, 'deactivate_Preview_Ai' );

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
function run_Preview_Ai() {

	$plugin = new Preview_Ai();
	$plugin->run();

}
run_Preview_Ai();
