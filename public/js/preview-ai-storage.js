(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Storage = (function() {
		// localStorage keys.
		var STORAGE_KEY        = 'previewAiUserPhoto';
		var TRYONS_STORAGE_KEY = 'previewAiTryOns';
		var USAGE_KEY          = 'previewAiWeeklyUsage';
		var MAX_TRYONS         = 10;

		/**
		 * Get the start of the current week (Monday 00:00:00 UTC).
		 */
		function getWeekStart() {
			var now = new Date();
			var day = now.getUTCDay();
			var diff = ( day === 0 ) ? 6 : day - 1; // Monday = 0.
			var monday = new Date( Date.UTC( now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() - diff ) );
			return monday.toISOString().split( 'T' )[0]; // "YYYY-MM-DD".
		}

		return {
			/**
			 * Get how many previews the user has generated this week.
			 */
			getWeeklyUsage: function() {
				try {
					var data = JSON.parse( localStorage.getItem( USAGE_KEY ) || '{}' );
					var weekStart = getWeekStart();
					return ( data.week === weekStart ) ? ( data.count || 0 ) : 0;
				} catch ( e ) {
					return 0;
				}
			},

			/**
			 * Increment the weekly usage counter.
			 */
			incrementWeeklyUsage: function() {
				try {
					var weekStart = getWeekStart();
					var currentCount = this.getWeeklyUsage();
					localStorage.setItem( USAGE_KEY, JSON.stringify( {
						week: weekStart,
						count: currentCount + 1
					} ) );
				} catch ( e ) {
					// Storage not available.
				}
			},

			/**
			 * Check if user can generate more previews this week.
			 */
			canGeneratePreview: function() {
				if ( typeof previewAiData === 'undefined' || ! previewAiData.maxPreviewsWeekly ) {
					return true; // No limit configured.
				}
				return this.getWeeklyUsage() < previewAiData.maxPreviewsWeekly;
			},

			/**
			 * Get remaining previews for this week.
			 */
			getRemainingPreviews: function() {
				if ( typeof previewAiData === 'undefined' || ! previewAiData.maxPreviewsWeekly ) {
					return null; // No limit.
				}
				return Math.max( 0, previewAiData.maxPreviewsWeekly - this.getWeeklyUsage() );
			},

			// Check if we have a saved photo
			getSavedPhoto: function() {
				try {
					return localStorage.getItem( STORAGE_KEY );
				} catch ( e ) {
					return null;
				}
			},

			// Save photo to localStorage
			savePhoto: function( base64Data ) {
				try {
					localStorage.setItem( STORAGE_KEY, base64Data );
				} catch ( e ) {
					// Storage full or not available, silently ignore
				}
			},

			// Remove saved photo
			forgetSavedPhoto: function() {
				try {
					localStorage.removeItem( STORAGE_KEY );
				} catch ( e ) {
					// Ignore
				}
			},

			// Get looks from localStorage.
			getTryOns: function() {
				try {
					var data = localStorage.getItem( TRYONS_STORAGE_KEY );
					return data ? JSON.parse( data ) : [];
				} catch ( e ) {
					return [];
				}
			},

			// Save look to localStorage.
			saveTryOn: function( tryOnData ) {
				try {
					var tryOns = this.getTryOns();

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
			},

			// Delete a look by id.
			deleteTryOn: function( id ) {
				try {
					var tryOns = this.getTryOns();
					tryOns = tryOns.filter( function( t ) {
						return t.id !== id;
					} );
					localStorage.setItem( TRYONS_STORAGE_KEY, JSON.stringify( tryOns ) );
				} catch ( e ) {
					// Ignore.
				}
			}
		};
	})();

})( jQuery );

