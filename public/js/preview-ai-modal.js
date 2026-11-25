(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Modal = (function() {
		var loadingTimer = null;
		var loadingStart = 0;
		var currentStep  = 0;

		// Progress (%) at which each loading step starts.
		var STEP_AT = [ 0, 14, 36, 60, 82 ];

		function showStep( index ) {
			var $steps = $( '.preview-ai-step' );
			if ( index === currentStep || index >= $steps.length ) {
				return;
			}
			var $prev = $steps.eq( currentStep );
			var $next = $steps.eq( index );
			currentStep = index;

			$prev.addClass( 'pai-is-exiting' ).removeClass( 'pai-is-active' );
			setTimeout( function() {
				$prev.removeClass( 'pai-is-exiting' );
				if ( currentStep === index ) {
					$steps.removeClass( 'pai-is-active' );
					$next.addClass( 'pai-is-active' );
				}
			}, 200 );
		}

		function setProgress( pct ) {
			var value = Math.round( pct );
			var step  = 0;

			$( '#preview-ai-gen-bar' ).css( 'width', pct + '%' );
			$( '#preview-ai-gen-percent' ).text( value + '%' );
			$( '.pai-gen__progress' ).attr( 'aria-valuenow', value );

			for ( var i = 0; i < STEP_AT.length; i++ ) {
				if ( value >= STEP_AT[ i ] ) {
					step = i;
				}
			}
			showStep( step );
		}

		function clearTimer() {
			if ( loadingTimer ) {
				clearInterval( loadingTimer );
				loadingTimer = null;
			}
		}

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
					$els.$savedPhoto.addClass( 'pai-is-visible' );
					$els.$instructions.addClass( 'pai-has-saved-photo' );
				} else {
					$els.$savedPhoto.removeClass( 'pai-is-visible' );
					$els.$instructions.removeClass( 'pai-has-saved-photo' );
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
				$els.$savedPhoto.removeClass( 'pai-is-visible' );
				$els.$instructions.addClass( 'pai-is-hidden' );
				$els.$stage.addClass( 'pai-is-visible' ).removeClass( 'pai-is-result' );
				$els.$actions.addClass( 'pai-is-visible' );
				$els.$generate.removeClass( 'pai-is-hidden' );
				$els.$generate.prop( 'disabled', true );
				$els.$resultActions.removeClass( 'pai-is-visible' );
				$els.$disclaimer.removeClass( 'pai-is-visible' );
				startPrecheck( state.selectedFile );
			},

			/**
			 * Start the loading screen: show both images and run the progress bar.
			 *
			 * The real duration is unknown, so progress eases towards 95% and only
			 * reaches 100% when the result arrives (see completeLoading).
			 */
			startLoadingSteps: function() {
				clearTimer();
				$( '#preview-ai-gen-photo' ).attr( 'src', $( '#preview-ai-img-before' ).attr( 'src' ) || '' );
				$( '#preview-ai-gen-product' ).attr( 'src', $( '#preview-ai-intro-product' ).attr( 'src' ) || '' );

				currentStep = 0;
				$( '.preview-ai-step' ).removeClass( 'pai-is-active pai-is-exiting' ).first().addClass( 'pai-is-active' );
				setProgress( 0 );

				loadingStart = Date.now();
				loadingTimer = setInterval( function() {
					var seconds = ( Date.now() - loadingStart ) / 1000;
					setProgress( 95 * ( 1 - Math.exp( -seconds / 15 ) ) );
				}, 250 );
			},

			/**
			 * Fill the progress bar to 100% before showing the result.
			 *
			 * @param {Function} done Called once the bar is full.
			 */
			completeLoading: function( done ) {
				clearTimer();
				setProgress( 100 );
				setTimeout( done, 450 );
			},

			/**
			 * Stop and reset the loading screen.
			 * Called unbound as a callback, so it must not use `this`.
			 */
			stopLoadingSteps: function() {
				clearTimer();
				currentStep = 0;
				$( '.preview-ai-step' ).removeClass( 'pai-is-active pai-is-exiting' ).first().addClass( 'pai-is-active' );
				$( '#preview-ai-gen-bar' ).css( 'width', '0' );
				$( '#preview-ai-gen-percent' ).text( '0%' );
				$( '.pai-gen__progress' ).attr( 'aria-valuenow', 0 );
			},

			/**
			 * Close modal.
			 *
			 * @param {jQuery} $modal Modal element.
			 */
			close: function( $modal ) {
				$modal.removeClass( 'pai-is-visible' );
				setTimeout( function() {
					$modal.removeClass( 'pai-is-open' );
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
				$els.$checkStatus.removeClass( 'pai-is-ok pai-is-warning pai-is-error' ).empty().show();
				$els.$generate.prop( 'disabled', true );
				$els.$imgBefore.attr( 'src', '' );
				$els.$imgAfter.attr( 'src', '' );
				$els.$stage.removeClass( 'pai-is-visible pai-is-loading pai-is-result' );
				$els.$actions.removeClass( 'pai-is-visible' );
				$els.$generate.removeClass( 'pai-is-hidden' );
				$els.$changeBtn.removeClass( 'pai-is-hidden' );
				$els.$resultActions.removeClass( 'pai-is-visible' );
				$els.$disclaimer.removeClass( 'pai-is-visible' );
				$els.$instructions.removeClass( 'pai-is-hidden' );
				$els.$tryons.removeClass( 'pai-is-visible' );

				// Check for saved photo again (unless explicitly disabled)
				if ( showSaved !== false ) {
					this.showSavedPhotoSection( $els );
				} else {
					// Hide saved photo section and go directly to upload
					$els.$savedPhoto.removeClass( 'pai-is-visible' );
					$els.$instructions.removeClass( 'pai-has-saved-photo' );
				}
			}
		};
	})();

})( jQuery );

