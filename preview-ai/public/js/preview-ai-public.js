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
		var $resultActions = $( '#preview-ai-result-actions' );
		var $download      = $( '#preview-ai-download' );
		var $newPhotoBtn   = $( '#preview-ai-new-photo' );
		var $changeBtn     = $( '#preview-ai-change' );
		var $disclaimer    = $( '#preview-ai-disclaimer' );
		var $lightbox      = $( '#preview-ai-lightbox' );
		var $lbImg         = $( '#preview-ai-lightbox-img' );

		var selectedFile      = null;
		var generatedImageUrl = null;

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
			$camera.val( '' );
			$gallery.val( '' );
			selectedFile = null;
			generatedImageUrl = null;
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
					$resultActions.removeClass( 'is-visible' );
					$disclaimer.removeClass( 'is-visible' );
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
