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

		$relevant_screens = array( 'toplevel_page_preview-ai', 'preview-ai_page_preview-ai-widget', 'preview-ai_page_preview-ai-stats', 'preview-ai_page_preview-ai-products', 'product', 'edit-product' );
		if ( ! in_array( $screen->id, $relevant_screens, true ) ) return;

		$api_key = get_option( 'preview_ai_api_key', '' );

		if ( empty( $api_key ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Preview AI:', 'preview-ai' ); ?></strong>
					<?php esc_html_e( 'API key not configured. The widget is hidden from your customers.', 'preview-ai' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=preview-ai' ) ); ?>">
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

		$tokens_remaining = isset( $status['tokens_remaining'] ) ? intval( $status['tokens_remaining'] ) : -1;
		$tokens_limit     = isset( $status['tokens_limit'] ) ? intval( $status['tokens_limit'] ) : 0;
		$upgrade_url      = ! empty( $status['recomended_plan'] ) ? $status['recomended_plan'] : 'https://previewai.app/pricing';
		$renew_date       = ! empty( $status['current_period_end'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $status['current_period_end'] ) ) : '';

		// Tokens exhausted — widget is silently disabled for shoppers.
		if ( 0 === $tokens_remaining ) {
			?>
			<div class="notice notice-error preview-ai-quota-notice">
				<div class="preview-ai-quota-notice__inner">
					<div class="preview-ai-quota-notice__body">
						<p class="preview-ai-quota-notice__title">
							<strong><?php esc_html_e( 'Your customers can\'t try on your products right now.', 'preview-ai' ); ?></strong>
						</p>
						<p class="preview-ai-quota-notice__desc">
							<?php
							if ( $renew_date ) {
								printf(
									/* translators: %s: renewal date */
									esc_html__( 'You\'ve used all your Preview AI quota. The virtual try-on widget is hidden from your store until your plan resets on %s — every shopper visiting your product pages between now and then leaves without seeing your clothes on themselves.', 'preview-ai' ),
									'<strong>' . esc_html( $renew_date ) . '</strong>'
								);
							} else {
								esc_html_e( 'You\'ve used all your Preview AI quota. The virtual try-on widget is hidden from your store — every shopper visiting your product pages leaves without seeing your clothes on themselves.', 'preview-ai' );
							}
							?>
						</p>
					</div>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary preview-ai-quota-notice__btn">
						<?php esc_html_e( 'Upgrade My Plan →', 'preview-ai' ); ?>
					</a>
				</div>
			</div>
			<?php
			return;
		}

		// Tokens running low — warn before the widget goes dark.
		if ( $tokens_limit > 0 && $tokens_remaining >= 0 && ( $tokens_remaining / $tokens_limit ) < 0.2 ) {
			?>
			<div class="notice notice-warning preview-ai-quota-notice">
				<div class="preview-ai-quota-notice__inner">
					<div class="preview-ai-quota-notice__body">
						<p class="preview-ai-quota-notice__title">
							<strong>
								<?php
								printf(
									/* translators: %s: number of remaining previews */
									esc_html__( 'Heads up — only %s try-on previews left this period.', 'preview-ai' ),
									'<strong>' . esc_html( number_format_i18n( $tokens_remaining ) ) . '</strong>'
								);
								?>
							</strong>
						</p>
						<p class="preview-ai-quota-notice__desc">
							<?php esc_html_e( 'When they run out, the virtual try-on disappears from your store and shoppers go back to guessing whether it fits. Upgrade now to keep converting browsers into buyers.', 'preview-ai' ); ?>
						</p>
					</div>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary preview-ai-quota-notice__btn">
						<?php esc_html_e( 'Get More Previews →', 'preview-ai' ); ?>
					</a>
				</div>
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

