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
		var $apiKeyField = $( '#preview_ai_api_key' );
		var $statusIndicator = $( '#pai-status-indicator' );

		// Helper to render status message.
		function renderStatus( res ) {
			if ( res.success ) {
				var data = res.data || {};
				var tokensLimit = parseInt( data.tokens_limit || 0, 10 );
				var tokensUsed = parseInt( data.tokens_used || 0, 10 );
				var tokensRemaining = Math.max( 0, tokensLimit - tokensUsed );
				var usagePercentage = tokensLimit > 0 ? Math.min( 100, Math.round( ( tokensUsed / tokensLimit ) * 100 ) ) : 0;

				$( '#pai-tokens-used' ).text( tokensUsed.toLocaleString() );
				$( '#pai-tokens-limit' ).text( tokensLimit.toLocaleString() );
				$( '#pai-usage-bar' ).css( 'width', usagePercentage + '%' );
				$( '#pai-tokens-remaining-text' ).html( '<strong>' + tokensRemaining.toLocaleString() + '</strong> remaining' );

				if ( data.renew_date ) {
					$( '#pai-renewal-date-container' ).html( 'Resets on <strong>' + data.renew_date + '</strong>' );
				} else {
					$( '#pai-renewal-date-container' ).empty();
				}

				var emailText = data.email || '—';
				var domainText = data.domain || '—';

				$( '#pai-status-email' ).text( emailText );
				$( '#pai-status-domain' ).text( domainText );

				if ( $statusIndicator.length ) {
					$statusIndicator.html( '<span class="dashicons dashicons-yes-alt" style="font-size: 18px; width: 18px; height: 18px;"></span> Verified' )
									.css( 'color', '#00a32a' );
				}
			} else {
				// Error handling for the new UI
				if ( $statusIndicator.length ) {
					$statusIndicator.html( '<span class="dashicons dashicons-warning" style="font-size: 18px; width: 18px; height: 18px;"></span> Error' )
									.css( 'color', '#d63638' );
				}
			}
		}

		// Auto-check status on page load (uses saved API key from DB).
		if ( $apiKeyField.length && $apiKeyField.val() && typeof previewAiAdmin !== 'undefined' ) {
			if ( $statusIndicator.length ) {
				$statusIndicator.html( '<span style="color:#646970; font-weight:400;">Checking...</span>' );
			}
			$.ajax( {
				url: previewAiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_verify_api_key',
					nonce: previewAiAdmin.verifyNonce
				},
				success: renderStatus
			} );
		}

		// Analyze & Enable Catalog with background processing support.
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

		// Handle toggle for Preview AI metabox via AJAX.
		var $productToggle = $( '#_preview_ai_enabled[data-product-id]' );

		if ( $productToggle.length && typeof previewAiAdmin !== 'undefined' ) {
			$productToggle.on( 'change', function() {
				var $input = $( this );
				var previousChecked = ! $input.is( ':checked' );
				var desiredState = $input.is( ':checked' ) ? 'yes' : 'no';
				var productId = $input.data( 'product-id' );
				var $status = $input.closest( '.preview-ai-metabox-header' ).find( '.preview-ai-col' );

				$input.prop( 'disabled', true );

				$.ajax({
					url: previewAiAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'preview_ai_toggle_product',
						nonce: previewAiAdmin.toggleProductNonce,
						product_id: productId,
						enabled: desiredState
					},
					success: function( response ) {
						if ( ! response.success ) {
							alert( response.data && response.data.message ? response.data.message : previewAiAdmin.i18n.error );
							$input.prop( 'checked', previousChecked );
							$input.prop( 'disabled', false );
							return;
						}

						var status = response.data;
						$status.attr( 'class', 'preview-ai-col ' + ( status.status_class || 'preview-ai-col--disabled' ) );
						$status.html( ( status.status_icon || '' ) + ' ' + ( status.status_text || '' ) );
						$input.prop( 'checked', !! status.is_enabled );
						$input.prop( 'disabled', !! status.toggle_disabled );
					},
					error: function() {
						alert( previewAiAdmin.i18n.error );
						$input.prop( 'checked', previousChecked );
						$input.prop( 'disabled', false );
					}
				});
			});
		}

		// Onboarding: activation form.
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

		// ================================
		// Deactivation Feedback Modal
		// ================================

		var $deactivateLink = $( '#the-list [data-slug="' + ( previewAiAdmin.pluginSlug || 'preview-ai' ) + '"] .deactivate a' );

		if ( $deactivateLink.length ) {
			var deactivateUrl = $deactivateLink.attr( 'href' );
			var $modal = $( '#preview-ai-deactivation-modal' );
			var $form = $( '#preview-ai-deactivation-form' );
			var $details = $( '#preview-ai-deactivation-details' );
			var $submitBtn = $( '#preview-ai-deactivation-submit' );

			$deactivateLink.on( 'click', function( e ) {
				e.preventDefault();
				$modal.fadeIn( 200 );
			} );

			// Close modal.
			$( '#preview-ai-deactivation-close' ).on( 'click', function() {
				$modal.fadeOut( 150 );
			} );

			// Close on overlay click.
			$modal.on( 'click', function( e ) {
				if ( $( e.target ).is( $modal ) ) {
					$modal.fadeOut( 150 );
				}
			} );

			// Close on Esc key.
			$( document ).on( 'keydown', function( e ) {
				if ( 27 === e.keyCode && $modal.is( ':visible' ) ) {
					$modal.fadeOut( 150 );
				}
			} );

			// Show/hide details textarea based on selection.
			$form.on( 'change', 'input[name="preview_ai_deactivation_reason"]', function() {
				$submitBtn.prop( 'disabled', false );
				var val = $( this ).val();
				if ( 'other' === val || 'not_working' === val || 'not_compatible' === val ) {
					$details.slideDown( 150 );
				} else {
					$details.slideUp( 150 );
				}
			} );

			// Skip & Deactivate.
			$( '#preview-ai-deactivation-skip' ).on( 'click', function() {
				window.location.href = deactivateUrl;
			} );

			// Submit & Deactivate.
			$form.on( 'submit', function( e ) {
				e.preventDefault();
				var reason = $form.find( 'input[name="preview_ai_deactivation_reason"]:checked' ).val();

				if ( ! reason ) {
					window.location.href = deactivateUrl;
					return;
				}

				$submitBtn.prop( 'disabled', true ).text( previewAiAdmin.i18n.deactivating || 'Deactivating...' );

				$.ajax( {
					url: previewAiAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'preview_ai_deactivation_feedback',
						nonce: previewAiAdmin.deactivationNonce,
						reason: reason,
						details: $details.val() || ''
					},
					complete: function() {
						window.location.href = deactivateUrl;
					}
				} );
			} );
		}

	});

})( jQuery );
