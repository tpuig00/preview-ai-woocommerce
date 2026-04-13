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
	 * Register the top-level Preview AI menu with submenus.
	 */
	public function add_admin_menu() {
		$icon_svg = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15.5 2H8.5L4 6l3 2v12h10V8l3-2z"/><path d="M8.5 2C9.5 3.5 10.5 4.5 12 4.5S14.5 3.5 15.5 2"/></svg>' );

		add_menu_page(
			__( 'Preview AI', 'preview-ai' ),
			__( 'Preview AI', 'preview-ai' ),
			'manage_woocommerce',
			'preview-ai',
			array( $this, 'render_general_page' ),
			$icon_svg,
			58
		);

		add_submenu_page(
			'preview-ai',
			__( 'General', 'preview-ai' ),
			__( 'General', 'preview-ai' ),
			'manage_woocommerce',
			'preview-ai',
			array( $this, 'render_general_page' )
		);

		add_submenu_page(
			'preview-ai',
			__( 'Widget', 'preview-ai' ),
			__( 'Widget', 'preview-ai' ),
			'manage_woocommerce',
			'preview-ai-widget',
			array( $this, 'render_widget_page' )
		);

		add_submenu_page(
			'preview-ai',
			__( 'Statistics', 'preview-ai' ),
			__( 'Statistics', 'preview-ai' ),
			'manage_woocommerce',
			'preview-ai-stats',
			array( $this, 'render_stats_page' )
		);

		add_submenu_page(
			'preview-ai',
			__( 'Products', 'preview-ai' ),
			__( 'Products', 'preview-ai' ),
			'manage_woocommerce',
			'preview-ai-products',
			array( $this, 'render_products_page' )
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
			'<a href="' . admin_url( 'admin.php?page=preview-ai' ) . '">' . __( 'Settings', 'preview-ai' ) . '</a>',
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


	public function add_product_data_tab( $tabs ) {
		return $this->product->add_product_data_tab( $tabs );
	}

	public function render_product_data_panel() {
		$this->product->render_product_data_panel();
	}

	public function save_product_data( $post_id ) {
		$this->product->save_product_data( $post_id );
	}

	public function maybe_analyze_on_publish( $product_id, $product = null ) {
		$this->product->maybe_analyze_on_publish( $product_id, $product );
	}

	public function add_product_column( $columns ) {
		return $this->product->add_product_column( $columns );
	}

	public function render_product_column( $column, $post_id ) {
		$this->product->render_product_column( $column, $post_id );
	}

	public function add_product_filter_dropdown() {
		$this->product->add_product_filter_dropdown();
	}

	public function filter_products_by_preview_ai( $query ) {
		$this->product->filter_products_by_preview_ai( $query );
	}

	public function make_column_sortable( $columns ) {
		return $this->product->make_column_sortable( $columns );
	}

	public function sort_by_preview_ai( $query ) {
		$this->product->sort_by_preview_ai( $query );
	}

	public function handle_toggle_product() {
		$this->product->handle_toggle_product();
	}

	public function register_bulk_actions( $actions ) {
		return $this->product->register_bulk_actions( $actions );
	}

	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		return $this->product->handle_bulk_actions( $redirect_to, $action, $post_ids );
	}

	public function process_bulk_activate_batch() {
		$this->product->process_bulk_activate_batch();
	}

	public function show_bulk_action_notice() {
		$this->product->show_bulk_action_notice();
	}

	public function handle_toggle_category() {
		$this->product->handle_toggle_category();
	}

	public function handle_get_category_tree() {
		$this->product->handle_get_category_tree();
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
	 * Render the General settings page.
	 */
	public function render_general_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; only triggers onboarding wizard display, no data modification. Value sanitized with sanitize_key().
		$is_onboarding = isset( $_GET['onboarding'] ) && 'complete' === sanitize_key( wp_unslash( $_GET['onboarding'] ) );

		if ( $is_onboarding ) {
			add_action( 'admin_footer', array( $this->onboarding, 'render_onboarding_wizard' ) );
		}

		$preview_ai_current_page = 'general';
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
	}

	/**
	 * Render the Widget settings page.
	 */
	public function render_widget_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$preview_ai_current_page = 'widget';
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
	}

	/**
	 * Render the Statistics page.
	 */
	public function render_stats_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$preview_ai_current_page = 'stats';
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
	}

	/**
	 * Render the Products management page.
	 */
	public function render_products_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$preview_ai_current_page = 'products';
		include plugin_dir_path( __FILE__ ) . 'partials/preview-ai-admin-display.php';
	}

	/**
	 * Render the deactivation feedback modal on the plugins page.
	 */
	public function render_deactivation_modal() {
		$screen = get_current_screen();
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		?>
		<div id="preview-ai-deactivation-modal" class="preview-ai-deactivation-overlay" style="display:none;">
			<div class="preview-ai-deactivation-modal">
				<button type="button" class="preview-ai-deactivation-close" id="preview-ai-deactivation-close">&times;</button>
				<div class="preview-ai-deactivation-header">
					<div class="preview-ai-deactivation-icon">
						<svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
					</div>
					<h3><?php esc_html_e( 'Quick feedback before you go', 'preview-ai' ); ?></h3>
					<p><?php esc_html_e( 'We\'d love to know why you\'re deactivating so we can improve.', 'preview-ai' ); ?></p>
				</div>
				<form id="preview-ai-deactivation-form">
					<ul class="preview-ai-deactivation-reasons">
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="too_complex">
								<span><?php esc_html_e( 'Too complex to set up or use', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="not_working">
								<span><?php esc_html_e( 'Doesn\'t work as expected', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="not_compatible">
								<span><?php esc_html_e( 'Not compatible with my store/theme', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="too_expensive">
								<span><?php esc_html_e( 'Too expensive', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="just_testing">
								<span><?php esc_html_e( 'Just testing, not ready yet', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="found_alternative">
								<span><?php esc_html_e( 'Found a better alternative', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="ai_quality">
								<span><?php esc_html_e( 'AI-generated images aren\'t good enough', 'preview-ai' ); ?></span>
							</label>
						</li>
						<li>
							<label>
								<input type="radio" name="preview_ai_deactivation_reason" value="other">
								<span><?php esc_html_e( 'Other', 'preview-ai' ); ?></span>
							</label>
						</li>
					</ul>
					<textarea id="preview-ai-deactivation-details" class="preview-ai-deactivation-details" rows="3" placeholder="<?php esc_attr_e( 'Any additional details? (optional)', 'preview-ai' ); ?>" style="display:none;"></textarea>
					<div class="preview-ai-deactivation-actions">
						<button type="button" class="button" id="preview-ai-deactivation-skip">
							<?php esc_html_e( 'Skip & Deactivate', 'preview-ai' ); ?>
						</button>
						<button type="submit" class="button button-primary" id="preview-ai-deactivation-submit" disabled>
							<?php esc_html_e( 'Submit & Deactivate', 'preview-ai' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle deactivation feedback AJAX request.
	 */
	public function handle_deactivation_feedback() {
		check_ajax_referer( 'preview_ai_deactivation_feedback', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'preview-ai' ) ) );
		}

		$reason  = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
		$details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';

		if ( empty( $reason ) ) {
			wp_send_json_error( array( 'message' => __( 'No reason provided.', 'preview-ai' ) ) );
		}

		$api_key = get_option( 'preview_ai_api_key', '' );

		if ( ! empty( $api_key ) ) {
			$api = new PREVIEW_AI_Api();
			$api->request( 'feedback/deactivation', array(
				'reason'         => $reason,
				'details'        => $details,
				'plugin_version' => PREVIEW_AI_VERSION,
				'site_url'       => home_url(),
			), 5 );
		}

		wp_send_json_success();
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
					'configureIn'          => __( 'Configure in: Preview AI → Widget', 'preview-ai' ),
					'analyzingBackground'  => __( 'Analyzing and enabling in background', 'preview-ai' ),
					'productsAnalyzed'     => __( 'products are being analyzed and enabled. This may take a few minutes.', 'preview-ai' ),
					'closeAndCheck'        => __( 'You can close this window and check progress in Preview AI settings.', 'preview-ai' ),
					'closeAndContinue'     => __( 'Close & Continue', 'preview-ai' ),
					'tryNow'               => __( 'Try Preview AI Now', 'preview-ai' ),
					'experienceMagic'      => __( 'See how your customers will experience the magic!', 'preview-ai' ),
					'closeAndConfigure'    => __( 'Close & Configure Products', 'preview-ai' ),
					'catalogConfigured'    => __( 'Catalog analyzed and enabled!', 'preview-ai' ),
					'productsReady'        => __( 'products ready for virtual try-on', 'preview-ai' ),
					'couldNotAnalyze'      => __( 'Could not analyze catalog', 'preview-ai' ),
					'manualConfig'         => __( 'You can configure products manually.', 'preview-ai' ),
					'continueToSettings'   => __( 'Continue to Settings', 'preview-ai' ),
					'couldNotConnect'      => __( 'Could not connect to server', 'preview-ai' ),
					'analyzeLater'         => __( 'You can analyze and enable your catalog later from settings.', 'preview-ai' ),
					'continue'             => __( 'Continue', 'preview-ai' ),
				),
			)
		);

		$preview_ai_pages = array(
			'toplevel_page_preview-ai',
			'preview-ai_page_preview-ai-widget',
			'preview-ai_page_preview-ai-stats',
			'preview-ai_page_preview-ai-products',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; determines which scripts to enqueue based on admin page. Values sanitized with sanitize_key().
		$is_settings_page = ( in_array( $hook, $preview_ai_pages, true ) || ( isset( $_GET['page'] ) && 0 === strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'preview-ai' ) ) );
		$is_product_page  = ( 'post.php' === $hook || 'post-new.php' === $hook );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; conditionally enqueues onboarding script. Value sanitized with sanitize_key().
		$is_onboarding    = isset( $_GET['onboarding'] ) && 'complete' === sanitize_key( wp_unslash( $_GET['onboarding'] ) );

		if ( $is_onboarding ) {
			wp_enqueue_script( 'preview-ai-onboarding' );
		}

		wp_localize_script(
			$this->plugin_name,
			'previewAiAdmin',
			array(
				'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
				'nonce'                 => wp_create_nonce( 'preview_ai_learn_catalog' ),
				'verifyNonce'           => wp_create_nonce( 'preview_ai_verify_api_key' ),
				'dismissNonce'          => wp_create_nonce( 'preview_ai_dismiss_notice' ),
				'registerNonce'         => wp_create_nonce( 'preview_ai_register_site' ),
				'toggleProductNonce'    => wp_create_nonce( 'preview_ai_toggle_product' ),
				'toggleCategoryNonce'   => wp_create_nonce( 'preview_ai_toggle_category' ),
				'deactivationNonce'     => wp_create_nonce( 'preview_ai_deactivation_feedback' ),
				'pluginSlug'            => 'preview-ai',
				'i18n'                  => array(
					'error'        => __( 'An error occurred.', 'preview-ai' ),
					'apiPending'   => __( '(API integration pending)', 'preview-ai' ),
					'activating'   => __( 'Activating...', 'preview-ai' ),
					'activated'    => __( 'Preview AI activated! Redirecting...', 'preview-ai' ),
					'analyzing'    => __( 'Analyzing and enabling products...', 'preview-ai' ),
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
