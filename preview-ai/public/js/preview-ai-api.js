(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Api = (function() {
		return {
			/**
			 * Render photo check status.
			 *
			 * @param {jQuery} $checkStatus Status element.
			 * @param {string} status Status (ok, warning, error).
			 * @param {string} message Message.
			 * @param {Array} warnings Array of warnings.
			 */
			renderCheckStatus: function( $checkStatus, status, message, warnings ) {
				$checkStatus.removeClass( 'is-ok is-warning is-error' ).empty();

				if ( ! message && ( ! warnings || ! warnings.length ) ) {
					return;
				}

				if ( status === 'ok' ) {
					$checkStatus.addClass( 'is-ok' );
				} else if ( status === 'warning' ) {
					$checkStatus.addClass( 'is-warning' );
				} else if ( status === 'error' ) {
					$checkStatus.addClass( 'is-error' );
				}

				if ( message ) {
					$( '<div />' ).text( message ).appendTo( $checkStatus );
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

				var formData = new FormData();
				formData.append( 'action', 'preview_ai_check' );
				formData.append( 'nonce', previewAiData.nonce );
				formData.append( 'product_id', previewAiData.productId );

				var $var = $( 'input.variation_id' );
				if ( $var.length && $var.val() ) {
					formData.append( 'variation_id', $var.val() );
				}

				formData.append( 'image', file );

				var self = this;
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
			},

			/**
			 * Show error message on stage.
			 *
			 * @param {Object} $els UI elements.
			 * @param {string} message Error message.
			 * @param {Function} stopLoadingSteps Callback to stop loading.
			 */
			showError: function( $els, message, stopLoadingSteps ) {
				$els.$stage.removeClass( 'is-loading' );
				stopLoadingSteps();
				$els.$generate.removeClass( 'is-hidden' );
				$els.$changeBtn.removeClass( 'is-hidden' );

				var errorMsg = message || ( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.';

				$els.$stage.addClass( 'is-error' );
				$els.$stage.append(
					'<div class="preview-ai-error-overlay">' +
					'<span class="preview-ai-error-icon">😔</span>' +
					'<p>' + errorMsg + '</p>' +
					'</div>'
				);

				setTimeout( function() {
					$els.$stage.find( '.preview-ai-error-overlay' ).remove();
					$els.$stage.removeClass( 'is-error' );
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

				var formData = new FormData();
				formData.append( 'action', 'preview_ai_upload' );
				formData.append( 'nonce', previewAiData.nonce );
				formData.append( 'product_id', previewAiData.productId );

				var $var = $( 'input.variation_id' );
				if ( $var.length && $var.val() ) {
					formData.append( 'variation_id', $var.val() );
				}

				formData.append( 'image', state.selectedFile );

				$els.$stage.addClass( 'is-loading' );
				$els.$generate.addClass( 'is-hidden' );
				$els.$changeBtn.addClass( 'is-hidden' );
				$els.$checkStatus.removeClass( 'is-ok is-warning is-error' ).empty().hide();
				modal.startLoadingSteps();

				var self = this;
				$.ajax( {
					url: previewAiData.ajaxUrl,
					type: 'POST',
					data: formData,
					contentType: false,
					processData: false,
					success: function( res ) {
						$els.$stage.removeClass( 'is-loading' );
						modal.stopLoadingSteps();

						if ( res && res.success && res.data && res.data.generated_image_url ) {
							var img = new Image();
							img.onload = function() {
								state.generatedImageUrl = res.data.generated_image_url;
								$els.$imgAfter.attr( 'src', state.generatedImageUrl );
								$els.$stage.addClass( 'is-result' );
								$els.$resultActions.addClass( 'is-visible' );
								$els.$disclaimer.addClass( 'is-visible' );

								var variationId = $( 'input.variation_id' ).val() || '';
								PreviewAI.Storage.saveTryOn( {
									id: Date.now().toString(),
									generatedImageUrl: state.generatedImageUrl,
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
			}
		};
	})();

})( jQuery );

