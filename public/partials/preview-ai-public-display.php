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
if ( ! isset( $preview_ai_product_id ) ) {
	global $product;
	$preview_ai_product_id = $product ? $product->get_id() : 0;
}

// Set defaults if not provided by render_widget_output.
if ( ! isset( $preview_ai_button_text ) ) {
	$preview_ai_button_text = __( 'See it on you', 'preview-ai' );
}
if ( ! isset( $preview_ai_button_svg ) ) {
	$preview_ai_button_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8L19 13"/><path d="M15 9h.01"/><path d="M17.8 6.2L19 5"/><path d="M3 21l9-9"/><path d="M12.2 6.2L11 5"/></svg>';
}
if ( ! isset( $preview_ai_button_position ) ) {
	$preview_ai_button_position = 'center';
}
if ( ! isset( $preview_ai_button_shape ) ) {
	$preview_ai_button_shape = 'pill';
}
if ( ! isset( $preview_ai_button_height ) ) {
	$preview_ai_button_height = 38;
}
if ( ! isset( $preview_ai_button_full_width ) ) {
	$preview_ai_button_full_width = 0;
}
if ( ! isset( $preview_ai_show_credit ) ) {
	$preview_ai_show_credit = 0;
}
if ( ! isset( $preview_ai_product_image ) ) {
	$preview_ai_product_image = PREVIEW_AI_Public::get_intro_product_image( $preview_ai_product_id );
}

$preview_ai_position_class = 'preview-ai-position-' . esc_attr( $preview_ai_button_position );
$preview_ai_shape_class    = 'preview-ai-shape-' . esc_attr( $preview_ai_button_shape );
$preview_ai_height_style   = ( 38 !== (int) $preview_ai_button_height ) ? 'height:' . absint( $preview_ai_button_height ) . 'px !important;' : '';
$preview_ai_width_style    = ( ! empty( $preview_ai_button_full_width ) ) ? 'width: 100% !important; justify-content: center !important;' : '';
$preview_ai_inline_style   = $preview_ai_height_style . $preview_ai_width_style;

// Script data fallback (read by JS when the localized previewAiData inline script is missing).
if ( ! isset( $preview_ai_config ) && $preview_ai_product_id ) {
	$preview_ai_config = PREVIEW_AI_Public::get_script_data( $preview_ai_product_id );
}
$preview_ai_config_attr = ! empty( $preview_ai_config ) ? wp_json_encode( $preview_ai_config ) : '';
?>

<!-- Action Chip Container -->
<div class="preview-ai-chip-wrapper <?php echo esc_attr( $preview_ai_position_class ); ?>"<?php if ( $preview_ai_config_attr ) : ?> data-preview-ai-config="<?php echo esc_attr( $preview_ai_config_attr ); ?>"<?php endif; ?>>
	<button type="button" id="preview-ai-trigger" class="preview-ai-chip <?php echo esc_attr( $preview_ai_shape_class ); ?>" <?php echo $preview_ai_inline_style ? 'style="' . esc_attr( $preview_ai_inline_style ) . '"' : ''; ?>>
		<span class="preview-ai-chip-icon"><?php
			echo wp_kses(
				$preview_ai_button_svg,
				array(
					'svg'      => array( 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'xmlns' => true, 'class' => true ),
					'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true ),
					'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ),
					'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true ),
					'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true ),
				)
			);
			?></span>
		<span class="preview-ai-chip-text"><?php echo esc_html( $preview_ai_button_text ); ?></span>
	</button>
</div>

