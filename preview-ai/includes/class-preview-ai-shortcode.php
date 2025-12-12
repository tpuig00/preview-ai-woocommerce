<?php
/**
 * Shortcode handler for Preview AI widget.
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
 * Class PREVIEW_AI_Shortcode
 *
 * Registers [preview_ai] shortcode.
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
				'product_id'    => 0,
				'primary_color' => '',
				'button_text'   => '',
				'upload_text'   => '',
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

		// Build branding overrides from shortcode atts.
		$branding = array_filter(
			array(
				'primary_color' => sanitize_hex_color( $atts['primary_color'] ),
				'button_text'   => sanitize_text_field( $atts['button_text'] ),
				'upload_text'   => sanitize_text_field( $atts['upload_text'] ),
			)
		);

		return PREVIEW_AI_Public::render_widget_output( $product_id, $branding );
	}
}

