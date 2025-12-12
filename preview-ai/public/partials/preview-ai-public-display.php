<?php
/**
 * Public widget template.
 *
 * @link       http://preview-ai.com
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/public/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get product_id and branding from calling context.
if ( ! isset( $product_id ) ) {
	global $product;
	$product_id = $product ? $product->get_id() : 0;
}

if ( ! isset( $branding ) ) {
	$branding = PREVIEW_AI_Admin::get_branding_settings();
	if ( empty( $branding['button_text'] ) ) {
		$branding['button_text'] = __( 'Generate', 'preview-ai' );
	}
	if ( empty( $branding['upload_text'] ) ) {
		$branding['upload_text'] = __( 'Upload your photo', 'preview-ai' );
	}
}
?>

<div class="preview-ai-widget preview-ai-widget-<?php echo esc_attr( $product_id ); ?>">
	<form id="preview-ai-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="preview_ai_upload" />
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'preview_ai_ajax' ) ); ?>" />

		<label for="preview_ai_image" class="preview-ai-upload">
			<span id="preview-ai-upload-icon">📷</span>
			<span id="preview-ai-upload-text"><?php echo esc_html( $branding['upload_text'] ); ?></span>
			<input type="file" id="preview_ai_image" name="image" accept="image/*" required />
		</label>

		<button type="submit" id="preview-ai-submit">
			<?php echo esc_html( $branding['button_text'] ); ?>
		</button>

		<div id="preview-ai-status"></div>
	</form>

	<!-- Result thumbnail - clickable -->
	<div id="preview-ai-result" class="preview-ai-result">
		<img id="preview-ai-thumb" src="" alt="<?php esc_attr_e( 'Preview', 'preview-ai' ); ?>" />
		<span class="preview-ai-hint"><?php esc_html_e( 'Tap to enlarge', 'preview-ai' ); ?></span>
	</div>
</div>

<!-- Lightbox modal for full image -->
<div id="preview-ai-lightbox" class="preview-ai-lightbox">
	<img id="preview-ai-full" src="" alt="" />
</div>
