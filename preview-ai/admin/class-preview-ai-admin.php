<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/admin
 */

class PREVIEW_AI_Admin {

	/**
	 * Sub-module instances.
	 */
	private $settings;
	private $product;
	private $catalog;
	private $onboarding;
	private $notices;

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

		$this->settings   = new PREVIEW_AI_Admin_Settings();
		$this->product    = new PREVIEW_AI_Admin_Product();
		$this->catalog    = new PREVIEW_AI_Admin_Catalog();
		$this->onboarding = new PREVIEW_AI_Admin_Onboarding();
		$this->notices    = new PREVIEW_AI_Admin_Notices();
	}

	/**
	 * Register the admin menu under Products.
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
	 * Add settings link to plugin action links.
	 *
	 * @param array $links Array of plugin action links.
	 * @return array Modified array of plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'edit.php?post_type=product&page=preview-ai' ) . '">' . __( 'Settings', 'preview-ai' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}

	/**
	 * Delegated methods to sub-modules.
	 */
	public function register_settings() {
		$this->settings->register_settings();
	}

	public static function get_button_icons() {
		return PREVIEW_AI_Admin_Settings::get_button_icons();
	}

	public static function get_widget_settings() {
		return PREVIEW_AI_Admin_Settings::get_widget_settings();
	}

	public static function get_product_types() {
		return PREVIEW_AI_Admin_Settings::get_product_types();
	}

	public static function get_clothing_subtypes() {
		return PREVIEW_AI_Admin_Settings::get_clothing_subtypes();
	}

	public static function kses_svg( $svg ) {
		return PREVIEW_AI_Admin_Settings::kses_svg( $svg );
	}

	public function add_product_data_tab( $tabs ) {
		return $this->product->add_product_data_tab( $tabs );
	}

	public function render_product_data_panel() {
		$this->product->render_product_data_panel();
	}

	public function save_product_data( $post_id ) {
		$this->product->save_product_data( $post_id );
	}

	public function add_product_column( $columns ) {
		return $this->product->add_product_column( $columns );
	}

	public function render_product_column( $column, $post_id ) {
		$this->product->render_product_column( $column, $post_id );
	}

	public function handle_toggle_product() {
		$this->product->handle_toggle_product();
	}

	public function handle_learn_catalog() {
		$this->catalog->handle_learn_catalog();
	}

	public function process_catalog_batch() {
		$this->catalog->process_catalog_batch();
	}

	public function handle_catalog_status() {
		$this->catalog->handle_catalog_status();
	}

	public function handle_reverify_compatibility() {
		$this->catalog->handle_reverify_compatibility();
	}

	public static function get_catalog_analysis_status() {
		return PREVIEW_AI_Admin_Catalog::get_catalog_analysis_status();
	}

	public function display_admin_notices() {
		$this->notices->display_admin_notices();
	}

	public function handle_dismiss_notice() {
		$this->notices->handle_dismiss_notice();
	}

	public function handle_dismiss_try_notice() {
		$this->notices->handle_dismiss_try_notice();
	}

	public function handle_register_site() {
		$this->onboarding->handle_register_site();
	}

	public function display_onboarding_notice() {
		$this->onboarding->display_onboarding_notice();
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_onboarding = isset( $_GET['onboarding'] ) && 'complete' === $_GET['onboarding'];

		if ( $is_onboarding ) {
			add_action( 'admin_footer', array( $this->onboarding, 'render_onboarding_wizard' ) );
		}

		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/preview-ai-admin.css',
			array(),
			$this->version,
			'all'
		);
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 */
	public function enqueue_scripts( $hook ) {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			$this->version,
			true
		);

		// Register onboarding wizard script.
		wp_register_script(
			'preview-ai-onboarding',
			plugin_dir_url( __FILE__ ) . 'js/preview-ai-onboarding-wizard.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'preview-ai-onboarding',
			'previewAiOnboarding',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'preview_ai_learn_catalog' ),
				'i18n'    => array(
					'configuring'          => __( 'Configuring products...', 'preview-ai' ),
					'customTemplate'       => __( 'Using a custom product template?', 'preview-ai' ),
					'manualAdd'            => __( 'If the widget does not appear automatically, you can add it manually:', 'preview-ai' ),
					'manualAddNow'         => __( 'You can add the widget manually:', 'preview-ai' ),
					'elementorSearch'      => __( 'Search for "Preview AI" widget', 'preview-ai' ),
					'configureIn'          => __( 'Configure in: Products → Preview AI → Widget tab', 'preview-ai' ),
					'analyzingBackground'  => __( 'Analyzing in background', 'preview-ai' ),
					'productsAnalyzed'     => __( 'products are being analyzed. This may take a few minutes.', 'preview-ai' ),
					'closeAndCheck'        => __( 'You can close this window and check progress in Preview AI settings.', 'preview-ai' ),
					'closeAndContinue'     => __( 'Close & Continue', 'preview-ai' ),
					'tryNow'               => __( 'Try Preview AI Now', 'preview-ai' ),
					'experienceMagic'      => __( 'See how your customers will experience the magic!', 'preview-ai' ),
					'closeAndConfigure'    => __( 'Close & Configure Products', 'preview-ai' ),
					'catalogConfigured'    => __( 'Catalog configured!', 'preview-ai' ),
					'productsReady'        => __( 'products ready for preview', 'preview-ai' ),
					'couldNotAnalyze'      => __( 'Could not analyze catalog', 'preview-ai' ),
					'manualConfig'         => __( 'You can configure products manually.', 'preview-ai' ),
					'continueToSettings'   => __( 'Continue to Settings', 'preview-ai' ),
					'couldNotConnect'      => __( 'Could not connect to server', 'preview-ai' ),
					'analyzeLater'         => __( 'You can analyze your catalog later from settings.', 'preview-ai' ),
					'continue'             => __( 'Continue', 'preview-ai' ),
				),
			)
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking URL params to determine which scripts to load, no data processing.
		$is_settings_page = ( 'product_page_preview-ai' === $hook || ( isset( $_GET['page'] ) && 'preview-ai' === $_GET['page'] ) );
		$is_product_page  = ( 'post.php' === $hook || 'post-new.php' === $hook );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only checking URL param to conditionally enqueue script, no data processing.
		$is_onboarding    = isset( $_GET['onboarding'] ) && 'complete' === sanitize_key( $_GET['onboarding'] );

		if ( $is_onboarding ) {
			wp_enqueue_script( 'preview-ai-onboarding' );
		}

		wp_localize_script(
			$this->plugin_name,
			'previewAiAdmin',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'preview_ai_learn_catalog' ),
				'verifyNonce'        => wp_create_nonce( 'preview_ai_verify_api_key' ),
				'dismissNonce'       => wp_create_nonce( 'preview_ai_dismiss_notice' ),
				'registerNonce'      => wp_create_nonce( 'preview_ai_register_site' ),
				'toggleProductNonce' => wp_create_nonce( 'preview_ai_toggle_product' ),
				'i18n'               => array(
					'error'        => __( 'An error occurred.', 'preview-ai' ),
					'apiPending'   => __( '(API integration pending)', 'preview-ai' ),
					'activating'   => __( 'Activating...', 'preview-ai' ),
					'activated'    => __( 'Preview AI activated! Redirecting...', 'preview-ai' ),
					'analyzing'    => __( 'Analyzing your catalog...', 'preview-ai' ),
					'catalogStatus' => PREVIEW_AI_Admin::get_catalog_analysis_status()['status'],
				),
			)
		);
	}

	/**
	 * Handle AJAX request to verify API key.
	 */
	public function handle_verify_api_key() {
		check_ajax_referer( 'preview_ai_verify_api_key', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'preview-ai' ) ) );
		}

		$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : null;
		$api     = new PREVIEW_AI_Api( $api_key );
		$result  = $api->verify_api_key();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Always return the latest status from DB to ensure consistency.
		$status = PREVIEW_AI_Api::get_account_status();

		$tokens_limit        = isset( $status['tokens_limit'] ) ? intval( $status['tokens_limit'] ) : 0;
		$tokens_used         = isset( $status['tokens_used'] ) ? intval( $status['tokens_used'] ) : 0;
		$tokens_remaining    = isset( $status['tokens_remaining'] ) ? intval( $status['tokens_remaining'] ) : max( 0, $tokens_limit - $tokens_used );
		$period_end          = isset( $status['current_period_end'] ) ? $status['current_period_end'] : null;
		$renew_date          = $period_end ? date_i18n( get_option( 'date_format' ), strtotime( $period_end ) ) : '';
		$subscription_status = isset( $status['subscription_status'] ) ? sanitize_text_field( $status['subscription_status'] ) : '';
		$email               = isset( $status['email'] ) ? sanitize_email( $status['email'] ) : '';
		$domain              = isset( $status['domain'] ) ? sanitize_text_field( $status['domain'] ) : '';

		wp_send_json_success( array(
			'tokens_limit'        => $tokens_limit,
			'tokens_used'         => $tokens_used,
			'tokens_remaining'    => $tokens_remaining,
			'renew_date'          => $renew_date,
			'subscription_status' => $subscription_status,
			'email'               => $email,
			'domain'              => $domain,
		) );
	}
}
