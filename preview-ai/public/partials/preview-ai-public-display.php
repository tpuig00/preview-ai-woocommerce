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

global $product;
$product_id = $product ? $product->get_id() : 0;
?>

<div class="preview-ai-widget" id="preview-ai-widget">
	<form id="preview-ai-form" enctype="multipart/form-data">
		<input type="hidden" name="action" value="preview_ai_upload" />
		<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'preview_ai_ajax' ) ); ?>" />

		<label for="preview_ai_image">
			<?php esc_html_e( 'Upload your photo to preview', 'preview-ai' ); ?>
		</label>
		<input type="file" id="preview_ai_image" name="image" accept="image/*" required />

		<button type="submit">
			<?php esc_html_e( 'Generate Preview', 'preview-ai' ); ?>
		</button>

		<div id="preview-ai-status" aria-live="polite"></div>
	</form>
</div>
