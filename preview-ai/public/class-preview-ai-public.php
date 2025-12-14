<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/public
 * @author     Preview AI <hello@previewai.app>
 */
class PREVIEW_AI_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $Preview_Ai    The ID of this plugin.
	 */
	private $Preview_Ai;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $Preview_Ai       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $Preview_Ai, $version ) {

		$this->Preview_Ai = $Preview_Ai;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->Preview_Ai, plugin_dir_url( __FILE__ ) . 'css/preview-ai-public.css', array(), $this->version, 'all' );

		// Add custom accent color if set.
		$accent_color = get_option( 'preview_ai_accent_color', '#3b82f6' );

		if ( '#3b82f6' !== $accent_color && ! empty( $accent_color ) ) {
			wp_add_inline_style( $this->Preview_Ai, ':root { --pai-accent: ' . esc_attr( $accent_color ) . '; }' );
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script(
			$this->Preview_Ai,
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);

	}

	/**
	 * Render the widget in product detail page (via WooCommerce hook).
	 *
	 * @since 1.0.0
	 */
	public function render_widget() {
		// Check if widget is available (API key + credits).
		if ( ! PREVIEW_AI_Api::is_widget_available() ) {
			return;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		// Check display mode.
		$display_mode = get_option( 'preview_ai_display_mode', 'auto' );
		if ( 'manual' === $display_mode ) {
			return;
		}

		global $product;

		if ( ! $product || ! $product->get_id() ) {
			return;
		}

		if ( ! self::is_enabled_for_product( $product->get_id() ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in render method.
		echo self::render_widget_output( $product->get_id() );
	}

	/**
	 * Render widget output (static for shortcode/Elementor use).
	 *
	 * @since 1.0.0
	 * @param int   $product_id Product ID.
	 * @param array $overrides  Optional. Override default settings.
	 * @return string HTML output.
	 */
	public static function render_widget_output( $product_id, $overrides = array() ) {
		// Check if widget is available (API key + credits).
		if ( ! PREVIEW_AI_Api::is_widget_available() ) {
			return '';
		}

		if ( ! $product_id ) {
			return '';
		}

		// Enqueue assets.
		wp_enqueue_style( 'preview-ai' );
		wp_enqueue_script( 'preview-ai' );

		// Localize script data.
		wp_localize_script(
			'preview-ai',
			'previewAiData',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'preview_ai_ajax' ),
				'productId'   => $product_id,
				'variationId' => '',
			'i18n'        => array(
				'error'       => __( 'Something went wrong. Please try again later.', 'preview-ai' ),
				'openCamera'  => __( 'Open camera', 'preview-ai' ),
				'uploadPhoto' => __( 'Upload photo', 'preview-ai' ),
			),
			)
		);

		// Get widget settings and merge with overrides.
		$widget_settings = PREVIEW_AI_Admin::get_widget_settings();
		$widget_settings = wp_parse_args( $overrides, $widget_settings );
		$button_icons    = PREVIEW_AI_Admin::get_button_icons();

		// Prepare button text.
		$button_text = ! empty( $widget_settings['button_text'] )
			? $widget_settings['button_text']
			: __( 'See it on you', 'preview-ai' );

		// Prepare button icon SVG.
		$icon_key   = ! empty( $widget_settings['button_icon'] ) ? $widget_settings['button_icon'] : 'wand';
		$button_svg = isset( $button_icons[ $icon_key ] ) ? $button_icons[ $icon_key ]['svg'] : $button_icons['wand']['svg'];

		// Button position.
		$button_position = ! empty( $widget_settings['button_position'] ) ? $widget_settings['button_position'] : 'center';

		// Get clothing subtype and tips for this product.
		$clothing_subtype  = get_post_meta( $product_id, '_preview_ai_recommended_subtype', true );
		$clothing_subtypes = PREVIEW_AI_Admin::get_clothing_subtypes();

		// Default to 'mixed' if no subtype set.
		if ( empty( $clothing_subtype ) || ! isset( $clothing_subtypes[ $clothing_subtype ] ) ) {
			$clothing_subtype = 'mixed';
		}

		$tips = $clothing_subtypes[ $clothing_subtype ]['tips'];

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-public-display.php';
		return ob_get_clean();
	}

	/**
	 * Check if Preview AI is enabled for this product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_enabled_for_product( $product_id ) {
		$enabled = get_post_meta( $product_id, '_preview_ai_enabled', true );

		if ( '' === $enabled ) {
			return (bool) get_option( 'preview_ai_enabled', 0 );
		}

		return 'yes' === $enabled;
	}

}
