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
	 * Render the widget in product detail page.
	 *
	 * @since 1.0.0
	 */
	public function render_widget() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product || ! $product->get_id() ) {
			return;
		}

		if ( ! $this->is_enabled_for_product( $product->get_id() ) ) {
			return;
		}

		wp_localize_script(
			$this->Preview_Ai,
			'previewAiData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'preview_ai_ajax' ),
				'productId' => $product->get_id(),
				'variationId' => '',
				'i18n'      => array(
					'noFile'  => esc_html__( 'Please select an image.', 'preview-ai' ),
					'loading' => esc_html__( 'Uploading...', 'preview-ai' ),
					'success' => esc_html__( 'Preview ready!', 'preview-ai' ),
					'error'   => esc_html__( 'Error occurred.', 'preview-ai' ),
				),
			)
		);

		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-public-display.php';
	}

	/**
	 * Check if Preview AI is enabled for this product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function is_enabled_for_product( $product_id ) {
		$enabled = get_post_meta( $product_id, '_preview_ai_enabled', true );

		if ( '' === $enabled ) {
			return (bool) get_option( 'preview_ai_enabled', 0 );
		}

		return 'yes' === $enabled;
	}

}
