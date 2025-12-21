<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/admin
 */

class PREVIEW_AI_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

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
	 * @param    string $plugin_name    The name of this plugin.
	 * @param    string $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the admin menu under Products.
	 *
	 * @since    1.0.0
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Preview AI', 'preview-ai' ),
			__( 'Preview AI', 'preview-ai' ),
			'manage_woocommerce',
			'preview-ai',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		// General settings group.
		register_setting( 'preview_ai_general_settings', 'preview_ai_api_endpoint', 'esc_url_raw' );
		register_setting( 'preview_ai_general_settings', 'preview_ai_api_key', 'sanitize_text_field' );
		register_setting( 'preview_ai_general_settings', 'preview_ai_enabled', 'absint' );
		register_setting( 'preview_ai_general_settings', 'preview_ai_product_type', 'sanitize_key' );
		register_setting( 'preview_ai_general_settings', 'preview_ai_clothing_subtype', 'sanitize_key' );

		// Widget settings group.
		register_setting( 'preview_ai_widget_settings', 'preview_ai_display_mode', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_text', 'sanitize_text_field' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_icon', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_button_position', 'sanitize_key' );
		register_setting( 'preview_ai_widget_settings', 'preview_ai_accent_color', 'sanitize_hex_color' );

		// Clear account status when API key changes.
		add_action( 'update_option_preview_ai_api_key', array( 'PREVIEW_AI_Api', 'clear_account_status' ) );
	}

	/**
	 * Get available button icons.
	 *
	 * @since    1.0.0
	 * @return   array
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
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_widget_settings() {
		return array(
			'button_text'     => get_option( 'preview_ai_button_text', '' ),
			'button_icon'     => get_option( 'preview_ai_button_icon', 'wand' ),
			'button_position' => get_option( 'preview_ai_button_position', 'center' ),
			'accent_color'    => get_option( 'preview_ai_accent_color', '#3b82f6' ),
		);
	}

	/**
	 * Get available product types for AI context.
	 *
	 * @since    1.0.0
	 * @return   array    List of product types with availability status.
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
	 *
	 * @since    1.0.0
	 * @return   array    List of clothing subtypes.
	 */
	public static function get_clothing_subtypes() {
		return array(
			'mixed' => array(
				'label'    => __( 'Mixed / All types', 'preview-ai' ),
				'examples' => __( 'Set the correct subtype per product for precise results', 'preview-ai' ),
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
	 * Render the settings page.
	 *
	 * @since    1.0.0
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Check if coming from onboarding (just registered).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_onboarding = isset( $_GET['onboarding'] ) && 'complete' === $_GET['onboarding'];

		if ( $is_onboarding ) {
			// Add script to auto-trigger catalog analysis.
			add_action( 'admin_footer', array( $this, 'render_onboarding_wizard' ) );
		}

		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
	}

	/**
	 * Render onboarding wizard that auto-analyzes catalog.
	 *
	 * This is shown after user completes registration and is redirected
	 * to the settings page. It automatically triggers catalog analysis
	 * and shows progress/results.
	 *
	 * @since 1.0.0
	 */
	public function render_onboarding_wizard() {
		?>
		<div id="preview-ai-onboarding-wizard" style="position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100000;display:flex;align-items:center;justify-content:center;">
			<div style="background:#fff;border-radius:16px;padding:48px;max-width:520px;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
				<div style="width:72px;height:72px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;margin:0 auto 24px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 25px rgba(34,197,94,0.3);">
					<svg width="36" height="36" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
				</div>
				<h2 style="margin:0 0 8px;font-size:26px;color:#1e293b;font-weight:700;">🎉 <?php esc_html_e( 'Preview AI Activated!', 'preview-ai' ); ?></h2>
				<p style="color:#64748b;margin:0 0 32px;font-size:15px;"><?php esc_html_e( 'Setting up your store...', 'preview-ai' ); ?></p>
				
				<div id="onboarding-progress" style="margin-bottom:32px;">
					<div style="height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden;">
						<div id="onboarding-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#8b5cf6);transition:width 0.5s ease;"></div>
					</div>
					<p id="onboarding-status" style="margin:16px 0 0;color:#64748b;font-size:14px;"><?php esc_html_e( 'Analyzing your product catalog...', 'preview-ai' ); ?></p>
				</div>
				
				<div id="onboarding-result" style="display:none;"></div>
			</div>
		</div>

		<script>
		(function($) {
			'use strict';
			
			var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'preview_ai_learn_catalog' ) ); ?>';
			
			var $bar = $('#onboarding-bar');
			var $status = $('#onboarding-status');
			var $result = $('#onboarding-result');
			var $progress = $('#onboarding-progress');
			
			setTimeout(function() { $bar.css('width', '20%'); }, 200);
			setTimeout(function() { $bar.css('width', '40%'); }, 600);
			
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_learn_catalog',
					nonce: nonce
				},
				beforeSend: function() {
					$bar.css('width', '60%');
					$status.text('<?php echo esc_js( __( 'Configuring products...', 'preview-ai' ) ); ?>');
				},
				success: function(response) {
					$bar.css('width', '100%');
					
					setTimeout(function() {
						$progress.slideUp(300);
						
						if (response.success) {
							var configured = response.data.configured || 0;
							var total = response.data.total || 0;
							var isLimited = response.data.is_limited || false;
							var totalReceived = response.data.total_received || 0;
							
							var limitedNotice = '';
							if (isLimited) {
								limitedNotice = '<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:12px;margin-top:12px;">' +
									'<p style="color:#92400e;margin:0;font-size:13px;">⚡ <?php echo esc_js( __( 'Free trial: Only 3 random products were analyzed.', 'preview-ai' ) ); ?> ' +
									'</div>';
							}
							
							$result.html(
								'<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;margin-bottom:24px;">' +
								'<p style="color:#166534;font-weight:600;margin:0;font-size:16px;">✓ ' + 
								'<?php echo esc_js( __( 'Catalog configured!', 'preview-ai' ) ); ?></p>' +
								'<p style="color:#15803d;margin:8px 0 0;font-size:14px;">' + configured + ' <?php echo esc_js( __( 'products ready for preview', 'preview-ai' ) ); ?></p>' +
								limitedNotice +
								'</div>' +
								'<div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">' +
								'<a href="edit.php?post_type=product" class="button button-primary" style="height:auto;padding:12px 24px;font-size:14px;">' +
								'<?php echo esc_js( __( 'View Products', 'preview-ai' ) ); ?> →</a>' +
								'<button type="button" class="button" style="height:auto;padding:12px 24px;font-size:14px;" onclick="jQuery(\'#preview-ai-onboarding-wizard\').fadeOut(300)">' +
								'<?php echo esc_js( __( 'Explore Settings', 'preview-ai' ) ); ?></button>' +
								'</div>'
							).slideDown(300);
						} else {
							$result.html(
								'<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:24px;">' +
								'<p style="color:#dc2626;font-weight:600;margin:0;">' + (response.data.message || '<?php echo esc_js( __( 'Could not analyze catalog', 'preview-ai' ) ); ?>') + '</p>' +
								'<p style="color:#b91c1c;margin:8px 0 0;font-size:14px;"><?php echo esc_js( __( 'You can configure products manually.', 'preview-ai' ) ); ?></p>' +
								'</div>' +
								'<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;" onclick="jQuery(\'#preview-ai-onboarding-wizard\').fadeOut(300)">' +
								'<?php echo esc_js( __( 'Continue to Settings', 'preview-ai' ) ); ?></button>'
							).slideDown(300);
						}
					}, 500);
				},
				error: function() {
					$bar.css('width', '100%');
					$progress.slideUp(300);
					
					$result.html(
						'<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:20px;margin-bottom:24px;">' +
						'<p style="color:#92400e;font-weight:600;margin:0;"><?php echo esc_js( __( 'Could not connect to server', 'preview-ai' ) ); ?></p>' +
						'<p style="color:#a16207;margin:8px 0 0;font-size:14px;"><?php echo esc_js( __( 'You can analyze your catalog later from settings.', 'preview-ai' ) ); ?></p>' +
						'</div>' +
						'<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;" onclick="jQuery(\'#preview-ai-onboarding-wizard\').fadeOut(300)">' +
						'<?php echo esc_js( __( 'Continue', 'preview-ai' ) ); ?></button>'
					).slideDown(300);
				}
			});
			
			// Clean URL (remove onboarding param).
			if (history.replaceState) {
				var cleanUrl = window.location.href
					.replace(/[?&]onboarding=complete/, '')
					.replace(/\?$/, '');
				history.replaceState(null, '', cleanUrl);
			}
			
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/preview-ai-admin.css',
			array(),
			$this->version,
			'all'
		);

		// Color picker styles for settings page.
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook    The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			$this->version,
			true
		);

		// Only localize script data on Preview AI settings page or product edit pages.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_settings_page = ( 'product_page_preview-ai' === $hook || ( isset( $_GET['page'] ) && 'preview-ai' === $_GET['page'] ) );
		$is_product_page  = ( 'post.php' === $hook || 'post-new.php' === $hook );

		// Always localize for onboarding notice.
		wp_localize_script(
			$this->plugin_name,
			'previewAiAdmin',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'preview_ai_learn_catalog' ),
				'verifyNonce'   => wp_create_nonce( 'preview_ai_verify_api_key' ),
				'dismissNonce'  => wp_create_nonce( 'preview_ai_dismiss_notice' ),
				'registerNonce' => wp_create_nonce( 'preview_ai_register_site' ),
				'i18n'          => array(
					'error'        => __( 'An error occurred.', 'preview-ai' ),
					'apiPending'   => __( '(API integration pending)', 'preview-ai' ),
					'activating'   => __( 'Activating...', 'preview-ai' ),
					'activated'    => __( 'Preview AI activated! Redirecting...', 'preview-ai' ),
				),
			)
		);
	}

	/**
	 * Add Preview AI tab to WooCommerce Product Data.
	 *
	 * @since    1.0.0
	 * @param    array $tabs    Existing product data tabs.
	 * @return   array          Modified tabs.
	 */
	public function add_product_data_tab( $tabs ) {
		$tabs['preview_ai'] = array(
			'label'    => __( 'Preview AI', 'preview-ai' ),
			'target'   => 'preview_ai_product_data',
			'priority' => 80,
		);
		return $tabs;
	}

	/**
	 * Render Preview AI tab content in Product Data.
	 *
	 * @since    1.0.0
	 */
	public function render_product_data_panel() {
		global $post;
		$product        = wc_get_product( $post->ID );
		$has_image      = $product && $product->get_image_id();
		$enabled        = get_post_meta( $post->ID, '_preview_ai_enabled', true );
		$subtype        = get_post_meta( $post->ID, '_preview_ai_recommended_subtype', true );
		$global_enabled = get_option( 'preview_ai_enabled', 0 );

		// Determine current state.
		$is_enabled = false;
		if ( 'yes' === $enabled ) {
			$is_enabled = true;
		} elseif ( 'no' === $enabled ) {
			$is_enabled = false;
		} else {
			$is_enabled = (bool) $global_enabled;
		}

		// Can't be enabled without image.
		if ( ! $has_image ) {
			$is_enabled = false;
		}

		// Determine status for display.
		$status_class = 'preview-ai-col--disabled';
		$status_text  = __( 'Disabled', 'preview-ai' );
		$status_icon  = '—';

		if ( ! $has_image ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'No image', 'preview-ai' );
			$status_icon  = '⚠️';
		} elseif ( 'no' === $enabled ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'Disabled', 'preview-ai' );
			$status_icon  = '—';
		} elseif ( $is_enabled && ! empty( $subtype ) ) {
			$status_class = 'preview-ai-col--active';
			$status_text  = __( 'Active', 'preview-ai' );
			$status_icon  = '<span class="dashicons dashicons-visibility"></span>';
		} else {
			$status_class = 'preview-ai-col--configure';
			$status_text  = __( 'Configure', 'preview-ai' );
			$status_icon  = '<span class="dashicons dashicons-admin-generic"></span>';
		}

		?>
		<div id="preview_ai_product_data" class="panel woocommerce_options_panel">

			<?php if ( ! $has_image ) : ?>
			<div class="preview-ai-notice" style="margin: 12px; background: #fff8e5; border-left: 4px solid #ffb900;">
				<span class="preview-ai-notice__icon">⚠️</span>
				<div class="preview-ai-notice__content">
					<?php esc_html_e( 'This product has no image. Add a product image to enable Preview AI.', 'preview-ai' ); ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Status + Toggle row -->
			<div class="preview-ai-metabox-header">
				<div class="preview-ai-metabox-header__left">
					<span class="preview-ai-metabox-header__title"><?php esc_html_e( 'Preview AI', 'preview-ai' ); ?></span>
					<span class="preview-ai-col <?php echo esc_attr( $status_class ); ?>">
						<?php echo $status_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
						<?php echo esc_html( $status_text ); ?>
					</span>
				</div>
				<label class="preview-ai-switch">
					<input type="checkbox" 
						   id="_preview_ai_enabled" 
						   name="_preview_ai_enabled" 
						   value="yes" 
						   <?php checked( $is_enabled ); ?> 
						   <?php disabled( ! $has_image ); ?>
					/>
					<span class="preview-ai-switch__track"></span>
				</label>
			</div>

			<?php

			// Clothing subtype select.
			$subtype_options   = array( '' => __( 'Select clothing type...', 'preview-ai' ) );
			$clothing_subtypes = self::get_clothing_subtypes();
			foreach ( $clothing_subtypes as $key => $data ) {
				$subtype_options[ $key ] = $data['label'] . ' — ' . $data['examples'];
			}

			woocommerce_wp_select(
				array(
					'id'          => '_preview_ai_clothing_subtype',
					'label'       => __( 'Clothing Type', 'preview-ai' ),
					'options'     => $subtype_options,
					'value'       => $subtype,
					'desc_tip'    => true,
					'description' => __( 'Select what type of clothing this product is. This helps the AI generate accurate previews.', 'preview-ai' ),
				)
			);

		// Check if product has variations without images.
		$product                = wc_get_product( $post->ID );
		$has_shared_images      = false;

		if ( $product && $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				if ( empty( get_post_meta( $variation_id, '_thumbnail_id', true ) ) ) {
					$has_shared_images = true;
					break;
				}
			}
		}

		// Show notice only if there are variations sharing images.
		if ( $has_shared_images ) :
		?>
		<div class="options_group">
			<div class="preview-ai-notice">
				<span class="preview-ai-notice__icon">ℹ️</span>
				<div class="preview-ai-notice__content">
					<?php esc_html_e( "Some variations don't have their own image.", 'preview-ai' ); ?><br>
					<span class="preview-ai-notice__sub"><?php esc_html_e( 'Preview AI will use the main product image, so the preview will look the same for those variations.', 'preview-ai' ); ?></span><br>
					<a href="#" class="preview-ai-notice__link" title="<?php esc_attr_e( 'Add an image to each variation for more accurate previews.', 'preview-ai' ); ?>"><?php esc_html_e( 'See how to improve', 'preview-ai' ); ?> →</a>
				</div>
			</div>
		</div>
		<?php
		endif;
		?>
		</div>
		<?php
	}

	/**
	 * Save Preview AI product meta.
	 *
	 * @since    1.0.0
	 * @param    int $post_id    Product ID.
	 */
	public function save_product_data( $post_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
		$enabled = isset( $_POST['_preview_ai_enabled'] ) ? 'yes' : 'no';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$subtype = isset( $_POST['_preview_ai_clothing_subtype'] ) ? sanitize_key( $_POST['_preview_ai_clothing_subtype'] ) : '';

		// Can't enable if product has no image.
		if ( 'yes' === $enabled ) {
			$product = wc_get_product( $post_id );
			if ( $product && ! $product->get_image_id() ) {
				$enabled = 'no';
			}
		}

		update_post_meta( $post_id, '_preview_ai_enabled', $enabled );
		update_post_meta( $post_id, '_preview_ai_recommended_subtype', $subtype );
	}

	/**
	 * Add Preview AI column to product list.
	 *
	 * @since    1.0.0
	 * @param    array $columns    Existing columns.
	 * @return   array             Modified columns.
	 */
	public function add_product_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'name' === $key ) {
				$new_columns['preview_ai'] = __( 'Preview AI', 'preview-ai' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render Preview AI column content.
	 *
	 * @since    1.0.0
	 * @param    string $column     Column name.
	 * @param    int    $post_id    Product ID.
	 */
	public function render_product_column( $column, $post_id ) {
		if ( 'preview_ai' !== $column ) {
			return;
		}

		$enabled        = get_post_meta( $post_id, '_preview_ai_enabled', true );
		$subtype        = get_post_meta( $post_id, '_preview_ai_recommended_subtype', true );
		$global_enabled = get_option( 'preview_ai_enabled', 0 );

		// Determine if enabled.
		$is_enabled = false;
		if ( 'yes' === $enabled ) {
			$is_enabled = true;
		} elseif ( 'no' === $enabled ) {
			$is_enabled = false;
		} else {
			$is_enabled = (bool) $global_enabled;
		}

		// State A: Disabled / Not applicable.
		if ( 'no' === $enabled ) {
			echo '<span class="preview-ai-col preview-ai-col--disabled" title="' . esc_attr__( 'Preview AI disabled for this product', 'preview-ai' ) . '">—</span>';
			return;
		}

		// State B: Active (enabled + has subtype).
		if ( $is_enabled && ! empty( $subtype ) ) {
			$subtypes      = self::get_clothing_subtypes();
			$subtype_label = isset( $subtypes[ $subtype ] ) ? $subtypes[ $subtype ]['label'] : '';
			echo '<span class="preview-ai-col preview-ai-col--active" title="' . esc_attr__( 'Preview AI active on this product', 'preview-ai' ) . '">';
			echo '<span class="dashicons dashicons-visibility"></span> ';
			echo esc_html__( 'Active', 'preview-ai' );
			echo '</span>';
			return;
		}

		// State C: Needs configuration.
		echo '<span class="preview-ai-col preview-ai-col--configure" title="' . esc_attr__( 'Configure Preview AI for this product', 'preview-ai' ) . '">';
		echo '<span class="dashicons dashicons-admin-generic"></span> ';
		echo esc_html__( 'Configure', 'preview-ai' );
		echo '</span>';
	}

	/**
	 * Handle AJAX request for Learn My Catalog feature.
	 *
	 * @since    1.0.0
	 */
	public function handle_learn_catalog() {
		check_ajax_referer( 'preview_ai_learn_catalog', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'preview-ai' ) ) );
		}

		// Get all published products (parents only, no variations).
		$products_data = $this->get_catalog_products_data();

		if ( empty( $products_data ) ) {
			wp_send_json_error( array( 'message' => __( 'No products found to analyze.', 'preview-ai' ) ) );
		}

		// Send to API for classification.
		$api    = new PREVIEW_AI_Api();
		$result = $api->analyze_catalog( $products_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Process results and save as product meta.
		$stats = $this->save_catalog_classifications( $result );

		// Check if free tier limitation was applied.
		$is_limited      = isset( $result['is_limited'] ) && $result['is_limited'];
		$total_received  = isset( $result['total_received'] ) ? intval( $result['total_received'] ) : 0;
		$total_analyzed  = isset( $result['total_analyzed'] ) ? intval( $result['total_analyzed'] ) : 0;

		wp_send_json_success(
			array(
				'total'           => $stats['total'],
				'configured'      => $stats['configured'],
				'needs_review'    => $stats['needs_review'],
				'images_analyzed' => $stats['images_analyzed'],
				'is_limited'      => $is_limited,
				'total_received'  => $total_received,
				'total_analyzed'  => $total_analyzed,
				'message'         => sprintf(
					/* translators: 1: configured products, 2: products that couldn't be enabled, 3: images analyzed */
					__( '%1$d products configured. %2$d need review. %3$d images analyzed.', 'preview-ai' ),
					$stats['configured'],
					$stats['needs_review'],
					$stats['images_analyzed']
				),
			)
		);
	}

	/**
	 * Convert attachment to base64 data.
	 *
	 * @since    1.0.0
	 * @param    int $attachment_id Attachment ID.
	 * @return   array|null         Array with base64 and mime_type, or null on failure.
	 */
	private function get_attachment_base64( $attachment_id ) {
		if ( ! $attachment_id ) {
			return null;
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$image_data = file_get_contents( $file_path );
		if ( false === $image_data ) {
			return null;
		}

		$mime_type = get_post_mime_type( $attachment_id );
		if ( ! $mime_type ) {
			return null;
		}

		return array(
			'base64'    => base64_encode( $image_data ),
			'mime_type' => $mime_type,
		);
	}

	/**
	 * Get catalog products data for AI analysis.
	 *
	 * Includes parent products and variations with their own images.
	 * Each entry contains thumbnail as base64 for image analysis.
	 *
	 * @since    1.0.0
	 * @return   array    Array of products with id, title, categories, tags, thumbnail, variation_id.
	 */
	private function get_catalog_products_data() {
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
				'type'   => array( 'simple', 'variable' ),
			)
		);

		$products_data = array();

		foreach ( $products as $product ) {
			$product_id = $product->get_id();

			// Get categories.
			$categories     = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
			$categories_str = is_array( $categories ) ? implode( ', ', $categories ) : '';

			// Get tags.
			$tags     = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
			$tags_str = is_array( $tags ) ? implode( ', ', $tags ) : '';

			// Get parent product thumbnail as base64.
			$thumbnail_id = $product->get_image_id();
			$thumbnail    = $this->get_attachment_base64( $thumbnail_id );

			// Add parent product data.
			$products_data[] = array(
				'id'           => $product_id,
				'title'        => $product->get_name(),
				'categories'   => $categories_str,
				'tags'         => $tags_str,
				'thumbnail'    => $thumbnail,
				'variation_id' => null,
			);

			// If variable product, include variations with their own images.
			if ( $product->is_type( 'variable' ) ) {
				$variation_ids = $product->get_children();

				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( ! $variation ) {
						continue;
					}

					$var_image_id = $variation->get_image_id();

					// Only include if variation has a different image than parent.
					if ( $var_image_id && $var_image_id !== $thumbnail_id ) {
						$var_thumbnail = $this->get_attachment_base64( $var_image_id );

						$products_data[] = array(
							'id'           => $product_id,
							'title'        => $product->get_name(),
							'categories'   => $categories_str,
							'tags'         => $tags_str,
							'thumbnail'    => $var_thumbnail,
							'variation_id' => $variation_id,
						);
					}
				}
			}
		}

		return $products_data;
	}

	/**
	 * Save AI classifications as product meta.
	 *
	 * Saves subtype on parent product and image_analysis on variation or parent.
	 *
	 * @since    1.0.0
	 * @param    array $result    API response with classifications.
	 * @return   array            Statistics array with total, configured, needs_review, images_analyzed.
	 */
	private function save_catalog_classifications( $result ) {
		$stats = array(
			'total'           => 0,
			'configured'      => 0,
			'needs_review'    => 0,
			'images_analyzed' => 0,
		);

		if ( empty( $result['classifications'] ) || ! is_array( $result['classifications'] ) ) {
			return $stats;
		}

		$valid_subtypes   = array_keys( self::get_clothing_subtypes() );
		$processed_parents = array(); // Track which parents we've already processed for subtype.

		foreach ( $result['classifications'] as $classification ) {
			if ( empty( $classification['id'] ) ) {
				continue;
			}

			$product_id   = absint( $classification['id'] );
			$variation_id = isset( $classification['variation_id'] ) ? absint( $classification['variation_id'] ) : null;
			$subtype      = isset( $classification['subtype'] ) ? sanitize_key( $classification['subtype'] ) : '';

			// Only count and save subtype for parent products (once per parent).
			if ( ! in_array( $product_id, $processed_parents, true ) ) {
				$stats['total']++;
				$processed_parents[] = $product_id;

				if ( ! empty( $subtype ) && in_array( $subtype, $valid_subtypes, true ) ) {
					update_post_meta( $product_id, '_preview_ai_recommended_subtype', $subtype );
					$stats['configured']++;
				} else {
					update_post_meta( $product_id, '_preview_ai_enabled', 'no' );
					$stats['needs_review']++;
				}
			}

			// Save image_analysis on variation or parent.
			if ( ! empty( $classification['image_analysis'] ) ) {
				$analysis = $classification['image_analysis'];

				// Sanitize detected_objects array
				$detected_objects = array();
				if ( ! empty( $analysis['detected_objects'] ) && is_array( $analysis['detected_objects'] ) ) {
					$detected_objects = array_map( 'sanitize_text_field', $analysis['detected_objects'] );
				}

				$image_analysis = array(
					'has_model'        => ! empty( $analysis['has_model'] ),
					'shot_type'        => sanitize_key( $analysis['shot_type'] ?? 'unknown' ),
					'framing'          => sanitize_key( $analysis['framing'] ?? 'unknown' ),
					'multiple_garments' => ! empty( $analysis['multiple_garments'] ),
					'detected_objects'  => $detected_objects,
					'confidence'        => floatval( $analysis['confidence'] ?? 0.0 ),
					'image_id'          => absint( $analysis['image_id'] ?? 0 ),
					'updated_at'        => sanitize_text_field( $analysis['updated_at'] ?? current_time( 'Y-m-d' ) ),
				);

				// Save to variation if exists, otherwise to parent.
				$meta_target = $variation_id ? $variation_id : $product_id;
				update_post_meta( $meta_target, '_preview_ai_image_analysis', $image_analysis );
				$stats['images_analyzed']++;
			}
		}

		return $stats;
	}

	/**
	 * Handle AJAX request to verify API key.
	 *
	 * @since 1.0.0
	 */
	public function handle_verify_api_key() {
		check_ajax_referer( 'preview_ai_verify_api_key', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'preview-ai' ) ) );
		}

		// Use API key from field if provided, otherwise from DB.
		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : null;
		$api     = new PREVIEW_AI_Api( $api_key );
		$result  = $api->verify_api_key();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( $api_key ) {
			update_option( 'preview_ai_api_key', $api_key );
		}

		$tokens             = isset( $result['tokens_remaining'] ) ? intval( $result['tokens_remaining'] ) : 0;
		$period_end         = isset( $result['current_period_end'] ) ? $result['current_period_end'] : null;
		$renew_date         = $period_end ? date_i18n( 'F j, Y', strtotime( $period_end ) ) : '';
		$subscription_status = isset( $result['subscription_status'] ) ? sanitize_text_field( $result['subscription_status'] ) : null;

		wp_send_json_success( array(
			'tokens'             => $tokens,
			'renew_date'         => $renew_date,
			'subscription_status' => $subscription_status,
		) );
	}

	/**
	 * Display admin notices for API issues.
	 *
	 * @since 1.0.0
	 */
	public function display_admin_notices() {
		// Only show on relevant admin pages.
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		$relevant_screens = array( 'product_page_preview-ai', 'product', 'edit-product' );
		if ( ! in_array( $screen->id, $relevant_screens, true ) ) {
			return;
		}

		$api_key = get_option( 'preview_ai_api_key', '' );

		// No API key configured.
		if ( empty( $api_key ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( 'API key not configured. The widget is hidden from your customers.', 'preview-ai' ); ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai' ) ); ?>">
						<?php esc_html_e( 'Configure now', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		// Check account status.
		$status = PREVIEW_AI_Api::get_account_status();

		if ( empty( $status ) ) {
			return;
		}

		// No tokens left (only show if account is still active).
		if ( isset( $status['tokens_remaining'] ) && $status['tokens_remaining'] <= 0 &&
			( ! isset( $status['active'] ) || $status['active'] ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( '⚠️ Your tokens have run out. The widget has been automatically disabled and your customers cannot preview products.', 'preview-ai' ); ?>
					<a href="https://previewai.app/pricing" target="_blank" style="font-weight: bold;">
						<?php esc_html_e( 'Upgrade your plan →', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		// Account deactivated.
		if ( isset( $status['active'] ) && ! $status['active'] ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( '⚠️ Your subscription has been deactivated. The widget is hidden from your customers.', 'preview-ai' ); ?>
					<a href="https://previewai.app/support" target="_blank">
						<?php esc_html_e( 'Contact support', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		// Low tokens warning (less than 10%).
		if ( isset( $status['tokens_remaining'], $status['tokens_limit'] ) &&
			$status['tokens_limit'] > 0 &&
			( $status['tokens_remaining'] / $status['tokens_limit'] ) < 0.1 ) {
			
			$user_id = get_current_user_id();
			if ( get_user_meta( $user_id, 'preview_ai_dismissed_low_tokens', true ) ) {
				return;
			}
			?>
			<div class="notice notice-warning is-dismissible" data-notice="preview_ai_low_tokens">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php
					printf(
						/* translators: %d: tokens remaining */
						esc_html__( 'You have only %d tokens remaining this month.', 'preview-ai' ),
						intval( $status['tokens_remaining'] )
					);
					?>
					<a href="https://previewai.app/pricing" target="_blank">
						<?php esc_html_e( 'Upgrade your plan', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Handle AJAX request to dismiss admin notice.
	 *
	 * @since 1.0.0
	 */
	public function handle_dismiss_notice() {
		check_ajax_referer( 'preview_ai_dismiss_notice', 'nonce' );

		$notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';

		if ( 'preview_ai_low_tokens' === $notice ) {
			update_user_meta( get_current_user_id(), 'preview_ai_dismissed_low_tokens', true );
		}

		wp_send_json_success();
	}

	/**
	 * Handle AJAX request to register site for free trial.
	 *
	 * This is called when the user submits their email during
	 * the onboarding process. Creates a free-tier API key automatically.
	 *
	 * @since 1.0.0
	 */
	public function handle_register_site() {
		check_ajax_referer( 'preview_ai_register_site', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'preview-ai' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'preview-ai' ) ) );
		}

		// Call the registration API.
		$result = PREVIEW_AI_Api::register_site( $email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Save the API key.
		if ( isset( $result['api_key'] ) ) {
			update_option( 'preview_ai_api_key', sanitize_text_field( $result['api_key'] ) );
			delete_option( 'preview_ai_needs_onboarding' );

			// Update account status.
			PREVIEW_AI_Api::update_account_status( array(
				'tokens_remaining' => $result['tokens_limit'] ?? 0,
				'tokens_limit'     => $result['tokens_limit'] ?? 0,
				'active'           => true,
			) );
		}

		wp_send_json_success( array(
			'message'      => $result['message'] ?? __( 'Your free trial has been activated!', 'preview-ai' ),
			'tokens_limit' => $result['tokens_limit'] ?? 0,
		) );
	}

	/**
	 * Display onboarding notice for new installations.
	 *
	 * Shows an inline form to collect email and activate free trial.
	 *
	 * @since 1.0.0
	 */
	public function display_onboarding_notice() {
		// Only show if onboarding is needed and no API key exists.
		if ( ! get_option( 'preview_ai_needs_onboarding' ) ) {
			return;
		}

		$api_key = get_option( 'preview_ai_api_key', '' );
		if ( ! empty( $api_key ) ) {
			delete_option( 'preview_ai_needs_onboarding' );
			return;
		}

		// Get admin email as default.
		$admin_email = get_option( 'admin_email', '' );
		?>
		<div class="notice notice-info preview-ai-onboarding-notice" id="preview-ai-onboarding">
			<div class="preview-ai-onboarding__content">
				<div class="preview-ai-onboarding__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
						<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
					</svg>
				</div>
				<div class="preview-ai-onboarding__text">
					<h3><?php esc_html_e( 'Activate Preview AI', 'preview-ai' ); ?></h3>
					<p><?php esc_html_e( 'Get 50 free previews to try Preview AI on your store. Enter your email to activate:', 'preview-ai' ); ?></p>
				</div>
				<form class="preview-ai-onboarding__form" id="preview-ai-register-form">
					<input type="email" 
						   name="email" 
						   id="preview-ai-register-email"
						   value="<?php echo esc_attr( $admin_email ); ?>" 
						   placeholder="<?php esc_attr_e( 'Your email address', 'preview-ai' ); ?>"
						   required />
					<button type="submit" class="button button-primary">
						<span class="preview-ai-onboarding__btn-text"><?php esc_html_e( 'Start Free Trial', 'preview-ai' ); ?></span>
						<span class="preview-ai-onboarding__btn-loading" style="display:none;">
							<span class="spinner is-active" style="margin:0;float:none;"></span>
						</span>
					</button>
				</form>
			</div>
			<div class="preview-ai-onboarding__success" style="display:none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<span class="preview-ai-onboarding__success-text"></span>
			</div>
		</div>
		<style>
			.preview-ai-onboarding-notice {
				padding: 16px 20px;
				border-left-color: #6366f1;
			}
			.preview-ai-onboarding__content {
				display: flex;
				align-items: center;
				gap: 16px;
				flex-wrap: wrap;
			}
			.preview-ai-onboarding__icon {
				flex-shrink: 0;
				width: 48px;
				height: 48px;
				background: linear-gradient(135deg, #6366f1, #8b5cf6);
				border-radius: 12px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.preview-ai-onboarding__icon svg {
				width: 24px;
				height: 24px;
				stroke: white;
			}
			.preview-ai-onboarding__text {
				flex: 1;
				min-width: 200px;
			}
			.preview-ai-onboarding__text h3 {
				margin: 0 0 4px;
				font-size: 15px;
			}
			.preview-ai-onboarding__text p {
				margin: 0;
				color: #646970;
			}
			.preview-ai-onboarding__form {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
			.preview-ai-onboarding__form input[type="email"] {
				width: 280px;
				max-width: 100%;
			}
			.preview-ai-onboarding__success {
				display: flex;
				align-items: center;
				gap: 8px;
				color: #00a32a;
				font-weight: 500;
			}
			.preview-ai-onboarding__success .dashicons {
				color: #00a32a;
				font-size: 24px;
				width: 24px;
				height: 24px;
			}
		</style>
		<?php
	}
}
