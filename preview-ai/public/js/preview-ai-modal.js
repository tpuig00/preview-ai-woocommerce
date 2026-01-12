(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Modal = (function() {
		var loadingInterval = null;

		return {
			/**
			 * Show saved photo section.
			 *
			 * @param {Object} $els Collection of UI elements.
			 */
			showSavedPhotoSection: function( $els ) {
				var savedData = PreviewAI.Storage.getSavedPhoto();
				if ( savedData ) {
					$els.$savedThumb.attr( 'src', savedData );
					$els.$savedPhoto.addClass( 'is-visible' );
					$els.$instructions.addClass( 'has-saved-photo' );
				} else {
					$els.$savedPhoto.removeClass( 'is-visible' );
					$els.$instructions.removeClass( 'has-saved-photo' );
				}
			},

			/**
			 * Use the saved photo.
			 *
			 * @param {Object} $els Collection of UI elements.
			 * @param {Object} state Plugin state (selectedFile, etc).
			 * @param {Function} startPrecheck Callback to start precheck.
			 */
			useSavedPhoto: function( $els, state, startPrecheck ) {
				var savedData = PreviewAI.Storage.getSavedPhoto();
				if ( ! savedData ) {
					return;
				}

				state.selectedFile = PreviewAI.Image.base64ToFile( savedData, 'saved-photo.jpg' );
				$els.$imgBefore.attr( 'src', savedData );
				$els.$savedPhoto.removeClass( 'is-visible' );
				$els.$instructions.addClass( 'is-hidden' );
				$els.$stage.addClass( 'is-visible' ).removeClass( 'is-result' );
				$els.$actions.addClass( 'is-visible' );
				$els.$generate.removeClass( 'is-hidden' );
				$els.$generate.prop( 'disabled', true );
				$els.$resultActions.removeClass( 'is-visible' );
				$els.$disclaimer.removeClass( 'is-visible' );
				startPrecheck( state.selectedFile );
			},

			/**
			 * Loading steps animation.
			 */
			startLoadingSteps: function() {
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
			},

			/**
			 * Stop loading steps animation.
			 */
			stopLoadingSteps: function() {
				if ( loadingInterval ) {
					clearInterval( loadingInterval );
					loadingInterval = null;
				}
				$( '.preview-ai-step' ).removeClass( 'is-active is-exiting' );
				$( '.preview-ai-step' ).first().addClass( 'is-active' );
			},

			/**
			 * Close modal.
			 *
			 * @param {jQuery} $modal Modal element.
			 */
			close: function( $modal ) {
				$modal.removeClass( 'is-visible' );
				setTimeout( function() {
					$modal.removeClass( 'is-open' );
				}, 200 );
				$( 'body' ).css( 'overflow', '' );
			},

			/**
			 * Reset to initial state.
			 *
			 * @param {Object} $els UI elements.
			 * @param {Object} state Plugin state.
			 * @param {boolean} showSaved Whether to show saved photo.
			 * @param {Object} api Ajax/API module.
			 */
			resetToInstructions: function( $els, state, showSaved, api ) {
				if ( state.checkXhr && state.checkXhr.abort ) {
					state.checkXhr.abort();
				}
				this.stopLoadingSteps();
				$els.$upload.val( '' );
				state.selectedFile = null;
				state.generatedImageUrl = null;
				$els.$checkStatus.removeClass( 'is-ok is-warning is-error' ).empty().show();
				$els.$generate.prop( 'disabled', true );
				$els.$imgBefore.attr( 'src', '' );
				$els.$imgAfter.attr( 'src', '' );
				$els.$stage.removeClass( 'is-visible is-loading is-result' );
				$els.$actions.removeClass( 'is-visible' );
				$els.$generate.removeClass( 'is-hidden' );
				$els.$changeBtn.removeClass( 'is-hidden' );
				$els.$resultActions.removeClass( 'is-visible' );
				$els.$disclaimer.removeClass( 'is-visible' );
				$els.$instructions.removeClass( 'is-hidden' );
				$els.$tryons.removeClass( 'is-visible' );

				// Check for saved photo again (unless explicitly disabled)
				if ( showSaved !== false ) {
					this.showSavedPhotoSection( $els );
				} else {
					// Hide saved photo section and go directly to upload
					$els.$savedPhoto.removeClass( 'is-visible' );
					$els.$instructions.removeClass( 'has-saved-photo' );
				}
			}
		};
	})();

})( jQuery );

