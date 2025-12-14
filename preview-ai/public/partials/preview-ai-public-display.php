<?php
/**
 * Public widget template - Google Try-On Style.
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

// Get product_id from calling context.
if ( ! isset( $product_id ) ) {
	global $product;
	$product_id = $product ? $product->get_id() : 0;
}

// Set defaults if not provided by render_widget_output.
if ( ! isset( $button_text ) ) {
	$button_text = __( 'See it on you', 'preview-ai' );
}
if ( ! isset( $button_svg ) ) {
	$button_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8L19 13"/><path d="M15 9h.01"/><path d="M17.8 6.2L19 5"/><path d="M3 21l9-9"/><path d="M12.2 6.2L11 5"/></svg>';
}
if ( ! isset( $button_position ) ) {
	$button_position = 'center';
}

$position_class = 'preview-ai-position-' . esc_attr( $button_position );
?>

<!-- Action Chip Container -->
<div class="preview-ai-chip-wrapper <?php echo esc_attr( $position_class ); ?>">
	<button type="button" id="preview-ai-trigger" class="preview-ai-chip">
		<span class="preview-ai-chip-icon"><?php echo $button_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="preview-ai-chip-text"><?php echo esc_html( $button_text ); ?></span>
	</button>
</div>

<!-- Modal -->
<div id="preview-ai-modal" class="preview-ai-modal">
	<div class="preview-ai-modal-content">
		<button type="button" id="preview-ai-close" class="preview-ai-close" aria-label="<?php esc_attr_e( 'Close', 'preview-ai' ); ?>">×</button>

		<!-- Instructions -->
		<div id="preview-ai-instructions" class="preview-ai-instructions">
			<div class="preview-ai-instructions-header">
				<span class="preview-ai-instructions-icon">🪄</span>
				<h3><?php esc_html_e( 'See it on you', 'preview-ai' ); ?></h3>
				<p><?php esc_html_e( 'See how this product looks on you', 'preview-ai' ); ?></p>
			</div>

			<div class="preview-ai-illustration">
				<svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="60" cy="35" r="18" fill="#e5e7eb"/>
					<path d="M30 95c0-16.569 13.431-30 30-30s30 13.431 30 30" fill="#e5e7eb"/>
					<rect x="72" y="50" width="22" height="38" rx="3" fill="#3b82f6"/>
					<rect x="74" y="53" width="18" height="28" rx="1" fill="#818cf8"/>
					<circle cx="83" cy="59" r="4" fill="#bfdbfe" stroke="#3b82f6" stroke-width="1"/>
					<ellipse cx="78" cy="82" rx="8" ry="6" fill="#d1d5db"/>
				</svg>
			</div>

			<div class="preview-ai-tips">
				<p class="preview-ai-tips-title"><?php esc_html_e( 'For best results:', 'preview-ai' ); ?></p>
				<ul>
					<?php foreach ( $tips as $tip ) : ?>
						<li><?php echo esc_html( $tip ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p class="preview-ai-tips-pro"><strong><?php esc_html_e( 'Tip: wearing a similar item helps the preview look more realistic', 'preview-ai' ); ?></strong></p>
			</div>

			<!-- Camera button (mobile) / Upload button (desktop) -->
			<label for="preview_ai_camera" class="preview-ai-camera-btn">
				<span class="preview-ai-camera-icon">📸</span>
				<span class="preview-ai-camera-text"><?php esc_html_e( 'Open camera', 'preview-ai' ); ?></span>
			</label>

			<!-- Gallery link (mobile only) -->
			<label for="preview_ai_gallery" class="preview-ai-gallery-link"><?php esc_html_e( 'or upload from gallery', 'preview-ai' ); ?></label>

			<!-- Hidden file inputs -->
			<input type="file" id="preview_ai_camera" accept="image/*" class="preview-ai-file-input" />
			<input type="file" id="preview_ai_gallery" accept="image/*" class="preview-ai-file-input" />
		</div>

		<!-- Image Stage -->
		<div id="preview-ai-stage" class="preview-ai-stage">
			<img id="preview-ai-img-before" class="preview-ai-img-before" src="" alt="" />
			<img id="preview-ai-img-after" class="preview-ai-img-after" src="" alt="<?php esc_attr_e( 'Generated preview', 'preview-ai' ); ?>" />

			<div id="preview-ai-loading" class="preview-ai-loading">
				<div class="preview-ai-loading-shimmer"></div>
				<div class="preview-ai-loading-content">
					<div class="preview-ai-loading-spinner"></div>
					<span><?php esc_html_e( 'Creating your preview...', 'preview-ai' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Action Buttons -->
		<div id="preview-ai-actions" class="preview-ai-actions">
			<button type="button" id="preview-ai-change" class="preview-ai-change-link">
				<?php esc_html_e( 'Change photo', 'preview-ai' ); ?>
			</button>
			<button type="button" id="preview-ai-generate" class="preview-ai-generate">
				<?php esc_html_e( 'Generate preview', 'preview-ai' ); ?>
			</button>
			<div id="preview-ai-result-actions" class="preview-ai-result-actions">
				<button type="button" id="preview-ai-download" class="preview-ai-action-btn">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
						<polyline points="7 10 12 15 17 10"/>
						<line x1="12" y1="15" x2="12" y2="3"/>
					</svg>
					<?php esc_html_e( 'Download', 'preview-ai' ); ?>
				</button>
				<button type="button" id="preview-ai-new-photo" class="preview-ai-action-btn preview-ai-action-btn--secondary">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
						<circle cx="12" cy="13" r="4"/>
					</svg>
					<?php esc_html_e( 'New photo', 'preview-ai' ); ?>
				</button>
			</div>

			<!-- AI Disclaimer -->
			<div id="preview-ai-disclaimer" class="preview-ai-disclaimer">
				<span class="preview-ai-disclaimer-icon">✨</span>
				<p><?php esc_html_e( 'This is an AI-generated preview for reference only. Actual fit and appearance may vary — please check the size guide before ordering.', 'preview-ai' ); ?></p>
			</div>
		</div>
	</div>
</div>

<!-- Lightbox -->
<div id="preview-ai-lightbox" class="preview-ai-lightbox">
	<img id="preview-ai-lightbox-img" src="" alt="" />
</div>
