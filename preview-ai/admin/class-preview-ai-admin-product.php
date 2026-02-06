<?php

/**
 * Handle WooCommerce product integration.
 */
class PREVIEW_AI_Admin_Product {

	/**
	 * Add Preview AI tab to WooCommerce Product Data.
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
	 */
	public function render_product_data_panel() {
		global $post;
		$product        = wc_get_product( $post->ID );
		$has_image      = $product && $product->get_image_id();
		$enabled        = get_post_meta( $post->ID, '_preview_ai_enabled', true );
		$subtype        = get_post_meta( $post->ID, '_preview_ai_recommended_subtype', true );
		$supported      = get_post_meta( $post->ID, '_preview_ai_supported', true );
		$global_enabled = get_option( 'preview_ai_enabled', 0 );
		$was_analyzed   = '' !== $supported; // Product has been processed by catalog.

		// Determine current state.
		$is_enabled = false;

		// Can only be enabled if product was analyzed and is supported.
		if ( $was_analyzed && 'yes' === $supported && $has_image ) {
			if ( 'yes' === $enabled ) {
				$is_enabled = true;
			} elseif ( 'no' === $enabled ) {
				$is_enabled = false;
			} else {
				// No explicit setting, use global.
				$is_enabled = (bool) $global_enabled;
			}
		}

		// Determine status for display.
		if ( ! $has_image ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'No Image', 'preview-ai' );
			$status_icon  = '—';
		} elseif ( 'no' === $supported ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'Not Supported', 'preview-ai' );
			$status_icon  = '<span class="dashicons dashicons-warning"></span>';
		} elseif ( ! $is_enabled ) {
			if ( ! $was_analyzed ) {
				$status_class = 'preview-ai-col--pending';
				$status_text  = __( 'Not Analyzed', 'preview-ai' );
				$status_icon  = '<span class="dashicons dashicons-clock"></span>';
			} else {
				$status_class = 'preview-ai-col--disabled';
				$status_text  = __( 'Disabled', 'preview-ai' );
				$status_icon  = '—';
			}
		} else {
			// Enabled.
			if ( ! $was_analyzed ) {
				$status_class = 'preview-ai-col--active';
				$status_text  = __( 'Active (Pending Analysis)', 'preview-ai' );
				$status_icon  = '<span class="dashicons dashicons-update"></span>';
			} else {
				$status_class = 'preview-ai-col--active';
				$status_text  = __( 'Active', 'preview-ai' );
				$status_icon  = '<span class="dashicons dashicons-visibility"></span>';
			}
		}

		?>
		<div id="preview_ai_product_data" class="panel woocommerce_options_panel">
			<?php wp_nonce_field( 'preview_ai_save_product_data', 'preview_ai_product_nonce' ); ?>

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
						<?php echo wp_kses_post( $status_icon ); ?> 
						<?php echo esc_html( $status_text ); ?>
					</span>
				</div>
		<label class="preview-ai-switch">
			<input type="checkbox" 
				   id="_preview_ai_enabled" 
				   name="_preview_ai_enabled" 
				   value="yes" 
				   data-product-id="<?php echo esc_attr( $post->ID ); ?>"
				   <?php checked( $is_enabled ); ?> 
				   <?php disabled( ! $has_image || 'no' === $supported ); ?>
			/>
			<span class="preview-ai-switch__track"></span>
		</label>
			</div>

			<?php
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
	 */
	public function save_product_data( $post_id ) {
		// Verify nonce.
		$nonce = '';
		if ( isset( $_POST['preview_ai_product_nonce'] ) ) {
			$nonce = sanitize_key( wp_unslash( $_POST['preview_ai_product_nonce'] ) );
		}

		if ( ! wp_verify_nonce( $nonce, 'preview_ai_save_product_data' ) ) {
			return;
		}

		$enabled = isset( $_POST['_preview_ai_enabled'] ) ? 'yes' : 'no';
		$product = wc_get_product( $post_id );

		if ( ! $product ) {
			return;
		}

		// Can't enable if product has no image.
		if ( 'yes' === $enabled && ! $product->get_image_id() ) {
			$enabled = 'no';
		}

		// Check analysis status.
		$supported = get_post_meta( $post_id, '_preview_ai_supported', true );

		// If enabling and not yet analyzed, trigger analysis now.
		if ( 'yes' === $enabled && '' === $supported ) {
			$analysis_result = $this->analyze_single_product( $product );

			if ( ! is_wp_error( $analysis_result ) ) {
				// We need to save the classifications first so we can check if it's supported.
				if ( isset( $analysis_result['classifications'] ) ) {
					$catalog = new PREVIEW_AI_Admin_Catalog();
					$catalog->save_catalog_classifications( $analysis_result );
				} else {
					$this->save_single_product_classification( $post_id, $analysis_result );
				}

				// Re-fetch support status after analysis.
				$supported = get_post_meta( $post_id, '_preview_ai_supported', true );
			}
		}

		// Final check: can't be enabled if analysis says it's not supported.
		if ( 'yes' === $enabled && 'no' === $supported ) {
			$enabled = 'no';
		}

		update_post_meta( $post_id, '_preview_ai_enabled', $enabled );
	}

	/**
	 * Analyze a single product via backend API.
	 */
	public function analyze_single_product( $product ) {
		$product_id     = $product->get_id();
		$categories     = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
		$categories_str = is_array( $categories ) ? implode( ', ', $categories ) : '';
		$tags           = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
		$tags_str       = is_array( $tags ) ? implode( ', ', $tags ) : '';
		$thumbnail_id   = $product->get_image_id();
		$thumbnail_url  = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : null;

		$product_data = array(
			'id'                    => $product_id,
			'title'                 => $product->get_name(),
			'categories'            => $categories_str,
			'tags'                  => $tags_str,
			'thumbnail_url'         => $thumbnail_url,
			'variations'            => array(),
			'single_product'        => true
		);

		// Add variations with different images.
		if ( $product->is_type( 'variable' ) ) {
			$variation_ids = $product->get_children();
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation ) {
					continue;
				}

				// Skip out of stock variations.
				if ( ! $variation->is_in_stock() ) {
					continue;
				}

				// Skip already analyzed variations.
				$var_analysis = get_post_meta( $variation_id, '_preview_ai_image_analysis', true );
				if ( ! empty( $var_analysis ) ) {
					continue;
				}

				$var_image_id = $variation->get_image_id();
				if ( $var_image_id && $var_image_id !== $thumbnail_id ) {
					$var_thumbnail_url            = wp_get_attachment_url( $var_image_id );
					$product_data['variations'][] = array(
						'variation_id'  => $variation_id,
						'thumbnail_url' => $var_thumbnail_url,
					);
				}
			}
		}

		$api = new PREVIEW_AI_Api();
		return $api->analyze_product( $product_data );
	}

	/**
	 * Save single product classification from backend response.
	 */
	public function save_single_product_classification( $product_id, $classification ) {
		$valid_subtypes = array_keys( PREVIEW_AI_Admin_Settings::get_clothing_subtypes() );

		$subtype      = isset( $classification['subtype'] ) ? sanitize_key( $classification['subtype'] ) : '';
		$garment_type = isset( $classification['garment_type'] ) ? sanitize_key( $classification['garment_type'] ) : '';
		$is_supported = isset( $classification['supported'] ) && $classification['supported'];

		update_post_meta( $product_id, '_preview_ai_supported', $is_supported ? 'yes' : 'no' );

		if ( ! empty( $subtype ) && in_array( $subtype, $valid_subtypes, true ) ) {
			update_post_meta( $product_id, '_preview_ai_recommended_subtype', $subtype );
		}

		if ( ! empty( $garment_type ) ) {
			update_post_meta( $product_id, '_preview_ai_garment_type', $garment_type );
		}

		// Save parent product image analysis.
		if ( ! empty( $classification['image_analysis'] ) ) {
			$this->save_image_analysis_meta( $product_id, $classification['image_analysis'] );
		}

		// Save variations image analysis.
		if ( ! empty( $classification['variations'] ) && is_array( $classification['variations'] ) ) {
			foreach ( $classification['variations'] as $variation_data ) {
				if ( ! empty( $variation_data['variation_id'] ) && ! empty( $variation_data['image_analysis'] ) ) {
					$this->save_image_analysis_meta(
						absint( $variation_data['variation_id'] ),
						$variation_data['image_analysis']
					);
				}
			}
		}
	}

	/**
	 * Save image analysis data to post meta.
	 *
	 * @param int   $post_id  Post ID (product or variation).
	 * @param array $analysis Image analysis data from backend.
	 */
	private function save_image_analysis_meta( $post_id, $analysis ) {
		$detected_objects = array();
		if ( ! empty( $analysis['detected_objects'] ) && is_array( $analysis['detected_objects'] ) ) {
			$detected_objects = array_map( 'sanitize_text_field', $analysis['detected_objects'] );
		}

		$image_analysis = array(
			'has_model'         => ! empty( $analysis['has_model'] ),
			'shot_type'         => sanitize_key( $analysis['shot_type'] ?? 'unknown' ),
			'framing'           => sanitize_key( $analysis['framing'] ?? 'unknown' ),
			'multiple_garments' => ! empty( $analysis['multiple_garments'] ),
			'detected_objects'  => $detected_objects,
			'confidence'        => floatval( $analysis['confidence'] ?? 0.0 ),
			'image_id'          => absint( $analysis['image_id'] ?? 0 ),
			'updated_at'        => sanitize_text_field( $analysis['updated_at'] ?? current_time( 'Y-m-d' ) ),
		);

		update_post_meta( $post_id, '_preview_ai_image_analysis', $image_analysis );
	}

	/**
	 * Handle AJAX toggle product request.
	 */
	public function handle_toggle_product() {
		check_ajax_referer( 'preview_ai_toggle_product', 'nonce' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'preview-ai' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$enabled    = isset( $_POST['enabled'] ) && 'yes' === sanitize_key( wp_unslash( $_POST['enabled'] ) );

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'preview-ai' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'preview-ai' ) ) );
		}

		// Can't enable if product has no image.
		if ( $enabled && ! $product->get_image_id() ) {
			wp_send_json_error( array( 'message' => __( 'Product has no image.', 'preview-ai' ) ) );
		}

		// If disabling, just save and return.
		if ( ! $enabled ) {
			update_post_meta( $product_id, '_preview_ai_enabled', 'no' );
			wp_send_json_success( $this->get_product_status( $product_id ) );
		}

		// If enabling, call backend for classification and analysis.
		$result = $this->analyze_single_product( $product );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Save classification.
		$this->save_single_product_classification( $product_id, $result );

		// Check if supported.
		$supported = get_post_meta( $product_id, '_preview_ai_supported', true );
		if ( 'no' === $supported ) {
			wp_send_json_error( array( 'message' => __( 'Product type not supported.', 'preview-ai' ) ) );
		}

		// Enable the product.
		update_post_meta( $product_id, '_preview_ai_enabled', 'yes' );

		wp_send_json_success( $this->get_product_status( $product_id ) );
	}

	/**
	 * Get product status data for UI update.
	 */
	private function get_product_status( $product_id ) {
		$product        = wc_get_product( $product_id );
		$has_image      = $product && $product->get_image_id();
		$enabled        = get_post_meta( $product_id, '_preview_ai_enabled', true );
		$supported      = get_post_meta( $product_id, '_preview_ai_supported', true );
		$global_enabled = get_option( 'preview_ai_enabled', 0 );
		$was_analyzed   = '' !== $supported;

		$is_enabled = false;
		if ( $was_analyzed && 'yes' === $supported && $has_image ) {
			if ( 'yes' === $enabled ) {
				$is_enabled = true;
			} elseif ( 'no' === $enabled ) {
				$is_enabled = false;
			} else {
				$is_enabled = (bool) $global_enabled;
			}
		}

		// Determine status.
		if ( ! $has_image ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'No Image', 'preview-ai' );
			$status_icon  = '—';
		} elseif ( 'no' === $supported ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'Not Supported', 'preview-ai' );
			$status_icon  = '<span class="dashicons dashicons-warning"></span>';
		} elseif ( ! $is_enabled ) {
			$status_class = 'preview-ai-col--disabled';
			$status_text  = __( 'Disabled', 'preview-ai' );
			$status_icon  = '—';
		} else {
			$status_class = 'preview-ai-col--active';
			$status_text  = __( 'Active', 'preview-ai' );
			$status_icon  = '<span class="dashicons dashicons-visibility"></span>';
		}

		return array(
			'is_enabled'      => $is_enabled,
			'status_class'    => $status_class,
			'status_text'     => $status_text,
			'status_icon'     => $status_icon,
			'toggle_disabled' => ! $has_image || 'no' === $supported,
		);
	}

	/**
	 * Add Preview AI status filter dropdown to product list.
	 */
	public function add_product_filter_dropdown() {
		global $typenow;
		if ( 'product' !== $typenow ) {
			return;
		}

		$current = isset( $_GET['preview_ai_status'] ) ? sanitize_key( wp_unslash( $_GET['preview_ai_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$statuses = array(
			''              => __( 'All Preview AI statuses', 'preview-ai' ),
			'active'        => __( 'Active', 'preview-ai' ),
			'disabled'      => __( 'Disabled', 'preview-ai' ),
			'not_analyzed'  => __( 'Not Analyzed', 'preview-ai' ),
			'not_supported' => __( 'Not Supported', 'preview-ai' ),
		);

		echo '<select name="preview_ai_status">';
		foreach ( $statuses as $value => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Filter products by Preview AI status in the product list.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function filter_products_by_preview_ai( $query ) {
		global $typenow, $pagenow;

		if ( 'edit.php' !== $pagenow || 'product' !== $typenow || ! $query->is_main_query() ) {
			return;
		}

		if ( ! isset( $_GET['preview_ai_status'] ) || '' === $_GET['preview_ai_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['preview_ai_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		$global_enabled = get_option( 'preview_ai_enabled', 0 );

		switch ( $status ) {
			case 'not_analyzed':
				$meta_query[] = array(
					'key'     => '_preview_ai_supported',
					'compare' => 'NOT EXISTS',
				);
				break;

			case 'not_supported':
				$meta_query[] = array(
					'key'   => '_preview_ai_supported',
					'value' => 'no',
				);
				break;

			case 'active':
				if ( $global_enabled ) {
					// Global ON: active = supported AND not explicitly disabled.
					$meta_query[] = array(
						'key'   => '_preview_ai_supported',
						'value' => 'yes',
					);
					$meta_query[] = array(
						'relation' => 'OR',
						array(
							'key'     => '_preview_ai_enabled',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_preview_ai_enabled',
							'value'   => 'no',
							'compare' => '!=',
						),
					);
				} else {
					// Global OFF: active = supported AND explicitly enabled.
					$meta_query[] = array(
						'key'   => '_preview_ai_supported',
						'value' => 'yes',
					);
					$meta_query[] = array(
						'key'   => '_preview_ai_enabled',
						'value' => 'yes',
					);
				}
				break;

			case 'disabled':
				if ( $global_enabled ) {
					// Global ON: disabled = supported AND explicitly disabled.
					$meta_query['relation'] = 'AND';
					$meta_query[]           = array(
						'key'   => '_preview_ai_supported',
						'value' => 'yes',
					);
					$meta_query[]           = array(
						'key'   => '_preview_ai_enabled',
						'value' => 'no',
					);
				} else {
					// Global OFF: disabled = supported AND not explicitly enabled.
					$meta_query['relation'] = 'AND';
					$meta_query[]           = array(
						'key'   => '_preview_ai_supported',
						'value' => 'yes',
					);
					$meta_query[]           = array(
						'relation' => 'OR',
						array(
							'key'     => '_preview_ai_enabled',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_preview_ai_enabled',
							'value'   => 'yes',
							'compare' => '!=',
						),
					);
				}
				break;
		}

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Make Preview AI column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function make_column_sortable( $columns ) {
		$columns['preview_ai'] = 'preview_ai';
		return $columns;
	}

	/**
	 * Handle sorting by Preview AI column.
	 *
	 * Uses named meta_query clauses with EXISTS/NOT EXISTS so WordPress
	 * performs a LEFT JOIN, including products without the meta key
	 * (i.e. "Not Analyzed" products).
	 *
	 * @param WP_Query $query The current query.
	 */
	public function sort_by_preview_ai( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'preview_ai' !== $query->get( 'orderby' ) ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );
		if ( ! is_array( $meta_query ) ) {
			$meta_query = array();
		}

		$meta_query['relation']               = 'OR';
		$meta_query['preview_ai_has_status']   = array(
			'key'     => '_preview_ai_supported',
			'compare' => 'EXISTS',
		);
		$meta_query['preview_ai_no_status']    = array(
			'key'     => '_preview_ai_supported',
			'compare' => 'NOT EXISTS',
		);

		$query->set( 'meta_query', $meta_query );
		$query->set( 'orderby', 'preview_ai_has_status' );
	}

	/**
	 * Add Preview AI column to product list.
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
	 */
	public function render_product_column( $column, $post_id ) {
		if ( 'preview_ai' !== $column ) return;

		$enabled        = get_post_meta( $post_id, '_preview_ai_enabled', true );
		$supported      = get_post_meta( $post_id, '_preview_ai_supported', true );
		$was_analyzed   = '' !== $supported;
		$global_enabled = get_option( 'preview_ai_enabled', 0 );

		// Product hasn't been analyzed yet.
		if ( ! $was_analyzed ) {
			printf(
				'<span class="preview-ai-col preview-ai-col--pending" title="%s"><span class="dashicons dashicons-clock"></span> %s</span>',
				esc_attr__( 'Not analyzed yet - run Analyze & Enable from settings', 'preview-ai' ),
				esc_html__( 'Not Analyzed', 'preview-ai' )
			);
			return;
		}

		// Product analyzed but not supported.
		if ( 'no' === $supported ) {
			printf(
				'<span class="preview-ai-col preview-ai-col--disabled" title="%s">%s</span>',
				esc_attr__( 'Product type not supported yet (V1 supports tops and pants only)', 'preview-ai' ),
				esc_html__( 'Not Supported', 'preview-ai' )
			);
			return;
		}

		// Product is supported - check if enabled.
		$is_enabled = false;
		if ( 'yes' === $enabled ) {
			$is_enabled = true;
		} elseif ( 'no' === $enabled ) {
			$is_enabled = false;
		} else {
			$is_enabled = (bool) $global_enabled;
		}

		if ( $is_enabled ) {
			printf(
				'<span class="preview-ai-col preview-ai-col--active" title="%s"><span class="dashicons dashicons-visibility"></span> %s</span>',
				esc_attr__( 'Preview AI active on this product', 'preview-ai' ),
				esc_html__( 'Active', 'preview-ai' )
			);
		} else {
			printf(
				'<span class="preview-ai-col preview-ai-col--disabled" title="%s">—</span>',
				esc_attr__( 'Preview AI disabled for this product', 'preview-ai' )
			);
		}
	}

	// =========================================================================
	// Bulk Actions
	// =========================================================================

	/**
	 * Option keys for background bulk-activate processing.
	 */
	const BULK_ACTIVATE_STATUS_OPTION   = 'preview_ai_bulk_activate_status';
	const BULK_ACTIVATE_PROGRESS_OPTION = 'preview_ai_bulk_activate_progress';
	const BULK_ACTIVATE_PENDING_OPTION  = 'preview_ai_bulk_activate_pending';

	/**
	 * Batch size for background bulk-activate processing.
	 */
	const BULK_ACTIVATE_BATCH_SIZE = 50;

	/**
	 * Register Preview AI bulk actions in the product list dropdown.
	 *
	 * @param array $actions Existing bulk actions.
	 * @return array
	 */
	public function register_bulk_actions( $actions ) {
		$actions['preview_ai_enable']  = __( 'Enable Preview AI', 'preview-ai' );
		$actions['preview_ai_disable'] = __( 'Disable Preview AI', 'preview-ai' );
		return $actions;
	}

	/**
	 * Handle Preview AI bulk actions.
	 *
	 * @param string $redirect_to Current redirect URL.
	 * @param string $action      The bulk action being processed.
	 * @param array  $post_ids    Array of selected post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		if ( 'preview_ai_disable' === $action ) {
			return $this->handle_bulk_disable( $redirect_to, $post_ids );
		}

		if ( 'preview_ai_enable' === $action ) {
			return $this->handle_bulk_enable( $redirect_to, $post_ids );
		}

		return $redirect_to;
	}

	/**
	 * Handle bulk disable: just toggle meta locally (no API call).
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param array  $post_ids    Selected product IDs.
	 * @return string
	 */
	private function handle_bulk_disable( $redirect_to, $post_ids ) {
		foreach ( $post_ids as $post_id ) {
			update_post_meta( absint( $post_id ), '_preview_ai_enabled', 'no' );
		}

		set_transient(
			'preview_ai_bulk_result_' . get_current_user_id(),
			array(
				'action'         => 'disable',
				'disabled_count' => count( $post_ids ),
			),
			120
		);

		return $redirect_to;
	}

	/**
	 * Handle bulk enable: toggle already-analyzed products locally,
	 * send unanalyzed products to the backend for classification.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param array  $post_ids    Selected product IDs.
	 * @return string
	 */
	private function handle_bulk_enable( $redirect_to, $post_ids ) {
		$already_supported = array();
		$not_supported     = 0;
		$need_analysis     = array();

		// Step 1: Categorize products.
		foreach ( $post_ids as $post_id ) {
			$supported = get_post_meta( $post_id, '_preview_ai_supported', true );

			if ( 'yes' === $supported ) {
				$already_supported[] = $post_id;
			} elseif ( 'no' === $supported ) {
				$not_supported++;
			} else {
				// Not analyzed yet — may need backend classification.
				$product = wc_get_product( $post_id );
				if ( $product && $product->get_image_id() ) {
					$need_analysis[] = $post_id;
				} else {
					$not_supported++;
				}
			}
		}

		// Step 2: Enable already-analyzed supported products immediately.
		foreach ( $already_supported as $post_id ) {
			update_post_meta( $post_id, '_preview_ai_enabled', 'yes' );
		}

		$enabled_count  = count( $already_supported );
		$error_message  = '';
		$pending_count  = 0;

		// Step 3: Analyze unanalyzed products via backend.
		if ( ! empty( $need_analysis ) ) {
			// Build data only for the first batch (avoid loading all products at once).
			$first_batch_ids = array_splice( $need_analysis, 0, self::BULK_ACTIVATE_BATCH_SIZE );
			$first_batch     = $this->build_products_data( $first_batch_ids );

			$result = $this->process_activate_batch_sync( $first_batch );

			$enabled_count += $result['enabled'];
			$not_supported += $result['not_supported'];

			if ( ! empty( $result['error_message'] ) ) {
				// Backend error (e.g. 405 free tier) — don't schedule remaining.
				$error_message = $result['error_message'];
			} elseif ( ! empty( $need_analysis ) ) {
				// First batch succeeded and there are more products — schedule in background.
				// Store only IDs (lightweight); data is built lazily per batch.
				$this->schedule_bulk_activate( $need_analysis );
				$pending_count = count( $need_analysis );
			}
		}

		set_transient(
			'preview_ai_bulk_result_' . get_current_user_id(),
			array(
				'action'          => 'enable',
				'enabled_count'   => $enabled_count,
				'not_supported'   => $not_supported,
				'pending_count'   => $pending_count,
				'error_message'   => $error_message,
			),
			120
		);

		return $redirect_to;
	}

	/**
	 * Build product data array for the backend API from product IDs.
	 *
	 * @param array $product_ids Array of product IDs.
	 * @return array Products data formatted for the API.
	 */
	private function build_products_data( $product_ids ) {
		$products_data = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$categories     = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
			$categories_str = is_array( $categories ) ? implode( ', ', $categories ) : '';
			$tags           = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
			$tags_str       = is_array( $tags ) ? implode( ', ', $tags ) : '';
			$thumbnail_id   = $product->get_image_id();
			$thumbnail_url  = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : null;

			$product_data = array(
				'id'            => $product_id,
				'title'         => $product->get_name(),
				'categories'    => $categories_str,
				'tags'          => $tags_str,
				'thumbnail_url' => $thumbnail_url,
				'variations'    => array(),
			);

			// Add variations with different images.
			if ( $product->is_type( 'variable' ) ) {
				$variation_ids = $product->get_children();
				foreach ( $variation_ids as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( ! $variation || ! $variation->is_in_stock() ) {
						continue;
					}

					$var_image_id = $variation->get_image_id();
					if ( $var_image_id && $var_image_id !== $thumbnail_id ) {
						$var_thumbnail_url            = wp_get_attachment_url( $var_image_id );
						$product_data['variations'][] = array(
							'variation_id'  => $variation_id,
							'thumbnail_url' => $var_thumbnail_url,
						);
					}
				}
			}

			$products_data[] = $product_data;
		}

		return $products_data;
	}

	/**
	 * Process a batch of products synchronously via the activate API.
	 *
	 * @param array $products_data Products data for the API.
	 * @return array Results with 'enabled', 'not_supported', 'error_message' keys.
	 */
	private function process_activate_batch_sync( $products_data ) {
		$result = array(
			'enabled'       => 0,
			'not_supported' => 0,
			'error_message' => '',
		);

		$api      = new PREVIEW_AI_Api();
		$response = $api->activate_products( $products_data );

		if ( is_wp_error( $response ) ) {
			$result['error_message'] = $response->get_error_message();
			return $result;
		}

		// Save classifications and enable supported products.
		$catalog = new PREVIEW_AI_Admin_Catalog();
		$stats   = $catalog->save_catalog_classifications( $response );

		// Enable supported products that were just classified.
		if ( ! empty( $stats['configured_ids'] ) ) {
			foreach ( $stats['configured_ids'] as $product_id ) {
				update_post_meta( $product_id, '_preview_ai_enabled', 'yes' );
			}
		}

		$result['enabled']       = $stats['configured'];
		$result['not_supported'] = $stats['not_supported'];

		return $result;
	}

	/**
	 * Schedule remaining products for background activation via Action Scheduler.
	 *
	 * Stores only product IDs (lightweight). Product data is built lazily
	 * in each batch to avoid loading hundreds of products at once.
	 *
	 * @param array $product_ids Array of product IDs to process.
	 */
	public function schedule_bulk_activate( $product_ids ) {
		update_option( self::BULK_ACTIVATE_PENDING_OPTION, array_map( 'absint', $product_ids ), false );
		update_option(
			self::BULK_ACTIVATE_PROGRESS_OPTION,
			array(
				'total'         => count( $product_ids ),
				'processed'     => 0,
				'enabled'       => 0,
				'not_supported' => 0,
				'errors'        => 0,
			),
			false
		);
		update_option( self::BULK_ACTIVATE_STATUS_OPTION, 'processing', false );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 2, 'preview_ai_process_bulk_activate_batch' );
		} else {
			// No Action Scheduler — mark as completed (first batch already processed sync).
			update_option( self::BULK_ACTIVATE_STATUS_OPTION, 'completed', false );
			delete_option( self::BULK_ACTIVATE_PENDING_OPTION );
		}
	}

	/**
	 * Process a batch of bulk-activate products in the background.
	 *
	 * Invoked by Action Scheduler. Takes BULK_ACTIVATE_BATCH_SIZE product IDs,
	 * builds product data lazily, classifies them, enables supported ones,
	 * and schedules the next batch.
	 */
	public function process_bulk_activate_batch() {
		$pending_ids = get_option( self::BULK_ACTIVATE_PENDING_OPTION, array() );
		$progress    = get_option( self::BULK_ACTIVATE_PROGRESS_OPTION, array() );

		if ( empty( $pending_ids ) ) {
			update_option( self::BULK_ACTIVATE_STATUS_OPTION, 'completed', false );
			delete_option( self::BULK_ACTIVATE_PENDING_OPTION );
			return;
		}

		// Take next batch of IDs and build product data lazily.
		$batch_ids = array_splice( $pending_ids, 0, self::BULK_ACTIVATE_BATCH_SIZE );
		update_option( self::BULK_ACTIVATE_PENDING_OPTION, $pending_ids, false );

		$batch  = $this->build_products_data( $batch_ids );
		$result = $this->process_activate_batch_sync( $batch );

		$progress['processed']     += count( $batch_ids );
		$progress['enabled']       += $result['enabled'];
		$progress['not_supported'] += $result['not_supported'];
		if ( ! empty( $result['error_message'] ) ) {
			$progress['errors']++;
		}

		update_option( self::BULK_ACTIVATE_PROGRESS_OPTION, $progress, false );

		if ( ! empty( $pending_ids ) && empty( $result['error_message'] ) && function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 2, 'preview_ai_process_bulk_activate_batch' );
		} else {
			update_option( self::BULK_ACTIVATE_STATUS_OPTION, 'completed', false );
			delete_option( self::BULK_ACTIVATE_PENDING_OPTION );
		}
	}

	/**
	 * Show admin notice with bulk action results.
	 */
	public function show_bulk_action_notice() {
		$screen = get_current_screen();
		if ( ! $screen || 'edit-product' !== $screen->id ) {
			return;
		}

		// Show immediate results from transient.
		$transient_key = 'preview_ai_bulk_result_' . get_current_user_id();
		$result        = get_transient( $transient_key );

		if ( $result ) {
			delete_transient( $transient_key );
			$this->render_bulk_result_notice( $result );
		}

		// Show background processing status.
		$bg_status = get_option( self::BULK_ACTIVATE_STATUS_OPTION, 'idle' );

		if ( 'processing' === $bg_status ) {
			$progress = get_option( self::BULK_ACTIVATE_PROGRESS_OPTION, array() );
			printf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: 1: processed count, 2: total count */
					esc_html__( 'Preview AI: Analyzing products in background... %1$d of %2$d processed.', 'preview-ai' ),
					intval( $progress['processed'] ?? 0 ),
					intval( $progress['total'] ?? 0 )
				)
			);
		} elseif ( 'completed' === $bg_status ) {
			$progress = get_option( self::BULK_ACTIVATE_PROGRESS_OPTION, array() );

			if ( ! empty( $progress ) ) {
				$parts = array();

				if ( ! empty( $progress['enabled'] ) ) {
					$parts[] = sprintf(
						/* translators: %d: number of products enabled */
						_n( '%d product enabled', '%d products enabled', $progress['enabled'], 'preview-ai' ),
						$progress['enabled']
					);
				}
				if ( ! empty( $progress['not_supported'] ) ) {
					$parts[] = sprintf(
						/* translators: %d: number of products not supported */
						_n( '%d not supported', '%d not supported', $progress['not_supported'], 'preview-ai' ),
						$progress['not_supported']
					);
				}
				if ( ! empty( $progress['errors'] ) ) {
					$parts[] = sprintf(
						/* translators: %d: number of errors */
						_n( '%d error', '%d errors', $progress['errors'], 'preview-ai' ),
						$progress['errors']
					);
				}

				if ( ! empty( $parts ) ) {
					printf(
						'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
						/* translators: %s: summary of bulk activation results */
						esc_html( sprintf( __( 'Preview AI bulk activation complete: %s.', 'preview-ai' ), implode( ', ', $parts ) ) )
					);
				}
			}

			// Clean up.
			update_option( self::BULK_ACTIVATE_STATUS_OPTION, 'idle', false );
			delete_option( self::BULK_ACTIVATE_PROGRESS_OPTION );
		}
	}

	/**
	 * Render the immediate bulk result admin notice.
	 *
	 * @param array $result Result data from transient.
	 */
	private function render_bulk_result_notice( $result ) {
		if ( 'disable' === $result['action'] ) {
			$count = intval( $result['disabled_count'] ?? 0 );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: number of products disabled */
					esc_html( _n(
						'Preview AI: %d product disabled.',
						'Preview AI: %d products disabled.',
						$count,
						'preview-ai'
					) ),
					$count
				)
			);
			return;
		}

		// Enable action.
		$parts = array();

		$enabled = intval( $result['enabled_count'] ?? 0 );
		if ( $enabled > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: number of products enabled */
				_n( '%d product enabled', '%d products enabled', $enabled, 'preview-ai' ),
				$enabled
			);
		}

		$not_supported = intval( $result['not_supported'] ?? 0 );
		if ( $not_supported > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: number of products not supported */
				_n( '%d not supported', '%d not supported', $not_supported, 'preview-ai' ),
				$not_supported
			);
		}

		$pending = intval( $result['pending_count'] ?? 0 );
		if ( $pending > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: number of products being analyzed */
				_n( '%d more product being analyzed in background', '%d more products being analyzed in background', $pending, 'preview-ai' ),
				$pending
			);
		}

		$error_message = $result['error_message'] ?? '';
		$notice_type   = empty( $error_message ) ? 'success' : 'warning';

		$message = '';
		if ( ! empty( $parts ) ) {
			$message = sprintf( __( 'Preview AI: %s.', 'preview-ai' ), implode( ', ', $parts ) );
		}

		if ( ! empty( $error_message ) ) {
			if ( ! empty( $message ) ) {
				$message .= ' ';
			}
			$message .= $error_message;
		}

		if ( ! empty( $message ) ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice_type ),
				esc_html( $message )
			);
		}
	}
}

