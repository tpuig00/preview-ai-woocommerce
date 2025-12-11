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
	}

	/**
	 * Get available product types for AI context.
	 *
	 * @since    1.0.0
	 * @return   array    List of product types.
	 */
	public static function get_product_types() {
		return array(
			'clothing'   => __( 'Clothing', 'preview-ai' ),
			'furniture'  => __( 'Furniture', 'preview-ai' ),
			'decoration' => __( 'Decoration', 'preview-ai' ),
			'crafts'     => __( 'Crafts', 'preview-ai' ),
			'generic'    => __( 'Other (Generic)', 'preview-ai' ),
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
			array( 'jquery' ),
			$this->version,
			true
		);

		// Only localize script data on Preview AI settings page or product edit pages.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_settings_page = ( 'product_page_preview-ai' === $hook || ( isset( $_GET['page'] ) && 'preview-ai' === $_GET['page'] ) );
		$is_product_page  = ( 'post.php' === $hook || 'post-new.php' === $hook );

		if ( $is_settings_page || $is_product_page ) {
			$clothing_subtypes = self::get_clothing_subtypes();
			wp_localize_script(
				$this->plugin_name,
				'previewAiAdmin',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'preview_ai_learn_catalog' ),
					'subtypeExamples' => array_map(
						function( $data ) {
							return $data['examples'];
						},
						$clothing_subtypes
					),
					'i18n'            => array(
						'examples'   => __( 'Examples:', 'preview-ai' ),
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
		$enabled = get_post_meta( $post->ID, '_preview_ai_enabled', true );
		$type    = get_post_meta( $post->ID, '_preview_ai_product_type', true );

		$global_enabled      = get_option( 'preview_ai_enabled', 0 );
		$global_type         = get_option( 'preview_ai_product_type', 'generic' );
		$product_types       = self::get_product_types();
		$global_type_text    = isset( $product_types[ $global_type ] ) ? $product_types[ $global_type ] : $product_types['generic'];
		
		$enabled_options = array(
			'' => $global_enabled
				? __( 'Yes (default)', 'preview-ai' )
				: __( 'No (default)', 'preview-ai' ),
		);
		if ( $global_enabled ) {
			$enabled_options['no'] = __( 'No', 'preview-ai' );
		} else {
			$enabled_options['yes'] = __( 'Yes', 'preview-ai' );
		}

		?>
		<div id="preview_ai_product_data" class="panel woocommerce_options_panel">
			<?php
			woocommerce_wp_select(
				array(
					'id'          => '_preview_ai_enabled',
					'label'       => __( 'Enable Preview AI', 'preview-ai' ),
					'options'     => $enabled_options,
					'value'       => $enabled,
					'desc_tip'    => true,
					'description' => __( 'Override the global setting for this product.', 'preview-ai' ),
				)
			);

			$type_options = array( '' => sprintf( __( '%s (default)', 'preview-ai' ), $global_type_text ) );
			foreach ( $product_types as $key => $label ) {
				if ( $key !== $global_type ) {
					$type_options[ $key ] = $label;
				}
			}

		woocommerce_wp_select(
			array(
				'id'          => '_preview_ai_product_type',
				'label'       => __( 'Product Type', 'preview-ai' ),
				'options'     => $type_options,
				'value'       => $type,
				'desc_tip'    => true,
				'description' => __( 'Override the default product type for AI preview.', 'preview-ai' ),
			)
		);

		$product                  = wc_get_product( $post->ID );
		$variations_without_image = array();

		if ( $product && $product->is_type( 'variable' ) ) {
			$variation_ids = $product->get_children();

			foreach ( $variation_ids as $variation_id ) {
				$variation_image = get_post_meta( $variation_id, '_thumbnail_id', true );

				if ( empty( $variation_image ) ) {
					$variation = wc_get_product( $variation_id );
					if ( ! $variation ) {
						continue;
					}
					$variation_attributes = $variation->get_variation_attributes();
					$attribute_values     = array_filter( $variation_attributes );
					$formatted_values = array_map( 'ucfirst', $attribute_values );
					$variation_name   = ! empty( $formatted_values ) ? implode( ' / ', $formatted_values ) : '#' . $variation_id;
					$variations_without_image[] = $variation_name;
				}
			}
		}

		?>

		<div class="form-field" style="margin-top: 15px; padding: 12px; background: #F7F8FA; border: 1px solid #D7DDE4; border-radius: 6px;">
			<strong style="display: block; margin-bottom: 12px; color: #1F1F1F; font-size: 14px;"><?php esc_html_e( 'Image Settings', 'preview-ai' ); ?></strong>

			<?php if ( ! empty( $variations_without_image ) ) : ?>
				<?php $variations_list = esc_html( implode( ', ', $variations_without_image ) ); ?>
				<div style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; margin-bottom: 12px; background: #FEF8E8; border-left: 3px solid #dba617; border-radius: 2px; font-size: 13px; color: #5D4800;">
					<span class="dashicons dashicons-info" style="color: #dba617; flex-shrink: 0;"></span>
					<span><?php esc_html_e( 'These variations don\'t have their specific variation image, using base image:', 'preview-ai' ); ?> <strong><?php echo $variations_list; ?></strong></span>
				</div>
			<?php endif; ?>

			<div style="margin-bottom: 12px;">
				<strong style="display: block; margin-bottom: 8px; color: #1F1F1F; font-size: 13px;"><?php esc_html_e( 'Base image priority:', 'preview-ai' ); ?></strong>
				<div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 13px; color: #1F1F1F;">
					<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #4C82F7; color: white; border-radius: 50%; font-size: 11px; font-weight: 600;">1</span>
					<?php esc_html_e( 'Variation', 'preview-ai' ); ?>
					<span style="color: #D7DDE4;">→</span>
					<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #4C82F7; color: white; border-radius: 50%; font-size: 11px; font-weight: 600;">2</span>
					<?php esc_html_e( 'Featured', 'preview-ai' ); ?>
					<span style="color: #D7DDE4;">→</span>
					<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #4C82F7; color: white; border-radius: 50%; font-size: 11px; font-weight: 600;">3</span>
					<?php esc_html_e( 'Gallery', 'preview-ai' ); ?>
				</div>
			</div>

			<div style="display: flex; align-items: flex-start; gap: 6px; padding-top: 10px; border-top: 1px solid #E5E8EB;">
				<span class="dashicons dashicons-lightbulb" style="font-size: 14px; width: 14px; height: 14px; color: #4C82F7; flex-shrink: 0; margin-top: 2px;"></span>
				<p style="margin: 0; font-size: 12px; color: #666; line-height: 1.4;">
					<?php esc_html_e( 'For the most realistic previews, upload clear images with a neutral background and good lighting. Images with harsh shadows or busy backgrounds may produce lower quality previews.', 'preview-ai' ); ?>
				</p>
			</div>
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
		$enabled = isset( $_POST['_preview_ai_enabled'] ) ? sanitize_key( $_POST['_preview_ai_enabled'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type = isset( $_POST['_preview_ai_product_type'] ) ? sanitize_key( $_POST['_preview_ai_product_type'] ) : '';

		update_post_meta( $post_id, '_preview_ai_enabled', $enabled );
		update_post_meta( $post_id, '_preview_ai_product_type', $type );
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
