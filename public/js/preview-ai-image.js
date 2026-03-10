(function( $ ) {
	'use strict';

	window.PreviewAI = window.PreviewAI || {};

	PreviewAI.Image = (function() {
		// Image resize settings
		var MAX_IMAGE_SIZE = 1536; // Max width/height in pixels
		var IMAGE_QUALITY  = 0.85; // JPEG quality (0-1)

		return {
			/**
			 * Resize image using Canvas API.
			 * Reduces large images (e.g., 4200x5700 from iOS) to reasonable dimensions.
			 *
			 * @param {File} file Original image file.
			 * @param {function} callback Callback with resized File and base64 data URL.
			 */
			resizeImage: function( file, callback ) {
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
			},

			/**
			 * Convert base64 to File object
			 *
			 * @param {string} base64 Base64 string.
			 * @param {string} filename Desired filename.
			 * @return {File} File object.
			 */
			base64ToFile: function( base64, filename ) {
				var arr = base64.split( ',' );
				var mime = arr[0].match( /:(.*?);/ )[1];
				var bstr = atob( arr[1] );
				var n = bstr.length;
				var u8arr = new Uint8Array( n );
				while ( n-- ) {
					u8arr[n] = bstr.charCodeAt( n );
				}
				return new File( [u8arr], filename, { type: mime } );
			},

			/**
			 * Download image from URL.
			 *
			 * @param {string} url Image URL.
			 * @param {string} filename Desired filename.
			 */
			downloadImage: function( url, filename ) {
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
		};
	})();

})( jQuery );

