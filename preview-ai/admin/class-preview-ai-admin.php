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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_settings_page = ( 'product_page_preview-ai' === $hook || ( isset( $_GET['page'] ) && 'preview-ai' === $_GET['page'] ) );
		$is_product_page  = ( 'post.php' === $hook || 'post-new.php' === $hook );

		wp_localize_script(
			$this->plugin_name,
			'previewAiAdmin',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'preview_ai_learn_catalog' ),
				'verifyNonce'   => wp_create_nonce( 'preview_ai_verify_api_key' ),
				'dismissNonce'  => wp_create_nonce( 'preview_ai_dismiss_notice' ),
				'registerNonce' => wp_create_nonce( 'preview_ai_register_site' ),
				'i18n'          => array(
					'error'        => __( 'An error occurred.', 'preview-ai' ),
					'apiPending'   => __( '(API integration pending)', 'preview-ai' ),
					'activating'   => __( 'Activating...', 'preview-ai' ),
					'activated'    => __( 'Preview AI activated! Redirecting...', 'preview-ai' ),
					'analyzing'    => __( 'Analyzing your catalog...', 'preview-ai' ),
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

		if ( $api_key ) {
			update_option( 'preview_ai_api_key', $api_key );
		}

		$tokens_limit        = isset( $result['tokens_limit'] ) ? intval( $result['tokens_limit'] ) : 0;
		$tokens_used         = isset( $result['tokens_used'] ) ? intval( $result['tokens_used'] ) : 0;
		$tokens_remaining    = isset( $result['tokens_remaining'] ) ? intval( $result['tokens_remaining'] ) : 0;
		$period_end          = isset( $result['current_period_end'] ) ? $result['current_period_end'] : null;
		$renew_date          = $period_end ? date_i18n( get_option( 'date_format' ), strtotime( $period_end ) ) : '';
		$subscription_status = isset( $result['subscription_status'] ) ? sanitize_text_field( $result['subscription_status'] ) : null;
		$email               = isset( $result['email'] ) ? sanitize_email( $result['email'] ) : null;
		$domain              = isset( $result['domain'] ) ? sanitize_text_field( $result['domain'] ) : null;

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
