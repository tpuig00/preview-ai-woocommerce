<?php
/**
 * API client for AI backend communication.
 *
 * @link       https://previewai.app
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
	 * Option key for account status (permanent, no expiry).
	 */
	const STATUS_OPTION = 'preview_ai_account_status';

	/**
	 * Initialize API client.
	 *
	 * @param string|null $api_key Optional API key. If null, uses saved option.
	 */
	public function __construct( $api_key = null ) {
		$this->api_key = $api_key ?? get_option( 'preview_ai_api_key', '' );
	}

	/**
	 * Central method to make API requests.
	 *
	 * Updates account status cache from every response.
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

		if ( is_wp_error( $response ) ) {
			PREVIEW_AI_Logger::error( 'API request failed', array(
				'path'  => $path,
				'error' => $response->get_error_message(),
			) );
			return $response;
		}

		$code   = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		// Update account status from response (backend includes this in every response).
		if ( is_array( $result ) && isset( $result['account'] ) ) {
			self::update_account_status( $result['account'] );
		}

		// Handle error codes.
		if ( 429 === $code ) {
			// Tokens exhausted.
			self::update_account_status( array(
				'tokens_remaining' => 0,
				'active'           => true,
			) );
		}

		if ( 403 === $code ) {
			// Account deactivated.
			self::update_account_status( array(
				'tokens_remaining' => 0,
				'active'           => false,
			) );
		}

		if ( $code >= 400 ) {
			$message = isset( $result['detail'] )
				? $result['detail']
				: ( isset( $result['error'] ) ? $result['error'] : __( 'API request failed', 'preview-ai' ) );
			PREVIEW_AI_Logger::error( 'API request failed with HTTP error', array(
				'path'          => $path,
				'status_code'   => $code,
				'error_message' => $message,
			) );
			return new WP_Error( 'api_error', $message, array( 'status' => $code ) );
		}

		return $result;
	}

	/**
	 * Check if widget can be displayed.
	 * Returns false if no API key, deactivated, or no tokens.
	 *
	 * @return bool
	 */
	public static function is_widget_available() {
		$api_key = get_option( 'preview_ai_api_key', '' );

		// No API key = don't show widget.
		if ( empty( $api_key ) ) {
			return false;
		}

		// Check account status.
		$status = self::get_account_status();

		// No status yet = first time, allow widget (will update after first request).
		if ( empty( $status ) ) {
			return true;
		}

		// No tokens left.
		if ( isset( $status['tokens_remaining'] ) && $status['tokens_remaining'] <= 0 ) {
			return false;
		}

		// Account deactivated.
		if ( isset( $status['active'] ) && ! $status['active'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Get account status from database.
	 *
	 * @return array Account status or empty array.
	 */
	public static function get_account_status() {
		return get_option( self::STATUS_OPTION, array() );
	}

	/**
	 * Update account status in database (permanent, no expiry).
	 *
	 * @param array $status Status data from backend.
	 */
	public static function update_account_status( $status ) {
		if ( ! is_array( $status ) ) {
			return;
		}

		// Merge with existing to preserve fields.
		$current = self::get_account_status();
		$updated = array_merge( $current, $status );

		update_option( self::STATUS_OPTION, $updated, false );
	}

	/**
	 * Clear account status (used when API key changes).
	 */
	public static function clear_account_status() {
		delete_option( self::STATUS_OPTION );
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
			'product_id'   => $product_data['parentId'],
			'variation_id' => $product_data['variation_id'],
		) );

		$result = $this->request( 'generate/', array(
			'user_image'      => $user_image,
			'product_id'      => $product_data['parentId'],
			'variation_id'    => $product_data['variation_id'],
			'name'            => $product_data['name'],
			'product_images'  => $product_data['images'],
			'product_type'    => $product_data['type'],
			'product_subtype' => $product_data['subtype'],
		), 120 );

		if ( ! is_wp_error( $result ) ) {
			PREVIEW_AI_Logger::info( 'Preview generation completed successfully' );
		}

		return $result;
	}

	/**
	 * Pre-check user image quality via backend.
	 *
	 * @param array $user_image User image data (base64 + mime_type).
	 * @return array|WP_Error
	 */
	public function check_user_image( $user_image, $product_data ) {
		PREVIEW_AI_Logger::debug( 'Starting user image pre-check', array(
			'product_data' => $product_data
		) );
		return $this->request(
			'generate/check',
			array(
				'user_image' => $user_image,
				'product_data' => $product_data,
			),
			10
		);
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

	/**
	 * Verify API key and get account status.
	 *
	 * @return array|WP_Error Account status or error.
	 */
	public function verify_api_key() {
		$result = $this->request( 'account/status', array(), 10 );

		if ( ! is_wp_error( $result ) && is_array( $result ) ) {
			self::update_account_status( $result );
		}

		return $result;
	}
}
