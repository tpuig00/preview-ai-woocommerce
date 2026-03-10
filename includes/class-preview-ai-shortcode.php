<?php
/**
 * Shortcode handler for Preview AI widget.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PREVIEW_AI_Shortcode
 *
 * Registers [preview_ai] shortcode.
 *
 * Usage:
 * [preview_ai]
 * [preview_ai product_id="123"]
 * [preview_ai button_text="See it on you" button_icon="camera" button_position="left"]
 */
class PREVIEW_AI_Shortcode {

	/**
	 * Register the shortcode.
	 */
	public function __construct() {
		add_shortcode( 'preview_ai', array( $this, 'render' ) );
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'product_id'      => 0,
				'button_text'     => '',
				'button_icon'     => '',
				'button_position' => '',
			),
			$atts,
			'preview_ai'
		);

		// Get product ID from context if not provided.
		$product_id = absint( $atts['product_id'] );
		if ( ! $product_id ) {
			global $product;
			$product_id = $product ? $product->get_id() : 0;
		}

		if ( ! $product_id ) {
			return '';
		}

		// Build overrides from shortcode atts (only non-empty values).
		$overrides = array();

		if ( ! empty( $atts['button_text'] ) ) {
			$overrides['button_text'] = sanitize_text_field( $atts['button_text'] );
		}

		if ( ! empty( $atts['button_icon'] ) ) {
			$overrides['button_icon'] = sanitize_key( $atts['button_icon'] );
		}

		if ( ! empty( $atts['button_position'] ) ) {
			$overrides['button_position'] = sanitize_key( $atts['button_position'] );
		}

		return PREVIEW_AI_Public::render_widget_output( $product_id, $overrides );
	}
}
