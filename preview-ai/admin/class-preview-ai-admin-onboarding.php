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
		<div id="preview-ai-onboarding-wizard" style="position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:100000;display:flex;align-items:center;justify-content:center;">
			<div style="background:#fff;border-radius:16px;padding:48px;max-width:520px;text-align:center;box-shadow:0 25px 50px rgba(0,0,0,0.3);position:relative;">
				<button type="button" id="preview-ai-onboarding-close" style="position:absolute;top:16px;right:16px;background:none;border:none;cursor:pointer;padding:8px;border-radius:50%;transition:background 0.2s;" title="<?php esc_attr_e( 'Close', 'preview-ai' ); ?>">
					<svg width="20" height="20" fill="none" stroke="#94a3b8" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
				</button>
				<div style="width:72px;height:72px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;margin:0 auto 24px;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 25px rgba(34,197,94,0.3);">
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

		<script>
		(function($) {
			'use strict';
			
			var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
			var nonce = '<?php echo esc_js( wp_create_nonce( 'preview_ai_learn_catalog' ) ); ?>';
			
			var $bar = $('#onboarding-bar');
			var $status = $('#onboarding-status');
			var $result = $('#onboarding-result');
			var $progress = $('#onboarding-progress');
			
			var currentWidth = 0;
			var progressInterval = setInterval(function() {
				if (currentWidth < 95) {
					// Load slowly to give sense of thorough analysis
					var step = currentWidth < 60 ? Math.floor(Math.random() * 3) + 1 : (Math.random() < 0.5 ? 1 : 0);
					if (step > 0 || currentWidth < 20) { // Keep moving at the start, then allow "pauses"
						currentWidth = Math.min(95, currentWidth + (step || 1));
						$bar.css('width', currentWidth + '%');
					}
				}
			}, 800);
			
			$.ajax({
				url: ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_learn_catalog',
					nonce: nonce
				},
				beforeSend: function() {
					$status.text('<?php echo esc_js( __( 'Configuring products...', 'preview-ai' ) ); ?>');
				},
				success: function(response) {
					clearInterval(progressInterval);
					$bar.css('width', '100%');
					
					setTimeout(function() {
						$progress.slideUp(300);
						
						if (response.success) {
							var status = response.data.status || 'completed';
							
							if (status === 'scheduled') {
								var totalProducts = response.data.total || 0;
								var scheduledPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:16px;text-align:left;">' +
									'<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 <?php echo esc_js( __( 'Using a custom product template?', 'preview-ai' ) ); ?></p>' +
									'<p style="color:#0284c7;margin:0 0 8px;font-size:12px;"><?php echo esc_js( __( 'If the widget does not appear automatically, you can add it manually:', 'preview-ai' ) ); ?></p>' +
									'<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
									'<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
									'<li><strong>Elementor:</strong> <?php echo esc_js( __( 'Search for "Preview AI" widget', 'preview-ai' ) ); ?></li>' +
									'</ul>' +
									'<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ <?php echo esc_js( __( 'Configure in: Products → Preview AI → Widget tab', 'preview-ai' ) ); ?></p>' +
									'</div>';
								$result.html(
									'<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px;margin-bottom:24px;">' +
									'<p style="color:#1d4ed8;font-weight:600;margin:0;font-size:16px;">⏳ ' + 
									'<?php echo esc_js( __( 'Analyzing in background', 'preview-ai' ) ); ?></p>' +
									'<p style="color:#1e40af;margin:8px 0 0;font-size:14px;">' + totalProducts + ' <?php echo esc_js( __( 'products are being analyzed. This may take a few minutes.', 'preview-ai' ) ); ?></p>' +
									'<p style="color:#3b82f6;margin:12px 0 0;font-size:13px;"><?php echo esc_js( __( 'You can close this window and check progress in Preview AI settings.', 'preview-ai' ) ); ?></p>' +
									'</div>' +
									scheduledPdpNotice +
									'<div style="text-align:center;margin-top:16px;">' +
									'<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;font-size:14px;" onclick="location.reload()">' +
									'<?php echo esc_js( __( 'Close & Continue', 'preview-ai' ) ); ?></button>' +
									'</div>'
								).slideDown(300);
								return;
							}
							
							var configured = response.data.configured || 0;
							var total = response.data.total || 0;
							var isLimited = response.data.is_limited || false;
							var tryProductUrl = response.data.try_product_url || '';
							var warning = response.data.warning || '';
							
							var limitedNotice = '';
							if (isLimited) {
								limitedNotice = '<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:12px;margin-top:12px;">' +
									'<p style="color:#92400e;margin:0;font-size:13px;">⚡ <?php echo esc_js( __( 'Free trial: Few random products were analyzed.', 'preview-ai' ) ); ?></p>' +
									'</div>';
							}
							
							var warningNotice = '';
							if (warning) {
								warningNotice = '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;margin-top:12px;">' +
									'<p style="color:#dc2626;margin:0;font-size:13px;">⚠️ ' + warning + '</p>' +
									'</div>';
							}
							
							var customPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:12px;text-align:left;">' +
								'<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 <?php echo esc_js( __( 'Using a custom product template?', 'preview-ai' ) ); ?></p>' +
								'<p style="color:#0284c7;margin:0 0 8px;font-size:12px;"><?php echo esc_js( __( 'If the widget does not appear automatically, you can add it manually:', 'preview-ai' ) ); ?></p>' +
								'<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
								'<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
								'<li><strong>Elementor:</strong> <?php echo esc_js( __( 'Search for "Preview AI" widget', 'preview-ai' ) ); ?></li>' +
								'</ul>' +
								'<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ <?php echo esc_js( __( 'Configure in: Products → Preview AI → Widget tab', 'preview-ai' ) ); ?></p>' +
								'</div>';

							var actionButtons = '';
							if (tryProductUrl && configured > 0) {
								actionButtons = '<div style="margin-bottom:16px;">' +
									'<a href="' + tryProductUrl + '" target="_blank" class="button button-primary" style="height:auto;padding:14px 32px;font-size:15px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;box-shadow:0 4px 14px rgba(99,102,241,0.4);">' +
									'✨ <?php echo esc_js( __( 'Try Preview AI Now', 'preview-ai' ) ); ?></a>' +
									'</div>' +
									'<p style="color:#64748b;font-size:13px;margin:0;"><?php echo esc_js( __( 'See how your customers will experience the magic!', 'preview-ai' ) ); ?></p>' +
									customPdpNotice;
							} else {
								actionButtons = '<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;font-size:14px;" onclick="location.reload()">' +
									'<?php echo esc_js( __( 'Close & Configure Products', 'preview-ai' ) ); ?></button>' +
									customPdpNotice;
							}
							
							$result.html(
								'<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;margin-bottom:24px;">' +
								'<p style="color:#166534;font-weight:600;margin:0;font-size:16px;">✓ ' + 
								'<?php echo esc_js( __( 'Catalog configured!', 'preview-ai' ) ); ?></p>' +
								'<p style="color:#15803d;margin:8px 0 0;font-size:14px;">' + configured + ' <?php echo esc_js( __( 'products ready for preview', 'preview-ai' ) ); ?></p>' +
								limitedNotice +
								warningNotice +
								'</div>' +
								'<div style="text-align:center;">' +
								actionButtons +
								'</div>'
							).slideDown(300);
						} else {
							var errorPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:16px;text-align:left;">' +
								'<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 <?php echo esc_js( __( 'Using a custom product template?', 'preview-ai' ) ); ?></p>' +
								'<p style="color:#0284c7;margin:0 0 8px;font-size:12px;"><?php echo esc_js( __( 'You can add the widget manually:', 'preview-ai' ) ); ?></p>' +
								'<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
								'<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
								'<li><strong>Elementor:</strong> <?php echo esc_js( __( 'Search for "Preview AI" widget', 'preview-ai' ) ); ?></li>' +
								'</ul>' +
								'<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ <?php echo esc_js( __( 'Configure in: Products → Preview AI → Widget tab', 'preview-ai' ) ); ?></p>' +
								'</div>';
							$result.html(
								'<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:24px;">' +
								'<p style="color:#dc2626;font-weight:600;margin:0;">' + (response.data.message || '<?php echo esc_js( __( 'Could not analyze catalog', 'preview-ai' ) ); ?>') + '</p>' +
								'<p style="color:#b91c1c;margin:8px 0 0;font-size:14px;"><?php echo esc_js( __( 'You can configure products manually.', 'preview-ai' ) ); ?></p>' +
								'</div>' +
								errorPdpNotice +
								'<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;margin-top:16px;" onclick="location.reload()">' +
								'<?php echo esc_js( __( 'Continue to Settings', 'preview-ai' ) ); ?></button>'
							).slideDown(300);
						}
					}, 500);
				},
				error: function() {
					clearInterval(progressInterval);
					$bar.css('width', '100%');
					$progress.slideUp(300);
					
					var connectionPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:16px;text-align:left;">' +
						'<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 <?php echo esc_js( __( 'Using a custom product template?', 'preview-ai' ) ); ?></p>' +
						'<p style="color:#0284c7;margin:0 0 8px;font-size:12px;"><?php echo esc_js( __( 'You can add the widget manually:', 'preview-ai' ) ); ?></p>' +
						'<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
						'<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
						'<li><strong>Elementor:</strong> <?php echo esc_js( __( 'Search for "Preview AI" widget', 'preview-ai' ) ); ?></li>' +
						'</ul>' +
						'<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ <?php echo esc_js( __( 'Configure in: Products → Preview AI → Widget tab', 'preview-ai' ) ); ?></p>' +
						'</div>';
					$result.html(
						'<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:20px;margin-bottom:24px;">' +
						'<p style="color:#92400e;font-weight:600;margin:0;"><?php echo esc_js( __( 'Could not connect to server', 'preview-ai' ) ); ?></p>' +
						'<p style="color:#a16207;margin:8px 0 0;font-size:14px;"><?php echo esc_js( __( 'You can analyze your catalog later from settings.', 'preview-ai' ) ); ?></p>' +
						'</div>' +
						connectionPdpNotice +
						'<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;margin-top:16px;" onclick="location.reload()">' +
						'<?php echo esc_js( __( 'Continue', 'preview-ai' ) ); ?></button>'
					).slideDown(300);
				}
			});
			
			if (history.replaceState) {
				var cleanUrl = window.location.href
					.replace(/[?&]onboarding=complete/, '')
					.replace(/\?$/, '');
				history.replaceState(null, '', cleanUrl);
			}
			
			$('#preview-ai-onboarding-close').on('click', function() {
				$('#preview-ai-onboarding-wizard').fadeOut(300, function() {
					$(this).remove();
				});
			}).on('mouseenter', function() {
				$(this).css('background', '#f1f5f9');
			}).on('mouseleave', function() {
				$(this).css('background', 'none');
			});
			
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Handle AJAX request to register site for free trial.
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
			'message'      => $result['message'] ?? __( 'Your free trial has been activated!', 'preview-ai' ),
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
					<p><?php esc_html_e( 'Get 20 FREE previews to try Preview AI on your store. Enter your email to activate:', 'preview-ai' ); ?></p>
				</div>
				<form class="preview-ai-onboarding__form" id="preview-ai-register-form">
					<input type="email" 
						   name="email" 
						   id="preview-ai-register-email"
						   value="<?php echo esc_attr( $admin_email ); ?>" 
						   placeholder="<?php esc_attr_e( 'Your email address', 'preview-ai' ); ?>"
						   required />
					<button type="submit" class="button button-primary">
						<span class="preview-ai-onboarding__btn-text"><?php esc_html_e( 'Start Free Trial', 'preview-ai' ); ?></span>
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
		<style>
			.preview-ai-onboarding-notice {
				padding: 16px 20px;
				border-left-color: #6366f1;
			}
			.preview-ai-onboarding__content {
				display: flex;
				align-items: center;
				gap: 16px;
				flex-wrap: wrap;
			}
			.preview-ai-onboarding__icon {
				flex-shrink: 0;
				width: 48px;
				height: 48px;
				background: linear-gradient(135deg, #6366f1, #8b5cf6);
				border-radius: 12px;
				display: flex;
				align-items: center;
				justify-content: center;
			}
			.preview-ai-onboarding__icon svg {
				width: 24px;
				height: 24px;
				stroke: white;
			}
			.preview-ai-onboarding__text {
				flex: 1;
				min-width: 200px;
			}
			.preview-ai-onboarding__text h3 {
				margin: 0 0 4px;
				font-size: 15px;
			}
			.preview-ai-onboarding__text p {
				margin: 0;
				color: #646970;
			}
			.preview-ai-onboarding__form {
				display: flex;
				gap: 8px;
				flex-wrap: wrap;
			}
			.preview-ai-onboarding__form input[type="email"] {
				width: 280px;
				max-width: 100%;
			}
			.preview-ai-onboarding__success {
				display: flex;
				align-items: center;
				gap: 8px;
				color: #00a32a;
				font-weight: 500;
			}
			.preview-ai-onboarding__success .dashicons {
				color: #00a32a;
				font-size: 24px;
				width: 24px;
				height: 24px;
			}
		</style>
		<?php
	}
}

