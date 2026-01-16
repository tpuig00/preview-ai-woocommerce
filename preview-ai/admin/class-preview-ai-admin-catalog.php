<?php

/**
 * Handle mass catalog analysis.
 */
class PREVIEW_AI_Admin_Catalog {

	/**
	 * Option keys for background catalog analysis.
	 */
	const ANALYSIS_STATUS_OPTION    = 'preview_ai_catalog_analysis_status';
	const ANALYSIS_PROGRESS_OPTION  = 'preview_ai_catalog_analysis_progress';
	const ANALYSIS_PENDING_OPTION   = 'preview_ai_catalog_pending_products';
	const ANALYSIS_RESULTS_OPTION   = 'preview_ai_catalog_analysis_results';
	const PREFLIGHT_RESULT_OPTION   = 'preview_ai_store_compatibility';

	/**
	 * Batch size for background processing.
	 */
	const CATALOG_BATCH_SIZE = 50;

	/**
	 * Free tier limits.
	 * - MAX_PRODUCTS: Maximum products to send to backend for analysis.
	 * - MAX_ANALYZE: Maximum products the backend will classify/analyze.
	 */
	const FREE_TIER_MAX_PRODUCTS = 300;
	const FREE_TIER_MAX_ANALYZE  = 20;

	/**
	 * Check if current account is on free trial.
	 */
	private function is_free_tier() {
		$status = PREVIEW_AI_Api::get_account_status();
		$subscription_status = isset( $status['subscription_status'] ) ? $status['subscription_status'] : 'free_trial';
		return 'free_trial' === $subscription_status;
	}

