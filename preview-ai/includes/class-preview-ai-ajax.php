<?php
/**
 * AJAX handler for frontend requests.
 *
 * @link       https://previewai.app
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
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_uploaded_file() below.
		$image_file = isset( $_FILES['image'] ) ? $this->sanitize_uploaded_file( $_FILES['image'] ) : null;

		// Validate upload structure and safety.
		$validation = $this->validate_upload_file( $image_file );
		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
		}

		$upload = $this->upload_image( $image_file );
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

		// Track preview for conversion.
		PREVIEW_AI_Tracking::track_preview( $product_id, $variation_id ? $variation_id : null );

		wp_send_json_success( $result );
	}

	/**
	 * Handle image pre-check (quality validation) before generating.
	 */
	public function handle_check() {
		check_ajax_referer( 'preview_ai_ajax', 'nonce' );

		if ( empty( $_FILES['image'] ) ) {
			wp_send_json_error( array( 'message' => __( 'No image provided', 'preview-ai' ) ) );
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product', 'preview-ai' ) ) );
		}

		if ( ! $this->is_enabled_for_product( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Preview AI not enabled for this product', 'preview-ai' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_uploaded_file() below.
		$image_file = isset( $_FILES['image'] ) ? $this->sanitize_uploaded_file( $_FILES['image'] ) : null;
		$validation = $this->validate_upload_file( $image_file );
		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
		}

		$upload = $this->upload_image( $image_file );
		if ( is_wp_error( $upload ) ) {
			wp_send_json_error( array( 'message' => $upload->get_error_message() ) );
		}

		$product_data = $this->get_basic_product_data( $product_id, $variation_id );

		$check_result = $this->api->check_user_image( $upload, $product_data );
		if ( is_wp_error( $check_result ) ) {
			wp_send_json_error( array( 'message' => $check_result->get_error_message() ) );
		}

		wp_send_json_success( $check_result );
	}

	/**
	 * Check if Preview AI is enabled for a product.
	 *
	 * V1: Also checks if product subtype is supported (upper_body, lower_body and full_body).
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function is_enabled_for_product( $product_id ) {
		// If `_preview_ai_supported` is empty, the product hasn't been processed yet.
		$supported = get_post_meta( $product_id, '_preview_ai_supported', true );

		// Product hasn't been analyzed yet - don't allow.
		if ( '' === $supported ) {
			return false;
		}

		// V1: Check if product type is supported (upper_body, lower_body and full_body).
		// Products with footwear, accessories, etc. are not supported yet.
		if ( 'no' === $supported ) {
			return false;
		}

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
		// Validations already handled by validate_upload_file.
		
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$image_data = file_get_contents( $file['tmp_name'] );
		if ( false === $image_data ) {
			return new WP_Error( 'read_error', __( 'Could not read image file.', 'preview-ai' ) );
		}

		$file_info = wp_check_filetype( $file['name'] );

		return array(
			'base64'    => base64_encode( $image_data ),
			'mime_type' => $file_info['type'] ? $file_info['type'] : 'image/jpeg',
		);
	}

	/**
	 * Sanitize uploaded file data from $_FILES.
	 *
	 * @param array|null $file Raw $_FILES element.
	 * @return array|null Sanitized file array or null if invalid.
	 */
	private function sanitize_uploaded_file( $file ) {
		if ( empty( $file ) || ! is_array( $file ) ) {
			return null;
		}

		return array(
			'name'     => isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '',
			'type'     => isset( $file['type'] ) ? sanitize_mime_type( wp_unslash( $file['type'] ) ) : '',
			'tmp_name' => isset( $file['tmp_name'] ) ? sanitize_text_field( $file['tmp_name'] ) : '',
			'error'    => isset( $file['error'] ) ? absint( $file['error'] ) : UPLOAD_ERR_NO_FILE,
			'size'     => isset( $file['size'] ) ? absint( $file['size'] ) : 0,
		);
	}

	/**
	 * Validate user upload file (type/size/readability).
	 *
	 * @param array $file Sanitized $_FILES element.
	 * @return true|WP_Error
	 */
	private function validate_upload_file( $file ) {
		if ( empty( $file ) || ! is_array( $file ) || empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_file', __( 'No file provided', 'preview-ai' ) );
		}

		if ( ! empty( $file['error'] ) ) {
			return new WP_Error( 'upload_error', __( 'Upload error.', 'preview-ai' ) );
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'invalid_upload', __( 'Invalid file upload', 'preview-ai' ) );
		}

		// Verify it's an actual image.
		$check = getimagesize( $file['tmp_name'] );
		if ( false === $check ) {
			return new WP_Error( 'invalid_image', __( 'File is not a valid image.', 'preview-ai' ) );
		}

		$allowed_mimes = array( 'image/jpeg', 'image/png', 'image/webp' );
		$file_info     = wp_check_filetype( $file['name'] );
		
		if ( ! in_array( $file_info['type'], $allowed_mimes, true ) || ! in_array( $check['mime'], $allowed_mimes, true ) ) {
			return new WP_Error( 'invalid_type', __( 'Invalid image type. Use JPG, PNG or WebP.', 'preview-ai' ) );
		}

		$max_size = 5 * 1024 * 1024; // 5MB.
		if ( ! empty( $file['size'] ) && $file['size'] > $max_size ) {
			return new WP_Error( 'file_too_large', __( 'Image too large. Max 5MB.', 'preview-ai' ) );
		}

		return true;
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

		// Currently only clothing is supported.
		$type = 'clothing';

		// Get recommended subtype and garment_type from catalog analysis (stored in parent product).
		$subtype      = get_post_meta( $product_id, '_preview_ai_recommended_subtype', true );
		$garment_type = get_post_meta( $product_id, '_preview_ai_garment_type', true );

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

		// Get image analysis metadata (prioritize variation, fallback to parent).
		$image_analysis = null;
		if ( $variation_id ) {
			$image_analysis = get_post_meta( $variation_id, '_preview_ai_image_analysis', true );
		}
		if ( empty( $image_analysis ) ) {
			$image_analysis = get_post_meta( $product_id, '_preview_ai_image_analysis', true );
		}

		return array(
			'id'             => $variation_id ? $variation_id : $product_id,
			'parentId'       => $product_id,
			'variation_id'   => $variation_id ? $variation_id : null,
			'name'           => $product->get_name(),
			'type'           => $type,
			'subtype'        => $subtype ? $subtype : 'mixed',
			'garment_type'   => $garment_type ? $garment_type : null,
			'images'         => $images,
			'image_analysis' => $image_analysis ? $image_analysis : null,
		);
	}

	private function get_basic_product_data( $product_id, $variation_id = 0 ) {
		$product = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );

		// Fallback to parent if variation not found.
		if ( ! $product ) {
			$product = wc_get_product( $product_id );
		}

		$type = 'clothing';

		$subtype      = get_post_meta( $product_id, '_preview_ai_recommended_subtype', true );
		$garment_type = get_post_meta( $product_id, '_preview_ai_garment_type', true );

		return array(
			'id'           => $variation_id ? $variation_id : $product_id,
			'parentId'     => $product_id,
			'variation_id' => $variation_id ? $variation_id : null,
			'name'         => $product->get_name(),
			'type'         => $type,
			'subtype'      => $subtype ? $subtype : 'mixed',
			'garment_type' => $garment_type ? $garment_type : null,
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

