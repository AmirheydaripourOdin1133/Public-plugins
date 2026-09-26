<?php
defined('ABSPATH') || exit;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class MFS_Elementor_Search_Widget extends Widget_Base {

	public function get_name() {
		return 'mfs_search';
	}

	public function get_title() {
		return 'جستجوی زنده';
	}

	public function get_icon() {
		return 'eicon-search';
	}

	public function get_categories() {
		return array('mfs-search', 'general');
	}

	public function get_keywords() {
		return array('search', 'جستجو', 'mfs', 'live', 'modal');
	}

	protected function register_controls() {
		$this->start_controls_section('section_content', array(
			'label' => 'تنظیمات نمایش',
		));

		$skin_options = array('' => 'پیش‌فرض افزونه');
		foreach (MFS_Plugin::skins() as $slug => $skin) {
			$skin_options[$slug] = $skin['label'];
		}

		$this->add_control('skin', array(
			'label'   => 'سبک نمایش',
			'type'    => Controls_Manager::SELECT,
			'default' => '',
			'options' => $skin_options,
		));

		$this->add_control('mode', array(
			'label'       => 'حالت خروجی',
			'type'        => Controls_Manager::SELECT,
			'default'     => 'auto',
			'options'     => array(
				'auto'    => 'خودکار (پیشنهادی)',
				'trigger' => 'فقط آیکون بازکننده',
				'field'   => 'اینپوت / فیلد',
				'panel'   => 'فقط پنل',
				'full'    => 'کامل (آیکون + پنل)',
			),
			'description' => 'برای سبک مودال و تاپ‌پنل، «خودکار» فقط آیکون می‌گذارد و پنل در فوتر نصب می‌شود.',
		));

		$this->add_control('mode_help', array(
			'type'            => Controls_Manager::RAW_HTML,
			'raw'             => '<div style="font-size:12px;line-height:1.7;color:#646970;">سبک خطی: ویجت را دقیقاً جایی بگذارید که اینپوت باید دیده شود.<br>سبک مودال/تاپ‌پنل: ویجت را در هدر بگذارید؛ پنل خودکار است.</div>',
			'content_classes' => 'elementor-descriptor',
		));

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$skin     = !empty($settings['skin']) ? $settings['skin'] : '';
		$mode     = !empty($settings['mode']) ? $settings['mode'] : 'auto';

		echo MFS_Render::render_by_mode($skin, $mode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	protected function content_template() {
		?>
		<#
		var label = 'جستجوی زنده';
		if ( settings.mode === 'trigger' ) { label = 'آیکون جستجو'; }
		else if ( settings.mode === 'field' ) { label = 'فیلد جستجو'; }
		#>
		<div style="padding:12px 16px;border:1px dashed #8ed557;border-radius:10px;background:#f4fbf0;color:#153f30;font-size:13px;text-align:center;">
			<span class="eicon-search" style="margin-left:6px;"></span>
			{{{ label }}}
			<# if ( settings.skin ) { #> — {{ settings.skin }}<# } #>
		</div>
		<?php
	}
}
