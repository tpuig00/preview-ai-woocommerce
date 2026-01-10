<?php
/**
 * Demo Tour - Multi-step onboarding overlay.
 *
 * Only loaded when ?demo=yes parameter is present.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/public/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<style>
.preview-ai-demo-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.8);
	z-index: 999998;
	opacity: 0;
	transition: opacity 0.3s ease;
}

.preview-ai-demo-overlay.is-active {
	opacity: 1;
}

.preview-ai-demo-highlight {
	position: relative;
	z-index: 999999 !important;
	box-shadow: 0 0 0 4px #3b82f6, 0 0 40px rgba(59, 130, 246, 0.6) !important;
	border-radius: 12px !important;
	background: #fff;
	animation: preview-ai-pulse 2s infinite;
}

@keyframes preview-ai-pulse {
	0%, 100% {
		box-shadow: 0 0 0 4px #3b82f6, 0 0 40px rgba(59, 130, 246, 0.6);
	}
	50% {
		box-shadow: 0 0 0 8px #3b82f6, 0 0 60px rgba(59, 130, 246, 0.8);
	}
}

/* Tooltip - Fixed bottom sheet on all devices for simplicity */
.preview-ai-demo-tooltip {
	position: fixed;
	z-index: 1000000;
	background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
	color: #f8fafc;
	padding: 24px 20px;
	padding-bottom: calc(24px + env(safe-area-inset-bottom, 0px));
	border-radius: 24px 24px 0 0;
	box-shadow: 0 -10px 60px rgba(0, 0, 0, 0.5);
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
	border: 1px solid rgba(255, 255, 255, 0.1);
	border-bottom: none;
	
	/* Fixed bottom sheet */
	left: 0;
	right: 0;
	bottom: 0;
	max-height: 70vh;
	overflow-y: auto;
	
	/* Animation */
	opacity: 0;
	transform: translateY(100%);
	transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.preview-ai-demo-tooltip.is-visible {
	opacity: 1;
	transform: translateY(0);
}

/* Handle indicator */
.preview-ai-demo-tooltip::before {
	content: '';
	position: absolute;
	top: 10px;
	left: 50%;
	transform: translateX(-50%);
	width: 40px;
	height: 4px;
	background: rgba(255, 255, 255, 0.3);
	border-radius: 2px;
}

/* Desktop: Centered card above the fold */
@media (min-width: 768px) {
	.preview-ai-demo-tooltip {
		left: 50%;
		right: auto;
		bottom: 24px;
		max-height: none;
		overflow-y: visible;
		padding: 28px 32px;
		padding-bottom: 28px;
		
		/* Desktop floating card */
		border-radius: 20px;
		max-width: 440px;
		width: calc(100% - 48px);
		box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.6);
		border: 1px solid rgba(255, 255, 255, 0.1);
		
		/* Center horizontally */
		transform: translateX(-50%) translateY(30px);
	}
	
	.preview-ai-demo-tooltip.is-visible {
		transform: translateX(-50%) translateY(0);
	}
	
	/* Hide handle on desktop */
	.preview-ai-demo-tooltip::before {
		display: none;
	}
}

.preview-ai-demo-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16px;
	margin-top: 8px;
}

@media (min-width: 768px) {
	.preview-ai-demo-header {
		margin-top: 0;
	}
}

.preview-ai-demo-badge {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	background: linear-gradient(135deg, #3b82f6, #8b5cf6);
	color: #fff;
	font-size: 11px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.8px;
	padding: 5px 12px;
	border-radius: 20px;
}

.preview-ai-demo-badge svg {
	width: 14px;
	height: 14px;
}

.preview-ai-demo-steps {
	display: flex;
	align-items: center;
	gap: 6px;
}

.preview-ai-demo-step-dot {
	width: 8px;
	height: 8px;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.2);
	transition: all 0.3s ease;
}

.preview-ai-demo-step-dot.is-active {
	background: #3b82f6;
	width: 24px;
	border-radius: 4px;
}

.preview-ai-demo-step-dot.is-completed {
	background: #22c55e;
}

.preview-ai-demo-icon {
	width: 48px;
	height: 48px;
	background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
	border-radius: 14px;
	display: flex;
	align-items: center;
	justify-content: center;
	margin-bottom: 16px;
}

.preview-ai-demo-icon svg {
	width: 26px;
	height: 26px;
	stroke: #3b82f6;
}

.preview-ai-demo-title {
	font-size: 18px;
	font-weight: 700;
	margin: 0 0 10px;
	color: #f8fafc !important;
	line-height: 1.3;
}

@media (min-width: 768px) {
	.preview-ai-demo-title {
		font-size: 20px;
		margin-bottom: 12px;
	}
}

.preview-ai-demo-text {
	font-size: 14px;
	line-height: 1.6;
	color: #94a3b8;
	margin: 0 0 20px;
}

@media (min-width: 768px) {
	.preview-ai-demo-text {
		font-size: 15px;
		line-height: 1.7;
		margin-bottom: 24px;
	}
}

