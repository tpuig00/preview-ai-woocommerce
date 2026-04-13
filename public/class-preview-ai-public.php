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
			$this->Preview_Ai . '-storage',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-storage.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->Preview_Ai . '-image',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-image.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->Preview_Ai . '-tryons',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-tryons.js',
			array( 'jquery', $this->Preview_Ai . '-storage' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->Preview_Ai . '-modal',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-modal.js',
			array( 'jquery', $this->Preview_Ai . '-storage', $this->Preview_Ai . '-image' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->Preview_Ai . '-api',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-api.js',
			array( 'jquery', $this->Preview_Ai . '-storage' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->Preview_Ai,
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-public.js',
			array( 'jquery', $this->Preview_Ai . '-storage', $this->Preview_Ai . '-image', $this->Preview_Ai . '-tryons', $this->Preview_Ai . '-modal', $this->Preview_Ai . '-api' ),
			$this->version,
			true
		);

		// Register demo tour scripts and styles.
		wp_register_style(
			'preview-ai-demo-tour',
			plugin_dir_url( __FILE__ ) . 'css/preview-ai-demo-tour.css',
			array(),
			$this->version,
			'all'
		);

		wp_register_script(
			'preview-ai-demo-tour',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-demo-tour.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'preview-ai-demo-tour',
			'previewAiDemo',
			array(
				'i18n' => array(
					'demoTour'        => __( 'Demo Tour', 'preview-ai' ),
					'skip'            => __( 'Skip', 'preview-ai' ),
					'next'            => __( 'Next', 'preview-ai' ),
					'tryItNow'        => __( 'Try it now!', 'preview-ai' ),
					'step1Title'      => __( '🎨 Preview AI Understands Your Catalog', 'preview-ai' ),
					'step1Text'       => __( 'Our AI analyzes your <strong>product images</strong>, including all <strong>variations and color options</strong>. If you have photos for each variant, Preview AI will use them for more accurate try-ons.', 'preview-ai' ),
					'autoDetection'   => __( 'Auto-detection', 'preview-ai' ),
					'realTime'        => __( 'Real-time', 'preview-ai' ),
					'allColors'       => __( 'All colors', 'preview-ai' ),
					'step2Title'      => __( '✨ Your Customers See This', 'preview-ai' ),
					'step2Text'       => __( 'This widget is <strong>fully customizable</strong> and adapts to your store\'s design. Customers click here to instantly try on your products using their own photo.', 'preview-ai' ),
					'customizable'    => __( 'Customizable', 'preview-ai' ),
					'responsive'      => __( 'Responsive', 'preview-ai' ),
					'oneClick'        => __( 'One-click', 'preview-ai' ),
				),
			)
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML template output; all dynamic values escaped with esc_html/esc_attr/esc_url in preview-ai-public-display.php.
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
		if ( ! $product_id || ! self::is_enabled_for_product( $product_id ) ) {
			return '';
		}

		// Get product data for looks history.
		$product           = wc_get_product( $product_id );
		$product_name      = $product ? $product->get_name() : '';
		$product_url       = $product ? get_permalink( $product_id ) : '';
		$product_image_url = '';
		if ( $product ) {
			$image_id = $product->get_image_id();
			if ( $image_id ) {
				$product_image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
			}
		}

		// Enqueue assets.
		wp_enqueue_style( 'preview-ai' );
		wp_enqueue_script( 'preview-ai' );

		// Localize script data.
		wp_localize_script(
			'preview-ai',
			'previewAiData',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'preview_ai_ajax' ),
				'productId'          => $product_id,
				'variationId'        => '',
				'productName'        => $product_name,
				'productUrl'         => $product_url,
				'productImageUrl'    => $product_image_url,
				'maxPreviewsWeekly'  => PREVIEW_AI_Admin_Settings::get_max_previews_per_user_weekly(),
				'i18n'      => array(
					'error'              => __( 'Something went wrong. Please try again later.', 'preview-ai' ),
					'weeklyLimitReached' => __( 'You have used all your weekly previews! Come back next week to try on more products.', 'preview-ai' ),
					'openCamera'         => __( 'Open camera', 'preview-ai' ),
					'uploadPhoto'        => __( 'Upload photo', 'preview-ai' ),
					'checkingPhoto'      => __( 'Checking photo…', 'preview-ai' ),
					'photoOk'            => __( 'Photo looks good.', 'preview-ai' ),
					'photoWarning'       => __( 'Photo is valid, but could be improved.', 'preview-ai' ),
					'photoBad'           => __( 'Photo is not valid. Please try another one.', 'preview-ai' ),
					'warningCodes'  => array(
						// Generic / heuristic checks (backend/src/api/routes/generate.py).
						'LOW_RESOLUTION' => __( 'The photo has low resolution; Try to use a photo with a higher resolution.', 'preview-ai' ),
						'LANDSCAPE'      => __( 'The photo looks landscape; it usually works better in portrait (front-facing body).', 'preview-ai' ),
						'LOW_LIGHT'      => __( 'The photo is a bit dark; try more front-facing light.', 'preview-ai' ),
						'OVEREXPOSED'    => __( 'The photo is overexposed; avoid strong backlight or very direct light.', 'preview-ai' ),

						// Pose checks (backend/src/services/pose_check.py).
						'NO_POSE'                => __( 'No person is clearly detected.', 'preview-ai' ),
						'MULTI_PERSON'           => __( 'The photo seems to contain more than one person.', 'preview-ai' ),
						'TIP_LIGHT'              => __( 'Try a front-facing photo with good lighting.', 'preview-ai' ),
						'TIP_CROP'               => __( 'Avoid heavy crops and occlusions.', 'preview-ai' ),
						'TIP_SINGLE'             => __( 'Use a front-facing photo with a single person.', 'preview-ai' ),
						'TIP_BG'                 => __( 'Avoid people in the background, mirrors, or posters with people.', 'preview-ai' ),
						'LOW_TORSO_CONF'          => __( 'Torso is not detected clearly; try a front-facing photo with the torso visible.', 'preview-ai' ),
						'LOW_ARMS_CONF'           => __( 'Arms are not detected clearly. Try to include shoulders and arms.', 'preview-ai' ),
						'ARMS_RAISED'            => __( 'Arms look raised. Please keep arms relaxed down.', 'preview-ai' ),
						'ARMS_BENT'              => __( 'Arms look bent. Relax your arms if possible.', 'preview-ai' ),
						'ARMS_CROSSED'           => __( 'Arms look crossed. Keep arms separated and relaxed.', 'preview-ai' ),
						'NO_TORSO_DETECTED'      => __( 'Upper torso is not detected. The photo may be cropped from the waist, which can still work for pants.', 'preview-ai' ),
						'LEGS_BENT'              => __( 'Legs look quite bent. Stand with straighter legs if possible.', 'preview-ai' ),
						'ANKLES_CROPPED'         => __( 'Feet/ankles look cropped. Please include them fully.', 'preview-ai' ),
						'NO_KNEES_DETECTED'      => __( 'Knees are not detected. It can still work, but for best results include from knees down to the full feet.', 'preview-ai' ),
						'HEAD_CROPPED'           => __( 'Head looks cropped. We need the full body in frame.', 'preview-ai' ),
						'FEET_CROPPED'           => __( 'Feet are cropped. We need the full body from head to toe.', 'preview-ai' ),
						'POSE_CROUCHED'          => __( 'Pose looks crouched or seated. Stand upright if possible.', 'preview-ai' ),
						'POSE_SIDEWAYS'          => __( 'Photo looks sideways. A front-facing photo works best.', 'preview-ai' ),
						'LEGS_TOO_BENT'          => __( 'Legs are too bent (seated/crouching). Stand with straighter legs.', 'preview-ai' ),
						'MISSING_LEFT_SHOULDER'  => __( 'Left shoulder is not detected clearly.', 'preview-ai' ),
						'MISSING_RIGHT_SHOULDER' => __( 'Right shoulder is not detected clearly.', 'preview-ai' ),
						'MISSING_LEFT_HIP'       => __( 'Left hip is not detected clearly.', 'preview-ai' ),
						'MISSING_RIGHT_HIP'      => __( 'Right hip is not detected clearly.', 'preview-ai' ),
						'MISSING_LEFT_KNEE'      => __( 'Left knee is not detected clearly.', 'preview-ai' ),
						'MISSING_RIGHT_KNEE'     => __( 'Right knee is not detected clearly.', 'preview-ai' ),
						'MISSING_LEFT_ANKLE'     => __( 'Left ankle/foot is not detected clearly.', 'preview-ai' ),
						'MISSING_RIGHT_ANKLE'    => __( 'Right ankle/foot is not detected clearly.', 'preview-ai' ),
					),
					// Your Looks section.
					'today'       => __( 'Today', 'preview-ai' ),
					'yesterday'   => __( 'Yesterday', 'preview-ai' ),
					'daysAgo'     => __( '{n} days ago', 'preview-ai' ),
					'result'      => __( 'result', 'preview-ai' ),
					'results'     => __( 'results', 'preview-ai' ),
					'addToCart'   => __( 'Add to Cart', 'preview-ai' ),
					'download'    => __( 'Download', 'preview-ai' ),
					'viewProduct' => __( 'View Product', 'preview-ai' ),
					'delete'      => __( 'Delete', 'preview-ai' ),
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

		// Button shape.
		$button_shape = ! empty( $widget_settings['button_shape'] ) ? $widget_settings['button_shape'] : 'pill';

		// Button height.
		$button_height = ! empty( $widget_settings['button_height'] ) ? absint( $widget_settings['button_height'] ) : 38;

		// Button full width.
		$button_full_width = ! empty( $widget_settings['button_full_width'] ) ? (int) $widget_settings['button_full_width'] : 0;

		// Get clothing subtype and tips for this product.
		$clothing_subtype  = get_post_meta( $product_id, '_preview_ai_recommended_subtype', true );
		$clothing_subtypes = PREVIEW_AI_Admin::get_clothing_subtypes();

		// Default to 'mixed' if no subtype set.
		if ( empty( $clothing_subtype ) || ! isset( $clothing_subtypes[ $clothing_subtype ] ) ) {
			$clothing_subtype = 'mixed';
		}

		$tips = $clothing_subtypes[ $clothing_subtype ]['tips'];

		// Prepare variables for the partial.
		$preview_ai_product_id        = $product_id;
		$preview_ai_button_text       = $button_text;
		$preview_ai_button_svg        = $button_svg;
		$preview_ai_button_position   = $button_position;
		$preview_ai_button_shape      = $button_shape;
		$preview_ai_button_height     = $button_height;
		$preview_ai_button_full_width = $button_full_width;
		$preview_ai_clothing_subtype  = $clothing_subtype;
		$preview_ai_tips              = $tips;

		ob_start();
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-public-display.php';
		return ob_get_clean();
	}

	/**
	 * Check if Preview AI is enabled for this product.
	 *
	 * Delegates to the single source of truth in PREVIEW_AI_Admin_Product.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public static function is_enabled_for_product( $product_id ) {
		$status = PREVIEW_AI_Admin_Product::resolve_product_status( $product_id );
		return $status['is_enabled'];
	}

}
