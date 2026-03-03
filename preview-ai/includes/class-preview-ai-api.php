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
	private $endpoint;

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
	 * Default production API endpoint.
	 *
	 * @var string
	 */
	const DEFAULT_ENDPOINT = 'https://api.previewai.app/api/';

	/**
	 * Initialize API client.
	 *
	 * @param string|null $api_key Optional API key. If null, uses saved option.
	 */
	public function __construct( $api_key = null ) {
		$this->api_key  = $api_key ?? get_option( 'preview_ai_api_key', '' );
		$this->endpoint = get_option( 'preview_ai_api_endpoint', self::DEFAULT_ENDPOINT );
	}

	/**
	 * Central method to make API requests.
	 *
	 * Updates account status cache from every response.
	 *
	 * @param string $path    API endpoint path (e.g., 'generate', 'catalog/analyze').
	 * @param array  $data    Request data to send.
	 * @param int    $timeout Request timeout in seconds (default 120).
	 * @param string $method  HTTP method (default 'POST').
	 * @return array|WP_Error Response data or error.
	 */
	public function request( $path, $data = array(), $timeout = 120, $method = 'POST' ) {
		if ( empty( $this->api_key ) ) {
			PREVIEW_AI_Logger::error( 'API key not configured' );
			return new WP_Error( 'not_configured', __( 'API not configured', 'preview-ai' ) );
		}

		$endpoint = rtrim( $this->endpoint, '/' ) . '/' . ltrim( $path, '/' );

		$args = array(
			'method'  => $method,
			'timeout' => $timeout,
			'headers' => array(
				'Content-Type' => 'application/json',
				'X-Api-Key'    => $this->api_key,
			),
			'body'    => wp_json_encode( $data ),
		);

		$response = wp_remote_request( $endpoint, $args );

		PREVIEW_AI_Logger::debug( 'API request response', array(
			'endpoint' => $endpoint,
			'method'   => $method,
			'response' => $response,
		) );

		if ( is_wp_error( $response ) ) {
			PREVIEW_AI_Logger::error( 'API request failed', array(
				'path'  => $endpoint,
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
			// Rate limit exceeded.
			self::update_account_status( array(
				'tokens_remaining' => 0,
				'active'           => true,
			) );
		}

		if ( 402 === $code ) {
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
	 * Returns false if no API key or deactivated.
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

		// Account deactivated by the service.
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

		$result = $this->request( 'generate', array(
			'user_image'      => $user_image,
			'product_id'      => $product_data['parentId'],
			'variation_id'    => $product_data['variation_id'],
			'name'            => $product_data['name'],
			'product_images'  => $product_data['images'],
			'product_type'    => $product_data['type'],
			'product_subtype' => $product_data['subtype'],
			'garment_type'    => $product_data['garment_type'] ?? null,
			'image_analysis'  => $product_data['image_analysis'],
			'session_id'      => get_option( 'preview_ai_analytics_enabled', 0 ) ? PREVIEW_AI_Tracking::get_session_id() : null,
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
	 * Analyze a single product for classification and image analysis.
	 *
	 * @param array $product_data Product data with id, title, categories, tags, thumbnail_url, variation_id.
	 * @return array|WP_Error     Response data with classification or error.
	 */
	public function analyze_product( $product_data, $single_product = false ) {
		PREVIEW_AI_Logger::debug( 'Starting single product analysis', array(
			'product_id' => $product_data['id'],
		) );

		$result = $this->request( 'catalog/analyze-product', $product_data, 30 );

		if ( ! is_wp_error( $result ) ) {
			PREVIEW_AI_Logger::info( 'Single product analysis completed', array(
				'product_id' => $product_data['id'],
				'subtype'    => $result['subtype'] ?? '',
				'supported'  => $result['supported'] ?? false,
			) );
		}

		return $result;
	}

	/**
	 * Check if the store is compatible with Preview AI based on categories and sample products.
	 *
	 * @param array $preflight_data Data with categories and samples.
	 * @return array|WP_Error Response data or error.
	 */
	public function check_store_compatibility( $preflight_data ) {
		PREVIEW_AI_Logger::debug( 'Starting store compatibility check' );

		return $this->request( 'catalog/preflight', $preflight_data, 30 );
	}

	/**
	 * Activate products via bulk action (classify unanalyzed products).
	 *
	 * Uses the /catalog/activate endpoint.
	 *
	 * @param array $products_data Array of products with id, title, categories, tags, thumbnail_url.
	 * @return array|WP_Error      Response data with classifications or error.
	 */
	public function activate_products( $products_data ) {
		PREVIEW_AI_Logger::debug( 'Starting bulk activate', array(
			'product_count' => count( $products_data ),
		) );

		$result = $this->request( 'catalog/activate', array(
			'products' => $products_data,
		), 120 );

		if ( ! is_wp_error( $result ) ) {
			PREVIEW_AI_Logger::info( 'Bulk activate completed', array(
				'total_analyzed' => $result['total_analyzed'] ?? 0,
			) );
		}

		return $result;
	}

	/**
	 * Send catalog data to AI backend for product classification and image analysis.
	 *
	 * @param array $products_data  Array of products with id, title, categories, tags, thumbnail_url, variation_id.
	 * @param bool  $analyze_images Whether to analyze product images (default true).
	 * @return array|WP_Error       Response data with classifications and image_analysis or error.
	 */
	public function analyze_catalog( $products_data, $analyze_images = true ) {
		PREVIEW_AI_Logger::debug( 'Starting catalog analysis', array(
			'product_count'  => count( $products_data ),
			'analyze_images' => $analyze_images,
		) );

		PREVIEW_AI_Logger::debug( 'Products data', array( 'products_data' => $products_data ) );

		$result = $this->request( 'catalog/analyze', array(
			'products'       => $products_data,
			'analyze_images' => $analyze_images,
		), 300 );

		PREVIEW_AI_Logger::debug( 'Catalog analysis response', array( 'result' => $result ) );

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

	/**
	 * Record a conversion (purchase) in the backend for attribution.
	 *
	 * Sends order data so the backend can attribute purchases to try-ons.
	 *
	 * @param string      $session_id  Client session ID.
	 * @param string      $order_id    WooCommerce order ID.
	 * @param float       $order_total Order total amount.
	 * @param string      $currency    Currency code (ISO 4217).
	 * @param array       $items       Array of items with product_id and variation_id.
	 * @return array|WP_Error Response data or error.
	 */
	public function record_conversion( $session_id, $order_id, $order_total, $currency, $items ) {
		return $this->request(
			'analytics/conversions',
			array(
				'source'      => 'wordpress',
				'session_id'  => $session_id,
				'order_id'    => (string) $order_id,
				'order_total' => (float) $order_total,
				'currency'    => $currency,
				'items'       => $items,
			),
			15,
			'PUT'
		);
	}

	/**
	 * Record a refund in the backend.
	 *
	 * @param string $order_id WooCommerce order ID.
	 * @return array|WP_Error Response data or error.
	 */
	public function record_refund( $order_id ) {
		return $this->request(
			'analytics/refunds/' . (string) $order_id,
			array(
				'source'   => 'wordpress',
				'order_id' => (string) $order_id,
			),
			15,
			'PUT'
		);
	}

	/**
	 * Register site for Preview AI service (auto-provisioning).
	 *
	 * This creates a free-tier API key automatically when user
	 * provides their email during plugin activation.
	 *
	 * @param string $email User email address.
	 * @return array|WP_Error API key data or error.
	 */
	public static function register_site( $email ) {

		// Get site info for registration.
		$site_url = home_url();
		$domain   = wp_parse_url( $site_url, PHP_URL_HOST );

		global $wp_version;

		$endpoint = get_option( 'preview_ai_api_endpoint', self::DEFAULT_ENDPOINT );

		$response = wp_remote_post(
			trailingslashit( $endpoint ) . 'register',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( array(
					'email'          => $email,
					'domain'         => $domain,
					'site_url'       => $site_url,
					'wp_version'     => $wp_version,
					'plugin_version' => PREVIEW_AI_VERSION,
				) ),
			)
		);

		PREVIEW_AI_Logger::debug( 'Site registration response', array(
			'endpoint' => trailingslashit( $endpoint ) . 'register',
			'email' => $email,
			'domain' => $domain,
			'site_url' => $site_url,
			'wp_version' => $wp_version,
			'plugin_version' => PREVIEW_AI_VERSION,
			'response' => $response,
		) );

		if ( is_wp_error( $response ) ) {
			PREVIEW_AI_Logger::error( 'Site registration failed', array(
				'error' => $response->get_error_message(),
			) );
			return $response;
		}

		$code   = wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( $code >= 400 ) {
			$message = isset( $result['detail'] )
				? $result['detail']
				: __( 'Registration failed. Please try again.', 'preview-ai' );
			return new WP_Error( 'registration_failed', $message );
		}

		PREVIEW_AI_Logger::info( 'Site registered successfully', array(
			'domain' => $domain,
			'tokens' => $result['tokens_limit'] ?? 0,
		) );

		return $result;
	}
}
