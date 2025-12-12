(function( $ ) {
	'use strict';

	$( function() {
		var $form       = $( '#preview-ai-form' );
		var $file       = $( '#preview_ai_image' );
		var $status     = $( '#preview-ai-status' );
		var $submit     = $( '#preview-ai-submit' );
		var $result     = $( '#preview-ai-result' );
		var $thumb      = $( '#preview-ai-thumb' );
		var $lightbox   = $( '#preview-ai-lightbox' );
		var $full       = $( '#preview-ai-full' );
		var $uploadIcon = $( '#preview-ai-upload-icon' );
		var $uploadText = $( '#preview-ai-upload-text' );

		// Show filename when file selected
		$file.on( 'change', function() {
			if ( this.files.length ) {
				var name = this.files[0].name;
				if ( name.length > 26 ) {
					name = name.substring( 0, 19 ) + '...';
				}
				$uploadIcon.text( '✓' );
				$uploadText.text( name );
				$( '.preview-ai-upload' ).addClass( 'has-file' );
			}
		} );

		// Open lightbox on thumbnail click
		$thumb.on( 'click', function() {
			$full.attr( 'src', $thumb.attr( 'src' ) );
			$lightbox.addClass( 'is-open' );
		} );

		// Close lightbox on click anywhere
		$lightbox.on( 'click', function() {
			$lightbox.removeClass( 'is-open' );
		} );

		// Form submit
		$form.on( 'submit', function( e ) {
			e.preventDefault();

			if ( ! $file[0].files.length ) {
				$status.text( 'Select a photo' );
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

			formData.append( 'image', $file[0].files[0] );

			$status.text( 'Generating...' );
			$submit.prop( 'disabled', true );
			$result.hide();

			$.ajax( {
				url: previewAiData.ajaxUrl,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				success: function( res ) {
					$submit.prop( 'disabled', false );

					if ( res?.success && res?.data?.generated_image_url ) {
						$status.text( '' );
						$thumb.attr( 'src', res.data.generated_image_url );
						$result.fadeIn();
					} else {
						$status.text( res?.data?.message || 'Error' );
					}
				},
				error: function() {
					$submit.prop( 'disabled', false );
					$status.text( 'Error' );
				}
			} );
		} );
	} );

})( jQuery );
