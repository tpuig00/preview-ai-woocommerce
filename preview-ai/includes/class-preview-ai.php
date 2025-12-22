<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 * @author     Preview AI <hello@previewai.app>
 */
class Preview_Ai {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      PREVIEW_AI_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $Preview_Ai    The string used to uniquely identify this plugin.
	 */
	protected $Preview_Ai;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PREVIEW_AI_VERSION' ) ) {
			$this->version = PREVIEW_AI_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->Preview_Ai = 'preview-ai';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - PREVIEW_AI_Loader. Orchestrates the hooks of the plugin.
	 * - PREVIEW_AI_i18n. Defines internationalization functionality.
	 * - PREVIEW_AI_Admin. Defines all hooks for the admin area.
	 * - PREVIEW_AI_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-preview-ai-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-preview-ai-public.php';

		/**
		 * API client for AI backend communication.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-api.php';

		/**
		 * AJAX handler for frontend requests.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-ajax.php';

		/**
		 * Conversion tracking.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-tracking.php';

		/**
		 * Logger utility for debugging and development.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-logger.php';

		/**
		 * Shortcode handler.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-shortcode.php';
		new PREVIEW_AI_Shortcode();

		/**
		 * Elementor integration.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-preview-ai-elementor.php';

		$this->loader = new PREVIEW_AI_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PREVIEW_AI_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new PREVIEW_AI_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new PREVIEW_AI_Admin( $this->get_Preview_Ai(), $this->get_version() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		// WooCommerce Product Data tab.
		$this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'add_product_data_tab' );
		$this->loader->add_action( 'woocommerce_product_data_panels', $plugin_admin, 'render_product_data_panel' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'save_product_data' );

		// Product list column.
		$this->loader->add_filter( 'manage_edit-product_columns', $plugin_admin, 'add_product_column' );
		$this->loader->add_action( 'manage_product_posts_custom_column', $plugin_admin, 'render_product_column', 10, 2 );

		// Admin AJAX handlers.
		$this->loader->add_action( 'wp_ajax_preview_ai_learn_catalog', $plugin_admin, 'handle_learn_catalog' );
		$this->loader->add_action( 'wp_ajax_preview_ai_verify_api_key', $plugin_admin, 'handle_verify_api_key' );
		$this->loader->add_action( 'wp_ajax_preview_ai_dismiss_notice', $plugin_admin, 'handle_dismiss_notice' );
		$this->loader->add_action( 'wp_ajax_preview_ai_dismiss_try_notice', $plugin_admin, 'handle_dismiss_try_notice' );
		$this->loader->add_action( 'wp_ajax_preview_ai_register_site', $plugin_admin, 'handle_register_site' );
		$this->loader->add_action( 'wp_ajax_preview_ai_catalog_status', $plugin_admin, 'handle_catalog_status' );

		// Action Scheduler hook for background catalog processing.
		$this->loader->add_action( 'preview_ai_process_catalog_batch', $plugin_admin, 'process_catalog_batch' );

		// Admin notices for API status and onboarding.
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_onboarding_notice' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notices' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new PREVIEW_AI_Public( $this->get_Preview_Ai(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_single_product_summary', $plugin_public, 'render_widget', 31 );

		// AJAX handlers.
		$ajax_handler = new PREVIEW_AI_Ajax();
		$this->loader->add_action( 'wp_ajax_preview_ai_upload', $ajax_handler, 'handle_upload' );
		$this->loader->add_action( 'wp_ajax_nopriv_preview_ai_upload', $ajax_handler, 'handle_upload' );
		$this->loader->add_action( 'wp_ajax_preview_ai_check', $ajax_handler, 'handle_check' );
		$this->loader->add_action( 'wp_ajax_nopriv_preview_ai_check', $ajax_handler, 'handle_check' );

		// Conversion tracking.
		$this->loader->add_action( 'woocommerce_checkout_order_processed', 'PREVIEW_AI_Tracking', 'save_to_order' );
		$this->loader->add_action( 'woocommerce_payment_complete', 'PREVIEW_AI_Tracking', 'track_completed' );
		$this->loader->add_action( 'woocommerce_order_status_completed', 'PREVIEW_AI_Tracking', 'track_completed' );
		$this->loader->add_action( 'woocommerce_order_status_processing', 'PREVIEW_AI_Tracking', 'track_completed' );
		$this->loader->add_action( 'woocommerce_order_status_refunded', 'PREVIEW_AI_Tracking', 'track_refunded' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_Preview_Ai() {
		return $this->Preview_Ai;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PREVIEW_AI_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
