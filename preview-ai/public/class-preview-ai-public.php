<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
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
 * @author     Your Name <email@example.com>
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

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PREVIEW_AI_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PREVIEW_AI_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->Preview_Ai, plugin_dir_url( __FILE__ ) . 'css/preview-ai-public.css', array(), $this->version, 'all' );

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
	 * @param array $branding   Optional branding overrides.
	 * @return string HTML output.
	 */
	public static function render_widget_output( $product_id, $branding = array() ) {
		if ( ! $product_id ) {
			return '';
		}

		// Merge with global branding settings.
		$defaults = PREVIEW_AI_Admin::get_branding_settings();
		$branding = wp_parse_args( $branding, $defaults );

		// Set default texts if empty.
		if ( empty( $branding['button_text'] ) ) {
			$branding['button_text'] = __( 'Generate', 'preview-ai' );
		}
		if ( empty( $branding['upload_text'] ) ) {
			$branding['upload_text'] = __( 'Upload your photo', 'preview-ai' );
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
					'noFile'  => esc_html__( 'Please select an image.', 'preview-ai' ),
					'loading' => esc_html__( 'Uploading...', 'preview-ai' ),
					'success' => esc_html__( 'Preview ready!', 'preview-ai' ),
					'error'   => esc_html__( 'Error occurred.', 'preview-ai' ),
				),
			)
		);

		// Custom CSS for branding.
		$custom_css = '';
		if ( ! empty( $branding['primary_color'] ) && '#111111' !== $branding['primary_color'] ) {
			$color = sanitize_hex_color( $branding['primary_color'] );
			if ( $color ) {
				$custom_css = sprintf(
					'<style>.preview-ai-widget-%d #preview-ai-submit{background:%s;}</style>',
					absint( $product_id ),
					esc_attr( $color )
				);
			}
		}

		ob_start();
		// CSS is sanitized above with sanitize_hex_color() and esc_attr().
		echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
