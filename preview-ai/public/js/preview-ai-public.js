(function( $ ) {
	'use strict';

	$( function() {
		var $trigger       = $( '#preview-ai-trigger' );
		var $modal         = $( '#preview-ai-modal' );
		var $close         = $( '#preview-ai-close' );
		var $instructions  = $( '#preview-ai-instructions' );
		var $savedPhoto    = $( '#preview-ai-saved-photo' );
		var $savedThumb    = $( '#preview-ai-saved-thumb' );
		var $useSavedBtn   = $( '#preview-ai-use-saved' );
		var $newPhotoLink  = $( '#preview-ai-new-photo-link' );
		var $forgetPhoto   = $( '#preview-ai-forget-photo' );
		var $upload        = $( '#preview_ai_upload' );
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

		// Your Looks section elements.
		var $viewTryonsBtn  = $( '#preview-ai-view-tryons' );
		var $tryonsBadge    = $( '#preview-ai-tryons-badge' );
		var $tryons         = $( '#preview-ai-tryons' );
		var $tryonsBack     = $( '#preview-ai-tryons-back' );
		var $tryonsCount    = $( '#preview-ai-tryons-count' );
		var $tryonsList     = $( '#preview-ai-tryons-list' );
		var $tryonsEmpty    = $( '#preview-ai-tryons-empty' );
		var $showTryonsBtn  = $( '#preview-ai-show-tryons' );
		var $instructionsLooksBtn   = $( '#preview-ai-instructions-looks' );
		var $instructionsLooksBadge = $( '#preview-ai-instructions-looks-badge' );

	var selectedFile      = null;
	var generatedImageUrl = null;
	var checkXhr          = null;
	var checkToken        = 0;
	var loadingInterval   = null;

	// localStorage keys.
	var STORAGE_KEY        = 'previewAiUserPhoto';
	var TRYONS_STORAGE_KEY = 'previewAiTryOns';
	var MAX_TRYONS         = 10;

	// Image resize settings
	var MAX_IMAGE_SIZE = 1536; // Max width/height in pixels
	var IMAGE_QUALITY  = 0.85; // JPEG quality (0-1)

	/**
	 * Resize image using Canvas API.
	 * Reduces large images (e.g., 4200x5700 from iOS) to reasonable dimensions.
	 *
	 * @param {File} file Original image file.
	 * @param {function} callback Callback with resized File and base64 data URL.
	 */
	function resizeImage( file, callback ) {
		var reader = new FileReader();
		reader.onload = function( e ) {
			var img = new Image();
			img.onload = function() {
				var width  = img.width;
				var height = img.height;

				// Check if resize is needed
				if ( width <= MAX_IMAGE_SIZE && height <= MAX_IMAGE_SIZE ) {
					// No resize needed, return original
					callback( file, e.target.result );
					return;
				}

				// Calculate new dimensions maintaining aspect ratio
				var ratio = Math.min( MAX_IMAGE_SIZE / width, MAX_IMAGE_SIZE / height );
				var newWidth  = Math.round( width * ratio );
				var newHeight = Math.round( height * ratio );

				// Create canvas and resize
				var canvas = document.createElement( 'canvas' );
				canvas.width  = newWidth;
				canvas.height = newHeight;

				var ctx = canvas.getContext( '2d' );
				ctx.imageSmoothingEnabled = true;
				ctx.imageSmoothingQuality = 'high';
				ctx.drawImage( img, 0, 0, newWidth, newHeight );

				// Export as JPEG
				canvas.toBlob( function( blob ) {
					if ( ! blob ) {
						// Fallback to original if blob creation fails
						callback( file, e.target.result );
						return;
					}

					var resizedFile = new File(
						[ blob ],
						file.name.replace( /\.[^.]+$/, '.jpg' ),
						{ type: 'image/jpeg' }
					);
					var dataUrl = canvas.toDataURL( 'image/jpeg', IMAGE_QUALITY );

					callback( resizedFile, dataUrl );
				}, 'image/jpeg', IMAGE_QUALITY );
			};

			img.onerror = function() {
				// Fallback to original if image load fails
				callback( file, e.target.result );
			};

			img.src = e.target.result;
		};

		reader.onerror = function() {
			// Fallback: return original file without base64
			callback( file, null );
		};

		reader.readAsDataURL( file );
	}

		// Check if we have a saved photo
		function getSavedPhoto() {
			try {
				return localStorage.getItem( STORAGE_KEY );
			} catch ( e ) {
				return null;
			}
		}

		// Save photo to localStorage
		function savePhoto( base64Data ) {
			try {
				localStorage.setItem( STORAGE_KEY, base64Data );
			} catch ( e ) {
				// Storage full or not available, silently ignore
			}
		}

		// Remove saved photo
		function forgetSavedPhoto() {
			try {
				localStorage.removeItem( STORAGE_KEY );
			} catch ( e ) {
				// Ignore
			}
		}

		// Get looks from localStorage.
		function getTryOns() {
			try {
				var data = localStorage.getItem( TRYONS_STORAGE_KEY );
				return data ? JSON.parse( data ) : [];
			} catch ( e ) {
				return [];
			}
		}

		// Save look to localStorage.
		function saveTryOn( tryOnData ) {
			try {
				var tryOns = getTryOns();

				// Add new try-on at the beginning.
				tryOns.unshift( tryOnData );

				// Keep only the last MAX_TRYONS.
				if ( tryOns.length > MAX_TRYONS ) {
					tryOns = tryOns.slice( 0, MAX_TRYONS );
				}

				localStorage.setItem( TRYONS_STORAGE_KEY, JSON.stringify( tryOns ) );
			} catch ( e ) {
				// Storage full or not available.
			}
		}

		// Delete a look by id.
		function deleteTryOn( id ) {
			try {
				var tryOns = getTryOns();
				tryOns = tryOns.filter( function( t ) {
					return t.id !== id;
				} );
				localStorage.setItem( TRYONS_STORAGE_KEY, JSON.stringify( tryOns ) );
			} catch ( e ) {
				// Ignore.
			}
		}

		// Format date for display.
		function formatDate( timestamp ) {
			var date = new Date( timestamp );
			var now = new Date();
			var diffDays = Math.floor( ( now - date ) / ( 1000 * 60 * 60 * 24 ) );

			if ( diffDays === 0 ) {
				return previewAiData.i18n.today || 'Today';
			} else if ( diffDays === 1 ) {
				return previewAiData.i18n.yesterday || 'Yesterday';
			} else if ( diffDays < 7 ) {
				return ( previewAiData.i18n.daysAgo || '{n} days ago' ).replace( '{n}', diffDays );
			}

			// Format as "Jan 7"
			var months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
			return months[ date.getMonth() ] + ' ' + date.getDate();
		}

		// Render a single try-on card.
		function renderTryOnCard( tryOn ) {
			var $card = $( '<div class="preview-ai-tryon-card" data-id="' + tryOn.id + '"></div>' );

			// Image container.
			var $imgContainer = $( '<div class="preview-ai-tryon-img-container"></div>' );
			var $img = $( '<img />' )
				.attr( 'src', tryOn.generatedImageUrl )
				.attr( 'alt', tryOn.productName )
				.attr( 'loading', 'lazy' );
			$imgContainer.append( $img );

			// Product thumbnail overlay.
			if ( tryOn.productImageUrl ) {
				var $thumb = $( '<img class="preview-ai-tryon-product-thumb" />' )
					.attr( 'src', tryOn.productImageUrl )
					.attr( 'alt', '' );
				$imgContainer.append( $thumb );
			}

			$card.append( $imgContainer );

			// Info row.
			var $info = $( '<div class="preview-ai-tryon-info"></div>' );
			var $name = $( '<span class="preview-ai-tryon-name"></span>' ).text( tryOn.productName );
			var $date = $( '<span class="preview-ai-tryon-date"></span>' ).text( formatDate( tryOn.createdAt ) );
			$info.append( $name ).append( $date );
			$card.append( $info );

			// Action buttons.
			var $btns = $( '<div class="preview-ai-tryon-btns"></div>' );

			// Add to Cart button.
			var addToCartUrl = tryOn.productUrl + ( tryOn.productUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + 'add-to-cart=' + tryOn.productId;
			if ( tryOn.variationId ) {
				addToCartUrl += '&variation_id=' + tryOn.variationId;
			}
			var $addToCart = $( '<a class="preview-ai-tryon-btn preview-ai-tryon-btn--primary" href="' + addToCartUrl + '"></a>' )
				.html( '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>' + ( previewAiData.i18n.addToCart || 'Add to Cart' ) );
			$btns.append( $addToCart );

			// Download button.
			var $download = $( '<button type="button" class="preview-ai-tryon-btn preview-ai-tryon-btn--secondary" data-url="' + tryOn.generatedImageUrl + '"></button>' )
				.html( '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>' + ( previewAiData.i18n.download || 'Download' ) );
			$btns.append( $download );

			// View Product button.
			var $viewProduct = $( '<a class="preview-ai-tryon-btn preview-ai-tryon-btn--secondary" href="' + tryOn.productUrl + '" target="_blank"></a>' )
				.html( '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>' + ( previewAiData.i18n.viewProduct || 'View Product' ) );
			$btns.append( $viewProduct );

			$card.append( $btns );

			// Delete button.
			var $delete = $( '<button type="button" class="preview-ai-tryon-delete" data-id="' + tryOn.id + '" aria-label="' + ( previewAiData.i18n.delete || 'Delete' ) + '">×</button>' );
			$card.append( $delete );

			return $card;
		}

		// Render looks list.
		function renderTryOnsList() {
			var tryOns = getTryOns();
			$tryonsList.empty();

			if ( tryOns.length === 0 ) {
				$tryonsEmpty.addClass( 'is-visible' );
				$tryonsCount.text( '' );
				return;
			}

			$tryonsEmpty.removeClass( 'is-visible' );
			var countText = tryOns.length + ' ' + ( tryOns.length === 1 ? ( previewAiData.i18n.result || 'result' ) : ( previewAiData.i18n.results || 'results' ) );
			$tryonsCount.text( countText );

			tryOns.forEach( function( tryOn ) {
				$tryonsList.append( renderTryOnCard( tryOn ) );
			} );
		}

		// Update looks badge count.
		function updateTryOnsBadge() {
			var tryOns = getTryOns();
			if ( tryOns.length > 0 ) {
				$tryonsBadge.text( tryOns.length ).addClass( 'is-visible' );
				$viewTryonsBtn.addClass( 'has-tryons' );
				$instructionsLooksBadge.text( tryOns.length ).addClass( 'is-visible' );
				$instructionsLooksBtn.addClass( 'has-looks' );
			} else {
				$tryonsBadge.text( '' ).removeClass( 'is-visible' );
				$viewTryonsBtn.removeClass( 'has-tryons' );
				$instructionsLooksBadge.text( '' ).removeClass( 'is-visible' );
				$instructionsLooksBtn.removeClass( 'has-looks' );
			}
		}

		// Show looks section.
		function showTryOnsSection() {
			renderTryOnsList();
			$savedPhoto.removeClass( 'is-visible' );
			$instructions.addClass( 'is-hidden' );
			$stage.removeClass( 'is-visible' );
			$actions.removeClass( 'is-visible' );
			$tryons.addClass( 'is-visible' );
		}

		// Hide looks section and go back.
		function hideTryOnsSection() {
			$tryons.removeClass( 'is-visible' );
			showSavedPhotoSection();
			$instructions.removeClass( 'is-hidden' );
		}

		// Convert base64 to File object
		function base64ToFile( base64, filename ) {
			var arr = base64.split( ',' );
			var mime = arr[0].match( /:(.*?);/ )[1];
			var bstr = atob( arr[1] );
			var n = bstr.length;
			var u8arr = new Uint8Array( n );
			while ( n-- ) {
				u8arr[n] = bstr.charCodeAt( n );
			}
			return new File( [u8arr], filename, { type: mime } );
		}

		// Show saved photo section
		function showSavedPhotoSection() {
			var savedData = getSavedPhoto();
			if ( savedData ) {
				$savedThumb.attr( 'src', savedData );
				$savedPhoto.addClass( 'is-visible' );
				$instructions.addClass( 'has-saved-photo' );
			} else {
				$savedPhoto.removeClass( 'is-visible' );
				$instructions.removeClass( 'has-saved-photo' );
			}
		}

		// Use the saved photo
		function useSavedPhoto() {
			var savedData = getSavedPhoto();
			if ( ! savedData ) {
				return;
			}

			selectedFile = base64ToFile( savedData, 'saved-photo.jpg' );
			$imgBefore.attr( 'src', savedData );
			$savedPhoto.removeClass( 'is-visible' );
			$instructions.addClass( 'is-hidden' );
			$stage.addClass( 'is-visible' ).removeClass( 'is-result' );
			$actions.addClass( 'is-visible' );
			$generate.removeClass( 'is-hidden' );
			$generate.prop( 'disabled', true );
			$resultActions.removeClass( 'is-visible' );
			$disclaimer.removeClass( 'is-visible' );
			startPrecheck( selectedFile );
		}

		// Loading steps animation
		function startLoadingSteps() {
			var $steps = $( '.preview-ai-step' );
			var current = 0;
			var total = $steps.length;

			if ( total === 0 ) {
				return;
			}

			// Reset to first step
			$steps.removeClass( 'is-active is-exiting' );
			$steps.eq( 0 ).addClass( 'is-active' );

			loadingInterval = setInterval( function() {
				var $current = $steps.eq( current );
				var next = ( current + 1 ) % total;
				var $next = $steps.eq( next );

				$current.addClass( 'is-exiting' ).removeClass( 'is-active' );

				setTimeout( function() {
					$current.removeClass( 'is-exiting' );
					$next.addClass( 'is-active' );
				}, 200 );

				current = next;
			}, 3100 );
		}

		function stopLoadingSteps() {
			if ( loadingInterval ) {
				clearInterval( loadingInterval );
				loadingInterval = null;
			}
			$( '.preview-ai-step' ).removeClass( 'is-active is-exiting' );
			$( '.preview-ai-step' ).first().addClass( 'is-active' );
		}

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

			// Check for saved photo and update looks badge
			showSavedPhotoSection();
			updateTryOnsBadge();
		} );

		// Use saved photo button
		$useSavedBtn.on( 'click', function() {
			useSavedPhoto();
		} );

		// "Upload new photo" link in saved photo section
		$newPhotoLink.on( 'click', function( e ) {
			e.preventDefault();
			forgetSavedPhoto();
			$savedPhoto.removeClass( 'is-visible' );
			$instructions.removeClass( 'has-saved-photo' );
		} );

		// Forget saved photo
		$forgetPhoto.on( 'click', function( e ) {
			e.preventDefault();
			forgetSavedPhoto();
			$savedPhoto.removeClass( 'is-visible' );
			$instructions.removeClass( 'has-saved-photo' );
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
	// @param {boolean} showSaved - Whether to show saved photo section (default: true)
	function resetToInstructions( showSaved ) {
		if ( checkXhr && checkXhr.abort ) {
			checkXhr.abort();
		}
		stopLoadingSteps();
		$upload.val( '' );
		selectedFile = null;
		generatedImageUrl = null;
		$checkStatus.removeClass( 'is-ok is-warning is-error' ).empty().show();
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
		$tryons.removeClass( 'is-visible' );

		// Check for saved photo again (unless explicitly disabled)
		if ( showSaved !== false ) {
			showSavedPhotoSection();
		} else {
			// Hide saved photo section and go directly to upload
			$savedPhoto.removeClass( 'is-visible' );
			$instructions.removeClass( 'has-saved-photo' );
		}
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
			var $ul = $( '<ul class="preview-ai-warnings-list" />' );
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

				// Check for translated text
				var translated = null;
				if ( code && previewAiData.i18n && previewAiData.i18n.warningCodes ) {
					translated = previewAiData.i18n.warningCodes[ code ] || null;
				}

				// Show only text, no code
				var finalText = translated || backendText || raw;

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
			var originalFile = input.files[0];

			// Resize image before processing (reduces iOS 4200x5700 images)
			resizeImage( originalFile, function( resizedFile, base64Data ) {
				selectedFile = resizedFile;
				$imgBefore.attr( 'src', base64Data );

				// Save resized photo to localStorage for future use
				savePhoto( base64Data );

				$savedPhoto.removeClass( 'is-visible' );
				$instructions.addClass( 'is-hidden' );
				$stage.addClass( 'is-visible' ).removeClass( 'is-result' );
				$actions.addClass( 'is-visible' );
				$generate.removeClass( 'is-hidden' );
				$generate.prop( 'disabled', true );
				$resultActions.removeClass( 'is-visible' );
				$disclaimer.removeClass( 'is-visible' );
				startPrecheck( selectedFile );
			} );
		}
	}

		$upload.on( 'change', function() {
			handleFileSelect( this );
		} );

	// Change photo - go directly to upload (skip saved photo section)
	$changeBtn.on( 'click', function() {
		resetToInstructions( false );
	} );
		$newPhotoBtn.on( 'click', function() {
			forgetSavedPhoto();
			resetToInstructions();
		} );

		// Show error message
		function showError( message ) {
			$stage.removeClass( 'is-loading' );
			stopLoadingSteps();
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
			$checkStatus.removeClass( 'is-ok is-warning is-error' ).empty().hide();
			startLoadingSteps();

			$.ajax( {
				url: previewAiData.ajaxUrl,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				success: function( res ) {
					$stage.removeClass( 'is-loading' );
					stopLoadingSteps();

					if ( res && res.success && res.data && res.data.generated_image_url ) {
						var img = new Image();
						img.onload = function() {
							generatedImageUrl = res.data.generated_image_url;
							$imgAfter.attr( 'src', generatedImageUrl );
							$stage.addClass( 'is-result' );
							$resultActions.addClass( 'is-visible' );
							$disclaimer.addClass( 'is-visible' );

							// Save to looks history.
							var variationId = $( 'input.variation_id' ).val() || '';
							saveTryOn( {
								id: Date.now().toString(),
								generatedImageUrl: generatedImageUrl,
								productId: previewAiData.productId,
								variationId: variationId,
								productName: previewAiData.productName || '',
								productUrl: previewAiData.productUrl || '',
								productImageUrl: previewAiData.productImageUrl || '',
								createdAt: Date.now()
							} );
							updateTryOnsBadge();
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

		// View looks from Welcome back section.
		$viewTryonsBtn.on( 'click', function() {
			showTryOnsSection();
		} );

		// View looks from result actions.
		$showTryonsBtn.on( 'click', function() {
			showTryOnsSection();
		} );

		// View looks from instructions section.
		$instructionsLooksBtn.on( 'click', function() {
			showTryOnsSection();
		} );

		// Back from looks section.
		$tryonsBack.on( 'click', function() {
			hideTryOnsSection();
		} );

		// Download from try-on card.
		$tryonsList.on( 'click', '.preview-ai-tryon-btn--secondary[data-url]', function( e ) {
			e.preventDefault();
			var url = $( this ).data( 'url' );
			if ( url ) {
				downloadImage( url, 'preview-ai-' + Date.now() + '.jpg' );
			}
		} );

		// Delete try-on.
		$tryonsList.on( 'click', '.preview-ai-tryon-delete', function( e ) {
			e.stopPropagation();
			var id = $( this ).data( 'id' );
			if ( id ) {
				deleteTryOn( String( id ) );
				renderTryOnsList();
				updateTryOnsBadge();
			}
		} );

		// Open lightbox from look card image.
		$tryonsList.on( 'click', '.preview-ai-tryon-img-container img:first-child', function() {
			var src = $( this ).attr( 'src' );
			if ( src ) {
				$lbImg.attr( 'src', src );
				$lightbox.addClass( 'is-open' );
			}
		} );

		// Initialize looks badge on load.
		updateTryOnsBadge();
	} );

})( jQuery );
