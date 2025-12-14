(function( $ ) {
	'use strict';

	$( function() {

		// Initialize color picker.
		$( '.preview-ai-color-picker' ).wpColorPicker();

		// Icon selector - update visual state on change.
		$( '.preview-ai-icon-option input' ).on( 'change', function() {
			$( '.preview-ai-icon-option' ).removeClass( 'is-selected' );
			$( this ).closest( '.preview-ai-icon-option' ).addClass( 'is-selected' );
		} );

		// Verify API Key functionality.
		$( '#preview_ai_verify_btn' ).on( 'click', function() {
			var $btn = $( this );
			var $status = $( '#preview_ai_verify_status' );
			var apiKey = $( '#preview_ai_api_key' ).val();

			if ( ! apiKey ) {
				$status.html( '<span style="color:#d63638;">✗ Enter an API key first</span>' );
				return;
			}

			$btn.prop( 'disabled', true ).text( '...' );
			$status.html( '' );

			$.ajax( {
				url: previewAiAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'preview_ai_verify_api_key',
					nonce: previewAiAdmin.verifyNonce
				},
				success: function( res ) {
					$btn.prop( 'disabled', false ).text( 'Verify' );
					if ( res.success ) {
						$status.html( '<span style="color:#00a32a;">' + res.data.message + '</span>' );
					} else {
						$status.html( '<span style="color:#d63638;">✗ ' + res.data.message + '</span>' );
					}
				},
				error: function() {
					$btn.prop( 'disabled', false ).text( 'Verify' );
					$status.html( '<span style="color:#d63638;">✗ Connection error</span>' );
				}
			} );
		} );

		// Learn My Catalog functionality.
		var learnBtn = document.getElementById( 'preview_ai_learn_catalog_btn' );
		var loadingEl = document.getElementById( 'preview_ai_learn_catalog_loading' );
		var resultEl = document.getElementById( 'preview_ai_learn_catalog_result' );

		if ( learnBtn && typeof previewAiAdmin !== 'undefined' ) {
			learnBtn.addEventListener( 'click', function() {
				learnBtn.disabled = true;
				loadingEl.style.display = 'block';
				resultEl.style.display = 'none';

				$.ajax({
					url: previewAiAdmin.ajaxUrl,
					type: 'POST',
					data: {
						action: 'preview_ai_learn_catalog',
						nonce: previewAiAdmin.nonce
					},
					success: function( response ) {
						learnBtn.disabled = false;
						loadingEl.style.display = 'none';
						resultEl.style.display = 'block';

						if ( response.success ) {
							resultEl.style.background = '#edfaef';
							resultEl.style.borderLeft = '4px solid #00a32a';
							resultEl.innerHTML = '<strong style="color:#00a32a;">✓</strong> ' + response.data.message;
							if ( response.data.pending ) {
								resultEl.innerHTML += '<br><small style="color:#787c82;">' + previewAiAdmin.i18n.apiPending + '</small>';
							}
						} else {
							resultEl.style.background = '#fcf0f1';
							resultEl.style.borderLeft = '4px solid #d63638';
							resultEl.innerHTML = '<strong style="color:#d63638;">✗</strong> ' + ( response.data.message || previewAiAdmin.i18n.error );
						}
					},
					error: function() {
						learnBtn.disabled = false;
						loadingEl.style.display = 'none';
						resultEl.style.display = 'block';
						resultEl.style.background = '#fcf0f1';
						resultEl.style.borderLeft = '4px solid #d63638';
						resultEl.innerHTML = '<strong style="color:#d63638;">✗</strong> ' + previewAiAdmin.i18n.error;
					}
				});
			});
		}

	});

})( jQuery );
