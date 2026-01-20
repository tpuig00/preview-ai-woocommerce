(function() {
	'use strict';

	var currentStep = 0;
	var overlay, tooltip;
	var highlightedElements = [];
	var isMobile = window.innerWidth < 768;

	// Tour steps configuration
	var steps = [
		{
			// Step 1: Product image and variations
			targets: ['.woocommerce-product-gallery', '.variations'],
			fallbackTargets: ['.product-gallery', '.wp-post-image', '.woocommerce-product-gallery__image'],
			icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>',
			title: previewAiDemo.i18n.step1Title,
			text: previewAiDemo.i18n.step1Text,
			features: [
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>', text: previewAiDemo.i18n.autoDetection },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>', text: previewAiDemo.i18n.realTime },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>', text: previewAiDemo.i18n.allColors }
			],
			cta: previewAiDemo.i18n.next
		},
		{
			// Step 2: The widget
			targets: ['.preview-ai-chip'],
			fallbackTargets: ['.preview-ai-chip-wrapper', '#preview-ai-trigger'],
			icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 11l2 2 4-4"/></svg>',
			title: previewAiDemo.i18n.step2Title,
			text: previewAiDemo.i18n.step2Text,
			features: [
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>', text: previewAiDemo.i18n.customizable },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>', text: previewAiDemo.i18n.responsive },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>', text: previewAiDemo.i18n.oneClick }
			],
			cta: previewAiDemo.i18n.tryItNow,
			triggerOnComplete: true
		}
	];

	function findElements(step) {
		var elements = [];
		
		for (var i = 0; i < step.targets.length; i++) {
			var el = document.querySelector(step.targets[i]);
			if (el && el.offsetParent !== null) {
				elements.push(el);
			}
		}
		
		// If no primary targets found, try fallbacks
		if (elements.length === 0 && step.fallbackTargets) {
			for (var j = 0; j < step.fallbackTargets.length; j++) {
				var fallback = document.querySelector(step.fallbackTargets[j]);
				if (fallback && fallback.offsetParent !== null) {
					elements.push(fallback);
					break;
				}
			}
		}
		
		return elements;
	}

	function clearHighlights() {
		highlightedElements.forEach(function(el) {
			el.classList.remove('preview-ai-demo-highlight');
		});
		highlightedElements = [];
	}

	function highlightElements(elements) {
		clearHighlights();
		elements.forEach(function(el) {
			el.classList.add('preview-ai-demo-highlight');
			highlightedElements.push(el);
		});
	}

	function scrollToElement(element) {
		isMobile = window.innerWidth < 768;
		
		var elementRect = element.getBoundingClientRect();
		var scrollY = window.scrollY || window.pageYOffset;
		
		// Leave space for the bottom tooltip (mobile: ~50% of screen, desktop: less)
		var tooltipSpace = isMobile ? window.innerHeight * 0.45 : window.innerHeight * 0.35;
		
		// Calculate target scroll position to center element in visible area above tooltip
		var visibleHeight = window.innerHeight - tooltipSpace;
		var targetScroll = scrollY + elementRect.top - (visibleHeight / 2) + (elementRect.height / 2);
		
		window.scrollTo({
			top: Math.max(0, targetScroll),
			behavior: 'smooth'
		});
	}

	function renderStep(stepIndex) {
		var step = steps[stepIndex];
		var elements = findElements(step);

		if (elements.length === 0) {
			// Skip this step if elements not found
			if (stepIndex < steps.length - 1) {
				currentStep++;
				renderStep(currentStep);
			} else {
				closeDemo();
			}
			return;
		}

		// Build features HTML
		var featuresHtml = '';
		if (step.features && step.features.length) {
			featuresHtml = '<div class="preview-ai-demo-features">';
			step.features.forEach(function(f) {
				featuresHtml += '<span class="preview-ai-demo-feature">' + f.icon + ' ' + f.text + '</span>';
			});
			featuresHtml += '</div>';
		}

		// Build step dots
		var dotsHtml = '';
		for (var i = 0; i < steps.length; i++) {
			var dotClass = 'preview-ai-demo-step-dot';
			if (i < stepIndex) dotClass += ' is-completed';
			if (i === stepIndex) dotClass += ' is-active';
			dotsHtml += '<span class="' + dotClass + '"></span>';
		}

		tooltip.innerHTML = 
			'<div class="preview-ai-demo-header">' +
				'<div class="preview-ai-demo-badge">' +
					'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
						'<path d="M12 2L2 7l10 5 10-5-10-5z"/>' +
						'<path d="M2 17l10 5 10-5"/>' +
						'<path d="M2 12l10 5 10-5"/>' +
					'</svg>' +
					previewAiDemo.i18n.demoTour +
				'</div>' +
				'<div class="preview-ai-demo-steps">' + dotsHtml + '</div>' +
			'</div>' +
			'<div class="preview-ai-demo-icon">' + step.icon + '</div>' +
			'<h3 class="preview-ai-demo-title">' + step.title + '</h3>' +
			'<p class="preview-ai-demo-text">' + step.text + '</p>' +
			featuresHtml +
			'<div class="preview-ai-demo-actions">' +
				'<button class="preview-ai-demo-cta">' +
					step.cta +
					'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
						'<path d="M5 12h14M12 5l7 7-7 7"/>' +
					'</svg>' +
				'</button>' +
				'<span class="preview-ai-demo-skip">' + previewAiDemo.i18n.skip + '</span>' +
			'</div>';

		// Highlight elements
		highlightElements(elements);

		// Scroll to first highlighted element
		scrollToElement(elements[0]);

		// Hide tooltip during transition, then show
		tooltip.classList.remove('is-visible');
		
		setTimeout(function() {
			tooltip.classList.add('is-visible');
		}, 400);

		// Bind events
		var ctaBtn = tooltip.querySelector('.preview-ai-demo-cta');
		var skipBtn = tooltip.querySelector('.preview-ai-demo-skip');

		ctaBtn.onclick = function() {
			if (stepIndex < steps.length - 1) {
				currentStep++;
				renderStep(currentStep);
			} else {
				var shouldTrigger = step.triggerOnComplete;
				var targetEl = elements[0];
				closeDemo();
				if (shouldTrigger && targetEl) {
					setTimeout(function() {
						targetEl.click();
					}, 400);
				}
			}
		};

		skipBtn.onclick = closeDemo;
	}

	function closeDemo() {
		clearHighlights();
		tooltip.classList.remove('is-visible');
		overlay.classList.remove('is-active');

		setTimeout(function() {
			if (overlay && overlay.parentNode) overlay.remove();
			if (tooltip && tooltip.parentNode) tooltip.remove();
		}, 400);

		// Remove demo param from URL
		var newUrl = new URL(window.location.href);
		newUrl.searchParams.delete('demo');
		window.history.replaceState({}, '', newUrl.toString());
	}

	function initDemo() {
		// Check if primary elements exist
		var hasElements = findElements(steps[0]).length > 0 || findElements(steps[1]).length > 0;
		
		if (!hasElements) {
			setTimeout(initDemo, 500);
			return;
		}

		// Create overlay
		overlay = document.createElement('div');
		overlay.className = 'preview-ai-demo-overlay';
		document.body.appendChild(overlay);

		// Create tooltip
		tooltip = document.createElement('div');
		tooltip.className = 'preview-ai-demo-tooltip';
		document.body.appendChild(tooltip);

		// Activate overlay and start tour
		setTimeout(function() {
			overlay.classList.add('is-active');
			renderStep(0);
		}, 300);

		// Close on overlay click
		overlay.addEventListener('click', closeDemo);

		// Handle resize
		var resizeTimeout;
		window.addEventListener('resize', function() {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function() {
				isMobile = window.innerWidth < 768;
				var elements = findElements(steps[currentStep]);
				if (elements.length > 0) {
					scrollToElement(elements[0]);
				}
			}, 100);
		});
	}

	// Start when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initDemo);
	} else {
		setTimeout(initDemo, 100);
	}
})();
