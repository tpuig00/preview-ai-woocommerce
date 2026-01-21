<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle admin settings and options.
 */
class PREVIEW_AI_Admin_Settings {

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		// General settings group.
		register_setting( 'preview_ai_general_settings', 'preview_ai_api_key', 'sanitize_text_field' );
		register_setting( 'preview_ai_general_settings', 'preview_ai_enabled', 'absint' );

		// Widget settings group.
		register_setting( 'preview_ai_widget_settings', 'preview_ai_display_mode', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_text', 'sanitize_text_field' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_icon', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_position', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_shape', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_height', 'absint' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_full_width', 'absint' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_accent_color', 'sanitize_hex_color' );

		// Clear account status when API key changes.
		add_action( 'update_option_preview_ai_api_key', array( 'PREVIEW_AI_Api', 'clear_account_status' ) );
	}

	/**
	 * Get available button icons.
	 */
	public static function get_button_icons() {
		return array(
			'wand'   => array(
				'label' => __( 'Magic Wand', 'preview-ai' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8L19 13"/><path d="M15 9h.01"/><path d="M17.8 6.2L19 5"/><path d="M3 21l9-9"/><path d="M12.2 6.2L11 5"/></svg>',
			),
			'camera' => array(
				'label' => __( 'Camera', 'preview-ai' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>',
			),
			'eye'    => array(
				'label' => __( 'Eye / Preview', 'preview-ai' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
			),
			'shirt'  => array(
				'label' => __( 'T-Shirt', 'preview-ai' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"/></svg>',
			),
			'spark'  => array(
				'label' => __( 'Sparkles / AI', 'preview-ai' ),
				'svg'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/></svg>',
			),
		);
	}

	/**
	 * Get widget settings with defaults.
	 */
	public static function get_widget_settings() {
		return array(
			'button_text'     => get_option( 'preview_ai_button_text', '' ),
			'button_icon'     => get_option( 'preview_ai_button_icon', 'wand' ),
			'button_position' => get_option( 'preview_ai_button_position', 'center' ),
			'button_shape'    => get_option( 'preview_ai_button_shape', 'pill' ),
			'button_height'   => get_option( 'preview_ai_button_height', 38 ),
			'button_full_width' => get_option( 'preview_ai_button_full_width', 0 ),
			'accent_color'    => get_option( 'preview_ai_accent_color', '#3b82f6' ),
		);
	}

	/**
	 * Get available product types for AI context.
	 */
	public static function get_product_types() {
		return array(
			'clothing' => array(
				'label'     => __( 'Clothing', 'preview-ai' ),
				'available' => true,
			),
			'furniture' => array(
				'label'     => __( 'Furniture', 'preview-ai' ),
				'available' => false,
			),
			'decoration' => array(
				'label'     => __( 'Decoration', 'preview-ai' ),
				'available' => false,
			),
			'crafts' => array(
				'label'     => __( 'Crafts', 'preview-ai' ),
				'available' => false,
			),
			'generic' => array(
				'label'     => __( 'Other (Generic)', 'preview-ai' ),
				'available' => false,
			),
		);
	}

	/**
	 * Get clothing subtypes with example items and tips.
	 */
	public static function get_clothing_subtypes() {
		return array(
			'mixed' => array(
				'label'    => __( 'Mixed / All types', 'preview-ai' ),
				'examples' => __( 'All types of clothing', 'preview-ai' ),
				'tips'     => array(
					__( 'One person only', 'preview-ai' ),
					__( 'Front-facing, good lighting', 'preview-ai' ),
					__( 'Avoid heavy cropping and occlusions', 'preview-ai' ),
				),
			),
			'upper_body' => array(
				'label'    => __( 'Upper Body', 'preview-ai' ),
				'examples' => __( 'T-shirts, shirts, blouses, jackets, hoodies, tops', 'preview-ai' ),
				'tips'     => array(
					__( 'Front-facing with shoulders and torso visible', 'preview-ai' ),
					__( 'Arms relaxed', 'preview-ai' ),
					__( 'Good light, no torso occlusions', 'preview-ai' ),
				),
			),
			'lower_body' => array(
				'label'    => __( 'Lower Body', 'preview-ai' ),
				'examples' => __( 'Pants, shorts, leggings, skirts', 'preview-ai' ),
				'tips'     => array(
					__( 'Hips, knees and full feet visible (no crop)', 'preview-ai' ),
					__( 'Standing, front-facing', 'preview-ai' ),
					__( 'Good light, no leg occlusions', 'preview-ai' ),
				),
			),
			'full_body' => array(
				'label'    => __( 'Full Body', 'preview-ai' ),
				'examples' => __( 'Dresses, jumpsuits, full suits', 'preview-ai' ),
				'tips'     => array(
					__( 'Full body head-to-toe (no crop)', 'preview-ai' ),
					__( 'Front-facing and upright (avoid side pose or crouching)', 'preview-ai' ),
					__( 'One person, simple background, good light', 'preview-ai' ),
				),
			),
			'headwear' => array(
				'label'    => __( 'Headwear', 'preview-ai' ),
				'examples' => __( 'Caps, hats, berets, head scarves', 'preview-ai' ),
				'tips'     => array(
					__( 'Include head plus some torso', 'preview-ai' ),
					__( 'Front-facing, good lighting', 'preview-ai' ),
					__( 'No occlusions over the head (hair/hands/objects)', 'preview-ai' ),
				),
			),
			'footwear' => array(
				'label'    => __( 'Footwear', 'preview-ai' ),
				'examples' => __( 'Shoes, boots, sandals, slippers', 'preview-ai' ),
				'tips'     => array(
					__( 'Both feet fully visible (not cropped)', 'preview-ai' ),
					__( 'Best framing: knees-to-feet, front-facing', 'preview-ai' ),
					__( 'Good light, sharp photo', 'preview-ai' ),
				),
			),
			'neckwear' => array(
				'label'    => __( 'Neckwear', 'preview-ai' ),
				'examples' => __( 'Necklaces, scarves, chokers', 'preview-ai' ),
				'tips'     => array(
					__( 'Include face, neck and some torso (no extreme close-up)', 'preview-ai' ),
					__( 'Front-facing, good lighting', 'preview-ai' ),
					__( 'No occlusions over the accessory', 'preview-ai' ),
				),
			),
			'waistwear' => array(
				'label'    => __( 'Waistwear', 'preview-ai' ),
				'examples' => __( 'Belts, fanny packs, waist bags', 'preview-ai' ),
				'tips'     => array(
					__( 'Front-facing with waist and hips visible', 'preview-ai' ),
					__( 'No hands/objects covering the waist area', 'preview-ai' ),
					__( 'One person, good lighting', 'preview-ai' ),
				),
			),
			'wrist_hand' => array(
				'label'    => __( 'Wrist & Hand', 'preview-ai' ),
				'examples' => __( 'Bracelets, watches, rings', 'preview-ai' ),
				'tips'     => array(
					__( 'Include arm and some torso (no extreme close-up)', 'preview-ai' ),
					__( 'Keep the accessory visible (no motion blur)', 'preview-ai' ),
					__( 'Good light, simple background', 'preview-ai' ),
				),
			),
			'ear' => array(
				'label'    => __( 'Ear Accessories', 'preview-ai' ),
				'examples' => __( 'Earrings, hoops, ear cuffs', 'preview-ai' ),
				'tips'     => array(
					__( 'Include face and some torso (no extreme close-up)', 'preview-ai' ),
					__( 'Good light, ear not covered by hair/hands', 'preview-ai' ),
					__( 'One person only', 'preview-ai' ),
				),
			),
		);
	}

	/**
	 * Sanitize SVG content using wp_kses.
	 */
	public static function kses_svg( $svg ) {
		return wp_kses(
			$svg,
			array(
				'svg'      => array(
					'viewbox'         => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'xmlns'           => true,
					'width'           => true,
					'height'          => true,
					'class'           => true,
					'style'           => true,
				),
				'path'     => array(
					'd'               => true,
					'fill'            => true,
					'stroke'          => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
				),
				'circle'   => array(
					'cx'     => true,
					'cy'     => true,
					'r'      => true,
					'fill'   => true,
					'stroke' => true,
				),
				'rect'     => array(
					'x'      => true,
					'y'      => true,
					'width'  => true,
					'height' => true,
					'rx'     => true,
					'fill'   => true,
				),
				'line'     => array(
					'x1'     => true,
					'y1'     => true,
					'x2'     => true,
					'y2'     => true,
					'stroke' => true,
				),
				'polyline' => array(
					'points' => true,
					'fill'   => true,
					'stroke' => true,
				),
			)
		);
	}
}

