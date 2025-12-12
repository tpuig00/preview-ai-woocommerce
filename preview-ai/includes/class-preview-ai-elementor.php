<?php
/**
 * Elementor integration for Preview AI.
 *
 * @link       http://preview-ai.com
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PREVIEW_AI_Elementor
 *
 * Registers Elementor widget when Elementor is active.
 *
 * @since 1.0.0
 */
class PREVIEW_AI_Elementor {

	/**
	 * Initialize Elementor integration.
	 */
	public function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
	}

	/**
	 * Register Preview AI widget with Elementor.
	 *
	 * @since 1.0.0
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widget( $widgets_manager ) {
		require_once plugin_dir_path( __FILE__ ) . 'widgets/class-preview-ai-elementor-widget.php';
		$widgets_manager->register( new PREVIEW_AI_Elementor_Widget() );
	}
}

/**
 * Initialize Elementor integration if Elementor is active.
 *
 * @since 1.0.0
 */
function preview_ai_init_elementor() {
	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		new PREVIEW_AI_Elementor();
	}
}
add_action( 'plugins_loaded', 'preview_ai_init_elementor' );

