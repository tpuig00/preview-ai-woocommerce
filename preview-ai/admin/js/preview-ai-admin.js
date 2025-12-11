(function( $ ) {
	'use strict';

	$( function() {

		// Settings page functionality.
		var productTypeSelect = document.getElementById( 'preview_ai_product_type' );
		var subtypeRow = document.getElementById( 'preview_ai_clothing_subtype_row' );
		var subtypeSelect = document.getElementById( 'preview_ai_clothing_subtype' );
		var examplesEl = document.getElementById( 'preview_ai_subtype_examples' );

		// Toggle clothing subtype visibility.
		if ( productTypeSelect && subtypeRow ) {
			productTypeSelect.addEventListener( 'change', function() {
				subtypeRow.style.display = this.value === 'clothing' ? '' : 'none';
			});
		}

		// Update subtype examples on change.
		if ( subtypeSelect && examplesEl && typeof previewAiAdmin !== 'undefined' ) {
			subtypeSelect.addEventListener( 'change', function() {
				var examples = previewAiAdmin.subtypeExamples[ this.value ] || '';
				examplesEl.textContent = examples ? previewAiAdmin.i18n.examples + ' ' + examples : '';
			});
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
