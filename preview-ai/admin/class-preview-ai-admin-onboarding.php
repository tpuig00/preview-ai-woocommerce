<?php

/**
 * Handle plugin onboarding.
 */
class PREVIEW_AI_Admin_Onboarding {

	/**
	 * Render onboarding wizard that auto-analyzes catalog.
	 */
	public function render_onboarding_wizard() {
		// Mark that user needs to try the widget (only during initial onboarding).
		update_option( 'preview_ai_needs_first_try', true );
		?>
		<div id="preview-ai-onboarding-wizard">
			<div id="preview-ai-onboarding-wizard-content">
				<button type="button" id="preview-ai-onboarding-close" title="<?php esc_attr_e( 'Close', 'preview-ai' ); ?>">
					<svg width="20" height="20" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
				</button>
				<div class="onboarding-success-icon" style="width:72px;height:72px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;margin:0 auto 24px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 25px rgba(34,197,94,0.3);">
					<svg width="36" height="36" fill="none" stroke="#fff" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
				</div>
				<h2 style="margin:0 0 8px;font-size:26px;color:#1e293b;font-weight:700;">🎉 <?php esc_html_e( 'Preview AI Activated!', 'preview-ai' ); ?></h2>
				<p style="color:#64748b;margin:0 0 32px;font-size:15px;"><?php esc_html_e( 'Setting up your store...', 'preview-ai' ); ?></p>
				
				<div id="onboarding-progress" style="margin-bottom:32px;">
					<div style="height:10px;background:#e2e8f0;border-radius:5px;overflow:hidden;">
						<div id="onboarding-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#6366f1,#8b5cf6);transition:width 0.5s ease;"></div>
					</div>
					<p id="onboarding-status" style="margin:16px 0 0;color:#64748b;font-size:14px;"><?php esc_html_e( 'Analyzing your product catalog...', 'preview-ai' ); ?></p>
				</div>
				
				<div id="onboarding-result" style="display:none;"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request to register site for Preview AI service.
	 */
	public function handle_register_site() {
		check_ajax_referer( 'preview_ai_register_site', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'preview-ai' ) ) );
		}

		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'preview-ai' ) ) );
		}

		$result = PREVIEW_AI_Api::register_site( $email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		if ( isset( $result['api_key'] ) ) {
			update_option( 'preview_ai_api_key', sanitize_text_field( $result['api_key'] ) );
			delete_option( 'preview_ai_needs_onboarding' );

			PREVIEW_AI_Api::update_account_status( array(
				'tokens_remaining' => $result['tokens_limit'] ?? 0,
				'tokens_limit'     => $result['tokens_limit'] ?? 0,
				'active'           => true,
			) );
		}

		wp_send_json_success( array(
			'message'      => $result['message'] ?? __( 'Preview AI has been activated!', 'preview-ai' ),
			'tokens_limit' => $result['tokens_limit'] ?? 0,
		) );
	}

	/**
	 * Display onboarding notice for new installations.
	 */
	public function display_onboarding_notice() {
		if ( ! get_option( 'preview_ai_needs_onboarding' ) ) {
			return;
		}

		$api_key = get_option( 'preview_ai_api_key', '' );
		if ( ! empty( $api_key ) ) {
			delete_option( 'preview_ai_needs_onboarding' );
			return;
		}

		$admin_email = get_option( 'admin_email', '' );
		?>
		<div class="notice notice-info preview-ai-onboarding-notice" id="preview-ai-onboarding">
			<div class="preview-ai-onboarding__content">
				<div class="preview-ai-onboarding__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
						<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
					</svg>
				</div>
				<div class="preview-ai-onboarding__text">
					<h3><?php esc_html_e( 'Activate Preview AI', 'preview-ai' ); ?></h3>
					<p><?php esc_html_e( 'Enter your email to activate Preview AI on your store:', 'preview-ai' ); ?></p>
				</div>
				<form class="preview-ai-onboarding__form" id="preview-ai-register-form">
					<input type="email" 
						   name="email" 
						   id="preview-ai-register-email"
						   value="<?php echo esc_attr( $admin_email ); ?>" 
						   placeholder="<?php esc_attr_e( 'Your email address', 'preview-ai' ); ?>"
						   required />
					<button type="submit" class="button button-primary">
						<span class="preview-ai-onboarding__btn-text"><?php esc_html_e( 'Activate Now', 'preview-ai' ); ?></span>
						<span class="preview-ai-onboarding__btn-loading" style="display:none;">
							<span class="spinner is-active" style="margin:0;float:none;"></span>
						</span>
					</button>
				</form>
			</div>
			<div class="preview-ai-onboarding__success" style="display:none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<span class="preview-ai-onboarding__success-text"></span>
			</div>
		</div>
		<?php
	}
}

