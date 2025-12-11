<?php
/**
 * API client for AI backend communication.
 *
 * @link       http://preview-ai.com
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

class PREVIEW_AI_Api {

	/**
	 * API endpoint URL.
	 *
	 * @var string
	 */
	private $endpoint = 'http://backend_app:8000/api/';

	/**
	 * API key for authentication.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Initialize API client.
	 */
	public function __construct() {
		$this->api_key = get_option( 'preview_ai_api_key', '' );
	}

	/**
	 * Central method to make API requests.
	 *
	 * Automatically includes api_key and domain in all requests.
	 *
	 * @param string $path    API endpoint path (e.g., 'generate/', 'catalog/analyze').
	 * @param array  $data    Request data to send.
	 * @param int    $timeout Request timeout in seconds (default 120).
	 * @return array|WP_Error Response data or error.
	 */
	public function request( $path, $data = array(), $timeout = 120 ) {
		if ( empty( $this->api_key ) ) {
			PREVIEW_AI_Logger::error( 'API key not configured' );
			return new WP_Error( 'not_configured', __( 'API not configured', 'preview-ai' ) );
		}

		$response = wp_remote_post(
			trailingslashit( $this->endpoint ) . ltrim( $path, '/' ),
			array(
				'timeout' => $timeout,
				'headers' => array(
					'Content-Type' => 'application/json',
					'X-Api-Key'    => $this->api_key,
				),
				'body'    => wp_json_encode( $data ),
			)
		);

		# Error codes:
		# 401: Missing or invalid API key.
		# 403: API key is deactivated.
		# 429: Monthly limit reached.

		if ( is_wp_error( $response ) ) {
			PREVIEW_AI_Logger::error( 'API request failed', array(
				'path'  => $path,
				'error' => $response->get_error_message(),
			) );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( $code >= 400 ) {
			$message = isset( $result['error'] ) ? $result['error'] : __( 'API request failed', 'preview-ai' );
			PREVIEW_AI_Logger::error( 'API request failed with HTTP error', array(
				'path'          => $path,
				'status_code'   => $code,
				'error_message' => $message,
			) );
			return new WP_Error( 'api_error', $message );
		}

		return $result;
	}

	/**
	 * Send images to AI backend for preview generation.
	 *
	 * @param array $user_image   User image data (base64 + mime_type).
	 * @param array $product_data Product context data with images as base64.
	 * @return array|WP_Error     Response data or error.
	 */
	public function generate_preview( $user_image, $product_data ) {
		PREVIEW_AI_Logger::debug( 'Starting preview generation', array(
			'product_id' => $product_data['id'],
		) );

		$result = $this->request( 'generate/', array(
			'user_image'     => $user_image,
			'product_id'     => $product_data['id'],
			'parent_id'      => $product_data['parentId'],
			'name'           => $product_data['name'],
			'product_images' => $product_data['images'],
			'product_type'   => $product_data['type'],
		), 120 );

		if ( ! is_wp_error( $result ) ) {
			PREVIEW_AI_Logger::info( 'Preview generation completed successfully' );
		}

		return $result;
	}

	/**
	 * Send catalog data to AI backend for product classification.
	 *
	 * @param array $products_data Array of products with id, title, categories, tags.
	 * @return array|WP_Error      Response data with classifications or error.
	 */
	public function analyze_catalog( $products_data ) {
		PREVIEW_AI_Logger::debug( 'Starting catalog analysis', array(
			'product_count' => count( $products_data ),
		) );

		$result = $this->request( 'catalog/analyze', array(
			'products' => $products_data,
		), 300 );

		if ( ! is_wp_error( $result ) ) {
			PREVIEW_AI_Logger::info( 'Catalog analysis completed successfully' );
		}

		return $result;
	}
}
