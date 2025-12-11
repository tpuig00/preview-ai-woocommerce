(function( $ ) {
	'use strict';

	$( function() {
		var $form   = $( '#preview-ai-form' );
		if ( ! $form.length ) {
			return;
		}

		var $file   = $( '#preview_ai_image' );
		var $status = $( '#preview-ai-status' );

		$form.on( 'submit', function( e ) {
			e.preventDefault();

			if ( ! $file[0].files.length ) {
				$status.text( previewAiData && previewAiData.i18n && previewAiData.i18n.noFile ? previewAiData.i18n.noFile : 'Please select an image.' );
				return;
			}

			var formData = new FormData();
			formData.append( 'action', 'preview_ai_upload' );
			formData.append( 'nonce', previewAiData.nonce );
			formData.append( 'product_id', previewAiData.productId );
			var variationId = '';
			var $variationInput = $( 'input.variation_id' );
			if ( $variationInput.length && $variationInput.val() ) {
				variationId = $variationInput.val();
			} else if ( previewAiData.variationId ) {
				variationId = previewAiData.variationId;
			}
			formData.append( 'variation_id', variationId );
			formData.append( 'image', $file[0].files[0] );

			$status.text( previewAiData && previewAiData.i18n && previewAiData.i18n.loading ? previewAiData.i18n.loading : 'Uploading...' );

			$.ajax( {
				url: previewAiData.ajaxUrl,
				type: 'POST',
				data: formData,
				contentType: false,
				processData: false,
				success: function( response ) {
					if ( response && response.success ) {
						$status.text( previewAiData && previewAiData.i18n && previewAiData.i18n.success ? previewAiData.i18n.success : 'Preview ready!' );
					} else {
						var msg = response && response.data && response.data.message ? response.data.message : 'Error.';
						$status.text( msg );
					}
				},
				error: function() {
					$status.text( previewAiData && previewAiData.i18n && previewAiData.i18n.error ? previewAiData.i18n.error : 'Error occurred.' );
				},
			} );
		} );
	} );

})( jQuery );