	/**
	 * Handle AJAX request for Learn My Catalog feature.
	 */
	public function handle_learn_catalog() {
		check_ajax_referer( 'preview_ai_learn_catalog', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'preview-ai' ) ) );
		}

		// 1. Check if we already have a preflight result.
		$preflight = get_option( self::PREFLIGHT_RESULT_OPTION );

		// 2. If it doesn't exist or is stale, perform preflight.
		if ( empty( $preflight ) || $this->is_preflight_stale( $preflight ) ) {
			$preflight_data = $this->get_store_preflight_data();
			$api            = new PREVIEW_AI_Api();
			$result         = $api->check_store_compatibility( $preflight_data );

			if ( ! is_wp_error( $result ) ) {
				$result['checked_at'] = time();
				update_option( self::PREFLIGHT_RESULT_OPTION, $result, false );
				$preflight = $result;
			}
		}

		// 3. If not compatible, send error and stop.
		if ( ! empty( $preflight ) && isset( $preflight['compatible'] ) && ! $preflight['compatible'] ) {
			wp_send_json_error( array(
				'code'    => 'store_not_compatible',
				'message' => $preflight['message'],
			) );
		}

		// 4. If compatible, get all products for classification (no category filtering).
		$products_data = $this->get_catalog_products_data();

		if ( empty( $products_data ) ) {
			wp_send_json_success(
				array(
					'status'  => 'complete',
					'message' => __( 'All products have already been analyzed. No new products to process.', 'preview-ai' ),
					'stats'   => array(
						'total'      => 0,
						'configured' => 0,
					),
				)
			);
			return;
		}

		$total_products = count( $products_data );

		if ( $this->is_free_tier() ) {
			$this->process_catalog_sync( $products_data );
			return;
		}

		if ( $total_products <= self::CATALOG_BATCH_SIZE ) {
			$this->process_catalog_sync( $products_data );
			return;
		}

		$this->schedule_catalog_analysis( $products_data );

		wp_send_json_success(
			array(
				'status'  => 'scheduled',
				'total'   => $total_products,
				'message' => sprintf(
					/* translators: %d: number of products */
					__( 'Analysis scheduled for %d products. Processing in background...', 'preview-ai' ),
					$total_products
				),
			)
		);
	}

	/**
	 * Process catalog synchronously.
	 */
	public function process_catalog_sync( $products_data ) {
		$api    = new PREVIEW_AI_Api();
		$result = $api->analyze_catalog( $products_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$stats = $this->save_catalog_classifications( $result );

		$is_limited      = isset( $result['is_limited'] ) && $result['is_limited'];
		$analysis_errors = isset( $result['analysis_errors'] ) ? intval( $result['analysis_errors'] ) : 0;

		$try_product_url = '';
		if ( ! empty( $stats['configured_ids'] ) ) {
			$try_product_url = add_query_arg( 'demo', 'yes', get_permalink( $stats['configured_ids'][0] ) );
		}

		wp_send_json_success(
			array(
				'status'          => 'completed',
				'total'           => $stats['total'],
				'configured'      => $stats['configured'],
				'needs_review'    => $stats['needs_review'],
				'images_analyzed' => $stats['images_analyzed'],
				'is_limited'      => $is_limited,
				'analysis_errors' => $analysis_errors,
				'try_product_url' => $try_product_url,
				'warning'         => $warning,
				'message'         => sprintf(
					/* translators: 1: number of configured products, 2: number of products needing review, 3: number of images analyzed */
					__( '%1$d products configured. %2$d need review. %3$d images analyzed.', 'preview-ai' ),
					$stats['configured'],
					$stats['needs_review'],
					$stats['images_analyzed']
				),
			)
		);
	}

	/**
	 * Schedule catalog analysis with Action Scheduler.
	 */
	public function schedule_catalog_analysis( $products_data ) {
		delete_option( self::ANALYSIS_RESULTS_OPTION );
		update_option( self::ANALYSIS_PENDING_OPTION, $products_data, false );
		update_option(
			self::ANALYSIS_PROGRESS_OPTION,
			array(
				'total'           => count( $products_data ),
				'processed'       => 0,
				'configured'      => 0,
				'needs_review'    => 0,
				'images_analyzed' => 0,
				'analysis_errors' => 0,
				'configured_ids'  => array(),
			),
			false
		);
		update_option( self::ANALYSIS_STATUS_OPTION, 'processing', false );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), 'preview_ai_process_catalog_batch' );
		} else {
			update_option( self::ANALYSIS_STATUS_OPTION, 'idle', false );
			$this->process_catalog_sync( $products_data );
		}
	}

	/**
	 * Process a batch of products.
	 */
	public function process_catalog_batch() {
		$products = get_option( self::ANALYSIS_PENDING_OPTION, array() );
		$progress = get_option( self::ANALYSIS_PROGRESS_OPTION, array() );

		if ( empty( $products ) ) {
			update_option( self::ANALYSIS_STATUS_OPTION, 'completed', false );
			delete_option( self::ANALYSIS_PENDING_OPTION );
			return;
		}

		$batch = array_splice( $products, 0, self::CATALOG_BATCH_SIZE );
		update_option( self::ANALYSIS_PENDING_OPTION, $products, false );

		$api    = new PREVIEW_AI_Api();
		$result = $api->analyze_catalog( $batch );

		if ( ! is_wp_error( $result ) ) {
			$stats = $this->save_catalog_classifications( $result );

			$progress['processed']       += count( $batch );
			$progress['configured']      += $stats['configured'];
			$progress['needs_review']    += $stats['needs_review'];
			$progress['images_analyzed'] += $stats['images_analyzed'];
			$progress['analysis_errors'] += isset( $result['analysis_errors'] ) ? intval( $result['analysis_errors'] ) : 0;
			$progress['configured_ids']   = array_merge( $progress['configured_ids'], $stats['configured_ids'] );

			update_option( self::ANALYSIS_PROGRESS_OPTION, $progress, false );
		}

		if ( ! empty( $products ) && function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 2, 'preview_ai_process_catalog_batch' );
		} else {
			update_option( self::ANALYSIS_STATUS_OPTION, 'completed', false );
			delete_option( self::ANALYSIS_PENDING_OPTION );
		}
	}

	/**
	 * AJAX handler to get catalog analysis status.
	 */
	public function handle_catalog_status() {
		check_ajax_referer( 'preview_ai_learn_catalog', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'preview-ai' ) ) );
		}

		$status   = get_option( self::ANALYSIS_STATUS_OPTION, 'idle' );
		$progress = get_option( self::ANALYSIS_PROGRESS_OPTION, array() );

		$response = array(
			'status' => $status,
		);

		if ( 'processing' === $status && ! empty( $progress ) ) {
			$response['total']     = $progress['total'];
			$response['processed'] = $progress['processed'];
			$response['message']   = sprintf(
				/* translators: 1: processed products, 2: total products */
				__( 'Processing... %1$d of %2$d products analyzed.', 'preview-ai' ),
				$progress['processed'],
				$progress['total']
			);
		} elseif ( 'completed' === $status && ! empty( $progress ) ) {
			$try_product_url = '';
			if ( ! empty( $progress['configured_ids'] ) ) {
				$try_product_url = add_query_arg( 'demo', 'yes', get_permalink( $progress['configured_ids'][0] ) );
			}

			$response['configured']      = $progress['configured'];
			$response['needs_review']    = $progress['needs_review'];
			$response['images_analyzed'] = $progress['images_analyzed'];
			$response['analysis_errors'] = $progress['analysis_errors'];
			$response['try_product_url'] = $try_product_url;
			$response['warning']         = $warning;
			$response['message']         = sprintf(
				/* translators: 1: number of configured products, 2: number of products needing review, 3: number of images analyzed */
				__( '%1$d products configured. %2$d need review. %3$d images analyzed.', 'preview-ai' ),
				$progress['configured'],
				$progress['needs_review'],
				$progress['images_analyzed']
			);

			update_option( self::ANALYSIS_STATUS_OPTION, 'idle', false );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Get current catalog analysis status.
	 */
	public static function get_catalog_analysis_status() {
		$status   = get_option( self::ANALYSIS_STATUS_OPTION, 'idle' );
		$progress = get_option( self::ANALYSIS_PROGRESS_OPTION, array() );

		return array(
			'status'    => $status,
			'progress'  => $progress,
		);
	}

	/**
	 * Check if preflight result is stale (> 7 days).
	 *
	 * @param array $preflight Preflight data.
	 * @return bool
	 */
	private function is_preflight_stale( $preflight ) {
		if ( empty( $preflight['checked_at'] ) ) {
			return true;
		}
		$seven_days = 7 * 24 * 60 * 60;
		return ( time() - $preflight['checked_at'] ) > $seven_days;
	}

	/**
	 * Handle AJAX request to re-verify compatibility.
	 */
	public function handle_reverify_compatibility() {
		check_ajax_referer( 'preview_ai_learn_catalog', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'preview-ai' ) ) );
		}

		// Delete old result and perform new verification.
		delete_option( self::PREFLIGHT_RESULT_OPTION );

		$preflight_data = $this->get_store_preflight_data();
		$api            = new PREVIEW_AI_Api();
		$result         = $api->check_store_compatibility( $preflight_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$result['checked_at'] = time();
		update_option( self::PREFLIGHT_RESULT_OPTION, $result, false );

		wp_send_json_success( $result );
	}

	/**
	 * Get store preflight data (categories and sample product titles).
	 */
	public function get_store_preflight_data() {
		$categories = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'fields'     => 'names',
		) );

		$samples = array();
		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $cat_name ) {
				$products = wc_get_products( array(
					'status'   => 'publish',
					'limit'    => 2,
					'category' => array( $cat_name ),
					'orderby'  => 'rand',
				) );

				foreach ( $products as $product ) {
					$samples[] = array(
						'category' => $cat_name,
						'title'    => $product->get_name(),
					);
				}
			}
		}

		return array(
			'categories' => is_wp_error( $categories ) ? array() : $categories,
			'samples'    => $samples,
		);
	}

	/**
	 * Get catalog products data for analysis.
	 *
	 * @return array Products with nested variations.
	 */
	public function get_catalog_products_data() {
		$is_free_tier = $this->is_free_tier();

		$args = array(
			'status'       => 'publish',
			'limit'        => $is_free_tier ? self::FREE_TIER_MAX_PRODUCTS : -1,
			'type'         => array( 'simple', 'variable' ),
			'stock_status' => 'instock',
			'meta_query'   => array(
				array(
					'key'     => '_preview_ai_supported',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$products = wc_get_products( $args );

		$products_data = array();
		foreach ( $products as $product ) {
			$product_id     = $product->get_id();
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
						$var_thumbnail_url              = wp_get_attachment_url( $var_image_id );
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
	 * Save catalog classifications from backend response.
	 *
	 * Structure: 1 product with nested variations.
	 */
	public function save_catalog_classifications( $result ) {
		$stats = array(
			'total'           => 0,
			'configured'      => 0,
			'needs_review'    => 0,
			'images_analyzed' => 0,
			'configured_ids'  => array(),
		);

		if ( empty( $result['classifications'] ) || ! is_array( $result['classifications'] ) ) {
			return $stats;
		}

		$valid_subtypes = array_keys( PREVIEW_AI_Admin_Settings::get_clothing_subtypes() );

		foreach ( $result['classifications'] as $classification ) {
			if ( empty( $classification['id'] ) ) {
				continue;
			}

			$product_id   = absint( $classification['id'] );
			$subtype      = isset( $classification['subtype'] ) ? sanitize_key( $classification['subtype'] ) : '';
			$garment_type = isset( $classification['garment_type'] ) ? sanitize_key( $classification['garment_type'] ) : '';
			$is_supported = isset( $classification['supported'] ) ? (bool) $classification['supported'] : true;

			// Count each product (1 product = 1 count, regardless of variations).
			$stats['total']++;

			// Save product classification.
			update_post_meta( $product_id, '_preview_ai_supported', $is_supported ? 'yes' : 'no' );

			if ( ! empty( $subtype ) && in_array( $subtype, $valid_subtypes, true ) ) {
				update_post_meta( $product_id, '_preview_ai_recommended_subtype', $subtype );
				if ( ! empty( $garment_type ) ) {
					update_post_meta( $product_id, '_preview_ai_garment_type', $garment_type );
				}
				if ( $is_supported ) {
					$stats['configured']++;
					$stats['configured_ids'][] = $product_id;
				} else {
					update_post_meta( $product_id, '_preview_ai_enabled', 'no' );
					$stats['needs_review']++;
				}
			} else {
				update_post_meta( $product_id, '_preview_ai_enabled', 'no' );
				$stats['needs_review']++;
			}

			// Save parent product image analysis.
			if ( ! empty( $classification['image_analysis'] ) ) {
				$this->save_image_analysis( $product_id, $classification['image_analysis'] );
				$stats['images_analyzed']++;
			}

			// Save variations image analysis.
			if ( ! empty( $classification['variations'] ) && is_array( $classification['variations'] ) ) {
				foreach ( $classification['variations'] as $variation_data ) {
					if ( ! empty( $variation_data['variation_id'] ) && ! empty( $variation_data['image_analysis'] ) ) {
						$this->save_image_analysis(
							absint( $variation_data['variation_id'] ),
							$variation_data['image_analysis']
						);
						$stats['images_analyzed']++;
					}
				}
			}
		}

		return $stats;
	}

	/**
	 * Save image analysis data to post meta.
	 *
	 * @param int   $post_id  Post ID (product or variation).
	 * @param array $analysis Image analysis data from backend.
	 */
	private function save_image_analysis( $post_id, $analysis ) {
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
}

