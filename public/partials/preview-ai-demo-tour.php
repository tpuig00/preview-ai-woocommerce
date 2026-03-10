<?php
/**
 * Demo Tour - Multi-step onboarding overlay.
 *
 * Only loaded when ?demo=yes parameter is present.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/public/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Enqueue demo tour assets.
wp_enqueue_style( 'preview-ai-demo-tour' );
wp_enqueue_script( 'preview-ai-demo-tour' );
?>
