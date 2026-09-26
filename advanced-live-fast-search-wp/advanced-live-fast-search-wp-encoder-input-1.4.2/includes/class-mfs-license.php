<?php
/**
 * RTL Theme license bootstrap and the locked-state admin experience.
 */
defined('ABSPATH') || exit;

class MFS_License {

	const PAGE_SLUG = 'mfs-settings';

	private static $reason = 'inactive';

	/**
	 * Register only the lightweight locked-state UI.
	 */
	public static function register_locked_admin($reason = 'inactive') {
		$allowed = array(
			'ioncube_missing',
			'file_missing',
			'file_invalid',
			'class_missing',
			'api_invalid',
			'check_failed',
			'inactive',
		);
		self::$reason = in_array($reason, $allowed, true) ? $reason : 'inactive';

		if (!is_admin()) {
			return;
		}

		add_action('admin_menu', array(__CLASS__, 'register_menu'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
		add_action('admin_head', array(__CLASS__, 'print_menu_icon_css'));
		add_action('admin_notices', array(__CLASS__, 'admin_notice'));
		add_filter('plugin_action_links_' . plugin_basename(MFS_FILE), array(__CLASS__, 'action_links'));
	}

	public static function register_menu() {
		add_menu_page(
			'فعال‌سازی لایسنس جستجوی زنده',
			'جستجوی زنده',
			'manage_options',
			self::PAGE_SLUG,
			array(__CLASS__, 'render_page'),
			self::menu_icon(),
			58
		);
	}

	public static function enqueue_assets($hook) {
		if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
			return;
		}

		wp_enqueue_style('mfs-admin', MFS_URL . 'assets/admin.css', array(), MFS_VERSION);
		wp_enqueue_style('mfs-license', MFS_URL . 'assets/license.css', array('mfs-admin'), MFS_VERSION);
	}

	public static function print_menu_icon_css() {
		echo '<style id="mfs-license-menu-icon">'
			. '#adminmenu #toplevel_page_' . esc_attr(self::PAGE_SLUG) . ' .wp-menu-image.svg{'
			. 'background-size:20px 20px!important;background-position:center!important;background-repeat:no-repeat!important;'
			. '}'
			. '</style>' . "\n";
	}

	public static function action_links($links) {
		array_unshift(
			$links,
			'<a href="' . esc_url(admin_url('admin.php?page=' . self::PAGE_SLUG)) . '">' .
			esc_html__('فعال‌سازی لایسنس', 'advanced-live-fast-search-wp') .
			'</a>'
		);
		return $links;
	}

	public static function admin_notice() {
		if (!current_user_can('manage_options')) {
			return;
		}

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;
		if ($screen && 'toplevel_page_' . self::PAGE_SLUG === $screen->id) {
			return;
		}

		$url = admin_url('admin.php?page=' . self::PAGE_SLUG);
		?>
		<div class="notice notice-warning mfs-license-notice">
			<p>
				<strong><?php esc_html_e('جستجوی زنده پیشرفته غیرفعال است.', 'advanced-live-fast-search-wp'); ?></strong>
				<?php esc_html_e('برای بارگذاری امکانات و تنظیمات افزونه، ابتدا لایسنس راست‌چین را برای این دامنه فعال کنید.', 'advanced-live-fast-search-wp'); ?>
				<a class="button button-primary" href="<?php echo esc_url($url); ?>">
					<?php esc_html_e('بررسی وضعیت لایسنس', 'advanced-live-fast-search-wp'); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public static function render_page() {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('شما اجازه دسترسی به این صفحه را ندارید.', 'advanced-live-fast-search-wp'));
		}

		$domain      = wp_parse_url(home_url('/'), PHP_URL_HOST);
		$domain      = $domain ? $domain : home_url('/');
		$reason      = self::$reason;
		$reason_text = self::reason_text($reason);
		$rtl_url     = 'https://www.rtl-theme.com/dashboard/#/downloads';
		$rsm_url     = admin_url('admin.php?page=RTL-RSM&tab=license');
		$check_url   = admin_url('admin.php?page=' . self::PAGE_SLUG . '&mfs-license-check=' . time());
		?>
		<div class="wrap mfs-wrap mfs-license-wrap" dir="rtl">
			<div class="mfs-hero mfs-license-hero">
				<div class="mfs-hero__brand">
					<div class="mfs-hero__mark" aria-hidden="true">
						<img src="<?php echo esc_url(MFS_URL . 'assets/icons/icon-plugin.webp'); ?>" alt="" width="52" height="52">
					</div>
					<div>
						<h1><?php esc_html_e('جستجوی زنده پیشرفته', 'advanced-live-fast-search-wp'); ?></h1>
						<p><?php esc_html_e('مدیریت وضعیت و فعال‌سازی لایسنس راست‌چین', 'advanced-live-fast-search-wp'); ?></p>
					</div>
				</div>
				<div class="mfs-hero__meta">
					<span class="mfs-pill"><?php echo esc_html('نسخه ' . MFS_VERSION); ?></span>
					<span class="mfs-pill mfs-pill--locked"><?php esc_html_e('لایسنس غیرفعال', 'advanced-live-fast-search-wp'); ?></span>
				</div>
			</div>

			<div class="mfs-license-card">
				<div class="mfs-license-card__visual" aria-hidden="true">
					<img src="<?php echo esc_url(MFS_URL . 'assets/icons/look_system.svg'); ?>" alt="" width="230" height="279">
				</div>

				<div class="mfs-license-card__content">
					<span class="mfs-license-eyebrow"><?php esc_html_e('وضعیت دسترسی محصول', 'advanced-live-fast-search-wp'); ?></span>
					<h2><?php esc_html_e('لایسنس این دامنه هنوز فعال نیست', 'advanced-live-fast-search-wp'); ?></h2>
					<p class="mfs-license-lead">
						<?php esc_html_e('برای استفاده از جستجوی زنده، دامنه را در حساب راست‌چین ثبت کنید و سپس وضعیت آن را از بخش مدیریت لایسنس راست‌چین در پیشخوان بررسی نمایید.', 'advanced-live-fast-search-wp'); ?>
					</p>

					<div class="mfs-license-status">
						<div class="mfs-license-status__row">
							<span><?php esc_html_e('دامنه فعلی', 'advanced-live-fast-search-wp'); ?></span>
							<strong dir="ltr"><?php echo esc_html($domain); ?></strong>
						</div>
						<div class="mfs-license-status__row">
							<span><?php esc_html_e('وضعیت لایسنس', 'advanced-live-fast-search-wp'); ?></span>
							<strong class="mfs-license-state mfs-license-state--off">
								<span aria-hidden="true"></span>
								<?php esc_html_e('غیرفعال', 'advanced-live-fast-search-wp'); ?>
							</strong>
						</div>
					</div>

					<div class="mfs-license-actions">
						<a class="mfs-btn mfs-btn--primary mfs-btn--lg" href="<?php echo esc_url($rtl_url); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e('ثبت یا تغییر دامنه در حساب راست‌چین', 'advanced-live-fast-search-wp'); ?>
						</a>
						<div class="mfs-license-actions__secondary">
							<a class="mfs-btn mfs-btn--ghost mfs-btn--lg" href="<?php echo esc_url($rsm_url); ?>">
								<?php esc_html_e('مدیریت لایسنس راست‌چین', 'advanced-live-fast-search-wp'); ?>
							</a>
							<a class="mfs-btn mfs-btn--ghost mfs-btn--lg" href="<?php echo esc_url($check_url); ?>">
								<?php esc_html_e('بررسی مجدد وضعیت', 'advanced-live-fast-search-wp'); ?>
							</a>
						</div>
					</div>

					<div class="mfs-license-steps">
						<div><span>۱</span><p><strong><?php esc_html_e('ثبت دامنه', 'advanced-live-fast-search-wp'); ?></strong><?php esc_html_e('دامنه فعلی را در مدیریت لایسنس محصول در حساب راست‌چین ثبت کنید.', 'advanced-live-fast-search-wp'); ?></p></div>
						<div><span>۲</span><p><strong><?php esc_html_e('مدیریت لایسنس راست‌چین', 'advanced-live-fast-search-wp'); ?></strong><?php esc_html_e('آخرین نسخه افزونه مدیریت هوشمند راست‌چین را نصب و فعال نگه دارید.', 'advanced-live-fast-search-wp'); ?></p></div>
						<div><span>۳</span><p><strong><?php esc_html_e('همگام‌سازی و بررسی', 'advanced-live-fast-search-wp'); ?></strong><?php esc_html_e('در صفحه مدیریت لایسنس راست‌چین، گزینه اشکال‌زدایی یا همگام‌سازی را اجرا کنید.', 'advanced-live-fast-search-wp'); ?></p></div>
					</div>

					<?php if ('inactive' !== $reason) : ?>
						<div class="mfs-license-diagnostic">
							<strong><?php esc_html_e('جزئیات فنی:', 'advanced-live-fast-search-wp'); ?></strong>
							<?php echo esc_html($reason_text); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function reason_text($reason) {
		$messages = array(
			'ioncube_missing' => 'ماژول ionCube Loader روی سرور فعال نیست.',
			'file_missing'    => 'فایل لایسنس راست‌چین در بسته افزونه پیدا نشد.',
			'file_invalid'    => 'صحت فایل لایسنس تأیید نشد؛ فایل را از بسته اصلی جایگزین کنید.',
			'class_missing'   => 'کلاس لایسنس پس از بارگذاری فایل در دسترس قرار نگرفت.',
			'api_invalid'     => 'رابط بررسی وضعیت لایسنس در دسترس نیست.',
			'check_failed'    => 'بررسی وضعیت لایسنس با خطا مواجه شد.',
			'inactive'        => 'لایسنس برای دامنه فعلی فعال نشده است.',
		);

		return isset($messages[$reason]) ? $messages[$reason] : 'وضعیت لایسنس قابل تشخیص نیست.';
	}

	private static function menu_icon() {
		$path = MFS_DIR . 'assets/icons/menu_icon.svg';
		if (!is_readable($path)) {
			return 'dashicons-search';
		}

		$svg = file_get_contents($path);
		if (!is_string($svg) || '' === $svg) {
			return 'dashicons-search';
		}

		$svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg);
		$svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg);
		$svg = preg_replace('/current[Cc]olor/', '#a7aaad', $svg);

		return 'data:image/svg+xml;base64,' . base64_encode(trim($svg));
	}
}
