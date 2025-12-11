<?php
/**
 * AJAX handler for frontend requests.
 *
 * @link       http://preview-ai.com
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

class PREVIEW_AI_Ajax {

	/**
	 * API client instance.
	 *
	 * @var PREVIEW_AI_Api
	 */
	private $api;

	/**
	 * Initialize AJAX handler.
	 */
	public function __construct() {
		$this->api = new PREVIEW_AI_Api();
	}

	/**
	 * Handle image upload and preview generation.
	 */
	public function handle_upload() {
		check_ajax_referer( 'preview_ai_ajax', 'nonce' );

		// Validate image.
		if ( empty( $_FILES['image'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No image provided', 'preview-ai' ) ) );
		}

		// Validate product.
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product', 'preview-ai' ) ) );
		}

		// Check if enabled for this product.
		if ( ! $this->is_enabled_for_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Preview AI not enabled for this product', 'preview-ai' ) ) );
		}

		// Process image to base64.
		$upload = $this->upload_image( $_FILES['image'] );
		if ( is_wp_error( $upload ) ) {
			wp_send_json_error( array( 'message' => $upload->get_error_message() ) );
		}

		// Get product data with images as base64.
		$product_data = $this->get_product_data( $product_id, $variation_id );

		// Call AI API with base64 images.
		$result = $this->api->generate_preview( $upload, $product_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Check if Preview AI is enabled for a product.
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

	/**
	 * Process user image and convert to base64.
	 *
	 * @param array $file $_FILES array element.
	 * @return array|WP_Error Base64 data or error.
	 */
	private function upload_image( $file ) {
		$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
		if ( ! in_array( $file['type'], $allowed, true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid image type. Use JPG, PNG or WebP.', 'preview-ai' ) );
		}

		$max_size = 5 * 1024 * 1024; // 5MB.
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'Image too large. Max 5MB.', 'preview-ai' ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$image_data = file_get_contents( $file['tmp_name'] );
		if ( false === $image_data ) {
			return new WP_Error( 'read_error', __( 'Could not read image file.', 'preview-ai' ) );
		}

		return array(
			'base64'    => base64_encode( $image_data ),
			'mime_type' => $file['type'],
		);
	}

	/**
	 * Get product data for AI context.
	 *
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID (optional).
	 * @return array Product data.
	 */
	private function get_product_data( $product_id, $variation_id = 0 ) {
		$product = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );

		// Fallback to parent if variation not found.
		if ( ! $product ) {
			$product = wc_get_product( $product_id );
		}

		// Get type (product override or global).
		$type = get_post_meta( $product_id, '_preview_ai_product_type', true );
		if ( empty( $type ) ) {
			$type = get_option( 'preview_ai_product_type', 'generic' );
		}

		// Get product image IDs: prioritize variation, fallback to parent.
		$image_ids = array();
		$main_image = $product->get_image_id();
		if ( $main_image ) {
			$image_ids[] = $main_image;
		}
		$image_ids = array_merge( $image_ids, $product->get_gallery_image_ids() );

		// If variation and no images, add parent's images.
		if ( $variation_id && empty( $image_ids ) ) {
			$parent = wc_get_product( $product_id );
			if ( $parent ) {
				$parent_main = $parent->get_image_id();
				if ( $parent_main ) {
					$image_ids[] = $parent_main;
				}
				$image_ids = array_merge( $image_ids, $parent->get_gallery_image_ids() );
			}
		}

		// Convert images to base64.
		$images = array();
		foreach ( $image_ids as $img_id ) {
			$base64_data = $this->image_to_base64( $img_id );
			if ( $base64_data ) {
				$images[] = $base64_data;
			}
		}

		return array(
			'id'       => $variation_id ? $variation_id : $product_id,
			'parentId' => $product_id,
			'name'     => $product->get_name(),
			'type'     => $type,
			'images'   => $images,
		);
	}

	/**
	 * Convert attachment image to base64.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array|null Base64 data with mime type or null.
	 */
	private function image_to_base64( $attachment_id ) {
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

		return array(
			'base64'    => base64_encode( $image_data ),
			'mime_type' => $mime_type ? $mime_type : 'image/jpeg',
		);
	}
}

