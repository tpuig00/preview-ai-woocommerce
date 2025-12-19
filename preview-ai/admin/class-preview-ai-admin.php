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
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
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

		if ( $is_settings_page || $is_product_page ) {
			wp_localize_script(
				$this->plugin_name,
				'previewAiAdmin',
				array(
					'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'preview_ai_learn_catalog' ),
					'verifyNonce'  => wp_create_nonce( 'preview_ai_verify_api_key' ),
					'dismissNonce' => wp_create_nonce( 'preview_ai_dismiss_notice' ),
					'i18n'         => array(
						'error'      => __( 'An error occurred.', 'preview-ai' ),
						'apiPending' => __( '(API integration pending)', 'preview-ai' ),
					),
				)
			);
		}
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

		PREVIEW_AI_Logger::debug( 'Products data', array( 'products_data' => $products_data ) );

		// Send to API for classification.
		$api    = new PREVIEW_AI_Api();
		$result = $api->analyze_catalog( $products_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Process results and save as product meta.
		$stats = $this->save_catalog_classifications( $result );

		wp_send_json_success(
			array(
				'total'        => $stats['total'],
				'configured'   => $stats['configured'],
				'needs_review' => $stats['needs_review'],
				'message'      => sprintf(
					/* translators: 1: configured products, 2: products that couldn't be enabled */
					__( '%1$d products configured and enabled for preview. %2$d products could not be enabled automatically.', 'preview-ai' ),
					$stats['configured'],
					$stats['needs_review']
				),
			)
		);
	}

	/**
	 * Get catalog products data for AI analysis.
	 *
	 * @since    1.0.0
	 * @return   array    Array of products with id, title, categories, tags.
	 */
	private function get_catalog_products_data() {
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => -1,
				'type'   => array( 'simple', 'variable' ), // Parents only.
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

			$products_data[] = array(
				'id'         => $product_id,
				'title'      => $product->get_name(),
				'categories' => $categories_str,
				'tags'       => $tags_str,
			);
		}

		return $products_data;
	}

	/**
	 * Save AI classifications as product meta.
	 *
	 * @since    1.0.0
	 * @param    array $result    API response with classifications.
	 * @return   array            Statistics array with total, configured, needs_review.
	 */
	private function save_catalog_classifications( $result ) {
		$stats = array(
			'total'        => 0,
			'configured'   => 0,
			'needs_review' => 0,
		);

		if ( empty( $result['classifications'] ) || ! is_array( $result['classifications'] ) ) {
			return $stats;
		}

		$valid_subtypes = array_keys( self::get_clothing_subtypes() );

		foreach ( $result['classifications'] as $classification ) {
			if ( empty( $classification['id'] ) ) {
				continue;
			}

			$stats['total']++;
			$product_id = absint( $classification['id'] );
			$subtype    = isset( $classification['subtype'] ) ? sanitize_key( $classification['subtype'] ) : '';

			if ( ! empty( $subtype ) && in_array( $subtype, $valid_subtypes, true ) ) {
				update_post_meta( $product_id, '_preview_ai_recommended_subtype', $subtype );
				$stats['configured']++;
			} else {
				update_post_meta( $product_id, '_preview_ai_enabled', 'no' );
				$stats['needs_review']++;
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

		$tokens     = isset( $result['tokens_remaining'] ) ? intval( $result['tokens_remaining'] ) : 0;
		$period_end = isset( $result['current_period_end'] ) ? $result['current_period_end'] : null;
		$renew_date = $period_end ? date_i18n( 'F j, Y', strtotime( $period_end ) ) : '';

		wp_send_json_success( array(
			'tokens'     => $tokens,
			'renew_date' => $renew_date,
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
}
