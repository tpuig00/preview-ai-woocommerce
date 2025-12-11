<?php
/**
 * Admin settings page view.
 *
 * @link       http://preview-ai.com
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Preview AI Settings', 'preview-ai' ); ?></h1>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'preview_ai_settings' );
		?>

		<table class="form-table" role="presentation">
			<tbody>
				<!-- Enabled -->
				<tr>
					<th scope="row">
						<label for="preview_ai_enabled">
							<?php esc_html_e( 'Enable Preview AI', 'preview-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="checkbox" 
							   id="preview_ai_enabled" 
							   name="preview_ai_enabled" 
							   value="1" 
							   <?php checked( 1, get_option( 'preview_ai_enabled', 0 ) ); ?> 
						/>
						<p class="description">
							<?php esc_html_e( 'Show the Preview AI widget on product pages.', 'preview-ai' ); ?>
						</p>
					</td>
				</tr>

				<!-- Default Product Type -->
				<tr>
					<th scope="row">
						<label for="preview_ai_product_type">
							<?php esc_html_e( 'Default Product Type', 'preview-ai' ); ?>
						</label>
					</th>
					<td>
						<?php $current_type = get_option( 'preview_ai_product_type', 'generic' ); ?>
						<select id="preview_ai_product_type" name="preview_ai_product_type">
							<?php foreach ( PREVIEW_AI_Admin::get_product_types() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_type, $value ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Select your store\'s main product type. This helps the AI generate better previews. Can be overridden per product.', 'preview-ai' ); ?>
						</p>
					</td>
				</tr>

				<!-- Clothing Subtype -->
				<tr id="preview_ai_clothing_subtype_row" style="<?php echo 'clothing' === $current_type ? '' : 'display:none;'; ?>">
					<th scope="row">
						<label for="preview_ai_clothing_subtype">
							<?php esc_html_e( 'Clothing Subtype', 'preview-ai' ); ?>
						</label>
					</th>
					<td>
						<?php
						$current_subtype   = get_option( 'preview_ai_clothing_subtype', 'mixed' );
						$clothing_subtypes = PREVIEW_AI_Admin::get_clothing_subtypes();
						?>
						<select id="preview_ai_clothing_subtype" name="preview_ai_clothing_subtype">
							<?php foreach ( $clothing_subtypes as $value => $data ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_subtype, $value ); ?>>
									<?php echo esc_html( $data['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description" id="preview_ai_subtype_examples">
							<?php
							if ( isset( $clothing_subtypes[ $current_subtype ] ) ) {
								/* translators: %s: Example items for the selected clothing subtype */
								printf( esc_html__( 'Examples: %s', 'preview-ai' ), esc_html( $clothing_subtypes[ $current_subtype ]['examples'] ) );
							}
							?>
						</p>
					</td>
				</tr>

				<!-- API Key -->
				<tr>
					<th scope="row">
						<label for="preview_ai_api_key">
							<?php esc_html_e( 'API Key', 'preview-ai' ); ?>
						</label>
					</th>
					<td>
						<input type="text"
							   id="preview_ai_api_key" 
							   name="preview_ai_api_key" 
							   value="<?php echo esc_attr( get_option( 'preview_ai_api_key', '' ) ); ?>" 
							   class="regular-text" 
						/>
						<p class="description">
							<?php esc_html_e( 'Your API key for authentication.', 'preview-ai' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>

	<!-- Learn My Catalog Section -->
	<div class="preview-ai-learn-catalog" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
		<h2 style="margin-top: 0; margin-bottom: 8px; font-size: 18px; color: #1d2327;">
			🧠 <?php esc_html_e( 'Learn My Catalog (AI)', 'preview-ai' ); ?>
		</h2>
		<p style="margin-bottom: 16px; color: #50575e; font-size: 14px; line-height: 1.5;">
			<?php esc_html_e( 'Preview AI will automatically detect what type of product each one is (t-shirts, dresses, belts, earrings, fanny packs…).', 'preview-ai' ); ?>
			<br><strong>
			<?php esc_html_e( 'This helps generate much more precise previews without manual configuration.', 'preview-ai' ); ?></strong>
		</p>
		<p style="margin-bottom: 16px; color: #787c82; font-size: 13px; font-style: italic;">
			<?php esc_html_e( 'Nothing will be modified in your store. Only recommendations will be assigned.', 'preview-ai' ); ?>
		</p>
		
		<button type="button" id="preview_ai_learn_catalog_btn" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px;">
			<span class="dashicons dashicons-welcome-learn-more" style="font-size: 18px; width: 18px; height: 18px;"></span>
			<?php esc_html_e( 'Analyze My Catalog', 'preview-ai' ); ?>
		</button>

		<div id="preview_ai_learn_catalog_loading" style="display: none; margin-top: 16px;">
			<span class="spinner" style="float: none; visibility: visible; margin: 0 8px 0 0;"></span>
			<span style="color: #50575e;"><?php esc_html_e( 'Analyzing your catalog...', 'preview-ai' ); ?></span>
		</div>

		<div id="preview_ai_learn_catalog_result" style="display: none; margin-top: 16px; padding: 12px 16px; border-radius: 4px;"></div>
	</div>
</div>
