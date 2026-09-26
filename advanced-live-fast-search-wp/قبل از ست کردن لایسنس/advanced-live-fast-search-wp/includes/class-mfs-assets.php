<?php
defined('ABSPATH') || exit;

class MFS_Assets {

	public static function enqueue_skin($skin) {
		static $enqueued = array();

		if (isset($enqueued[$skin])) {
			return;
		}
		$enqueued[$skin] = true;

		$info = MFS_Plugin::skin_info($skin);

		wp_enqueue_script('jquery');
		wp_enqueue_style('mfs-ui', MFS_URL . 'assets/mfs-ui.css', array(), MFS_VERSION);
		wp_enqueue_script(
			'mfs-core',
			MFS_URL . 'assets/mfs-core.js',
			array('jquery'),
			MFS_VERSION,
			true
		);

		wp_enqueue_style('mfs-' . $skin, $info['url'] . '/style.css', array('mfs-ui'), MFS_VERSION);
		wp_enqueue_script(
			'mfs-' . $skin,
			$info['url'] . '/main.js',
			array('jquery', 'mfs-core'),
			MFS_VERSION,
			true
		);
	}

	public static function maybe_enqueue_auto() {
		if (is_admin()) {
			return;
		}
		if (!MFS_Plugin::get('auto_mount')) {
			return;
		}
		$skin = MFS_Plugin::get('skin');
		if (!MFS_Plugin::is_overlay_skin($skin)) {
			return;
		}
		self::enqueue_skin($skin);
	}

	public static function config($skin) {
		$settings = MFS_Plugin::settings();

		$sections = array();
		foreach ((array) $settings['post_types'] as $post_type) {
			if (!post_type_exists($post_type)) {
				continue;
			}
			$object = get_post_type_object($post_type);
			$sections[$post_type] = array(
				'label' => $object ? $object->labels->name : $post_type,
				'limit' => (int) $settings['limit_items'],
			);
		}

		$terms = array();
		foreach ((array) $settings['taxonomies'] as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}
			$object = get_taxonomy($taxonomy);
			$terms[$taxonomy] = array(
				'label' => $object ? $object->labels->name : $taxonomy,
				'limit' => (int) $settings['limit_terms'],
			);
		}

		return array(
			'rest'     => esc_url_raw(rest_url('mfs/v1/data')),
			'skin'     => $skin,
			'sections' => $sections,
			'terms'    => $terms,
			'i18n'     => array(
				'notFound' => __('نتیجه‌ای یافت نشد.', 'advanced-live-fast-search-wp'),
			),
		);
	}
}
