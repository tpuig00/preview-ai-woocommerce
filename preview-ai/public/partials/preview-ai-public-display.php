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
if ( ! isset( $button_shape ) ) {
	$button_shape = 'pill';
}
if ( ! isset( $button_height ) ) {
	$button_height = 38;
}

$position_class = 'preview-ai-position-' . esc_attr( $button_position );
$shape_class    = 'preview-ai-shape-' . esc_attr( $button_shape );
$height_style   = ( 38 !== (int) $button_height ) ? 'height:' . absint( $button_height ) . 'px;' : '';
?>

<!-- Action Chip Container -->
<div class="preview-ai-chip-wrapper <?php echo esc_attr( $position_class ); ?>">
	<button type="button" id="preview-ai-trigger" class="preview-ai-chip <?php echo esc_attr( $shape_class ); ?>" <?php echo $height_style ? 'style="' . esc_attr( $height_style ) . '"' : ''; ?>>
		<span class="preview-ai-chip-icon"><?php echo $button_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<span class="preview-ai-chip-text"><?php echo esc_html( $button_text ); ?></span>
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
				<p><?php esc_html_e( 'Your photo is stored only on this device. We never upload or save it on our servers.', 'preview-ai' ); ?></p>
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
				<h3><?php esc_html_e( 'See it on you', 'preview-ai' ); ?></h3>
				<p><?php esc_html_e( 'See how this product looks on you', 'preview-ai' ); ?></p>
			</div>

			<?php
			// `$clothing_subtype` is set by `PREVIEW_AI_Public::render_widget_output()`.
			$clothing_subtype = isset( $clothing_subtype ) ? sanitize_key( $clothing_subtype ) : 'mixed';

			// For now, we only show examples for: full_body, upper_body, lower_body (legs).
			$ui_subtype = $clothing_subtype;
			if ( 'lower_body' === $ui_subtype ) {
				$ui_subtype = 'legs';
			}
			if ( ! in_array( $ui_subtype, array( 'full_body', 'upper_body', 'legs' ), true ) ) {
				$ui_subtype = 'full_body';
			}

			$subtype_class = 'pai-subtype-' . sanitize_html_class( $ui_subtype );
			$label         = __( 'Upload a Full Body Image', 'preview-ai' );

			$img_base = plugin_dir_url( __FILE__ ) . '../images/';
			$img_good = $img_base . 'example_good.png';
			$img_bad  = $img_base . 'example_bad.png';
			?>

			<div class="preview-ai-illustration">
				<div class="pai-examples <?php echo esc_attr( $subtype_class ); ?>" aria-hidden="true">
					<div class="pai-examples__stage">
						<figure class="pai-example pai-example--good">
							<img src="<?php echo esc_url( $img_good ); ?>" alt="<?php esc_attr_e( 'Good example photo', 'preview-ai' ); ?>" loading="lazy" decoding="async" />
						</figure>
						<figure class="pai-example pai-example--bad">
							<img src="<?php echo esc_url( $img_bad ); ?>" alt="<?php esc_attr_e( 'Bad example photo', 'preview-ai' ); ?>" loading="lazy" decoding="async" />
						</figure>
					</div>
					<div class="pai-examples__label"><?php echo esc_html( $label ); ?></div>
				</div>
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

			<!-- Upload button -->
			<label for="preview_ai_upload" class="preview-ai-upload-btn">
				<span class="preview-ai-upload-icon">📷</span>
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
				<div class="preview-ai-loading-shimmer"></div>
				<div class="preview-ai-loading-content">
					<div class="preview-ai-loading-spinner"></div>
				<div class="preview-ai-loading-steps">
					<span class="preview-ai-step is-active"><?php esc_html_e( 'Analyzing your photo...', 'preview-ai' ); ?></span>
					<span class="preview-ai-step"><?php esc_html_e( 'Preparing garment...', 'preview-ai' ); ?></span>
					<span class="preview-ai-step"><?php esc_html_e( 'Fitting to your body...', 'preview-ai' ); ?></span>
					<span class="preview-ai-step"><?php esc_html_e( 'Applying garment...', 'preview-ai' ); ?></span>
					<span class="preview-ai-step"><?php esc_html_e( 'Adding final touches...', 'preview-ai' ); ?></span>
				</div>
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
			<div id="preview-ai-check-status" class="preview-ai-check-status" role="status" aria-live="polite"></div>
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
			<div id="preview-ai-disclaimer" class="preview-ai-disclaimer">
				<span class="preview-ai-disclaimer-icon">✨</span>
				<p><?php echo wp_kses( __( 'This is an AI-generated preview for reference only. <strong>Actual fit and appearance may vary</strong> — please check the size guide before ordering.', 'preview-ai' ), array( 'strong' => array() ) ); ?></p>
			</div>
		</div>
	</div>
</div>

<!-- Lightbox -->
<div id="preview-ai-lightbox" class="preview-ai-lightbox">
	<img id="preview-ai-lightbox-img" src="" alt="" />
</div>

<?php
// Load demo tour only when ?demo=yes parameter is present.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for demo mode.
if ( isset( $_GET['demo'] ) && 'yes' === $_GET['demo'] ) {
	include __DIR__ . '/preview-ai-demo-tour.php';
}
?>