.preview-ai-demo-text strong {
	color: #60a5fa;
	font-weight: 600;
}

.preview-ai-demo-features {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-bottom: 20px;
}

@media (min-width: 768px) {
	.preview-ai-demo-features {
		margin-bottom: 24px;
	}
}

.preview-ai-demo-feature {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	background: rgba(59, 130, 246, 0.1);
	color: #60a5fa;
	font-size: 11px;
	font-weight: 500;
	padding: 5px 10px;
	border-radius: 8px;
	border: 1px solid rgba(59, 130, 246, 0.2);
}

@media (min-width: 768px) {
	.preview-ai-demo-feature {
		font-size: 12px;
		padding: 6px 12px;
	}
}

.preview-ai-demo-feature svg {
	width: 14px;
	height: 14px;
}

.preview-ai-demo-actions {
	display: flex;
	align-items: center;
	gap: 12px;
}

.preview-ai-demo-cta {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	background: linear-gradient(135deg, #3b82f6, #2563eb);
	color: #fff;
	border: none;
	padding: 14px 24px;
	border-radius: 12px;
	font-size: 15px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.2s ease;
	box-shadow: 0 4px 20px rgba(59, 130, 246, 0.4);
	flex: 1;
}

.preview-ai-demo-cta:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 30px rgba(59, 130, 246, 0.5);
}

.preview-ai-demo-cta:active {
	transform: translateY(0);
}

.preview-ai-demo-cta svg {
	width: 18px;
	height: 18px;
}

.preview-ai-demo-skip {
	color: #64748b;
	font-size: 13px;
	text-decoration: none;
	cursor: pointer;
	transition: color 0.2s;
	padding: 14px 16px;
	white-space: nowrap;
}

.preview-ai-demo-skip:hover {
	color: #94a3b8;
}
</style>

<script>
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
			title: '<?php echo esc_js( __( '🎨 Preview AI Understands Your Catalog', 'preview-ai' ) ); ?>',
			text: '<?php echo esc_js( __( 'Our AI analyzes your <strong>product images</strong>, including all <strong>variations and color options</strong>. If you have photos for each variant, Preview AI will use them for more accurate try-ons.', 'preview-ai' ) ); ?>',
			features: [
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>', text: '<?php echo esc_js( __( 'Auto-detection', 'preview-ai' ) ); ?>' },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>', text: '<?php echo esc_js( __( 'Real-time', 'preview-ai' ) ); ?>' },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>', text: '<?php echo esc_js( __( 'All colors', 'preview-ai' ) ); ?>' }
			],
			cta: '<?php echo esc_js( __( 'Next', 'preview-ai' ) ); ?>'
		},
		{
			// Step 2: The widget
			targets: ['.preview-ai-chip'],
			fallbackTargets: ['.preview-ai-chip-wrapper', '#preview-ai-trigger'],
			icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><path d="M16 11l2 2 4-4"/></svg>',
			title: '<?php echo esc_js( __( '✨ Your Customers See This', 'preview-ai' ) ); ?>',
			text: '<?php echo esc_js( __( 'This widget is <strong>fully customizable</strong> and adapts to your store\'s design. Customers click here to instantly try on your products using their own photo.', 'preview-ai' ) ); ?>',
			features: [
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>', text: '<?php echo esc_js( __( 'Customizable', 'preview-ai' ) ); ?>' },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>', text: '<?php echo esc_js( __( 'Responsive', 'preview-ai' ) ); ?>' },
				{ icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>', text: '<?php echo esc_js( __( 'One-click', 'preview-ai' ) ); ?>' }
			],
			cta: '<?php echo esc_js( __( 'Try it now!', 'preview-ai' ) ); ?>',
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

		tooltip.innerHTML = '\
			<div class="preview-ai-demo-header">\
				<div class="preview-ai-demo-badge">\
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">\
						<path d="M12 2L2 7l10 5 10-5-10-5z"/>\
						<path d="M2 17l10 5 10-5"/>\
						<path d="M2 12l10 5 10-5"/>\
					</svg>\
					<?php echo esc_js( __( 'Demo Tour', 'preview-ai' ) ); ?>\
				</div>\
				<div class="preview-ai-demo-steps">' + dotsHtml + '</div>\
			</div>\
			<div class="preview-ai-demo-icon">' + step.icon + '</div>\
			<h3 class="preview-ai-demo-title">' + step.title + '</h3>\
			<p class="preview-ai-demo-text">' + step.text + '</p>\
			' + featuresHtml + '\
			<div class="preview-ai-demo-actions">\
				<button class="preview-ai-demo-cta">\
					' + step.cta + '\
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">\
						<path d="M5 12h14M12 5l7 7-7 7"/>\
					</svg>\
				</button>\
				<span class="preview-ai-demo-skip"><?php echo esc_js( __( 'Skip', 'preview-ai' ) ); ?></span>\
			</div>\
		';

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
</script>
