<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
	return;
}

/**
 * Elementor Widget: راهنمای سایز بندی محصولات سفارشی.
 *
 * Renders the DSSG guide (tabs/accordions/modal) inside Elementor.
 */
class DSSG_Elementor_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'dssg_guide';
	}

	public function get_title() {
		return __( 'راهنمای سایز بندی محصولات', 'ds-size-guide' );
	}

	public function get_icon() {
		return 'eicon-table';
	}

	public function get_categories() {
		return [ 'woocommerce' ];
	}

	public function get_keywords() {
		return [ 'size', 'guide', 'accordion', 'woocommerce', 'ds-size-guide' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'محتوای راهنما', 'ds-size-guide' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'product_id',
			[
				'label'       => __( 'شناسه محصول', 'ds-size-guide' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'default'     => '',
				'placeholder' => '',
				'description' => __( 'خالی بگذارید تا در صفحهٔ محصول از محصول فعلی استفاده شود.', 'ds-size-guide' ),
			]
		);

		$this->add_control(
			'style',
			[
				'label'   => __( 'سبک نمایش', 'ds-size-guide' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''      => __( 'تنظیمات افزونه', 'ds-size-guide' ),
					'accordion' => __( 'آکاردئون', 'ds-size-guide' ),
					'tabs'      => __( 'تب', 'ds-size-guide' ),
					'modal'     => __( 'مودال (پاپ آپ)', 'ds-size-guide' ),
				],
			]
		);

		$this->add_control(
			'html_id',
			[
				'label'       => __( 'شناسه HTML (اختیاری)', 'ds-size-guide' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => '',
				'description' => __( 'برای استایل‌دهی خاص یا لینک مستقیم به راهنما.', 'ds-size-guide' ),
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$product_id = ! empty( $settings['product_id'] ) ? absint( $settings['product_id'] ) : 0;
		$style      = ! empty( $settings['style'] ) ? $settings['style'] : '';
		$html_id    = ! empty( $settings['html_id'] ) ? sanitize_html_class( $settings['html_id'] ) : '';

		if ( class_exists( 'WooCommerce' ) && $product_id > 0 && ! \DSSG\DSSG_Settings::is_enabled_for_product( $product_id ) ) {
			return;
		}

		echo \DSSG\DSSG_Render::render( $product_id, $style, $html_id );
	}

	protected function _content_template() {
		// Not used; server-side render only.
	}
}