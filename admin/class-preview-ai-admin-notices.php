<?php

/**
 * Handle admin notices.
 */
class PREVIEW_AI_Admin_Notices {

	/**
	 * Display admin notices for API issues and onboarding.
	 */
	public function display_admin_notices() {
		$screen = get_current_screen();
		if ( ! $screen ) return;

		$relevant_screens = array( 'product_page_preview-ai', 'product', 'edit-product' );
		if ( ! in_array( $screen->id, $relevant_screens, true ) ) return;

		$api_key = get_option( 'preview_ai_api_key', '' );

		if ( empty( $api_key ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( 'API key not configured. The widget is hidden from your customers.', 'preview-ai' ); ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai' ) ); ?>">
						<?php esc_html_e( 'Configure now', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		if ( get_option( 'preview_ai_needs_first_try' ) ) {
			// Helper moved to Product class.
			$product_class = new PREVIEW_AI_Admin_Product();
			$try_product_url = $this->get_first_configured_product_url();
			if ( $try_product_url ) {
				?>
				<div class="notice notice-info preview-ai-try-notice">
					<div class="preview-ai-try-notice-inner">
						<div class="preview-ai-try-notice-icon">
							<span>✨</span>
						</div>
						<div class="preview-ai-try-notice-content">
							<p class="preview-ai-try-notice-title">
								<?php esc_html_e( 'One last step: Try Preview AI!', 'preview-ai' ); ?>
							</p>
							<p class="preview-ai-try-notice-desc">
								<?php esc_html_e( 'See how your customers will experience the virtual try-on.', 'preview-ai' ); ?>
							</p>
						</div>
						<a href="<?php echo esc_url( $try_product_url ); ?>" target="_blank" class="button button-primary preview-ai-try-notice-btn">
							<?php esc_html_e( 'Try It Now →', 'preview-ai' ); ?>
						</a>
						<button type="button" class="button preview-ai-try-notice-dismiss" onclick="jQuery.post(ajaxurl, {action:'preview_ai_dismiss_try_notice',nonce:'<?php echo esc_js( wp_create_nonce( 'preview_ai_dismiss_notice' ) ); ?>'}, function(){jQuery('.preview-ai-try-notice').slideUp();});">
							<?php esc_html_e( 'I already tried it', 'preview-ai' ); ?>
						</button>
					</div>
				</div>
				<?php
			}
		}

		$status = PREVIEW_AI_Api::get_account_status();
		if ( empty( $status ) ) return;

		// Account deactivated by the service.
		if ( isset( $status['active'] ) && ! $status['active'] ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( '⚠️ There is an issue with your Preview AI service connection. Please check your account status.', 'preview-ai' ); ?>
					<a href="https://previewai.app/dashboard" target="_blank">
						<?php esc_html_e( 'View Dashboard', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}
	}

	/**
	 * Get URL of first configured product for onboarding.
	 */
	private function get_first_configured_product_url() {
		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary to find first configured product; limited to 1 result for minimal performance impact.
				'meta_query'     => array(
					array(
						'key'     => '_preview_ai_recommended_subtype',
						'value'   => '',
						'compare' => '!=',
					),
				),
				'fields'         => 'ids',
			)
		);

		if ( empty( $products ) ) {
			return false;
		}

		return add_query_arg( 'demo', 'yes', get_permalink( $products[0] ) );
	}

	/**
	 * Handle AJAX request to dismiss admin notice.
	 */
	public function handle_dismiss_notice() {
		check_ajax_referer( 'preview_ai_dismiss_notice', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'preview-ai' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * Handle AJAX request to dismiss the "try it" onboarding notice.
	 */
	public function handle_dismiss_try_notice() {
		check_ajax_referer( 'preview_ai_dismiss_notice', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized access.', 'preview-ai' ) ) );
		}

		delete_option( 'preview_ai_needs_first_try' );
		wp_send_json_success();
	}
}