<!-- Modal -->
<div id="preview-ai-modal" class="preview-ai-modal">
	<div class="preview-ai-modal-content">
		<button type="button" id="preview-ai-close" class="preview-ai-close" aria-label="<?php esc_attr_e( 'Close', 'preview-ai' ); ?>">×</button>

		<!-- Saved Photo Section -->
		<div id="preview-ai-saved-photo" class="preview-ai-saved-photo">
			<div class="preview-ai-saved-photo-header">
				<h3><?php esc_html_e( 'Welcome back!', 'preview-ai' ); ?></h3>
				<p><?php esc_html_e( 'Use your saved photo to try on this product instantly', 'preview-ai' ); ?></p>
			</div>
			<div class="preview-ai-saved-photo-preview">
				<img id="preview-ai-saved-thumb" src="" alt="<?php esc_attr_e( 'Your saved photo', 'preview-ai' ); ?>" />
			</div>
			<button type="button" id="preview-ai-use-saved" class="preview-ai-use-saved-btn">
				<?php esc_html_e( 'Use this photo', 'preview-ai' ); ?>
			</button>
			<a href="#" id="preview-ai-new-photo-link" class="preview-ai-new-photo-link">
				<?php esc_html_e( 'or upload a different photo', 'preview-ai' ); ?>
			</a>
			<button type="button" id="preview-ai-view-tryons" class="preview-ai-view-tryons-btn">
				<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
					<path d="M13 10.551v-.678A4.005 4.005 0 0 0 16 6c0-2.206-1.794-4-4-4S8 3.794 8 6h2c0-1.103.897-2 2-2s2 .897 2 2-.897 2-2 2a1 1 0 0 0-1 1v1.551l-8.665 7.702A1.001 1.001 0 0 0 3 20h18a1.001 1.001 0 0 0 .664-1.748L13 10.551zM5.63 18 12 12.338 18.37 18H5.63z"/>
				</svg>
				<?php esc_html_e( 'Your Looks', 'preview-ai' ); ?>
				<span id="preview-ai-tryons-badge" class="preview-ai-tryons-badge"></span>
			</button>
			<div class="preview-ai-saved-photo-privacy">
				<div class="preview-ai-privacy-info">
					<span class="preview-ai-privacy-icon">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
					</span>
					<p><?php esc_html_e( 'Your photos stay on your device. We never upload or store them.', 'preview-ai' ); ?></p>
				</div>
				<a href="#" id="preview-ai-forget-photo" class="preview-ai-forget-link"><?php esc_html_e( 'Delete my photo', 'preview-ai' ); ?></a>
			</div>
		</div>

		<!-- Your Looks Section -->
		<div id="preview-ai-tryons" class="preview-ai-tryons">
			<div class="preview-ai-tryons-header">
				<button type="button" id="preview-ai-tryons-back" class="preview-ai-tryons-back" aria-label="<?php esc_attr_e( 'Back', 'preview-ai' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<polyline points="15 18 9 12 15 6"/>
					</svg>
				</button>
				<div class="preview-ai-tryons-title">
					<h3><?php esc_html_e( 'Your Looks', 'preview-ai' ); ?></h3>
					<span id="preview-ai-tryons-count" class="preview-ai-tryons-count"></span>
				</div>
			</div>
			<div id="preview-ai-tryons-list" class="preview-ai-tryons-list">
				<!-- Cards will be rendered by JS -->
			</div>
			<div id="preview-ai-tryons-empty" class="preview-ai-tryons-empty">
				<span class="preview-ai-tryons-empty-icon">👕</span>
				<p><?php esc_html_e( 'No looks yet. Generate your first preview!', 'preview-ai' ); ?></p>
			</div>
		</div>

		<!-- Instructions -->
		<div id="preview-ai-instructions" class="preview-ai-instructions">
			<div class="preview-ai-instructions-header">
				<h3><?php esc_html_e( 'Try it on before you buy', 'preview-ai' ); ?></h3>
				<p><?php esc_html_e( 'Upload a photo and see how it looks on you in seconds.', 'preview-ai' ); ?></p>
			</div>

			<!-- Your photo + this item -->
			<div class="pai-combo" aria-hidden="true">
				<div class="pai-combo__item">
					<div class="pai-combo__card pai-combo__card--you">
						<span class="pai-combo__silhouette"></span>
					</div>
					<span class="pai-combo__caption"><?php esc_html_e( 'Your photo', 'preview-ai' ); ?></span>
				</div>
				<span class="pai-combo__plus">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
				</span>
				<div class="pai-combo__item">
					<div class="pai-combo__card pai-combo__card--product">
						<img id="preview-ai-intro-product" src="<?php echo esc_url( $preview_ai_product_image ); ?>" alt="" loading="lazy" decoding="async" />
					</div>
					<span class="pai-combo__caption"><?php esc_html_e( 'This item', 'preview-ai' ); ?></span>
				</div>
			</div>
			<p class="pai-combo__hint"><?php esc_html_e( 'Works best with a full-body photo', 'preview-ai' ); ?></p>

			<!-- Upload button -->
			<label for="preview_ai_upload" class="preview-ai-upload-btn">
				<svg class="preview-ai-upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
					<circle cx="12" cy="13" r="4"/>
				</svg>
				<span class="preview-ai-upload-text"><?php esc_html_e( 'Add your photo', 'preview-ai' ); ?></span>
			</label>

			<!-- Hidden file input -->
			<input type="file" id="preview_ai_upload" accept="image/*" class="preview-ai-file-input" />

			<!-- Your Looks button (visible only if has looks) -->
			<button type="button" id="preview-ai-instructions-looks" class="preview-ai-instructions-looks-btn">
				<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
					<path d="M13 10.551v-.678A4.005 4.005 0 0 0 16 6c0-2.206-1.794-4-4-4S8 3.794 8 6h2c0-1.103.897-2 2-2s2 .897 2 2-.897 2-2 2a1 1 0 0 0-1 1v1.551l-8.665 7.702A1.001 1.001 0 0 0 3 20h18a1.001 1.001 0 0 0 .664-1.748L13 10.551zM5.63 18 12 12.338 18.37 18H5.63z"/>
				</svg>
				<?php esc_html_e( 'Your Looks', 'preview-ai' ); ?>
				<span id="preview-ai-instructions-looks-badge" class="preview-ai-tryons-badge"></span>
			</button>
		</div>

		<!-- Image Stage -->
		<div id="preview-ai-stage" class="preview-ai-stage">
			<img id="preview-ai-img-before" class="preview-ai-img-before" src="" alt="" />
			<img id="preview-ai-img-after" class="preview-ai-img-after" src="" alt="<?php esc_attr_e( 'Generated preview', 'preview-ai' ); ?>" />

			<div id="preview-ai-loading" class="preview-ai-loading">
				<div class="preview-ai-loading-content">
					<!-- Your photo + this item, merging -->
					<div class="pai-gen" aria-hidden="true">
						<div class="pai-gen__card pai-gen__card--you"><img id="preview-ai-gen-photo" src="" alt="" /></div>
						<div class="pai-gen__card pai-gen__card--product"><img id="preview-ai-gen-product" src="" alt="" /></div>
						<span class="pai-gen__spinner">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M21 12a9 9 0 0 1-15.5 6.2"/><path d="M3 12A9 9 0 0 1 18.5 5.8"/>
								<polyline points="18.5 1.8 18.5 5.8 14.5 5.8"/><polyline points="5.5 22.2 5.5 18.2 9.5 18.2"/>
							</svg>
						</span>
					</div>
					<div class="preview-ai-loading-steps" aria-live="polite">
						<span class="preview-ai-step pai-is-active"><?php esc_html_e( 'Analyzing your photo...', 'preview-ai' ); ?></span>
						<span class="preview-ai-step"><?php esc_html_e( 'Preparing garment...', 'preview-ai' ); ?></span>
						<span class="preview-ai-step"><?php esc_html_e( 'Fitting to your body...', 'preview-ai' ); ?></span>
						<span class="preview-ai-step"><?php esc_html_e( 'Applying garment...', 'preview-ai' ); ?></span>
						<span class="preview-ai-step"><?php esc_html_e( 'Adding final touches...', 'preview-ai' ); ?></span>
					</div>
					<div class="pai-gen__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="<?php esc_attr_e( 'Generating preview', 'preview-ai' ); ?>">
						<span class="pai-gen__bar" id="preview-ai-gen-bar"></span>
					</div>
					<span class="pai-gen__percent" id="preview-ai-gen-percent">0%</span>
					<p class="pai-gen__note"><?php esc_html_e( 'Keep this window open while we create your look.', 'preview-ai' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Action Buttons -->
		<div id="preview-ai-actions" class="preview-ai-actions">
			<div id="preview-ai-check-status" class="preview-ai-check-status" role="status" aria-live="polite"></div>
			<button type="button" id="preview-ai-change" class="preview-ai-change-link">
				<?php esc_html_e( 'Change photo', 'preview-ai' ); ?>
			</button>
			<button type="button" id="preview-ai-generate" class="preview-ai-generate">
				<?php esc_html_e( 'Generate preview', 'preview-ai' ); ?>
			</button>
			<!-- Legal notice (shown with the Generate button) -->
			<p class="preview-ai-legal">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Format string and link parts are escaped individually.
				printf(
					/* translators: %s: link to the try-on privacy terms. */
					esc_html__( 'By using this service, you agree to the %s. AI can make mistakes.', 'preview-ai' ),
					'<a href="' . esc_url( 'https://www.previewai.app/terms/privacy-policy' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'try-on privacy terms', 'preview-ai' ) . '</a>'
				);
				?>
			</p>
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
				<button type="button" id="preview-ai-show-tryons" class="preview-ai-action-btn preview-ai-action-btn--tertiary">
					<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
						<path d="M13 10.551v-.678A4.005 4.005 0 0 0 16 6c0-2.206-1.794-4-4-4S8 3.794 8 6h2c0-1.103.897-2 2-2s2 .897 2 2-.897 2-2 2a1 1 0 0 0-1 1v1.551l-8.665 7.702A1.001 1.001 0 0 0 3 20h18a1.001 1.001 0 0 0 .664-1.748L13 10.551zM5.63 18 12 12.338 18.37 18H5.63z"/>
					</svg>
					<?php esc_html_e( 'Your Looks', 'preview-ai' ); ?>
				</button>
			</div>

			<!-- AI Disclaimer -->
			<p id="preview-ai-disclaimer" class="preview-ai-disclaimer">
				<?php esc_html_e( 'AI-generated preview for reference only. Actual fit may vary.', 'preview-ai' ); ?>
			</p>

			<?php if ( ! empty( $preview_ai_show_credit ) ) : ?>
				<!-- Opt-in credit (Settings > Widget > Show "Powered by"). Off by default. -->
				<p class="preview-ai-credit">
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Format string and link parts are escaped individually.
					printf(
						/* translators: %s: Preview AI brand name, linked. */
						esc_html__( 'Powered by %s', 'preview-ai' ),
						'<a href="' . esc_url( 'https://previewai.app/' ) . '" target="_blank" rel="noopener">Preview AI</a>'
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<!-- Lightbox -->
<div id="preview-ai-lightbox" class="preview-ai-lightbox">
	<img id="preview-ai-lightbox-img" src="" alt="" />
</div>

<?php
// Load demo tour only when ?demo=yes parameter is present.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; only loads demo tour JS if ?demo=yes is present, no data modification or user-specific content.
if ( isset( $_GET['demo'] ) && 'yes' === sanitize_key( wp_unslash( $_GET['demo'] ) ) ) {
	include __DIR__ . '/preview-ai-demo-tour.php';
}
?>
