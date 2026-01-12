(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.TryOns = (function() {
		// Private helper for date formatting
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

		return {
			/**
			 * Render a single try-on card.
			 *
			 * @param {Object} tryOn Try-on data.
			 * @return {jQuery} Card element.
			 */
			renderCard: function( tryOn ) {
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
			},

			/**
			 * Render looks list.
			 *
			 * @param {jQuery} $list List container.
			 * @param {jQuery} $empty Empty state element.
			 * @param {jQuery} $count Count element.
			 */
			renderList: function( $list, $empty, $count ) {
				var tryOns = PreviewAI.Storage.getTryOns();
				$list.empty();

				if ( tryOns.length === 0 ) {
					$empty.addClass( 'is-visible' );
					$count.text( '' );
					return;
				}

				$empty.removeClass( 'is-visible' );
				var countText = tryOns.length + ' ' + ( tryOns.length === 1 ? ( previewAiData.i18n.result || 'result' ) : ( previewAiData.i18n.results || 'results' ) );
				$count.text( countText );

				var self = this;
				tryOns.forEach( function( tryOn ) {
					$list.append( self.renderCard( tryOn ) );
				} );
			},

			/**
			 * Update looks badge count.
			 *
			 * @param {Object} $els Collection of badge/button elements.
			 */
			updateBadge: function( $els ) {
				var tryOns = PreviewAI.Storage.getTryOns();
				if ( tryOns.length > 0 ) {
					$els.$tryonsBadge.text( tryOns.length ).addClass( 'is-visible' );
					$els.$viewTryonsBtn.addClass( 'has-tryons' );
					$els.$instructionsLooksBadge.text( tryOns.length ).addClass( 'is-visible' );
					$els.$instructionsLooksBtn.addClass( 'has-looks' );
				} else {
					$els.$tryonsBadge.text( '' ).removeClass( 'is-visible' );
					$els.$viewTryonsBtn.removeClass( 'has-tryons' );
					$els.$instructionsLooksBadge.text( '' ).removeClass( 'is-visible' );
					$els.$instructionsLooksBtn.removeClass( 'has-looks' );
				}
			},

			/**
			 * Show looks section.
			 *
			 * @param {Object} $els Collection of UI elements.
			 */
			showSection: function( $els ) {
				this.renderList( $els.$tryonsList, $els.$tryonsEmpty, $els.$tryonsCount );
				$els.$savedPhoto.removeClass( 'is-visible' );
				$els.$instructions.addClass( 'is-hidden' );
				$els.$stage.removeClass( 'is-visible' );
				$els.$actions.removeClass( 'is-visible' );
				$els.$tryons.addClass( 'is-visible' );
			},

			/**
			 * Hide looks section and go back.
			 *
			 * @param {Object} $els Collection of UI elements.
			 * @param {Function} showSavedPhotoSection Callback to show saved photo.
			 */
			hideSection: function( $els, showSavedPhotoSection ) {
				$els.$tryons.removeClass( 'is-visible' );
				showSavedPhotoSection();
				$els.$instructions.removeClass( 'is-hidden' );
			}
		};
	})();

})( jQuery );

