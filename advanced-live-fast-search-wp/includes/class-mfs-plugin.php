<?php
defined('ABSPATH') || exit;

class MFS_Plugin {

	private static $instance = null;
	private static $settings = null;

	public static function instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action('init', array($this, 'load_textdomain'));
		add_action('init', array($this, 'register_menu_location'));
		add_action('init', array($this, 'register_content_hooks'));

		MFS_Rest::init();
		MFS_Render::init();
		MFS_Settings::init();

		if (is_admin()) {
			add_action('admin_notices', array($this, 'admin_notices'));
			add_filter('plugin_action_links_' . plugin_basename(MFS_FILE), array($this, 'action_links'));
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain('advanced-live-fast-search-wp', false, dirname(plugin_basename(MFS_FILE)) . '/languages');
	}

	public function register_menu_location() {
		register_nav_menu('mfs-popular', 'جستجوهای پرطرفدار (جستجوی زنده)');
	}

	public function register_content_hooks() {
		$taxonomies = $this->get('taxonomies');

		add_action('save_post', array('MFS_Cache', 'maybe_schedule_on_post_save'), 10, 2);
		add_action('deleted_post', array('MFS_Cache', 'maybe_schedule_on_post_delete'));

		foreach ((array) $taxonomies as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				continue;
			}
			add_action('edited_' . $taxonomy, array('MFS_Cache', 'schedule_refresh'));
			add_action('created_' . $taxonomy, array('MFS_Cache', 'schedule_refresh'));
			add_action('delete_' . $taxonomy, array('MFS_Cache', 'schedule_refresh'));
		}
	}

	public static function defaults() {
		return array(
			'skin'         => 'modal',
			'auto_mount'   => 1,
			'post_types'   => array('post', 'product'),
			'taxonomies'   => array('product_cat'),
			'placeholder'  => 'جستجو در سایت',
			'accent_color' => '',
			'icon_pack'    => 'search-1',
			'limit_items'  => 15,
			'limit_terms'  => 10,
			'cache_ttl'    => 1800,
		);
	}

	public static function settings() {
		if (null === self::$settings) {
			self::$settings = wp_parse_args((array) get_option(MFS_OPTION, array()), self::defaults());
		}
		return self::$settings;
	}

	public static function get($key) {
		$settings = self::settings();
		return isset($settings[$key]) ? $settings[$key] : null;
	}

	public static function skins() {
		return apply_filters('mfs_skins', array(
			'modal'  => array(
				'label'       => 'جستجوی مودال (تمام‌صفحه)',
				'type'        => 'modal',
				'desc'        => 'آیکون هرجا؛ پنل به‌صورت خودکار در فوتر. مناسب هدر و موبایل.',
			),
			'inline' => array(
				'label'       => 'جستجوی خطی (نوار هدر)',
				'type'        => 'inline',
				'desc'        => 'اینپوت دقیقاً همان‌جایی که شورت‌کد/ویجت را می‌گذارید نمایش داده می‌شود.',
			),
			'top-panel' => array(
				'label'       => 'پنل نیم‌صفحه (Top Panel)',
				'type'        => 'modal',
				'desc'        => 'آیکون هرجا؛ پنل از بالای صفحه باز می‌شود. نصب خودکار مثل مودال.',
			),
			'split-panel' => array(
				'label'       => 'پنل کشویی دوستونه (هدر)',
				'type'        => 'inline',
				'desc'        => 'اینپوت خطی در هدر؛ با کلیک/فوکوس پنل عریض دوستونه (محصولات + مطالب) باز می‌شود.',
			),
		));
	}

	/**
	 * Overlay skins (modal / top-panel) mount a fixed panel; shortcode only needs a trigger.
	 */
	public static function is_overlay_skin($skin = null) {
		if (null === $skin) {
			$skin = self::get('skin');
		}
		$info = self::skin_info($skin);
		return isset($info['type']) && 'modal' === $info['type'];
	}

	public static function skin_info($skin) {
		$skins = self::skins();
		if (!isset($skins[$skin])) {
			$skin = 'modal';
		}

		$dir = wp_normalize_path(apply_filters('mfs_skin_dir', MFS_DIR . 'skins/' . $skin, $skin));
		$dir = rtrim($dir, '/');

		$base = wp_normalize_path(MFS_DIR);
		if (0 === strpos($dir, $base)) {
			$url = MFS_URL . str_replace($base, '', $dir);
		} else {
			$content = wp_normalize_path(WP_CONTENT_DIR);
			$url     = content_url(str_replace($content, '', $dir));
		}

		return array(
			'slug' => $skin,
			'dir'  => $dir,
			'url'  => untrailingslashit($url),
			'type' => isset($skins[$skin]['type']) ? $skins[$skin]['type'] : 'inline',
		);
	}

	public static function available_post_types() {
		$post_types = array();
		foreach (get_post_types(array('public' => true), 'objects') as $post_type) {
			if ('attachment' === $post_type->name) {
				continue;
			}
			$post_types[$post_type->name] = $post_type->labels->name;
		}
		return $post_types;
	}

	public static function available_taxonomies() {
		$taxonomies = array();
		foreach (get_taxonomies(array('public' => true), 'objects') as $taxonomy) {
			if (empty($taxonomy->show_in_nav_menus)) {
				continue;
			}
			$taxonomies[$taxonomy->name] = $taxonomy->labels->name;
		}
		return $taxonomies;
	}

	public static function activate() {
		if (false === get_option(MFS_OPTION, false)) {
			add_option(MFS_OPTION, self::defaults());
		}
		if (false === get_option(MFS_CACHE_NAME, false)) {
			add_option(MFS_CACHE_NAME, 'data-' . wp_generate_password(12, false, false) . '.json', '', false);
		}
		MFS_Cache::ensure_dir();
		MFS_Cache::schedule_refresh();
		set_transient('mfs_just_activated', 1, 60);
	}

	public static function deactivate() {
		wp_clear_scheduled_hook(MFS_CACHE_HOOK);
		delete_transient(MFS_CACHE_LOCK);
	}

	public function action_links($links) {
		array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=mfs-settings')) . '">تنظیمات</a>');
		return $links;
	}

	public function admin_notices() {
		if (!current_user_can('manage_options')) {
			return;
		}

		if (get_transient('mfs_just_activated')) {
			delete_transient('mfs_just_activated');
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('افزونه «جستجوی زنده پیشرفته» فعال شد. کش داده‌ها در پس‌زمینه ساخته می‌شود؛ وضعیت را از صفحه تنظیمات پیگیری کنید.', 'advanced-live-fast-search-wp') . '</p></div>';
		}

		$post_types = (array) $this->get('post_types');
		$taxonomies = (array) $this->get('taxonomies');

		if (empty($post_types) && empty($taxonomies)) {
			echo '<div class="notice notice-warning"><p>' . esc_html__('جستجوی زنده: هیچ نوع محتوایی برای جستجو انتخاب نشده است.', 'advanced-live-fast-search-wp') . ' <a href="' . esc_url(admin_url('admin.php?page=mfs-settings')) . '">' . esc_html__('انتخاب منابع جستجو', 'advanced-live-fast-search-wp') . '</a></p></div>';
			return;
		}

		if (!MFS_Cache::exists()) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('جستجوی زنده: کش داده‌ها هنوز ساخته نشده و نتایج موقتاً خالی است.', 'advanced-live-fast-search-wp') . ' <a href="' . esc_url(admin_url('admin.php?page=mfs-settings')) . '">' . esc_html__('ساخت فوری کش', 'advanced-live-fast-search-wp') . '</a></p></div>';
		}
	}
}
