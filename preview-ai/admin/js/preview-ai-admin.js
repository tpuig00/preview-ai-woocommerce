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
				html += '</div>';
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

		// Learn My Catalog functional
		var learnBtn = document.getElementById( 'preview_ai_learn_catalog_btn' );
		var loadingEl = document.getElementById( 'preview_ai_learn_catalog_loading' );
		var resultEl = document.getElementById( 'preview_ai_learn_catalog_result' );

		if ( learnBtn && typeof previewAiAdmin !== 'undefined' ) {
			learnBtn.addEventListener( 'click', function() {
				learnBtn.disabled = true;
				loadingEl.style.display = 'block';
				resultEl.style.display = 'none';

				$.ajax({
					url: previewAiAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'preview_ai_learn_catalog',
						nonce: previewAiAdmin.nonce
					},
					success: function( response ) {
						learnBtn.disabled = false;
						loadingEl.style.display = 'none';
						resultEl.style.display = 'block';

						if ( response.success ) {
							resultEl.style.background = '#edfaef';
							resultEl.style.borderLeft = '4px solid #00a32a';
							resultEl.innerHTML = '<strong style="color:#00a32a;">✓</strong> ' + response.data.message;
							if ( response.data.pending ) {
								resultEl.innerHTML += '<br><small style="color:#787c82;">' + previewAiAdmin.i18n.apiPending + '</small>';
							}
						} else {
							resultEl.style.background = '#fcf0f1';
							resultEl.style.borderLeft = '4px solid #d63638';
							resultEl.innerHTML = '<strong style="color:#d63638;">✗</strong> ' + ( response.data.message || previewAiAdmin.i18n.error );
						}
					},
					error: function() {
						learnBtn.disabled = false;
						loadingEl.style.display = 'none';
						resultEl.style.display = 'block';
						resultEl.style.background = '#fcf0f1';
						resultEl.style.borderLeft = '4px solid #d63638';
						resultEl.innerHTML = '<strong style="color:#d63638;">✗</strong> ' + previewAiAdmin.i18n.error;
					}
				});
			});
		}

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

	});

})( jQuery );
