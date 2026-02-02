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

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; determines which settings tab to display, no data modification. Value sanitized with sanitize_key().
$preview_ai_active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Preview AI Settings', 'preview-ai' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=general' ) ); ?>" 
		   class="nav-tab <?php echo 'general' === $preview_ai_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'General', 'preview-ai' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=widget' ) ); ?>" 
		   class="nav-tab <?php echo 'widget' === $preview_ai_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Widget', 'preview-ai' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats' ) ); ?>" 
		   class="nav-tab <?php echo 'stats' === $preview_ai_active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Statistics', 'preview-ai' ); ?>
		</a>

		<a href="https://www.previewai.app/contact" target="_blank" class="nav-tab" style="margin-left: auto; border: none; color: #646970; font-weight: 400; font-size: 12px; opacity: 0.8;">
			<span class="dashicons dashicons-sos" style="margin-right: 4px; font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
			<?php esc_html_e( 'Contact Support', 'preview-ai' ); ?>
		</a>
	</nav>

	<?php if ( 'stats' === $preview_ai_active_tab ) : ?>
		<!-- Statistics Tab -->
		<?php
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only; determines stats period filter, no data modification. Value sanitized with sanitize_key().
		$preview_ai_period = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : '30days';
		$preview_ai_stats  = PREVIEW_AI_Tracking::get_detailed_stats( $preview_ai_period );
		?>
		
		<div class="preview-ai-stats-header">
			<label for="preview_ai_period"><?php esc_html_e( 'Period:', 'preview-ai' ); ?></label>
			<select id="preview_ai_period" onchange="window.location.href=this.value;">
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=today' ) ); ?>" <?php selected( $preview_ai_period, 'today' ); ?>>
					<?php esc_html_e( 'Today', 'preview-ai' ); ?>
				</option>
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=7days' ) ); ?>" <?php selected( $preview_ai_period, '7days' ); ?>>
					<?php esc_html_e( 'Last 7 days', 'preview-ai' ); ?>
				</option>
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=30days' ) ); ?>" <?php selected( $preview_ai_period, '30days' ); ?>>
					<?php esc_html_e( 'Last 30 days', 'preview-ai' ); ?>
				</option>
				<option value="<?php echo esc_url( admin_url( 'edit.php?post_type=product&page=preview-ai&tab=stats&period=all' ) ); ?>" <?php selected( $preview_ai_period, 'all' ); ?>>
					<?php esc_html_e( 'All time', 'preview-ai' ); ?>
				</option>
			</select>
		</div>

		<!-- Primary Stats Cards -->
		<div class="preview-ai-stats-grid">
			<div class="preview-ai-stat-card">
				<div class="preview-ai-stat-value preview-ai-stat-value--blue"><?php echo esc_html( number_format_i18n( $preview_ai_stats['users_tried'] ) ); ?></div>
				<div class="preview-ai-stat-label"><?php esc_html_e( 'Customers Used Preview AI', 'preview-ai' ); ?></div>
			</div>
			<div class="preview-ai-stat-card">
				<div class="preview-ai-stat-value preview-ai-stat-value--green"><?php echo esc_html( number_format_i18n( $preview_ai_stats['orders_influenced'] ) ); ?></div>
				<div class="preview-ai-stat-label"><?php esc_html_e( 'Orders Influenced', 'preview-ai' ); ?></div>
			</div>
			<div class="preview-ai-stat-card">
				<div class="preview-ai-stat-value preview-ai-stat-value--amber"><?php echo esc_html( $preview_ai_stats['user_conversion_rate'] ); ?>%</div>
				<div class="preview-ai-stat-label"><?php esc_html_e( 'User Conversion Rate', 'preview-ai' ); ?></div>
			</div>
			<div class="preview-ai-stat-card">
				<div class="preview-ai-stat-value preview-ai-stat-value--navy"><?php echo wp_kses_post( wc_price( $preview_ai_stats['influenced_revenue'] ) ); ?></div>
				<div class="preview-ai-stat-label"><?php esc_html_e( 'Revenue Influenced', 'preview-ai' ); ?></div>
			</div>
		</div>

		<!-- Secondary Stats -->
		<?php if ( $preview_ai_stats['avg_order_value'] > 0 || $preview_ai_stats['orders_refunded'] > 0 ) : ?>
		<div class="preview-ai-stats-secondary">
			<?php if ( $preview_ai_stats['avg_order_value'] > 0 ) : ?>
			<div class="preview-ai-secondary-stat">
				<div class="preview-ai-secondary-value"><?php echo wp_kses_post( wc_price( $preview_ai_stats['avg_order_value'] ) ); ?></div>
				<div class="preview-ai-secondary-label"><?php esc_html_e( 'Avg. Order Value', 'preview-ai' ); ?></div>
			</div>
			<?php endif; ?>
			<?php if ( $preview_ai_stats['orders_refunded'] > 0 ) : ?>
			<div class="preview-ai-secondary-stat">
				<div class="preview-ai-secondary-value preview-ai-secondary-value--red"><?php echo esc_html( number_format_i18n( $preview_ai_stats['orders_refunded'] ) ); ?></div>
				<div class="preview-ai-secondary-label"><?php esc_html_e( 'Orders Refunded', 'preview-ai' ); ?></div>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<div class="preview-ai-stats-footer">
			<!-- Top Products -->
			<div class="preview-ai-top-products">
				<h3 class="preview-ai-footer-title"><?php esc_html_e( 'Top Converting Products', 'preview-ai' ); ?></h3>
				<?php
				$preview_ai_top_products = PREVIEW_AI_Tracking::get_top_products( 5 );
				if ( empty( $preview_ai_top_products ) ) :
					?>
					<p class="preview-ai-empty-stats"><?php esc_html_e( 'No conversions yet.', 'preview-ai' ); ?></p>
				<?php else : ?>
					<table class="widefat preview-ai-stats-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'preview-ai' ); ?></th>
								<th class="preview-ai-text-center"><?php esc_html_e( 'Previews', 'preview-ai' ); ?></th>
								<th class="preview-ai-text-center"><?php esc_html_e( 'Conv.', 'preview-ai' ); ?></th>
								<th class="preview-ai-text-center"><?php esc_html_e( 'Rate', 'preview-ai' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $preview_ai_top_products as $preview_ai_product ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $preview_ai_product['product_id'] ) ); ?>">
											<?php echo esc_html( $preview_ai_product['product_name'] ); ?>
										</a>
									</td>
									<td class="preview-ai-text-center"><?php echo esc_html( $preview_ai_product['previews'] ); ?></td>
									<td class="preview-ai-text-center"><?php echo esc_html( $preview_ai_product['conversions'] ); ?></td>
									<td class="preview-ai-text-center"><?php echo esc_html( $preview_ai_product['conversion_rate'] ); ?>%</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Recent Conversions -->
			<div class="preview-ai-recent-conversions">
				<h3 class="preview-ai-footer-title"><?php esc_html_e( 'Recent Conversions', 'preview-ai' ); ?></h3>
				<?php
				$preview_ai_recent = PREVIEW_AI_Tracking::get_recent_conversions( 5 );
				if ( empty( $preview_ai_recent ) ) :
					?>
					<p class="preview-ai-empty-stats"><?php esc_html_e( 'No conversions yet.', 'preview-ai' ); ?></p>
				<?php else : ?>
					<table class="widefat preview-ai-stats-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Customer', 'preview-ai' ); ?></th>
								<th><?php esc_html_e( 'Product', 'preview-ai' ); ?></th>
								<th class="preview-ai-text-right"><?php esc_html_e( 'Order Total', 'preview-ai' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $preview_ai_recent as $preview_ai_conv ) : ?>
								<tr>
									<td>
										<?php echo esc_html( $preview_ai_conv['customer_name'] ); ?>
									</td>
									<td>
										<?php echo esc_html( $preview_ai_conv['product_name'] ); ?>
									</td>
									<td class="preview-ai-text-right">
										<?php echo wp_kses_post( wc_price( $preview_ai_conv['order_total'] ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

	<?php elseif ( 'general' === $preview_ai_active_tab ) : ?>
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

					<!-- Max Previews Per User Weekly -->
					<tr>
						<th scope="row">
							<label for="preview_ai_max_previews_per_user_weekly">
								<?php esc_html_e( 'Max Previews Per User (Weekly)', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<input type="number" 
								   id="preview_ai_max_previews_per_user_weekly" 
								   name="preview_ai_max_previews_per_user_weekly" 
								   value="<?php echo esc_attr( PREVIEW_AI_Admin_Settings::get_max_previews_per_user_weekly() ); ?>" 
								   class="small-text" 
								   min="1"
								   step="1"
							/>
							<p class="description">
								<?php esc_html_e( 'Maximum number of previews each visitor can generate per week. Default: 8.', 'preview-ai' ); ?>
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
							$preview_ai_status = PREVIEW_AI_Api::get_account_status();
							$preview_ai_tokens_limit = isset( $preview_ai_status['tokens_limit'] ) ? (int) $preview_ai_status['tokens_limit'] : 0;
							$preview_ai_tokens_used = isset( $preview_ai_status['tokens_used'] ) ? (int) $preview_ai_status['tokens_used'] : 0;
							$preview_ai_tokens_remaining = max( 0, $preview_ai_tokens_limit - $preview_ai_tokens_used );
							$preview_ai_usage_percentage = $preview_ai_tokens_limit > 0 ? min( 100, round( ( $preview_ai_tokens_used / $preview_ai_tokens_limit ) * 100 ) ) : 0;
							
							$preview_ai_renewal_date = isset( $preview_ai_status['current_period_end'] ) ? $preview_ai_status['current_period_end'] : null;
							?>

							<div class="preview-ai-account-card">
								<div class="preview-ai-usage-section">
									<div class="preview-ai-usage-header">
										<span class="preview-ai-usage-title"><?php esc_html_e( 'Monthly Usage', 'preview-ai' ); ?></span>
										<span class="preview-ai-usage-numbers">
											<strong id="pai-tokens-used"><?php echo esc_html( number_format_i18n( $preview_ai_tokens_used ) ); ?></strong> / <span id="pai-tokens-limit"><?php echo esc_html( number_format_i18n( $preview_ai_tokens_limit ) ); ?></span> <?php esc_html_e( 'previews', 'preview-ai' ); ?>
										</span>
									</div>
									<div class="preview-ai-usage-bar-container">
										<div id="pai-usage-bar" style="width: <?php echo esc_attr( $preview_ai_usage_percentage ); ?>%;"></div>
									</div>
									<div class="preview-ai-usage-footer">
										<span><span id="pai-tokens-remaining-text"><?php
											/* translators: %s: number of remaining previews */
											printf( esc_html__( '%s remaining', 'preview-ai' ), '<strong>' . esc_html( number_format_i18n( $preview_ai_tokens_remaining ) ) . '</strong>' );
											?></span></span>
										<span id="pai-renewal-date-container">
											<?php if ( $preview_ai_renewal_date ) : ?>
												<?php
												printf(
													/* translators: %s: renewal date */
													esc_html__( 'Resets on %s', 'preview-ai' ),
													'<strong id="pai-renewal-date">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $preview_ai_renewal_date ) ) ) . '</strong>'
												);
												?>
											<?php endif; ?>
										</span>
									</div>
								</div>

								<div id="pai-verification-status">
									<div class="pai-verification-header">
										<div class="pai-api-key-preview">
											<span class="dashicons dashicons-key"></span>
											<span>pvai_••••••••••••<?php echo esc_html( substr( get_option( 'preview_ai_api_key' ), -6 ) ); ?></span>
										</div>
										<div id="pai-status-indicator">
											<span class="dashicons dashicons-yes-alt"></span>
											<?php esc_html_e( 'Verified', 'preview-ai' ); ?>
										</div>
									</div>
									
									<div class="pai-account-details">
										<span class="pai-detail-label"><?php esc_html_e( 'Email:', 'preview-ai' ); ?></span>
										<span id="pai-status-email"><?php echo esc_html( $preview_ai_status['email'] ?? '—' ); ?></span>
										
										<span class="pai-detail-label"><?php esc_html_e( 'Domain:', 'preview-ai' ); ?></span>
										<span id="pai-status-domain"><?php echo esc_html( $preview_ai_status['domain'] ?? '—' ); ?></span>
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
			$preview_ai_catalog_status = PREVIEW_AI_Admin::get_catalog_analysis_status();
			$preview_ai_is_processing  = 'processing' === $preview_ai_catalog_status['status'];
			$preview_ai_progress       = $preview_ai_catalog_status['progress'];

			// Get compatibility status.
			$preview_ai_preflight     = get_option( 'preview_ai_store_compatibility' );
			$preview_ai_is_compatible = ! empty( $preview_ai_preflight ) ? $preview_ai_preflight['compatible'] : true;
			?>
			<div class="preview-ai-learn-catalog">
				<h2 class="preview-ai-catalog-title">
					🧠 <?php esc_html_e( 'Learn My Catalog (AI)', 'preview-ai' ); ?>
				</h2>
				<p class="preview-ai-catalog-desc">
					<?php esc_html_e( 'Preview AI will automatically detect what type of product each one is (t-shirts, dresses, belts, earrings, fanny packs…).', 'preview-ai' ); ?>
					<br><strong>
					<?php esc_html_e( 'This will analyze your catalog and assign the appropriate product type to each product.', 'preview-ai' ); ?></strong>
				</p>
				<p class="preview-ai-catalog-note">
					<?php esc_html_e( 'Nothing will be modified in your store. Only recommendations will be assigned.', 'preview-ai' ); ?>
				</p>
				
				<?php if ( ! $preview_ai_is_compatible ) : ?>
					<div id="preview_ai_compatibility_error" class="preview-ai-comp-error">
						<span class="dashicons dashicons-warning"></span>
						<?php echo esc_html( $preview_ai_preflight['message'] ); ?>
						<p class="preview-ai-reverify-para">
							<a href="#" id="preview_ai_reverify_compatibility">
								<?php esc_html_e( 'Re-verify compatibility', 'preview-ai' ); ?>
							</a>
						</p>
					</div>
				<?php endif; ?>

				<button type="button" id="preview_ai_learn_catalog_btn" class="button button-primary" <?php echo ( $preview_ai_is_processing || ! $preview_ai_is_compatible ) ? 'disabled' : ''; ?>>
					<span class="dashicons dashicons-welcome-learn-more"></span>
					<?php esc_html_e( 'Analyze My Catalog', 'preview-ai' ); ?>
				</button>

				<div id="preview_ai_learn_catalog_loading" style="<?php echo $preview_ai_is_processing ? '' : 'display: none;'; ?>">
					<span class="spinner"></span>
					<span id="preview_ai_learn_catalog_progress">
						<?php
						if ( $preview_ai_is_processing && ! empty( $preview_ai_progress ) ) {
							printf(
								/* translators: 1: processed count, 2: total count */
								esc_html__( 'Processing... %1$d of %2$d products analyzed.', 'preview-ai' ),
								absint( $preview_ai_progress['processed'] ),
								absint( $preview_ai_progress['total'] )
							);
						} else {
							esc_html_e( 'Analyzing your catalog...', 'preview-ai' );
						}
						?>
					</span>
				</div>

				<div id="preview_ai_learn_catalog_result" style="display: none;"></div>
			</div>
		</form>

	<?php elseif ( 'widget' === $preview_ai_active_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'preview_ai_widget_settings' ); ?>
			<!-- Widget Tab -->
			<?php
			$preview_ai_widget_settings = PREVIEW_AI_Admin::get_widget_settings();
			$preview_ai_button_icons    = PREVIEW_AI_Admin::get_button_icons();
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
								   value="<?php echo esc_attr( $preview_ai_widget_settings['button_text'] ); ?>" 
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
								<?php foreach ( $preview_ai_button_icons as $preview_ai_key => $preview_ai_icon ) : ?>
									<label class="preview-ai-icon-option <?php echo $preview_ai_widget_settings['button_icon'] === $preview_ai_key ? 'is-selected' : ''; ?>">
										<input type="radio" 
											   name="preview_ai_button_icon" 
											   value="<?php echo esc_attr( $preview_ai_key ); ?>"
											   <?php checked( $preview_ai_widget_settings['button_icon'], $preview_ai_key ); ?>
										/>
										<span class="preview-ai-icon-preview">
											<?php
															echo wp_kses(
																$preview_ai_icon['svg'],
																array(
																	'svg'      => array( 'viewbox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'xmlns' => true, 'class' => true ),
																	'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true ),
																	'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true ),
																	'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true ),
																	'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true ),
																)
															);
															?>
										</span>
										<span class="preview-ai-icon-label"><?php echo esc_html( $preview_ai_icon['label'] ); ?></span>
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
								<option value="left" <?php selected( $preview_ai_widget_settings['button_position'], 'left' ); ?>>
									<?php esc_html_e( 'Left', 'preview-ai' ); ?>
								</option>
								<option value="center" <?php selected( $preview_ai_widget_settings['button_position'], 'center' ); ?>>
									<?php esc_html_e( 'Center', 'preview-ai' ); ?>
								</option>
								<option value="right" <?php selected( $preview_ai_widget_settings['button_position'], 'right' ); ?>>
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
								<option value="pill" <?php selected( $preview_ai_widget_settings['button_shape'], 'pill' ); ?>>
									<?php esc_html_e( 'Pill (Rounded)', 'preview-ai' ); ?>
								</option>
								<option value="squared" <?php selected( $preview_ai_widget_settings['button_shape'], 'squared' ); ?>>
									<?php esc_html_e( 'Rounded Corners', 'preview-ai' ); ?>
								</option>
								<option value="sharp" <?php selected( $preview_ai_widget_settings['button_shape'], 'sharp' ); ?>>
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
								   value="<?php echo esc_attr( $preview_ai_widget_settings['button_height'] ); ?>"
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
									   <?php checked( 1, $preview_ai_widget_settings['button_full_width'] ); ?> 
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
								   value="<?php echo esc_attr( $preview_ai_widget_settings['accent_color'] ); ?>" 
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

	<div class="preview-ai-footer-help">
		<h3><?php esc_html_e( 'Need help or have a suggestion?', 'preview-ai' ); ?></h3>
		<p>
			<?php esc_html_e( 'We are here to help you get the most out of Preview AI. Whether you have a technical issue or an idea to improve the plugin, we want to hear from you.', 'preview-ai' ); ?>
		</p>
		<a href="https://www.previewai.app/contact" target="_blank" class="button">
			<span class="dashicons dashicons-external"></span>
			<?php esc_html_e( 'Contact Support & Suggestions', 'preview-ai' ); ?>
		</a>
	</div>
</div>
