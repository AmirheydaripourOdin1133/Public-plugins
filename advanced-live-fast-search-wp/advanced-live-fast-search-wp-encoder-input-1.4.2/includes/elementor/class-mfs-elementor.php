<?php
defined('ABSPATH') || exit;

class MFS_Elementor {

	public static function init() {
		add_action('elementor/widgets/register', array(__CLASS__, 'register_widgets'));
		add_action('elementor/elements/categories_registered', array(__CLASS__, 'register_category'));
	}

	public static function register_category($elements_manager) {
		$elements_manager->add_category('mfs-search', array(
			'title' => 'جستجوی زنده',
			'icon'  => 'fa fa-search',
		));
	}

	public static function register_widgets($widgets_manager) {
		require_once MFS_DIR . 'includes/elementor/class-mfs-search-widget.php';
		$widgets_manager->register(new MFS_Elementor_Search_Widget());
	}
}
