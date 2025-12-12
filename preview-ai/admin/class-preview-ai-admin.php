<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://preview-ai.com
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
		register_setting( 'preview_ai_settings', 'preview_ai_api_endpoint', 'esc_url_raw' );
		register_setting( 'preview_ai_settings', 'preview_ai_api_key', 'sanitize_text_field' );
		register_setting( 'preview_ai_settings', 'preview_ai_enabled', 'absint' );
		register_setting( 'preview_ai_settings', 'preview_ai_product_type', 'sanitize_key' );
		register_setting( 'preview_ai_settings', 'preview_ai_clothing_subtype', 'sanitize_key' );

		// Display mode.
		register_setting( 'preview_ai_settings', 'preview_ai_display_mode', 'sanitize_key' );

		// Branding settings.
		register_setting( 'preview_ai_settings', 'preview_ai_primary_color', 'sanitize_hex_color' );
		register_setting( 'preview_ai_settings', 'preview_ai_button_text', 'sanitize_text_field' );
		register_setting( 'preview_ai_settings', 'preview_ai_upload_text', 'sanitize_text_field' );
	}

	/**
	 * Get branding settings with defaults.
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public static function get_branding_settings() {
		return array(
			'primary_color' => get_option( 'preview_ai_primary_color', '#111111' ),
			'button_text'   => get_option( 'preview_ai_button_text', '' ),
			'upload_text'   => get_option( 'preview_ai_upload_text', '' ),
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
	 * Get clothing subtypes with example items.
	 *
	 * @since    1.0.0
	 * @return   array    List of clothing subtypes.
	 */
	public static function get_clothing_subtypes() {
		return array(
			'mixed'        => array(
				'label'    => __( 'Mixed / All types', 'preview-ai' ),
				'examples' => __( 'Set the correct subtype per product for precise results', 'preview-ai' ),
			),
			'upper_body'   => array(
				'label'    => __( 'Upper Body', 'preview-ai' ),
				'examples' => __( 'T-shirts, shirts, blouses, jackets, hoodies, tops', 'preview-ai' ),
			),
			'lower_body'   => array(
				'label'    => __( 'Lower Body', 'preview-ai' ),
				'examples' => __( 'Pants, shorts, leggings, skirts', 'preview-ai' ),
			),
			'full_body'    => array(
				'label'    => __( 'Full Body', 'preview-ai' ),
				'examples' => __( 'Dresses, jumpsuits, full suits', 'preview-ai' ),
			),
			'headwear'     => array(
				'label'    => __( 'Headwear', 'preview-ai' ),
				'examples' => __( 'Caps, hats, berets, head scarves', 'preview-ai' ),
			),
			'footwear'     => array(
				'label'    => __( 'Footwear', 'preview-ai' ),
				'examples' => __( 'Shoes, boots, sandals, slippers', 'preview-ai' ),
			),
			'neckwear'     => array(
				'label'    => __( 'Neckwear', 'preview-ai' ),
				'examples' => __( 'Necklaces, scarves, chokers', 'preview-ai' ),
			),
			'waistwear'    => array(
				'label'    => __( 'Waistwear', 'preview-ai' ),
				'examples' => __( 'Belts, fanny packs, waist bags', 'preview-ai' ),
			),
			'wrist_hand'   => array(
				'label'    => __( 'Wrist & Hand', 'preview-ai' ),
				'examples' => __( 'Bracelets, watches, rings', 'preview-ai' ),
			),
			'ear'          => array(
				'label'    => __( 'Ear Accessories', 'preview-ai' ),
				'examples' => __( 'Earrings, hoops, ear cuffs', 'preview-ai' ),
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

		// Color picker on widget settings tab.
		$screen = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		if ( $screen && 'product_page_preview-ai' === $screen->id && 'widget' === $tab ) {
			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook    The current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$deps = array( 'jquery' );

		// Add color picker on widget settings tab.
		$screen = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		if ( $screen && 'product_page_preview-ai' === $screen->id && 'widget' === $tab ) {
			$deps[] = 'wp-color-picker';
		}

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-admin.js',
			$deps,
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
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'preview_ai_learn_catalog' ),
					'i18n'    => array(
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

		// Determine status for display.
		$status_class = 'preview-ai-col--disabled';
		$status_text  = __( 'Disabled', 'preview-ai' );
		$status_icon  = '—';

		if ( 'no' === $enabled ) {
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
}
