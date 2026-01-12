(function( $ ) {
	'use strict';

	$( function() {

		// Initialize color picker.
		$( '.preview-ai-color-picker' ).wpColorPicker();

		// Icon selector - update visual state on change.
		$( '.preview-ai-icon-option input' ).on( 'change', function() {
			$( '.preview-ai-icon-option' ).removeClass( 'is-selected' );
			$( this ).closest( '.preview-ai-icon-option' ).addClass( 'is-selected' );
		} );

		// Verify API Key functionality.
		var $verifyBtn = $( '#preview_ai_verify_btn' );
		var $verifyStatus = $( '#preview_ai_verify_status' );
		var $apiKeyField = $( '#preview_ai_api_key' );

		// Helper to render status message.
		function renderStatus( res ) {
			var html;
			if ( res.success ) {
				html = '<div style="margin-top:12px; padding:12px 16px; background:#f0fdf4; border-left:4px solid #22c55e; border-radius:4px;">';
				html += '<div style="color:#15803d; font-weight:600; margin-bottom:4px;">✓ Plan active</div>';
				html += '<div style="color:#166534;">' + res.data.tokens + ' previews remaining</div>';
				if ( res.data.renew_date ) {
					html += '<div style="color:#64748b; font-size:12px; margin-top:4px;">Renews on ' + res.data.renew_date + '</div>';
				}
				// Show upgrade link if free trial
				if ( res.data.subscription_status === 'free_trial' ) {
					html += '<div style="margin-top:8px;">';
					html += '<a href="https://previewai.app/pricing" target="_blank" style="color:#2271b1; text-decoration:none; font-size:13px; font-weight:500;">';
					html += '↑ Upgrade your subscription →</a>';
					html += '</div>';
				}
				html += '</div>';
				
				// Show/hide "Manage Subscription" button based on subscription status.
				var $manageBtn = $( '#preview_ai_manage_subscription_btn' );
				var subscriptionStatus = res.data.subscription_status || '';
				var isPaidPlan = ( subscriptionStatus !== 'free_trial' && subscriptionStatus !== '' );
				
				if ( isPaidPlan ) {
					// Show button if hidden.
					if ( ! $manageBtn.length ) {
						$( '#preview_ai_verify_btn' ).after(
							'<a href="https://previewai.app/account/" ' +
							'target="_blank" class="button" style="margin-left: 8px;" ' +
							'id="preview_ai_manage_subscription_btn">Manage Subscription</a>'
						);
					}
				} else {
					// Hide button if shown.
					$manageBtn.remove();
				}
			} else {
				html = '<div style="margin-top:12px; padding:12px 16px; background:#fef2f2; border-left:4px solid #ef4444; border-radius:4px;">';
				html += '<div style="color:#dc2626; font-weight:600;">✗ ' + ( res.data.message || 'Verification failed' ) + '</div>';
				html += '</div>';
			}
			$verifyStatus.html( html );
		}

		// Auto-check status on page load (uses saved API key from DB).
		if ( $apiKeyField.length && $apiKeyField.val() && typeof previewAiAdmin !== 'undefined' ) {
			$verifyStatus.html( '<span style="color:#646970;">Checking...</span>' );
			$.ajax( {
				url: previewAiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_verify_api_key',
					nonce: previewAiAdmin.verifyNonce
				},
				success: renderStatus,
				error: function() {
					$verifyStatus.html( '' );
				}
			} );
		}

		// Manual verify button (uses API key from field).
		$verifyBtn.on( 'click', function() {
			var apiKey = $apiKeyField.val();

			if ( ! apiKey ) {
				var html = '<div style="margin-top:12px; padding:12px 16px; background:#fef2f2; border-left:4px solid #ef4444; border-radius:4px;">';
				html += '<div style="color:#dc2626; font-weight:600;">✗ Enter an API key first</div>';
				html += '</div>';
				$verifyStatus.html( html );
				return;
			}

			$verifyBtn.prop( 'disabled', true ).text( '...' );
			$verifyStatus.html( '' );

			$.ajax( {
				url: previewAiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_verify_api_key',
					nonce: previewAiAdmin.verifyNonce,
					api_key: apiKey
				},
				success: function( res ) {
					$verifyBtn.prop( 'disabled', false ).text( 'Verify' );
					renderStatus( res );
				},
				error: function() {
					$verifyBtn.prop( 'disabled', false ).text( 'Verify' );
					var html = '<div style="margin-top:12px; padding:12px 16px; background:#fef2f2; border-left:4px solid #ef4444; border-radius:4px;">';
					html += '<div style="color:#dc2626; font-weight:600;">✗ Connection error</div>';
					html += '</div>';
					$verifyStatus.html( html );
				}
			} );
		} );

		// Learn My Catalog functional with background processing support.
		var learnBtn = document.getElementById( 'preview_ai_learn_catalog_btn' );
		var loadingEl = document.getElementById( 'preview_ai_learn_catalog_loading' );
		var progressEl = document.getElementById( 'preview_ai_learn_catalog_progress' );
		var resultEl = document.getElementById( 'preview_ai_learn_catalog_result' );
		var pollInterval = null;

		// Show completed result.
		function showCompletedResult( data ) {
			learnBtn.disabled = false;
			loadingEl.style.display = 'none';
			resultEl.style.display = 'block';
			resultEl.style.background = '#edfaef';
			resultEl.style.borderLeft = '4px solid #00a32a';
			resultEl.innerHTML = '<strong style="color:#00a32a;">✓</strong> ' + data.message;
			if ( data.warning ) {
				resultEl.innerHTML += '<br><small style="color:#d63638;">⚠️ ' + data.warning + '</small>';
			}
		}

		// Show error result.
		function showErrorResult( message ) {
			learnBtn.disabled = false;
			loadingEl.style.display = 'none';
			resultEl.style.display = 'block';
			resultEl.style.background = '#fcf0f1';
			resultEl.style.borderLeft = '4px solid #d63638';
			resultEl.innerHTML = '<strong style="color:#d63638;">✗</strong> ' + message;
		}

		// Poll for status updates.
		function pollCatalogStatus() {
			$.ajax({
				url: previewAiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_catalog_status',
					nonce: previewAiAdmin.nonce
				},
				success: function( response ) {
					if ( ! response.success ) {
						return;
					}

					var data = response.data;

					if ( 'processing' === data.status ) {
						// Update progress text.
						if ( progressEl && data.message ) {
							progressEl.textContent = data.message;
						}
					} else if ( 'completed' === data.status ) {
						// Stop polling and show result.
						if ( pollInterval ) {
							clearInterval( pollInterval );
							pollInterval = null;
						}
						showCompletedResult( data );
					} else {
						// Idle or unknown - stop polling.
						if ( pollInterval ) {
							clearInterval( pollInterval );
							pollInterval = null;
						}
					}
				}
			});
		}

		// Start polling if already processing.
		if ( typeof window.previewAiCatalogStatus !== 'undefined' && 'processing' === window.previewAiCatalogStatus ) {
			pollInterval = setInterval( pollCatalogStatus, 3000 );
		}

		if ( learnBtn && typeof previewAiAdmin !== 'undefined' ) {
			learnBtn.addEventListener( 'click', function() {
				learnBtn.disabled = true;
				loadingEl.style.display = 'block';
				resultEl.style.display = 'none';
				if ( progressEl ) {
					progressEl.textContent = previewAiAdmin.i18n.analyzing || 'Analyzing your catalog...';
				}

				$.ajax({
					url: previewAiAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'preview_ai_learn_catalog',
						nonce: previewAiAdmin.nonce
					},
					success: function( response ) {
						if ( ! response.success ) {
							showErrorResult( response.data.message || previewAiAdmin.i18n.error );
							// If not compatible, we might want to reload to show the specific error UI
							if ( response.data && response.data.code === 'store_not_compatible' ) {
								setTimeout( function() { window.location.reload(); }, 3000 );
							}
							return;
						}

						var data = response.data;

						if ( 'scheduled' === data.status ) {
							// Background processing started - poll for updates.
							if ( progressEl ) {
								progressEl.textContent = data.message;
							}
							pollInterval = setInterval( pollCatalogStatus, 3000 );
						} else if ( 'completed' === data.status ) {
							// Small catalog - completed immediately.
							showCompletedResult( data );
						} else {
							// Fallback.
							showCompletedResult( data );
						}
					},
					error: function() {
						showErrorResult( previewAiAdmin.i18n.error );
					}
				});
			});
		}

		// Re-verify compatibility.
		$( document ).on( 'click', '#preview_ai_reverify_compatibility', function( e ) {
			e.preventDefault();
			var $link = $( this );
			$link.css( 'opacity', '0.5' ).css( 'pointer-events', 'none' );
			
			$.ajax({
				url: previewAiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_reverify_compatibility',
					nonce: previewAiAdmin.nonce
				},
				success: function() {
					window.location.reload();
				}
			});
		} );

		// Handle dismissible notices - save to user meta.
		$( document ).on( 'click', '.notice[data-notice] .notice-dismiss', function() {
			var $notice = $( this ).closest( '.notice' );
			var noticeId = $notice.data( 'notice' );

			if ( noticeId && typeof previewAiAdmin !== 'undefined' ) {
				$.post( previewAiAdmin.ajaxUrl, {
					action: 'preview_ai_dismiss_notice',
					notice: noticeId,
					nonce: previewAiAdmin.dismissNonce
				} );
			}
		} );

		// Onboarding: Free trial registration form.
		var $registerForm = $( '#preview-ai-register-form' );
		
		if ( $registerForm.length && typeof previewAiAdmin !== 'undefined' ) {
			$registerForm.on( 'submit', function( e ) {
				e.preventDefault();
				
				var $form = $( this );
				var $btn = $form.find( 'button[type="submit"]' );
				var $btnText = $form.find( '.preview-ai-onboarding__btn-text' );
				var $btnLoading = $form.find( '.preview-ai-onboarding__btn-loading' );
				var $notice = $( '#preview-ai-onboarding' );
				var $content = $notice.find( '.preview-ai-onboarding__content' );
				var $success = $notice.find( '.preview-ai-onboarding__success' );
				var email = $form.find( '#preview-ai-register-email' ).val();
				
				// Disable and show loading.
				$btn.prop( 'disabled', true );
				$btnText.hide();
				$btnLoading.show();
				
				$.ajax( {
					url: previewAiAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'preview_ai_register_site',
						nonce: previewAiAdmin.registerNonce,
						email: email
					},
					success: function( res ) {
						if ( res.success ) {
							// Show success message.
							$content.hide();
							$success.find( '.preview-ai-onboarding__success-text' ).text( res.data.message );
							$success.show();
							$notice.css( 'border-left-color', '#00a32a' );
							
							// Redirect to settings page with onboarding flag.
							setTimeout( function() {
								window.location.href = 'edit.php?post_type=product&page=preview-ai&onboarding=complete';
							}, 4000 );
						} else {
							// Show error inline.
							$btn.prop( 'disabled', false );
							$btnText.show();
							$btnLoading.hide();
							alert( res.data.message || previewAiAdmin.i18n.error );
						}
					},
					error: function() {
						$btn.prop( 'disabled', false );
						$btnText.show();
						$btnLoading.hide();
						alert( previewAiAdmin.i18n.error );
					}
				} );
			} );
		}

	});

})( jQuery );
