<?php
/**
 * Elementor widget for Preview AI.
 *
 * @link       https://previewai.app
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
				'label' => __( 'Button', 'preview-ai' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => __( 'Button Text', 'preview-ai' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'See it on you', 'preview-ai' ),
				'description' => __( 'Leave empty to use global settings.', 'preview-ai' ),
			)
		);

		$this->add_control(
			'button_icon',
			array(
				'label'   => __( 'Button Icon', 'preview-ai' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''       => __( 'Use Global Setting', 'preview-ai' ),
					'wand'   => __( 'Magic Wand', 'preview-ai' ),
					'camera' => __( 'Camera', 'preview-ai' ),
					'eye'    => __( 'Eye / Preview', 'preview-ai' ),
					'shirt'  => __( 'T-Shirt', 'preview-ai' ),
					'spark'  => __( 'Sparkles / AI', 'preview-ai' ),
				),
			)
		);

		$this->end_controls_section();

		// Style Section.
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Button', 'preview-ai' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'button_position',
			array(
				'label'   => __( 'Alignment', 'preview-ai' ),
				'type'    => \Elementor\Controls_Manager::CHOOSE,
				'default' => '',
				'options' => array(
					'left'   => array(
						'title' => __( 'Left', 'preview-ai' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => __( 'Center', 'preview-ai' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => __( 'Right', 'preview-ai' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'toggle'      => true,
				'description' => __( 'Leave unselected to use global settings.', 'preview-ai' ),
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
				echo '<p style="padding:20px;background:#f0f0f0;text-align:center;border-radius:8px;">';
				echo esc_html__( 'Preview AI: Use this widget on a product page.', 'preview-ai' );
				echo '</p>';
			}
			return;
		}

		$settings = $this->get_settings_for_display();

		// Build overrides array (only non-empty values).
		$overrides = array();

		if ( ! empty( $settings['button_text'] ) ) {
			$overrides['button_text'] = sanitize_text_field( $settings['button_text'] );
		}

		if ( ! empty( $settings['button_icon'] ) ) {
			$overrides['button_icon'] = sanitize_key( $settings['button_icon'] );
		}

		if ( ! empty( $settings['button_position'] ) ) {
			$overrides['button_position'] = sanitize_key( $settings['button_position'] );
		}

		// Output is escaped in PREVIEW_AI_Public::render_widget_output().
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo PREVIEW_AI_Public::render_widget_output( $product_id, $overrides );
	}
}
