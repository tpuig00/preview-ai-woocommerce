<?php
/**
 * Elementor widget for Preview AI.
 *
 * @link       http://preview-ai.com
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes/widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PREVIEW_AI_Elementor_Widget
 */
class PREVIEW_AI_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'preview_ai';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Preview AI', 'preview-ai' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-image-before-after';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'woocommerce-elements', 'general' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'preview', 'ai', 'try on', 'virtual', 'product' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// Content Section.
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Content', 'preview-ai' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => __( 'Button Text', 'preview-ai' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'Generate', 'preview-ai' ),
			)
		);

		$this->add_control(
			'upload_text',
			array(
				'label'       => __( 'Upload Text', 'preview-ai' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'Upload your photo', 'preview-ai' ),
			)
		);

		$this->end_controls_section();

		// Style Section.
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Style', 'preview-ai' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'primary_color',
			array(
				'label'   => __( 'Primary Color', 'preview-ai' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => '',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @since 1.0.0
	 */
	protected function render() {
		global $product;

		$product_id = $product ? $product->get_id() : 0;
		if ( ! $product_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p style="padding:20px;background:#f0f0f0;text-align:center;">';
				echo esc_html__( 'Preview AI: Use this widget on a product page.', 'preview-ai' );
				echo '</p>';
			}
			return;
		}

		$settings = $this->get_settings_for_display();

		// Sanitize Elementor settings before use.
		$branding = array_filter(
			array(
				'primary_color' => sanitize_hex_color( $settings['primary_color'] ),
				'button_text'   => sanitize_text_field( $settings['button_text'] ),
				'upload_text'   => sanitize_text_field( $settings['upload_text'] ),
			)
		);

		// Output is escaped in PREVIEW_AI_Public::render_widget_output().
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo PREVIEW_AI_Public::render_widget_output( $product_id, $branding );
	}
}

