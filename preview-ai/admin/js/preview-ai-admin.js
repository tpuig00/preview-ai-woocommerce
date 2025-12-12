(function( $ ) {
	'use strict';

	$( function() {

		// Initialize color picker (only if available and elements exist).
		if ( $.fn.wpColorPicker && $( '.preview-ai-color-picker' ).length ) {
			$( '.preview-ai-color-picker' ).wpColorPicker();
		}

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
