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
				<div class="notice notice-info preview-ai-try-notice" style="border-left-color:#6366f1;padding:16px 20px;">
					<div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
						<div style="flex-shrink:0;width:44px;height:44px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:12px;display:flex;align-items:center;justify-content:center;">
							<span style="font-size:22px;">✨</span>
						</div>
						<div style="flex:1;min-width:200px;">
							<p style="margin:0 0 4px;font-weight:600;color:#1e293b;">
								<?php esc_html_e( 'One last step: Try Preview AI!', 'preview-ai' ); ?>
							</p>
							<p style="margin:0;color:#64748b;font-size:13px;">
								<?php esc_html_e( 'See how your customers will experience the virtual try-on.', 'preview-ai' ); ?>
							</p>
						</div>
						<a href="<?php echo esc_url( $try_product_url ); ?>" target="_blank" class="button button-primary" style="height:auto;padding:10px 20px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;">
							<?php esc_html_e( 'Try It Now →', 'preview-ai' ); ?>
						</a>
						<button type="button" class="button" style="height:auto;padding:10px 16px;" onclick="jQuery.post(ajaxurl, {action:'preview_ai_dismiss_try_notice',nonce:'<?php echo esc_js( wp_create_nonce( 'preview_ai_dismiss_notice' ) ); ?>'}, function(){jQuery('.preview-ai-try-notice').slideUp();});">
							<?php esc_html_e( 'I already tried it', 'preview-ai' ); ?>
						</button>
					</div>
				</div>
				<?php
			}
		}

		$status = PREVIEW_AI_Api::get_account_status();
		if ( empty( $status ) ) return;

		if ( isset( $status['tokens_remaining'] ) && $status['tokens_remaining'] <= 0 && ( ! isset( $status['active'] ) || $status['active'] ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( '⚠️ Your tokens have run out. The widget has been automatically disabled and your customers cannot preview products.', 'preview-ai' ); ?>
					<a href="https://previewai.app/pricing" target="_blank" style="font-weight: bold;">
						<?php esc_html_e( 'Upgrade your plan →', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		if ( isset( $status['active'] ) && ! $status['active'] ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( '⚠️ Your subscription has been deactivated. The widget is hidden from your customers.', 'preview-ai' ); ?>
					<a href="https://previewai.app/support" target="_blank">
						<?php esc_html_e( 'Contact support', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
			return;
		}

		if ( isset( $status['tokens_remaining'], $status['tokens_limit'] ) && $status['tokens_limit'] > 0 && ( $status['tokens_remaining'] / $status['tokens_limit'] ) < 0.1 ) {
			$user_id = get_current_user_id();
			if ( get_user_meta( $user_id, 'preview_ai_dismissed_low_tokens', true ) ) return;
			?>
			<div class="notice notice-warning is-dismissible" data-notice="preview_ai_low_tokens">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php printf( esc_html__( 'You have only %d tokens remaining this month.', 'preview-ai' ), intval( $status['tokens_remaining'] ) ); ?>
					<a href="https://previewai.app/pricing" target="_blank">
						<?php esc_html_e( 'Upgrade your plan', 'preview-ai' ); ?>
					</a>
				</p>
			</div>
			<?php
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
		$notice = isset( $_POST['notice'] ) ? sanitize_key( $_POST['notice'] ) : '';
		if ( 'preview_ai_low_tokens' === $notice ) {
			update_user_meta( get_current_user_id(), 'preview_ai_dismissed_low_tokens', true );
		}
		wp_send_json_success();
	}

	/**
	 * Handle AJAX request to dismiss the "try it" onboarding notice.
	 */
	public function handle_dismiss_try_notice() {
		check_ajax_referer( 'preview_ai_dismiss_notice', 'nonce' );
		delete_option( 'preview_ai_needs_first_try' );
		wp_send_json_success();
	}
}

