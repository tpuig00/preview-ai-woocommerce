(function( $ ) {
	'use strict';

	$( function() {
		// UI Elements.
		var $els = {
			$trigger:       $( '#preview-ai-trigger' ),
			$modal:         $( '#preview-ai-modal' ),
			$close:         $( '#preview-ai-close' ),
			$instructions:  $( '#preview-ai-instructions' ),
			$savedPhoto:    $( '#preview-ai-saved-photo' ),
			$savedThumb:    $( '#preview-ai-saved-thumb' ),
			$useSavedBtn:   $( '#preview-ai-use-saved' ),
			$newPhotoLink:  $( '#preview-ai-new-photo-link' ),
			$forgetPhoto:   $( '#preview-ai-forget-photo' ),
			$upload:        $( '#preview_ai_upload' ),
			$stage:         $( '#preview-ai-stage' ),
			$imgBefore:     $( '#preview-ai-img-before' ),
			$imgAfter:      $( '#preview-ai-img-after' ),
			$actions:       $( '#preview-ai-actions' ),
			$generate:      $( '#preview-ai-generate' ),
			$checkStatus:   $( '#preview-ai-check-status' ),
			$resultActions: $( '#preview-ai-result-actions' ),
			$download:      $( '#preview-ai-download' ),
			$newPhotoBtn:   $( '#preview-ai-new-photo' ),
			$changeBtn:     $( '#preview-ai-change' ),
			$disclaimer:    $( '#preview-ai-disclaimer' ),
			$lightbox:      $( '#preview-ai-lightbox' ),
			$lbImg:         $( '#preview-ai-lightbox-img' ),
			$viewTryonsBtn:  $( '#preview-ai-view-tryons' ),
			$tryonsBadge:    $( '#preview-ai-tryons-badge' ),
			$tryons:         $( '#preview-ai-tryons' ),
			$tryonsBack:     $( '#preview-ai-tryons-back' ),
			$tryonsCount:    $( '#preview-ai-tryons-count' ),
			$tryonsList:     $( '#preview-ai-tryons-list' ),
			$tryonsEmpty:    $( '#preview-ai-tryons-empty' ),
			$showTryonsBtn:  $( '#preview-ai-show-tryons' ),
			$instructionsLooksBtn:   $( '#preview-ai-instructions-looks' ),
			$instructionsLooksBadge: $( '#preview-ai-instructions-looks-badge' )
		};

		// Plugin State.
		var state = {
			selectedFile:      null,
			generatedImageUrl: null,
			checkXhr:          null,
			checkToken:        0
		};

		// --- Event Handlers ---

		// Open modal
		$els.$trigger.on( 'click', function() {
			$els.$modal.addClass( 'is-open' );
			setTimeout( function() {
				$els.$modal.addClass( 'is-visible' );
			}, 10 );
			$( 'body' ).css( 'overflow', 'hidden' );

			PreviewAI.Modal.showSavedPhotoSection( $els );
			PreviewAI.TryOns.updateBadge( $els );
		} );

		// Use saved photo button
		$els.$useSavedBtn.on( 'click', function() {
			PreviewAI.Modal.useSavedPhoto( $els, state, function( file ) {
				PreviewAI.Api.startPrecheck( file, $els, state );
			} );
		} );

		// "Upload new photo" link in saved photo section
		$els.$newPhotoLink.on( 'click', function( e ) {
			e.preventDefault();
			PreviewAI.Storage.forgetSavedPhoto();
			$els.$savedPhoto.removeClass( 'is-visible' );
			$els.$instructions.removeClass( 'has-saved-photo' );
		} );

		// Forget saved photo
		$els.$forgetPhoto.on( 'click', function( e ) {
			e.preventDefault();
			PreviewAI.Storage.forgetSavedPhoto();
			$els.$savedPhoto.removeClass( 'is-visible' );
			$els.$instructions.removeClass( 'has-saved-photo' );
		} );

		// Close modal
		$els.$close.on( 'click', function() {
			PreviewAI.Modal.close( $els.$modal );
			setTimeout( function() {
				PreviewAI.Modal.resetToInstructions( $els, state, true, PreviewAI.Api );
			}, 250 );
		} );

		$els.$modal.on( 'click', function( e ) {
			if ( e.target === this ) {
				PreviewAI.Modal.close( $els.$modal );
				setTimeout( function() {
					PreviewAI.Modal.resetToInstructions( $els, state, true, PreviewAI.Api );
				}, 250 );
			}
		} );

		$( document ).on( 'keydown', function( e ) {
			if ( e.key === 'Escape' ) {
				if ( $els.$lightbox.hasClass( 'is-open' ) ) {
					$els.$lightbox.removeClass( 'is-open' );
				} else if ( $els.$modal.hasClass( 'is-open' ) ) {
					PreviewAI.Modal.close( $els.$modal );
					setTimeout( function() {
						PreviewAI.Modal.resetToInstructions( $els, state, true, PreviewAI.Api );
					}, 250 );
				}
			}
		} );

		// Handle file selection
		$els.$upload.on( 'change', function() {
			var input = this;
			if ( input.files && input.files[0] ) {
				var originalFile = input.files[0];

				PreviewAI.Image.resizeImage( originalFile, function( resizedFile, base64Data ) {
					state.selectedFile = resizedFile;
					$els.$imgBefore.attr( 'src', base64Data );

					PreviewAI.Storage.savePhoto( base64Data );

					$els.$savedPhoto.removeClass( 'is-visible' );
					$els.$instructions.addClass( 'is-hidden' );
					$els.$stage.addClass( 'is-visible' ).removeClass( 'is-result' );
					$els.$actions.addClass( 'is-visible' );
					$els.$generate.removeClass( 'is-hidden' );
					$els.$generate.prop( 'disabled', true );
					$els.$resultActions.removeClass( 'is-visible' );
					$els.$disclaimer.removeClass( 'is-visible' );
					PreviewAI.Api.startPrecheck( state.selectedFile, $els, state );
				} );
			}
		} );

		// Change photo
		$els.$changeBtn.on( 'click', function() {
			PreviewAI.Modal.resetToInstructions( $els, state, false, PreviewAI.Api );
		} );

		$els.$newPhotoBtn.on( 'click', function() {
			PreviewAI.Storage.forgetSavedPhoto();
			PreviewAI.Modal.resetToInstructions( $els, state, true, PreviewAI.Api );
		} );

		// Generate preview
		$els.$generate.on( 'click', function() {
			PreviewAI.Api.generatePreview( $els, state, PreviewAI.Modal, PreviewAI.TryOns );
		} );

		// Download button
		$els.$download.on( 'click', function( e ) {
			e.preventDefault();
			if ( state.generatedImageUrl ) {
				PreviewAI.Image.downloadImage( state.generatedImageUrl, 'preview-ai-' + Date.now() + '.jpg' );
			}
		} );

		// Open lightbox
		$els.$imgAfter.on( 'click', function() {
			if ( $els.$stage.hasClass( 'is-result' ) ) {
				$els.$lbImg.attr( 'src', $( this ).attr( 'src' ) );
				$els.$lightbox.addClass( 'is-open' );
			}
		} );

		// Close lightbox
		$els.$lightbox.on( 'click', function() {
			$( this ).removeClass( 'is-open' );
		} );

		// View looks
		$els.$viewTryonsBtn.on( 'click', function() {
			PreviewAI.TryOns.showSection( $els );
		} );

		$els.$showTryonsBtn.on( 'click', function() {
			PreviewAI.TryOns.showSection( $els );
		} );

		$els.$instructionsLooksBtn.on( 'click', function() {
			PreviewAI.TryOns.showSection( $els );
		} );

		// Back from looks
		$els.$tryonsBack.on( 'click', function() {
			PreviewAI.TryOns.hideSection( $els, function() {
				PreviewAI.Modal.showSavedPhotoSection( $els );
			} );
		} );

		// Download from try-on card
		$els.$tryonsList.on( 'click', '.preview-ai-tryon-btn--secondary[data-url]', function( e ) {
			e.preventDefault();
			var url = $( this ).data( 'url' );
			if ( url ) {
				PreviewAI.Image.downloadImage( url, 'preview-ai-' + Date.now() + '.jpg' );
			}
		} );

		// Delete try-on
		$els.$tryonsList.on( 'click', '.preview-ai-tryon-delete', function( e ) {
			e.stopPropagation();
			var id = $( this ).data( 'id' );
			if ( id ) {
				PreviewAI.Storage.deleteTryOn( String( id ) );
				PreviewAI.TryOns.renderList( $els.$tryonsList, $els.$tryonsEmpty, $els.$tryonsCount );
				PreviewAI.TryOns.updateBadge( $els );
			}
		} );

		// Open lightbox from look card image
		$els.$tryonsList.on( 'click', '.preview-ai-tryon-img-container img:first-child', function() {
			var src = $( this ).attr( 'src' );
			if ( src ) {
				$els.$lbImg.attr( 'src', src );
				$els.$lightbox.addClass( 'is-open' );
			}
		} );

		// Initialize looks badge on load.
		PreviewAI.TryOns.updateBadge( $els );
	} );

})( jQuery );
