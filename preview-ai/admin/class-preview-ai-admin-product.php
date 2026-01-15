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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce handles nonce.
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
	 * Analyze a single product.
	 */
	public function analyze_single_product( $product ) {
		$product_id = $product->get_id();

		// Get categories.
		$categories     = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
		$categories_str = is_array( $categories ) ? implode( ', ', $categories ) : '';

		// Get tags.
		$tags     = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
		$tags_str = is_array( $tags ) ? implode( ', ', $tags ) : '';

		// Get thumbnail URL.
		$thumbnail_id  = $product->get_image_id();
		$thumbnail_url = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : null;

		$products_data = array();
		$products_data[] = array(
			'id'            => $product_id,
			'title'         => $product->get_name(),
			'categories'    => $categories_str,
			'tags'          => $tags_str,
			'thumbnail_url' => $thumbnail_url,
			'variation_id'  => null,
		);

		if ( $product->is_type( 'variable' ) ) {
			$variation_ids = $product->get_children();
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation ) continue;

				// Skip out of stock variations.
				if ( ! $variation->is_in_stock() ) continue;

				// Skip already analyzed variations.
				$var_analysis = get_post_meta( $variation_id, '_preview_ai_image_analysis', true );
				if ( ! empty( $var_analysis ) ) continue;

				$var_image_id = $variation->get_image_id();
				if ( $var_image_id && $var_image_id !== $thumbnail_id ) {
					$var_thumbnail_url = wp_get_attachment_url( $var_image_id );
					$products_data[] = array(
						'id'            => $product_id,
						'title'         => $product->get_name(),
						'categories'    => $categories_str,
						'tags'          => $tags_str,
						'thumbnail_url' => $var_thumbnail_url,
						'variation_id'  => $variation_id,
					);
				}
			}
		}

		$api = new PREVIEW_AI_Api();
		if ( count( $products_data ) === 1 ) {
			return $api->analyze_product( $products_data[0] );
		}
		return $api->analyze_catalog( $products_data, true );
	}

	/**
	 * Save single product classification.
	 */
	public function save_single_product_classification( $product_id, $classification ) {
		$valid_subtypes = array_keys( PREVIEW_AI_Admin_Settings::get_clothing_subtypes() );

		$subtype      = isset( $classification['subtype'] ) ? sanitize_key( $classification['subtype'] ) : '';
		$garment_type = isset( $classification['garment_type'] ) ? sanitize_key( $classification['garment_type'] ) : '';
		$is_supported = isset( $classification['supported'] ) && $classification['supported'];
		$variation_id = isset( $classification['variation_id'] ) ? absint( $classification['variation_id'] ) : null;

		update_post_meta( $product_id, '_preview_ai_supported', $is_supported ? 'yes' : 'no' );

		if ( ! empty( $subtype ) && in_array( $subtype, $valid_subtypes, true ) ) {
			update_post_meta( $product_id, '_preview_ai_recommended_subtype', $subtype );
		}

		if ( ! empty( $garment_type ) ) {
			update_post_meta( $product_id, '_preview_ai_garment_type', $garment_type );
		}

		if ( ! empty( $classification['image_analysis'] ) ) {
			$analysis = $classification['image_analysis'];
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

			$meta_target = $variation_id ? $variation_id : $product_id;
			update_post_meta( $meta_target, '_preview_ai_image_analysis', $image_analysis );
		}
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
			echo '<span class="preview-ai-col preview-ai-col--pending" title="' . esc_attr__( 'Not analyzed yet - run Learn Catalog', 'preview-ai' ) . '">';
			echo '<span class="dashicons dashicons-clock"></span> ';
			echo esc_html__( 'Not Analyzed', 'preview-ai' );
			echo '</span>';
			return;
		}

		// Product analyzed but not supported.
		if ( 'no' === $supported ) {
			echo '<span class="preview-ai-col preview-ai-col--disabled" title="' . esc_attr__( 'Product type not supported yet (V1 supports tops and pants only)', 'preview-ai' ) . '">';
			echo esc_html__( 'Not Supported', 'preview-ai' );
			echo '</span>';
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
			echo '<span class="preview-ai-col preview-ai-col--active" title="' . esc_attr__( 'Preview AI active on this product', 'preview-ai' ) . '">';
			echo '<span class="dashicons dashicons-visibility"></span> ';
			echo esc_html__( 'Active', 'preview-ai' );
			echo '</span>';
		} else {
			echo '<span class="preview-ai-col preview-ai-col--disabled" title="' . esc_attr__( 'Preview AI disabled for this product', 'preview-ai' ) . '">—</span>';
		}
	}
}

