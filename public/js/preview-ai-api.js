(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Api = (function() {
		return {
			/**
			 * Fetch a fresh nonce from the server.
			 *
			 * Page-caching plugins freeze the nonce embedded in the HTML,
			 * so it can be expired by the time the visitor interacts.
			 * This lightweight call obtains a valid nonce before each
			 * real API request.
			 *
			 * @param {Function} callback Called once the nonce has been refreshed (or on failure).
			 */
			refreshNonce: function( callback ) {
				$.ajax( {
					url: previewAiData.ajaxUrl,
					type: 'POST',
					data: { action: 'preview_ai_nonce' },
					success: function( res ) {
						if ( res && res.success && res.data && res.data.nonce ) {
							previewAiData.nonce = res.data.nonce;
						}
						callback();
					},
					error: function() {
						// If refresh fails, proceed with whatever nonce we have.
						callback();
					}
				} );
			},

			/**
			 * Render photo check status.
			 *
			 * @param {jQuery} $checkStatus Status element.
			 * @param {string} status Status (ok, warning, error).
			 * @param {string} message Message.
			 * @param {Array} warnings Array of warnings.
			 */
			renderCheckStatus: function( $checkStatus, status, message, warnings ) {
				$checkStatus.removeClass( 'pai-is-ok pai-is-warning pai-is-error pai-is-checking' ).empty();

				if ( ! message && ( ! warnings || ! warnings.length ) ) {
					return;
				}

				var icons = {
					ok: '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
					warning: '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
					error: '<circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/>'
				};
				var stateClass = icons[ status ] ? 'pai-is-' + status : 'pai-is-checking';
				$checkStatus.addClass( stateClass );

				if ( message ) {
					var $head = $( '<div class="pai-check__head" />' );
					if ( icons[ status ] ) {
						$head.append( '<svg class="pai-check__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + icons[ status ] + '</svg>' );
					} else {
						$head.append( '<span class="pai-check__spinner" aria-hidden="true"></span>' );
					}
					$( '<span class="pai-check__msg" />' ).text( message ).appendTo( $head );
					$head.appendTo( $checkStatus );
				}

				if ( warnings && warnings.length ) {
					var $ul = $( '<ul class="preview-ai-warnings-list" />' );
					warnings.forEach( function( w ) {
						var raw = String( w || '' );
						var code = null;
						var backendText = raw;

						var idx = raw.indexOf( ':' );
						if ( idx > 0 ) {
							code = raw.slice( 0, idx ).trim();
							backendText = raw.slice( idx + 1 ).trim();
						}

						var translated = null;
						if ( code && previewAiData.i18n && previewAiData.i18n.warningCodes ) {
							translated = previewAiData.i18n.warningCodes[ code ] || null;
						}

						var finalText = translated || backendText || raw;
						$( '<li />' ).text( finalText ).appendTo( $ul );
					} );
					$ul.appendTo( $checkStatus );
				}
			},

			/**
			 * Start photo precheck.
			 *
			 * @param {File} file Image file.
			 * @param {Object} $els UI elements.
			 * @param {Object} state State.
			 */
			startPrecheck: function( file, $els, state ) {
				state.checkToken++;
				var token = state.checkToken;

				if ( state.checkXhr && state.checkXhr.abort ) {
					state.checkXhr.abort();
				}

				$els.$generate.prop( 'disabled', true );

				this.renderCheckStatus(
					$els.$checkStatus,
					null,
					( previewAiData.i18n && previewAiData.i18n.checkingPhoto ) || 'Comprobando foto...',
					[]
				);

				var self = this;

				// Refresh nonce first to handle page-cache scenarios.
				this.refreshNonce( function() {
					var formData = new FormData();
					formData.append( 'action', 'preview_ai_check' );
					formData.append( 'nonce', previewAiData.nonce );
					formData.append( 'product_id', previewAiData.productId );

					var $var = $( 'input.variation_id' );
					if ( $var.length && $var.val() ) {
						formData.append( 'variation_id', $var.val() );
					}

					formData.append( 'image', file );

					state.checkXhr = $.ajax( {
						url: previewAiData.ajaxUrl,
						type: 'POST',
						data: formData,
						contentType: false,
						processData: false,
						success: function( res ) {
							if ( token !== state.checkToken ) {
								return;
							}

							if ( res && res.success && res.data ) {
								var status = res.data.status;
								var warnings = res.data.warnings || [];

								if ( status === 'ok' ) {
									self.renderCheckStatus( $els.$checkStatus, 'ok', ( previewAiData.i18n && previewAiData.i18n.photoOk ) || 'Photo looks good.', [] );
									$els.$generate.prop( 'disabled', false );
									return;
								}

								if ( status === 'warning' ) {
									self.renderCheckStatus( $els.$checkStatus, 'warning', ( previewAiData.i18n && previewAiData.i18n.photoWarning ) || 'Photo is valid, but could be improved.', warnings );
									$els.$generate.prop( 'disabled', false );
									return;
								}

								self.renderCheckStatus( $els.$checkStatus, 'error', ( previewAiData.i18n && previewAiData.i18n.photoBad ) || 'Photo is not valid. Please try another one.', warnings );
								$els.$generate.prop( 'disabled', true );
								return;
							}

							self.renderCheckStatus(
								$els.$checkStatus,
								'error',
								( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.',
								[]
							);
						},
						error: function( xhr, statusText ) {
							if ( token !== state.checkToken ) {
								return;
							}
							if ( statusText === 'abort' ) {
								return;
							}
							self.renderCheckStatus(
								$els.$checkStatus,
								'error',
								( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.',
								[]
							);
						}
					} );
				} );
			},

			/**
			 * Show error message on stage.
			 *
			 * @param {Object} $els UI elements.
			 * @param {string} message Error message.
			 * @param {Function} stopLoadingSteps Callback to stop loading.
			 */
			showError: function( $els, message, stopLoadingSteps ) {
				$els.$stage.removeClass( 'pai-is-loading' );
				stopLoadingSteps();
				$els.$generate.removeClass( 'pai-is-hidden' );
				$els.$changeBtn.removeClass( 'pai-is-hidden' );

				var errorMsg = message || ( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.';

				$els.$stage.addClass( 'pai-is-error' );
				$els.$stage.append(
					'<div class="preview-ai-error-overlay">' +
					'<span class="preview-ai-error-icon">😔</span>' +
					'<p>' + errorMsg + '</p>' +
					'</div>'
				);

				setTimeout( function() {
					$els.$stage.find( '.preview-ai-error-overlay' ).remove();
					$els.$stage.removeClass( 'pai-is-error' );
				}, 3000 );
			},

			/**
			 * Generate preview.
			 *
			 * @param {Object} $els UI elements.
			 * @param {Object} state State.
			 * @param {Object} modal Modal module (for loading steps).
			 * @param {Object} tryOns TryOns module (for saving/badge).
			 */
			generatePreview: function( $els, state, modal, tryOns ) {
				if ( $els.$generate.prop( 'disabled' ) ) {
					return;
				}
				if ( ! state.selectedFile ) {
					return;
				}

				// Check weekly usage limit.
				if ( ! PreviewAI.Storage.canGeneratePreview() ) {
					var limitMsg = ( previewAiData.i18n && previewAiData.i18n.weeklyLimitReached ) 
						|| 'You have used all your weekly previews! Come back next week to try on more products.';
					this.showError( $els, limitMsg, modal.stopLoadingSteps );
					return;
				}

				$els.$stage.addClass( 'pai-is-loading' );
				$els.$generate.addClass( 'pai-is-hidden' );
				$els.$changeBtn.addClass( 'pai-is-hidden' );
				$els.$checkStatus.removeClass( 'pai-is-ok pai-is-warning pai-is-error' ).empty().hide();
				modal.startLoadingSteps();

				var self = this;

				// Refresh nonce first to handle page-cache scenarios.
				this.refreshNonce( function() {
					var formData = new FormData();
					formData.append( 'action', 'preview_ai_upload' );
					formData.append( 'nonce', previewAiData.nonce );
					formData.append( 'product_id', previewAiData.productId );

					var $var = $( 'input.variation_id' );
					if ( $var.length && $var.val() ) {
						formData.append( 'variation_id', $var.val() );
					}

					formData.append( 'image', state.selectedFile );

					$.ajax( {
						url: previewAiData.ajaxUrl,
						type: 'POST',
						data: formData,
						contentType: false,
						processData: false,
						success: function( res ) {
							if ( res && res.success && res.data && res.data.generated_image_url ) {
								var img = new Image();
								img.onload = function() {
									state.generatedImageUrl = res.data.generated_image_url;
									$els.$imgAfter.attr( 'src', state.generatedImageUrl );

									modal.completeLoading( function() {
										// Modal was closed or reset meanwhile.
										if ( ! $els.$stage.hasClass( 'pai-is-loading' ) ) {
											return;
										}
										$els.$stage.removeClass( 'pai-is-loading' ).addClass( 'pai-is-result' );
										modal.stopLoadingSteps();
										$els.$resultActions.addClass( 'pai-is-visible' );
										$els.$disclaimer.addClass( 'pai-is-visible' );
									} );

									// Increment weekly usage counter.
									PreviewAI.Storage.incrementWeeklyUsage();

									var variationId = $( 'input.variation_id' ).val() || '';
									PreviewAI.Storage.saveTryOn( {
										id: Date.now().toString(),
										generatedImageUrl: state.generatedImageUrl,
										blobPath: res.data.blob_path || '',
										productId: previewAiData.productId,
										variationId: variationId,
										productName: previewAiData.productName || '',
										productUrl: previewAiData.productUrl || '',
										productImageUrl: previewAiData.productImageUrl || '',
										createdAt: Date.now()
									} );
									tryOns.updateBadge( $els );
								};
								img.onerror = function() {
									self.showError( $els, null, modal.stopLoadingSteps );
								};
								img.src = res.data.generated_image_url;
							} else {
								self.showError( $els, null, modal.stopLoadingSteps );
							}
						},
						error: function() {
							self.showError( $els, null, modal.stopLoadingSteps );
						}
					} );
				} );
			}
		};
	})();

})( jQuery );

