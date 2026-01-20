<?php
/**
 * Admin settings page view.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Preview AI Settings', 'preview-ai' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=general' ) ); ?>" 
		   class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'preview-ai' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=widget' ) ); ?>" 
		   class="nav-tab <?php echo 'widget' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Widget', 'preview-ai' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats' ) ); ?>" 
		   class="nav-tab <?php echo 'stats' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Statistics', 'preview-ai' ); ?>
		</a>

		<a href="https://www.previewai.app/contact" target="_blank" class="nav-tab" style="margin-left: auto; border: none; color: #646970; font-weight: 400; font-size: 12px; opacity: 0.8;">
			<span class="dashicons dashicons-sos" style="margin-right: 4px; font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
			<?php esc_html_e( 'Contact Support', 'preview-ai' ); ?>
		</a>
	</nav>

	<?php if ( 'stats' === $active_tab ) : ?>
		<!-- Statistics Tab -->
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$period = isset( $_GET['period'] ) ? sanitize_key( $_GET['period'] ) : '30days';
		$stats  = PREVIEW_AI_Tracking::get_detailed_stats( $period );
		?>
		
		<div class="preview-ai-stats-header" style="margin: 20px 0; display: flex; align-items: center; gap: 16px;">
			<label for="preview_ai_period" style="font-weight: 500;"><?php esc_html_e( 'Period:', 'preview-ai' ); ?></label>
			<select id="preview_ai_period" onchange="window.location.href=this.value;">
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=today' ) ); ?>" <?php selected( $period, 'today' ); ?>>
					<?php esc_html_e( 'Today', 'preview-ai' ); ?>
				</option>
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=7days' ) ); ?>" <?php selected( $period, '7days' ); ?>>
					<?php esc_html_e( 'Last 7 days', 'preview-ai' ); ?>
				</option>
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=30days' ) ); ?>" <?php selected( $period, '30days' ); ?>>
					<?php esc_html_e( 'Last 30 days', 'preview-ai' ); ?>
				</option>
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=all' ) ); ?>" <?php selected( $period, 'all' ); ?>>
					<?php esc_html_e( 'All time', 'preview-ai' ); ?>
				</option>
			</select>
		</div>

		<!-- Primary Stats Cards -->
		<div class="preview-ai-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px;">
			<div class="preview-ai-stat-card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
				<div style="font-size: 32px; font-weight: 600; color: #2271b1;"><?php echo esc_html( number_format_i18n( $stats['users_tried'] ) ); ?></div>
				<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'Customers Used Preview AI', 'preview-ai' ); ?></div>
			</div>
			<div class="preview-ai-stat-card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
				<div style="font-size: 32px; font-weight: 600; color: #00a32a;"><?php echo esc_html( number_format_i18n( $stats['orders_influenced'] ) ); ?></div>
				<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'Orders Influenced', 'preview-ai' ); ?></div>
			</div>
			<div class="preview-ai-stat-card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
				<div style="font-size: 32px; font-weight: 600; color: #dba617;"><?php echo esc_html( $stats['user_conversion_rate'] ); ?>%</div>
				<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'User Conversion Rate', 'preview-ai' ); ?></div>
			</div>
			<div class="preview-ai-stat-card" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
				<div style="font-size: 32px; font-weight: 600; color: #135e96;"><?php echo wp_kses_post( wc_price( $stats['influenced_revenue'] ) ); ?></div>
				<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'Revenue Influenced', 'preview-ai' ); ?></div>
			</div>
		</div>

		<!-- Secondary Stats -->
		<?php if ( $stats['avg_order_value'] > 0 || $stats['orders_refunded'] > 0 ) : ?>
		<div class="preview-ai-stats-secondary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px;">
			<?php if ( $stats['avg_order_value'] > 0 ) : ?>
			<div style="background: #f6f7f7; padding: 16px; border-radius: 4px; text-align: center;">
				<div style="font-size: 24px; font-weight: 500;"><?php echo wp_kses_post( wc_price( $stats['avg_order_value'] ) ); ?></div>
				<div style="color: #787c82; font-size: 13px;"><?php esc_html_e( 'Avg. Order Value', 'preview-ai' ); ?></div>
			</div>
			<?php endif; ?>
			<?php if ( $stats['orders_refunded'] > 0 ) : ?>
			<div style="background: #f6f7f7; padding: 16px; border-radius: 4px; text-align: center;">
				<div style="font-size: 24px; font-weight: 500; color: #d63638;"><?php echo esc_html( number_format_i18n( $stats['orders_refunded'] ) ); ?></div>
				<div style="color: #787c82; font-size: 13px;"><?php esc_html_e( 'Orders Refunded', 'preview-ai' ); ?></div>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
			<!-- Top Products -->
			<div class="preview-ai-top-products" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
				<h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Top Converting Products', 'preview-ai' ); ?></h3>
				<?php
				$top_products = PREVIEW_AI_Tracking::get_top_products( 5 );
				if ( empty( $top_products ) ) :
					?>
					<p style="color: #787c82; font-style: italic;"><?php esc_html_e( 'No conversions yet.', 'preview-ai' ); ?></p>
				<?php else : ?>
					<table class="widefat" style="border: none;">
						<thead>
							<tr>
								<th style="padding: 8px 0;"><?php esc_html_e( 'Product', 'preview-ai' ); ?></th>
								<th style="padding: 8px 0; text-align: center;"><?php esc_html_e( 'Previews', 'preview-ai' ); ?></th>
								<th style="padding: 8px 0; text-align: center;"><?php esc_html_e( 'Conv.', 'preview-ai' ); ?></th>
								<th style="padding: 8px 0; text-align: center;"><?php esc_html_e( 'Rate', 'preview-ai' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_products as $product ) : ?>
								<tr>
									<td style="padding: 8px 0;">
										<a href="<?php echo esc_url( get_edit_post_link( $product['product_id'] ) ); ?>">
											<?php echo esc_html( $product['product_name'] ); ?>
										</a>
									</td>
									<td style="padding: 8px 0; text-align: center;"><?php echo esc_html( $product['previews'] ); ?></td>
									<td style="padding: 8px 0; text-align: center;"><?php echo esc_html( $product['conversions'] ); ?></td>
									<td style="padding: 8px 0; text-align: center;"><?php echo esc_html( $product['conversion_rate'] ); ?>%</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Recent Conversions -->
			<div class="preview-ai-recent-conversions" style="background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
				<h3 style="margin: 0 0 16px; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Recent Conversions', 'preview-ai' ); ?></h3>
				<?php
				$recent = PREVIEW_AI_Tracking::get_recent_conversions( 5 );
				if ( empty( $recent ) ) :
					?>
					<p style="color: #787c82; font-style: italic;"><?php esc_html_e( 'No conversions yet.', 'preview-ai' ); ?></p>
				<?php else : ?>
					<table class="widefat" style="border: none;">
						<thead>
							<tr>
								<th style="padding: 8px 0;"><?php esc_html_e( 'Customer', 'preview-ai' ); ?></th>
								<th style="padding: 8px 0;"><?php esc_html_e( 'Product', 'preview-ai' ); ?></th>
								<th style="padding: 8px 0; text-align: right;"><?php esc_html_e( 'Amount', 'preview-ai' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent as $conv ) : ?>
								<tr>
									<td style="padding: 8px 0;">
										<?php echo esc_html( $conv['customer_name'] ); ?>
									</td>
									<td style="padding: 8px 0;">
										<?php echo esc_html( $conv['product_name'] ); ?>
									</td>
									<td style="padding: 8px 0; text-align: right;">
										<?php echo wp_kses_post( wc_price( $conv['order_total'] ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

	<?php elseif ( 'general' === $active_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'preview_ai_general_settings' ); ?>
			<!-- General Tab -->
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
							<label class="preview-ai-toggle">
								<input type="hidden" name="preview_ai_enabled" value="0" />
								<input type="checkbox" 
									   id="preview_ai_enabled" 
									   name="preview_ai_enabled" 
									   value="1" 
									   <?php checked( 1, get_option( 'preview_ai_enabled', 0 ) ); ?> 
								/>
								<span class="preview-ai-toggle__slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Show the Preview AI widget on product pages.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>

					<!-- API Key -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'API Configuration', 'preview-ai' ); ?>
						</th>
						<td>
							<?php
							$status = PREVIEW_AI_Api::get_account_status();
							$tokens_limit = isset( $status['tokens_limit'] ) ? (int) $status['tokens_limit'] : 0;
							$tokens_used = isset( $status['tokens_used'] ) ? (int) $status['tokens_used'] : 0;
							$tokens_remaining = max( 0, $tokens_limit - $tokens_used );
							$usage_percentage = $tokens_limit > 0 ? min( 100, round( ( $tokens_used / $tokens_limit ) * 100 ) ) : 0;
							
							$renewal_date = isset( $status['current_period_end'] ) ? $status['current_period_end'] : null;
							?>

							<div class="preview-ai-account-card" style="background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 24px; max-width: 600px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 10px;">
								<div style="margin-bottom: 20px;">
									<div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
										<span style="font-weight: 600; color: #1d2327;"><?php esc_html_e( 'Monthly Usage', 'preview-ai' ); ?></span>
										<span style="color: #646970;">
											<strong id="pai-tokens-used"><?php echo esc_html( number_format_i18n( $tokens_used ) ); ?></strong> / <span id="pai-tokens-limit"><?php echo esc_html( number_format_i18n( $tokens_limit ) ); ?></span> <?php esc_html_e( 'previews', 'preview-ai' ); ?>
										</span>
									</div>
									<div style="background: #f0f0f1; height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 8px;">
										<div id="pai-usage-bar" style="background: #2271b1; width: <?php echo esc_attr( $usage_percentage ); ?>%; height: 100%; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);"></div>
									</div>
									<div style="display: flex; justify-content: space-between; font-size: 12px; color: #787c82;">
										<span><span id="pai-tokens-remaining-text"><?php printf( esc_html__( '%s remaining', 'preview-ai' ), '<strong>' . number_format_i18n( $tokens_remaining ) . '</strong>' ); ?></span></span>
										<span id="pai-renewal-date-container">
											<?php if ( $renewal_date ) : ?>
												<?php 
												printf( 
													esc_html__( 'Resets on %s', 'preview-ai' ), 
													'<strong id="pai-renewal-date">' . date_i18n( get_option( 'date_format' ), strtotime( $renewal_date ) ) . '</strong>' 
												); 
												?>
											<?php endif; ?>
										</span>
									</div>
								</div>

								<div id="pai-verification-status" style="background: #f6f7f7; padding: 16px; border-radius: 6px; border: 1px solid #dcdcde;">
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e2e4e7;">
										<div style="display: flex; align-items: center; gap: 8px; color: #646970; font-family: monospace; font-size: 12px;">
											<span class="dashicons dashicons-key" style="font-size: 16px; width: 16px; height: 16px; color: #8c8f94;"></span>
											<span>pvai_••••••••••••<?php echo esc_html( substr( get_option( 'preview_ai_api_key' ), -6 ) ); ?></span>
										</div>
										<div id="pai-status-indicator" style="color: #00a32a; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 4px;">
											<span class="dashicons dashicons-yes-alt" style="font-size: 18px; width: 18px; height: 18px;"></span>
											<?php esc_html_e( 'Verified', 'preview-ai' ); ?>
										</div>
									</div>
									
									<div style="display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; font-size: 12px; color: #646970;">
										<span style="font-weight: 600; color: #8c8f94;"><?php esc_html_e( 'Email:', 'preview-ai' ); ?></span>
										<span id="pai-status-email"><?php echo esc_html( $status['email'] ?? '—' ); ?></span>
										
										<span style="font-weight: 600; color: #8c8f94;"><?php esc_html_e( 'Domain:', 'preview-ai' ); ?></span>
										<span id="pai-status-domain"><?php echo esc_html( $status['domain'] ?? '—' ); ?></span>
									</div>
								</div>
							</div>
							
							<input type="hidden" id="preview_ai_api_key" name="preview_ai_api_key" value="<?php echo esc_attr( get_option( 'preview_ai_api_key', '' ) ); ?>" />
							
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>

			<!-- Learn My Catalog Section -->
			<?php
			$catalog_status = PREVIEW_AI_Admin::get_catalog_analysis_status();
			$is_processing  = 'processing' === $catalog_status['status'];
			$progress       = $catalog_status['progress'];

			// Get compatibility status.
			$preflight     = get_option( 'preview_ai_store_compatibility' );
			$is_compatible = ! empty( $preflight ) ? $preflight['compatible'] : true;
			?>
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
				
				<?php if ( ! $is_compatible ) : ?>
					<div id="preview_ai_compatibility_error" style="margin-bottom: 16px; padding: 12px 16px; border-radius: 4px; background-color: #fcf0f1; border: 1px solid #d63638; color: #d63638; font-size: 13px;">
						<span class="dashicons dashicons-warning" style="vertical-align: middle; margin-right: 8px; font-size: 18px; width: 18px; height: 18px;"></span>
						<?php echo esc_html( $preflight['message'] ); ?>
						<p style="margin: 8px 0 0;">
							<a href="#" id="preview_ai_reverify_compatibility" style="color: #d63638; text-decoration: underline;">
								<?php esc_html_e( 'Re-verify compatibility', 'preview-ai' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>

				<button type="button" id="preview_ai_learn_catalog_btn" class="button button-primary" style="display: inline-flex; align-items: center; gap: 8px;" <?php echo ( $is_processing || ! $is_compatible ) ? 'disabled' : ''; ?>>
					<span class="dashicons dashicons-welcome-learn-more" style="font-size: 18px; width: 18px; height: 18px;"></span>
					<?php esc_html_e( 'Analyze My Catalog', 'preview-ai' ); ?>
				</button>

				<div id="preview_ai_learn_catalog_loading" style="<?php echo $is_processing ? '' : 'display: none;'; ?> margin-top: 16px;">
					<span class="spinner" style="float: none; visibility: visible; margin: 0 8px 0 0;"></span>
					<span id="preview_ai_learn_catalog_progress" style="color: #50575e;">
						<?php
						if ( $is_processing && ! empty( $progress ) ) {
							printf(
								/* translators: 1: processed count, 2: total count */
								esc_html__( 'Processing... %1$d of %2$d products analyzed.', 'preview-ai' ),
								absint( $progress['processed'] ),
								absint( $progress['total'] )
							);
						} else {
							esc_html_e( 'Analyzing your catalog...', 'preview-ai' );
						}
						?>
					</span>
				</div>

				<div id="preview_ai_learn_catalog_result" style="display: none; margin-top: 16px; padding: 12px 16px; border-radius: 4px;"></div>
			</div>
			<script>
				// Pass initial status to JS.
				window.previewAiCatalogStatus = <?php echo wp_json_encode( $catalog_status['status'] ); ?>;
			</script>
		</form>

	<?php elseif ( 'widget' === $active_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'preview_ai_widget_settings' ); ?>
			<!-- Widget Tab -->
			<?php
			$widget_settings = PREVIEW_AI_Admin::get_widget_settings();
			$button_icons    = PREVIEW_AI_Admin::get_button_icons();
			?>
			<table class="form-table" role="presentation">
				<tbody>
					<!-- Display Mode -->
					<tr>
						<th scope="row">
							<label for="preview_ai_display_mode">
								<?php esc_html_e( 'Widget Display', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<select id="preview_ai_display_mode" name="preview_ai_display_mode">
								<option value="auto" <?php selected( get_option( 'preview_ai_display_mode', 'auto' ), 'auto' ); ?>>
									<?php esc_html_e( 'Automatic - Show on product pages', 'preview-ai' ); ?>
								</option>
								<option value="manual" <?php selected( get_option( 'preview_ai_display_mode' ), 'manual' ); ?>>
									<?php esc_html_e( 'Manual - Use shortcode or Elementor', 'preview-ai' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Use "Manual" if you want to place the widget with [preview_ai] shortcode or Elementor widget.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>

					<!-- Button Text -->
					<tr>
						<th scope="row">
							<label for="preview_ai_button_text">
								<?php esc_html_e( 'Button Text', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								   id="preview_ai_button_text" 
								   name="preview_ai_button_text" 
								   value="<?php echo esc_attr( $widget_settings['button_text'] ); ?>" 
								   class="regular-text" 
								   placeholder="<?php esc_attr_e( 'See it on you', 'preview-ai' ); ?>"
							/>
							<p class="description">
								<?php esc_html_e( 'Leave empty to use the default text.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>

					<!-- Button Icon -->
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Button Icon', 'preview-ai' ); ?>
						</th>
						<td>
							<div class="preview-ai-icon-selector">
								<?php foreach ( $button_icons as $key => $icon ) : ?>
									<label class="preview-ai-icon-option <?php echo $widget_settings['button_icon'] === $key ? 'is-selected' : ''; ?>">
										<input type="radio" 
											   name="preview_ai_button_icon" 
											   value="<?php echo esc_attr( $key ); ?>"
											   <?php checked( $widget_settings['button_icon'], $key ); ?>
										/>
										<span class="preview-ai-icon-preview">
											<?php echo PREVIEW_AI_Admin::kses_svg( $icon['svg'] ); ?>
										</span>
										<span class="preview-ai-icon-label"><?php echo esc_html( $icon['label'] ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</td>
					</tr>

					<!-- Button Position -->
					<tr>
						<th scope="row">
							<label for="preview_ai_button_position">
								<?php esc_html_e( 'Button Position', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<select id="preview_ai_button_position" name="preview_ai_button_position">
								<option value="left" <?php selected( $widget_settings['button_position'], 'left' ); ?>>
									<?php esc_html_e( 'Left', 'preview-ai' ); ?>
								</option>
								<option value="center" <?php selected( $widget_settings['button_position'], 'center' ); ?>>
									<?php esc_html_e( 'Center', 'preview-ai' ); ?>
								</option>
								<option value="right" <?php selected( $widget_settings['button_position'], 'right' ); ?>>
									<?php esc_html_e( 'Right', 'preview-ai' ); ?>
								</option>
							</select>
						</td>
					</tr>

					<!-- Button Shape -->
					<tr>
						<th scope="row">
							<label for="preview_ai_button_shape">
								<?php esc_html_e( 'Button Shape', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<select id="preview_ai_button_shape" name="preview_ai_button_shape">
								<option value="pill" <?php selected( $widget_settings['button_shape'], 'pill' ); ?>>
									<?php esc_html_e( 'Pill (Rounded)', 'preview-ai' ); ?>
								</option>
								<option value="squared" <?php selected( $widget_settings['button_shape'], 'squared' ); ?>>
									<?php esc_html_e( 'Rounded Corners', 'preview-ai' ); ?>
								</option>
								<option value="sharp" <?php selected( $widget_settings['button_shape'], 'sharp' ); ?>>
									<?php esc_html_e( 'Squared (Sharp)', 'preview-ai' ); ?>
								</option>
							</select>
						</td>
					</tr>

					<!-- Button Height -->
					<tr>
						<th scope="row">
							<label for="preview_ai_button_height">
								<?php esc_html_e( 'Button Height', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<input type="number"
								   id="preview_ai_button_height"
								   name="preview_ai_button_height"
								   value="<?php echo esc_attr( $widget_settings['button_height'] ); ?>"
								   min="24"
								   max="80"
								   step="1"
								   style="width: 80px;"
							/> px
							<p class="description">
								<?php esc_html_e( 'Height of the button in pixels. Default: 38px.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>

					<!-- Button Full Width -->
					<tr>
						<th scope="row">
							<label for="preview_ai_button_full_width">
								<?php esc_html_e( 'Full Width', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<label class="preview-ai-toggle">
								<input type="hidden" name="preview_ai_button_full_width" value="0" />
								<input type="checkbox" 
									   id="preview_ai_button_full_width" 
									   name="preview_ai_button_full_width" 
									   value="1" 
									   <?php checked( 1, $widget_settings['button_full_width'] ); ?> 
								/>
								<span class="preview-ai-toggle__slider"></span>
							</label>
							<p class="description">
								<?php esc_html_e( 'Make the button 100% width of its container.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>

					<!-- Accent Color -->
					<tr>
						<th scope="row">
							<label for="preview_ai_accent_color">
								<?php esc_html_e( 'Accent Color', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
								   id="preview_ai_accent_color" 
								   name="preview_ai_accent_color" 
								   value="<?php echo esc_attr( $widget_settings['accent_color'] ); ?>" 
								   class="preview-ai-color-picker"
								   data-default-color="#3b82f6"
							/>
							<p class="description">
								<?php esc_html_e( 'Choose the accent color for the widget and modal buttons.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>

			<!-- Shortcode info -->
			<div class="preview-ai-shortcode-info" style="margin-top: 20px; padding: 16px 20px; background: #fff; border: 1px solid #c3c4c7; border-left: 4px solid #2271b1;">
				<h3 style="margin: 0 0 8px; font-size: 14px;"><?php esc_html_e( 'Shortcode', 'preview-ai' ); ?></h3>
				<code style="display: inline-block; padding: 8px 12px; background: #f0f0f1; border-radius: 4px; font-size: 13px;">[preview_ai]</code>
				<p class="description" style="margin-top: 8px;">
					<?php esc_html_e( 'Use this shortcode to manually place the widget anywhere on your product pages.', 'preview-ai' ); ?>
				</p>
			</div>
		</form>
	<?php endif; ?>

	<div style="margin-top: 40px; padding: 24px; border: 1px dashed #c3c4c7; border-radius: 8px; background: #fff; text-align: center;">
		<h3 style="margin: 0 0 8px; color: #1d2327;"><?php esc_html_e( 'Need help or have a suggestion?', 'preview-ai' ); ?></h3>
		<p style="margin: 0 0 16px; color: #50575e;">
			<?php esc_html_e( 'We are here to help you get the most out of Preview AI. Whether you have a technical issue or an idea to improve the plugin, we want to hear from you.', 'preview-ai' ); ?>
		</p>
		<a href="https://www.previewai.app/contact" target="_blank" class="button">
			<span class="dashicons dashicons-external" style="font-size: 16px; vertical-align: middle; margin-right: 4px;"></span>
			<?php esc_html_e( 'Contact Support & Suggestions', 'preview-ai' ); ?>
		</a>
	</div>
</div>
