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

					<!-- Default Product Type -->
					<tr>
						<th scope="row">
							<label for="preview_ai_product_type">
								<?php esc_html_e( 'Default Product Type', 'preview-ai' ); ?>
							</label>
						</th>
						<td>
							<?php $current_type = get_option( 'preview_ai_product_type', 'clothing' ); ?>
							<select id="preview_ai_product_type" name="preview_ai_product_type">
								<?php foreach ( PREVIEW_AI_Admin::get_product_types() as $value => $data ) : ?>
									<?php if ( $data['available'] ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_type, $value ); ?>>
											<?php echo esc_html( $data['label'] ); ?>
										</option>
									<?php else : ?>
										<option value="" disabled>
											<?php echo esc_html( $data['label'] ); ?> — <?php esc_html_e( 'Coming Soon', 'preview-ai' ); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<!-- Clothing Subtype -->
					<tr id="preview_ai_clothing_subtype_row">
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
										<?php echo esc_html( $data['label'] . ' — ' . $data['examples'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
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
							<button type="button" id="preview_ai_verify_btn" class="button" style="margin-left: 8px;">
								<?php esc_html_e( 'Verify', 'preview-ai' ); ?>
							</button>
							<?php
							// Only show "Manage Subscription" for paid plans (not free_tier).
							$status = PREVIEW_AI_Api::get_account_status();
							$subscription_status = isset( $status['subscription_status'] ) ? $status['subscription_status'] : null;
							$is_paid_plan = ( 'free_trial' !== $subscription_status && ! empty( $subscription_status ) );
							?>
							<?php if ( $is_paid_plan ) : ?>
								<a href="https://billing.stripe.com/p/login/test_cNi4gyfXV4u2bnb8QHgIo00" 
								   target="_blank" 
								   class="button" 
								   style="margin-left: 8px;"
								   id="preview_ai_manage_subscription_btn">
									<?php esc_html_e( 'Manage Subscription', 'preview-ai' ); ?>
								</a>
							<?php endif; ?>
							<div id="preview_ai_verify_status"></div>
							<p class="description">
								<?php esc_html_e( 'Your API key for authentication.', 'preview-ai' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button(); ?>

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
											<?php echo $icon['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
</div>
