(function( $ ) {
	'use strict';

	$( function() {
		var $trigger       = $( '#preview-ai-trigger' );
		var $modal         = $( '#preview-ai-modal' );
		var $close         = $( '#preview-ai-close' );
		var $instructions  = $( '#preview-ai-instructions' );
		var $camera        = $( '#preview_ai_camera' );
		var $gallery       = $( '#preview_ai_gallery' );
		var $galleryLink   = $( '.preview-ai-gallery-link' );
		var $stage         = $( '#preview-ai-stage' );
		var $imgBefore     = $( '#preview-ai-img-before' );
		var $imgAfter      = $( '#preview-ai-img-after' );
		var $actions       = $( '#preview-ai-actions' );
		var $generate      = $( '#preview-ai-generate' );
		var $checkStatus   = $( '#preview-ai-check-status' );
		var $resultActions = $( '#preview-ai-result-actions' );
		var $download      = $( '#preview-ai-download' );
		var $newPhotoBtn   = $( '#preview-ai-new-photo' );
		var $changeBtn     = $( '#preview-ai-change' );
		var $disclaimer    = $( '#preview-ai-disclaimer' );
		var $lightbox      = $( '#preview-ai-lightbox' );
		var $lbImg         = $( '#preview-ai-lightbox-img' );

		var selectedFile      = null;
		var generatedImageUrl = null;
		var checkXhr          = null;
		var checkToken        = 0;

		// Detect mobile
		var isMobile = /Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent );

		// Adapt UI for device
		function adaptUIForDevice() {
			var $icon = $( '.preview-ai-camera-icon' );
			var $text = $( '.preview-ai-camera-text' );

			if ( isMobile ) {
				// Mobile: Camera button + gallery link
				$icon.text( '📸' );
				$text.text( previewAiData.i18n.openCamera || 'Open camera' );
				$camera.attr( 'capture', 'environment' );
				$galleryLink.show();
			} else {
				// Desktop: Just upload button, no gallery link
				$icon.text( '📁' );
				$text.text( previewAiData.i18n.uploadPhoto || 'Upload photo' );
				$camera.removeAttr( 'capture' );
				$galleryLink.hide();
			}
		}

		adaptUIForDevice();

		// Download image
		function downloadImage( url, filename ) {
			fetch( url )
				.then( function( response ) {
					return response.blob();
				} )
				.then( function( blob ) {
					var blobUrl = URL.createObjectURL( blob );
					var link = document.createElement( 'a' );
					link.href = blobUrl;
					link.download = filename || 'preview-ai.jpg';
					document.body.appendChild( link );
					link.click();
					document.body.removeChild( link );
					URL.revokeObjectURL( blobUrl );
				} )
				.catch( function() {
					window.open( url, '_blank' );
				} );
		}

		// Open modal
		$trigger.on( 'click', function() {
			$modal.addClass( 'is-open' );
			setTimeout( function() {
				$modal.addClass( 'is-visible' );
			}, 10 );
			$( 'body' ).css( 'overflow', 'hidden' );
		} );

		// Close modal
		function closeModal() {
			$modal.removeClass( 'is-visible' );
			setTimeout( function() {
				$modal.removeClass( 'is-open' );
			}, 200 );
			$( 'body' ).css( 'overflow', '' );
		}

		// Reset to initial state
		function resetToInstructions() {
			if ( checkXhr && checkXhr.abort ) {
				checkXhr.abort();
			}
			$camera.val( '' );
			$gallery.val( '' );
			selectedFile = null;
			generatedImageUrl = null;
			$checkStatus.removeClass( 'is-ok is-warning is-error' ).empty();
			$generate.prop( 'disabled', true );
			$imgBefore.attr( 'src', '' );
			$imgAfter.attr( 'src', '' );
			$stage.removeClass( 'is-visible is-loading is-result' );
			$actions.removeClass( 'is-visible' );
			$generate.removeClass( 'is-hidden' );
			$changeBtn.removeClass( 'is-hidden' );
			$resultActions.removeClass( 'is-visible' );
			$disclaimer.removeClass( 'is-visible' );
			$instructions.removeClass( 'is-hidden' );
		}

		function renderCheckStatus( status, message, warnings ) {
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
				var $ul = $( '<ul />' );
				warnings.forEach( function( w ) {
					var raw = String( w || '' );
					var code = null;
					var backendText = raw;

					// Parse stable format "CODE: message".
					var idx = raw.indexOf( ':' );
					if ( idx > 0 ) {
						code = raw.slice( 0, idx ).trim();
						backendText = raw.slice( idx + 1 ).trim();
					}

					var translated = null;
					if ( code && previewAiData.i18n && previewAiData.i18n.warningCodes ) {
						translated = previewAiData.i18n.warningCodes[ code ] || null;
					}

					var finalText = raw;
					if ( code && translated ) {
						finalText = code + ': ' + translated;
					} else if ( code && backendText ) {
						finalText = code + ': ' + backendText;
					}

					$( '<li />' ).text( finalText ).appendTo( $ul );
				} );
				$ul.appendTo( $checkStatus );
			}
		}

		function startPrecheck( file ) {
			checkToken++;
			var token = checkToken;

			if ( checkXhr && checkXhr.abort ) {
				checkXhr.abort();
			}

			$generate.prop( 'disabled', true );

			renderCheckStatus(
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

			checkXhr = $.ajax( {
				url: previewAiData.ajaxUrl,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				success: function( res ) {
					if ( token !== checkToken ) {
						return;
					}

					if ( res && res.success && res.data ) {
						var status = res.data.status;
						var warnings = res.data.warnings || [];

						if ( status === 'ok' ) {
							renderCheckStatus( 'ok', ( previewAiData.i18n && previewAiData.i18n.photoOk ) || 'Photo looks good.', [] );
							$generate.prop( 'disabled', false );
							return;
						}

						if ( status === 'warning' ) {
							renderCheckStatus( 'warning', ( previewAiData.i18n && previewAiData.i18n.photoWarning ) || 'Photo is valid, but could be improved.', warnings );
							$generate.prop( 'disabled', false );
							return;
						}

						renderCheckStatus( 'error', ( previewAiData.i18n && previewAiData.i18n.photoBad ) || 'Photo is not valid. Please try another one.', warnings );
						$generate.prop( 'disabled', true );
						return;
					}

					renderCheckStatus(
						'error',
						( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.',
						[]
					);
				},
				error: function( xhr, statusText ) {
					if ( token !== checkToken ) {
						return;
					}
					if ( statusText === 'abort' ) {
						return;
					}
					renderCheckStatus(
						'error',
						( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.',
						[]
					);
				}
			} );
		}

		$close.on( 'click', function() {
			closeModal();
			setTimeout( resetToInstructions, 250 );
		} );

		$modal.on( 'click', function( e ) {
			if ( e.target === this ) {
				closeModal();
				setTimeout( resetToInstructions, 250 );
			}
		} );

		$( document ).on( 'keydown', function( e ) {
			if ( e.key === 'Escape' ) {
				if ( $lightbox.hasClass( 'is-open' ) ) {
					$lightbox.removeClass( 'is-open' );
				} else if ( $modal.hasClass( 'is-open' ) ) {
					closeModal();
					setTimeout( resetToInstructions, 250 );
				}
			}
		} );

		// Handle file selection
		function handleFileSelect( input ) {
			if ( input.files && input.files[0] ) {
				selectedFile = input.files[0];
				var reader = new FileReader();
				reader.onload = function( e ) {
					$imgBefore.attr( 'src', e.target.result );
					$instructions.addClass( 'is-hidden' );
					$stage.addClass( 'is-visible' ).removeClass( 'is-result' );
					$actions.addClass( 'is-visible' );
					$generate.removeClass( 'is-hidden' );
					$generate.prop( 'disabled', true );
					$resultActions.removeClass( 'is-visible' );
					$disclaimer.removeClass( 'is-visible' );
					startPrecheck( selectedFile );
				};
				reader.readAsDataURL( selectedFile );
			}
		}

		$camera.on( 'change', function() {
			handleFileSelect( this );
		} );

		$gallery.on( 'change', function() {
			handleFileSelect( this );
		} );

		// Change photo / New photo
		$changeBtn.on( 'click', resetToInstructions );
		$newPhotoBtn.on( 'click', resetToInstructions );

		// Show error message
		function showError( message ) {
			$stage.removeClass( 'is-loading' );
			$generate.removeClass( 'is-hidden' );
			$changeBtn.removeClass( 'is-hidden' );

			var errorMsg = message || ( previewAiData.i18n && previewAiData.i18n.error ) || 'Something went wrong. Please try again later.';

			$stage.addClass( 'is-error' );
			$stage.append(
				'<div class="preview-ai-error-overlay">' +
				'<span class="preview-ai-error-icon">😔</span>' +
				'<p>' + errorMsg + '</p>' +
				'</div>'
			);

			setTimeout( function() {
				$stage.find( '.preview-ai-error-overlay' ).remove();
				$stage.removeClass( 'is-error' );
			}, 3000 );
		}

		// Generate preview
		$generate.on( 'click', function() {
			if ( $generate.prop( 'disabled' ) ) {
				return;
			}
			if ( ! selectedFile ) {
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

			formData.append( 'image', selectedFile );

			$stage.addClass( 'is-loading' );
			$generate.addClass( 'is-hidden' );
			$changeBtn.addClass( 'is-hidden' );

			$.ajax( {
				url: previewAiData.ajaxUrl,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				success: function( res ) {
					$stage.removeClass( 'is-loading' );

					if ( res && res.success && res.data && res.data.generated_image_url ) {
						var img = new Image();
						img.onload = function() {
							generatedImageUrl = res.data.generated_image_url;
							$imgAfter.attr( 'src', generatedImageUrl );
							$stage.addClass( 'is-result' );
							$resultActions.addClass( 'is-visible' );
							$disclaimer.addClass( 'is-visible' );
						};
						img.onerror = function() {
							showError();
						};
						img.src = res.data.generated_image_url;
					} else {
						showError();
					}
				},
				error: function() {
					showError();
				}
			} );
		} );

		// Download button
		$download.on( 'click', function( e ) {
			e.preventDefault();
			if ( generatedImageUrl ) {
				downloadImage( generatedImageUrl, 'preview-ai-' + Date.now() + '.jpg' );
			}
		} );

		// Open lightbox
		$imgAfter.on( 'click', function() {
			if ( $stage.hasClass( 'is-result' ) ) {
				$lbImg.attr( 'src', $( this ).attr( 'src' ) );
				$lightbox.addClass( 'is-open' );
			}
		} );

		// Close lightbox
		$lightbox.on( 'click', function() {
			$( this ).removeClass( 'is-open' );
		} );
	} );

})( jQuery );
