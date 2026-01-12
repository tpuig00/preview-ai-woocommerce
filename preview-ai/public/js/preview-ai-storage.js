(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Storage = (function() {
		// localStorage keys.
		var STORAGE_KEY        = 'previewAiUserPhoto';
		var TRYONS_STORAGE_KEY = 'previewAiTryOns';
		var MAX_TRYONS         = 10;

		return {
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

