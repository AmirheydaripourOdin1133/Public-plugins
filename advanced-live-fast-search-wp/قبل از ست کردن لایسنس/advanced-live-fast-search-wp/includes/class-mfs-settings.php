<?php
defined('ABSPATH') || exit;

class MFS_Settings {

	const PAGE_SLUG = 'mfs-settings';

	public static function init() {
		add_action('admin_menu', array(__CLASS__, 'register_menu'));
		add_action('admin_init', array(__CLASS__, 'register_settings'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
	}

	public static function register_menu() {
		add_menu_page(
			'جستجوی زنده پیشرفته',
			'جستجوی زنده',
			'manage_options',
			self::PAGE_SLUG,
			array(__CLASS__, 'render_page'),
			MFS_Icons::plugin_icon_menu(),
			58
		);
	}

	public static function register_settings() {
		register_setting('mfs_settings_group', MFS_OPTION, array(
			'sanitize_callback' => array(__CLASS__, 'sanitize'),
		));

		add_settings_section('mfs_sources', '', array(__CLASS__, 'section_sources_intro'), 'mfs_tab_sources');
		add_settings_field('mfs_field_post_types', 'انواع محتوا', array(__CLASS__, 'field_post_types'), 'mfs_tab_sources', 'mfs_sources');
		add_settings_field('mfs_field_taxonomies', 'طبقه‌بندی‌ها', array(__CLASS__, 'field_taxonomies'), 'mfs_tab_sources', 'mfs_sources');

		add_settings_section('mfs_appearance', '', array(__CLASS__, 'section_appearance_intro'), 'mfs_tab_appearance');
		add_settings_field('mfs_field_skin', 'سبک نمایش جستجو', array(__CLASS__, 'field_skin'), 'mfs_tab_appearance', 'mfs_appearance');
		add_settings_field('mfs_field_icon_pack', 'آیکون جستجو', array(__CLASS__, 'field_icon_pack'), 'mfs_tab_appearance', 'mfs_appearance');
		add_settings_field('mfs_field_auto_mount', 'نصب خودکار پنل', array(__CLASS__, 'field_auto_mount'), 'mfs_tab_appearance', 'mfs_appearance');
		add_settings_field('mfs_field_placeholder', 'متن Placeholder', array(__CLASS__, 'field_placeholder'), 'mfs_tab_appearance', 'mfs_appearance');
		add_settings_field('mfs_field_accent', 'رنگ اکسنت', array(__CLASS__, 'field_accent'), 'mfs_tab_appearance', 'mfs_appearance');

		add_settings_section('mfs_results', '', array(__CLASS__, 'section_results_intro'), 'mfs_tab_results');
		add_settings_field('mfs_field_limit_items', 'سقف نتایج محتوا', array(__CLASS__, 'field_limit_items'), 'mfs_tab_results', 'mfs_results');
		add_settings_field('mfs_field_limit_terms', 'سقف نتایج طبقه‌بندی', array(__CLASS__, 'field_limit_terms'), 'mfs_tab_results', 'mfs_results');

		add_settings_section('mfs_cache', '', array(__CLASS__, 'section_cache_intro'), 'mfs_tab_cache');
		add_settings_field('mfs_field_cache_ttl', 'مدت اعتبار کش', array(__CLASS__, 'field_cache_ttl'), 'mfs_tab_cache', 'mfs_cache');
	}

	public static function sanitize($input) {
		$defaults = MFS_Plugin::defaults();
		$prev     = (array) get_option(MFS_OPTION, array());
		$prev     = wp_parse_args($prev, $defaults);
		$raw      = (array) $input;
		$input    = wp_parse_args($raw, $defaults);
		$clean    = array();

		$skins         = array_keys(MFS_Plugin::skins());
		$clean['skin'] = in_array($input['skin'], $skins, true) ? $input['skin'] : $defaults['skin'];

		// Checkbox: missing key means off (do not fall back to default via parse_args).
		$clean['auto_mount'] = !empty($raw['auto_mount']) ? 1 : 0;

		$post_types          = MFS_Plugin::available_post_types();
		$clean['post_types'] = array();
		$raw_types           = isset($raw['post_types']) ? (array) $raw['post_types'] : array();
		foreach ($raw_types as $post_type) {
			if (isset($post_types[$post_type])) {
				$clean['post_types'][] = $post_type;
			}
		}

		$taxonomies          = MFS_Plugin::available_taxonomies();
		$clean['taxonomies'] = array();
		$raw_taxes           = isset($raw['taxonomies']) ? (array) $raw['taxonomies'] : array();
		foreach ($raw_taxes as $taxonomy) {
			if (isset($taxonomies[$taxonomy])) {
				$clean['taxonomies'][] = $taxonomy;
			}
		}

		$clean['placeholder']  = sanitize_text_field($input['placeholder']);
		$clean['accent_color'] = sanitize_hex_color($input['accent_color']);
		if (!$clean['accent_color']) {
			$clean['accent_color'] = '';
		}
		$packs = array_keys(MFS_Icons::packs());
		$clean['icon_pack'] = in_array($input['icon_pack'], $packs, true) ? $input['icon_pack'] : $defaults['icon_pack'];
		$clean['limit_items'] = min(100, max(1, absint($input['limit_items'])));
		$clean['limit_terms'] = min(100, max(1, absint($input['limit_terms'])));
		$clean['cache_ttl']   = min(604800, max(300, absint($input['cache_ttl'])));

		$sources_changed = ($clean['post_types'] !== (array) $prev['post_types'])
			|| ($clean['taxonomies'] !== (array) $prev['taxonomies']);

		if ($sources_changed) {
			MFS_Cache::schedule_refresh();
		}

		return $clean;
	}

	public static function enqueue_admin_assets($hook) {
		if ('toplevel_page_' . self::PAGE_SLUG !== $hook) {
			return;
		}

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_style('mfs-admin', MFS_URL . 'assets/admin.css', array(), MFS_VERSION);
		wp_enqueue_script('wp-color-picker');
		wp_enqueue_script('mfs-admin', MFS_URL . 'assets/admin.js', array('jquery'), MFS_VERSION, true);

		wp_localize_script('mfs-admin', 'mfsAdmin', array(
			'rest'  => esc_url_raw(rest_url(MFS_Rest::NAMESPACE_V1)),
			'nonce' => wp_create_nonce('wp_rest'),
			'error' => 'عملیات ناموفق بود؛ دوباره تلاش کنید.',
		));

		wp_add_inline_script('mfs-admin', 'jQuery(function($){$(".mfs-color-field").wpColorPicker();});', 'after');
	}

	public static function render_page() {
		$info     = MFS_Cache::info();
		$skin     = MFS_Plugin::get('skin');
		$skins    = MFS_Plugin::skins();
		$skin_lbl = isset($skins[$skin]['label']) ? $skins[$skin]['label'] : $skin;
		$is_overlay = MFS_Plugin::is_overlay_skin($skin);
		?>
		<div class="wrap mfs-wrap" dir="rtl">

			<div class="mfs-hero">
				<div class="mfs-hero__brand">
					<div class="mfs-hero__mark" aria-hidden="true">
						<?php
						$mfs_mark = MFS_Icons::plugin_icon_img(32, 'mfs-hero__logo');
						if ($mfs_mark) {
							echo $mfs_mark; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
						}
						?>
					</div>
					<div>
						<h1>جستجوی زنده پیشرفته</h1>
						<p>کش سمت سرور + جستجوی آنی سمت کاربر — آماده فروشگاه‌های وردپرسی</p>
					</div>
				</div>
				<div class="mfs-hero__meta">
					<span class="mfs-pill">نسخه <?php echo esc_html(MFS_VERSION); ?></span>
					<span class="mfs-pill mfs-pill--soft"><?php echo esc_html($skin_lbl); ?></span>
				</div>
			</div>

			<?php settings_errors(); ?>

			<nav class="mfs-tabs" aria-label="بخش‌های تنظیمات">
				<a href="#dashboard" class="mfs-tab is-active" data-tab="dashboard"><span class="dashicons dashicons-dashboard"></span>پیشخوان</a>
				<a href="#sources" class="mfs-tab" data-tab="sources"><span class="dashicons dashicons-archive"></span>منابع</a>
				<a href="#appearance" class="mfs-tab" data-tab="appearance"><span class="dashicons dashicons-admin-appearance"></span>ظاهر</a>
				<a href="#results" class="mfs-tab" data-tab="results"><span class="dashicons dashicons-list-view"></span>نتایج</a>
				<a href="#cache" class="mfs-tab" data-tab="cache"><span class="dashicons dashicons-database"></span>کش</a>
				<a href="#help" class="mfs-tab" data-tab="help"><span class="dashicons dashicons-editor-help"></span>راهنما</a>
			</nav>

			<div class="mfs-panel is-active" id="mfs-tab-dashboard">
				<div class="mfs-grid mfs-grid--2">
					<div class="mfs-card">
						<div class="mfs-card__head">
							<span class="dashicons dashicons-database"></span>
							<div>
								<strong>وضعیت کش</strong>
								<span>ایندکس زنده داده‌های جستجو</span>
							</div>
						</div>
						<div class="mfs-card__body">
							<?php if (MFS_Cache::exists()) : ?>
								<div class="mfs-status-row">
									<span class="mfs-badge mfs-badge--ok">فعال</span>
									<span>آخرین بازسازی: <strong><?php echo esc_html(human_time_diff($info['file_time']) . ' پیش'); ?></strong></span>
									<span>حجم: <strong><?php echo esc_html(size_format($info['file_size'])); ?></strong></span>
								</div>
								<?php $counts = isset($info['counts']) && is_array($info['counts']) ? $info['counts'] : array(); ?>
								<div class="mfs-stats">
									<?php foreach ((array) (isset($counts['items']) ? $counts['items'] : array()) as $type => $count) : ?>
										<div class="mfs-stat">
											<span class="mfs-stat__num"><?php echo esc_html(number_format_i18n($count)); ?></span>
											<span class="mfs-stat__label"><?php echo esc_html(self::label_for_post_type($type)); ?></span>
										</div>
									<?php endforeach; ?>
									<?php foreach ((array) (isset($counts['terms']) ? $counts['terms'] : array()) as $tax => $count) : ?>
										<div class="mfs-stat">
											<span class="mfs-stat__num"><?php echo esc_html(number_format_i18n($count)); ?></span>
											<span class="mfs-stat__label"><?php echo esc_html(self::label_for_taxonomy($tax)); ?></span>
										</div>
									<?php endforeach; ?>
								</div>
								<?php if (!MFS_Cache::is_fresh()) : ?>
									<p class="mfs-note mfs-note--warn"><span class="dashicons dashicons-info"></span><span class="mfs-note__text">مدت اعتبار کش (TTL) گذشته است؛ این یعنی فقط نشانگر تازگی است و جستجو همچنان با دادهٔ فعلی کار می‌کند. بازسازی خودکار با <strong>ویرایش/افزودن/حذف محتوا</strong> یا دکمهٔ زیر انجام می‌شود — نه صرفاً با گذشت زمان.</span></p>
								<?php endif; ?>
							<?php else : ?>
								<p class="mfs-note mfs-note--warn"><span class="mfs-badge mfs-badge--warn">بدون کش</span><span class="mfs-note__text"> هنوز کشی ساخته نشده.</span></p>
							<?php endif; ?>
							<div class="mfs-actions">
								<button type="button" class="mfs-btn mfs-btn--primary" id="mfs-rebuild"><span class="dashicons dashicons-update"></span> بازسازی کش</button>
								<button type="button" class="mfs-btn mfs-btn--ghost" id="mfs-clear"><span class="dashicons dashicons-trash"></span> پاک‌کردن</button>
								<span class="spinner"></span>
							</div>
							<p class="mfs-muted">REST: <code dir="ltr"><?php echo esc_html($info['rest_url']); ?></code></p>
						</div>
					</div>

					<div class="mfs-card">
						<div class="mfs-card__head">
							<span class="dashicons dashicons-editor-code"></span>
							<div>
								<strong>فراخوانی سریع</strong>
								<span><?php echo $is_overlay ? 'سبک overlay — فقط آیکون کافی است' : 'سبک اینپوت — فیلد همان‌جا می‌نشیند'; ?></span>
							</div>
						</div>
						<div class="mfs-card__body">
							<div class="mfs-code-row">
								<code dir="ltr" id="mfs-shortcode-code">[mfs_search]</code>
								<button type="button" class="mfs-btn mfs-btn--sm" id="mfs-copy-shortcode">کپی</button>
							</div>
							<ul class="mfs-mini-list">
								<li><code dir="ltr">[mfs_search mode="trigger"]</code> — فقط آیکون</li>
								<li><code dir="ltr">[mfs_search mode="field"]</code> — اینپوت خطی</li>
								<li><code dir="ltr">[mfs_search skin="top-panel"]</code> — سبک موقت</li>
								<li><code dir="ltr">[mfs_search skin="split-panel"]</code> — پنل دوستونه</li>
							</ul>
							<?php if (did_action('elementor/loaded')) : ?>
								<p class="mfs-note mfs-note--ok">ویجت Elementor «جستجوی زنده» در دستهٔ همین نام فعال است.</p>
							<?php else : ?>
								<p class="mfs-muted">با نصب Elementor، ویجت اختصاصی جستجو هم در دسترس قرار می‌گیرد.</p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="mfs-card mfs-card--placement">
					<div class="mfs-card__head">
						<span class="dashicons dashicons-location"></span>
						<div>
							<strong>مدل نصب (بدون سردرگمی)</strong>
							<span>مودال و تاپ‌پنل مثل هم کار می‌کنند</span>
						</div>
					</div>
					<div class="mfs-card__body">
						<div class="mfs-steps-row">
							<div class="mfs-step">
								<span class="mfs-step__n">۱</span>
								<strong>سبک نمایش را انتخاب کنید</strong>
								<p>مودال یا تاپ‌پنل = آیکون؛ خطی / پنل دوستونه = اینپوت در محل شورت‌کد.</p>
							</div>
							<div class="mfs-step">
								<span class="mfs-step__n">۲</span>
								<strong>آیکون را بگذارید</strong>
								<p>شورت‌کد یا ویجت Elementor در هدر — یا کلاس <code>search-js</code> روی المان خودتان.</p>
							</div>
							<div class="mfs-step">
								<span class="mfs-step__n">۳</span>
								<strong>پنل خودکار است</strong>
								<p>با «نصب خودکار پنل» دیگر نیازی به گذاشتن شورت‌کد در فوتر نیست.</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<form method="post" action="options.php" id="mfs-settings-form">
				<?php settings_fields('mfs_settings_group'); ?>

				<div class="mfs-panel" id="mfs-tab-sources">
					<div class="mfs-card">
						<div class="mfs-card__head">
							<span class="dashicons dashicons-archive"></span>
							<div><strong>منابع جستجو</strong><span>چه چیزهایی در نتایج بیایند</span></div>
						</div>
						<div class="mfs-card__body mfs-card__body--form">
							<?php do_settings_sections('mfs_tab_sources'); ?>
						</div>
					</div>
				</div>

				<div class="mfs-panel" id="mfs-tab-appearance">
					<div class="mfs-card">
						<div class="mfs-card__head">
							<span class="dashicons dashicons-admin-appearance"></span>
							<div><strong>ظاهر و سبک نمایش</strong><span>نمایش جستجو در فروشگاه</span></div>
						</div>
						<div class="mfs-card__body mfs-card__body--form">
							<?php do_settings_sections('mfs_tab_appearance'); ?>
						</div>
					</div>
				</div>

				<div class="mfs-panel" id="mfs-tab-results">
					<div class="mfs-card">
						<div class="mfs-card__head">
							<span class="dashicons dashicons-list-view"></span>
							<div><strong>سقف نتایج</strong><span>تعداد آیتم در هر بخش</span></div>
						</div>
						<div class="mfs-card__body mfs-card__body--form">
							<?php do_settings_sections('mfs_tab_results'); ?>
						</div>
					</div>
				</div>

				<div class="mfs-panel" id="mfs-tab-cache">
					<div class="mfs-card">
						<div class="mfs-card__head">
							<span class="dashicons dashicons-performance"></span>
							<div><strong>کش و بهینه‌سازی</strong><span>ایندکس JSON و زمان‌بندی بازسازی</span></div>
						</div>
						<div class="mfs-card__body mfs-card__body--form">
							<?php do_settings_sections('mfs_tab_cache'); ?>
						</div>
					</div>
				</div>

				<div class="mfs-savebar">
					<button type="submit" class="mfs-btn mfs-btn--primary mfs-btn--lg">ذخیرهٔ تنظیمات</button>
					<span class="mfs-muted">تغییر منابع، بازسازی کش را خودکار زمان‌بندی می‌کند.</span>
				</div>
			</form>

			<div class="mfs-panel" id="mfs-tab-help">
				<?php
				$rtl_logo     = self::rtl_logo_url();
				$author_url   = apply_filters('mfs_author_url', 'https://wp-amir.ir');
				$rtl_shop_url = apply_filters('mfs_author_rtl_products_url', 'https://www.rtl-theme.com/author/amirheydaripur/');
				?>
				<div class="mfs-card">
					<div class="mfs-card__head">
						<span class="dashicons dashicons-rocket"></span>
						<div><strong>شروع سریع</strong><span>۴ قدم</span></div>
					</div>
					<div class="mfs-card__body">
						<ol class="mfs-ol">
							<li>منابع جستجو را تیک بزنید و ذخیره کنید.</li>
							<li>از پیشخوان «بازسازی کش» را بزنید.</li>
							<li>شورت‌کد <code dir="ltr">[mfs_search]</code> یا ویجت Elementor را در هدر بگذارید.</li>
							<li>برای جستجوهای پرطرفدار، منو را به جایگاه مربوط وصل کنید.</li>
						</ol>
					</div>
				</div>

				<div class="mfs-card">
					<div class="mfs-card__head">
						<span class="dashicons dashicons-admin-customizer"></span>
						<div>
							<strong>دکمه / تریگر اختصاصی خودتان</strong>
							<span>بدون شورت‌کد؛ فقط یک کلاس روی المان</span>
						</div>
					</div>
					<div class="mfs-card__body">
						<p>اگر نمی‌خواهید آیکون پیش‌فرض افزونه را بگذارید و ترجیح می‌دهید <strong>دکمه، لینک یا آیکون خودتان</strong> (در هدر قالب، Elementor، یا هر المان HTML) جستجو را باز کند، کافی است کلاس زیر را به همان المان بدهید:</p>
						<p class="mfs-code-row"><code dir="ltr">search-js</code></p>
						<ul class="mfs-mini-list">
							<li>روی دکمه، لینک، <code dir="ltr">&lt;div&gt;</code> یا هر المان کلیک‌پذیر کلاس <code dir="ltr">search-js</code> را اضافه کنید.</li>
							<li>سبک نمایش باید <strong>مودال</strong> یا <strong>تاپ‌پنل</strong> باشد و گزینهٔ «نصب خودکار پنل» روشن باشد تا پنل در فوتر لود شود.</li>
							<li>با کلیک روی آن المان، همان پنل جستجوی زنده باز می‌شود — ظاهر دکمه کاملاً مال خودتان است؛ فقط رفتار بازشدن از افزونه می‌آید.</li>
							<li>کلاس کمکی <code dir="ltr">mfs-trigger</code> هم همان کار را می‌کند؛ اگر هر دو را بگذارید اشکالی ندارد.</li>
						</ul>
						<p class="description mfs-muted">مثال: در Elementor روی دکمه → Advanced → CSS Classes → بنویسید <code dir="ltr">search-js</code></p>
					</div>
				</div>

				<div class="mfs-card mfs-card--support">
					<div class="mfs-card__head">
						<span class="dashicons dashicons-heart"></span>
						<div><strong>حمایت از توسعه‌دهنده</strong><span>دربارهٔ این محصول</span></div>
					</div>
					<div class="mfs-card__body">
						<div class="mfs-dev-support">
							<div class="mfs-dev-support__icon" aria-hidden="true">
								<?php
								$mfs_help_icon = MFS_Icons::plugin_icon_img(80, 'mfs-dev-support__logo');
								if ($mfs_help_icon) {
									echo $mfs_help_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo '<span class="dashicons dashicons-search"></span>';
								}
								?>
							</div>
							<div class="mfs-dev-support__text">
								<p><strong>جستجوی زنده پیشرفته</strong> تجربهٔ جستجوی آنی در فروشگاه وردپرس/ووکامرس شماست؛ با کش JSON و جستجوی سمت مرورگر، بدون تأخیر در هر کلید.</p>
								<p>چهار سبک نمایش مودال، خطی، تاپ‌پنل و پنل دوستونه، ویجت Elementor و پنل تنظیمات فارسی — برای فروشگاه‌هایی که سرعت و UX حرفه‌ای می‌خواهند.</p>
								<p class="mfs-dev-support__links">
									<a class="mfs-btn mfs-btn--ghost mfs-btn--sm" href="<?php echo esc_url($author_url); ?>" target="_blank" rel="noopener noreferrer">وب‌سایت توسعه‌دهنده</a>
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="mfs-card mfs-card--rtl-store">
					<div class="mfs-card__body mfs-rtl-products">
						<h3 class="mfs-rtl-products__title">
							<span>دیگر محصولات ما در</span>
							<?php if ($rtl_logo) : ?>
								<img class="mfs-rtl-products__logo" src="<?php echo esc_url($rtl_logo); ?>" alt="راست‌چین" width="120" height="40" loading="lazy">
							<?php else : ?>
								<span class="mfs-rtl-products__fallback">راست‌چین</span>
							<?php endif; ?>
						</h3>
						<p class="mfs-muted">قالب‌ها و افزونه‌های بیشتر برای راه‌اندازی و رشد سایت وردپرسی شما.</p>
						<p>
							<a class="mfs-btn mfs-btn--primary" href="<?php echo esc_url($rtl_shop_url); ?>" target="_blank" rel="noopener noreferrer">مشاهدهٔ محصولات</a>
						</p>
						<p class="mfs-help-copyright">
							© <?php echo esc_html(gmdate('Y')); ?> Amir Heydaripur — Advanced Live Fast Search — نسخه <?php echo esc_html(MFS_VERSION); ?> — تمامی حقوق محفوظ است.
						</p>
					</div>
				</div>
			</div>

		</div>
		<?php
	}

	private static function label_for_post_type($name) {
		$types = MFS_Plugin::available_post_types();
		return isset($types[$name]) ? $types[$name] : $name;
	}

	private static function label_for_taxonomy($name) {
		$taxes = MFS_Plugin::available_taxonomies();
		return isset($taxes[$name]) ? $taxes[$name] : $name;
	}

	public static function section_sources_intro() {
		echo '<p class="description">پست‌تایپ‌ها و طبقه‌بندی‌های قابل جستجو را انتخاب کنید. هر مورد بخش جدا در نتایج می‌شود.</p>';
	}

	public static function field_post_types() {
		$selected = (array) MFS_Plugin::get('post_types');
		echo '<div class="mfs-checks">';
		foreach (MFS_Plugin::available_post_types() as $name => $label) {
			printf(
				'<label><input type="checkbox" name="%1$s[post_types][]" value="%2$s" %3$s><span class="mfs-check-label">%4$s</span><code>%2$s</code></label>',
				esc_attr(MFS_OPTION),
				esc_attr($name),
				checked(in_array($name, $selected, true), true, false),
				esc_html($label)
			);
		}
		echo '</div>';
	}

	public static function field_taxonomies() {
		$selected = (array) MFS_Plugin::get('taxonomies');
		echo '<div class="mfs-checks">';
		foreach (MFS_Plugin::available_taxonomies() as $name => $label) {
			printf(
				'<label><input type="checkbox" name="%1$s[taxonomies][]" value="%2$s" %3$s><span class="mfs-check-label">%4$s</span><code>%2$s</code></label>',
				esc_attr(MFS_OPTION),
				esc_attr($name),
				checked(in_array($name, $selected, true), true, false),
				esc_html($label)
			);
		}
		echo '</div>';
	}

	public static function section_appearance_intro() {
		echo '<p class="description">سبک نمایش، ساختار ظاهر جستجو در سایت است. مودال و تاپ‌پنل با نصب خودکار پنل کار می‌کنند؛ خطی و پنل دوستونه اینپوت را همان‌جا نشان می‌دهند.</p>';
	}

	public static function field_skin() {
		$current = MFS_Plugin::get('skin');
		echo '<div class="mfs-skins">';
		foreach (MFS_Plugin::skins() as $slug => $skin) {
			$desc = isset($skin['desc']) ? $skin['desc'] : '';
			printf(
				'<label class="mfs-skin-card"><input type="radio" name="%1$s[skin]" value="%2$s" %3$s><span class="mfs-skin-card__body"><span class="mfs-skin-name">%4$s</span><code>%2$s</code><span class="mfs-skin-desc">%5$s</span></span></label>',
				esc_attr(MFS_OPTION),
				esc_attr($slug),
				checked($current, $slug, false),
				esc_html($skin['label']),
				esc_html($desc)
			);
		}
		echo '</div>';
	}

	public static function field_icon_pack() {
		$current = MFS_Icons::current_pack();
		echo '<div class="mfs-icon-packs">';
		foreach (MFS_Icons::packs() as $slug => $pack) {
			printf(
				'<label class="mfs-icon-pack"><input type="radio" name="%1$s[icon_pack]" value="%2$s" %3$s><span class="mfs-icon-pack__body"><span class="mfs-icon-pack__preview" aria-hidden="true">%4$s</span><span class="mfs-icon-pack__name">%5$s</span><code>%2$s</code></span></label>',
				esc_attr(MFS_OPTION),
				esc_attr($slug),
				checked($current, $slug, false),
				MFS_Icons::pack_preview_svg($slug, 28), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html($pack['label'])
			);
		}
		echo '</div>';
		echo '<p class="description">همین آیکون روی دکمهٔ تریگر (مودال/تاپ‌پنل) و ذره‌بین داخل اینپوت (خطی/دوستونه) اعمال می‌شود. رنگ از <code>currentColor</code> و اکسنت قالب می‌آید.</p>';
	}

	public static function field_auto_mount() {
		printf(
			'<label class="mfs-switch"><input type="checkbox" name="%1$s[auto_mount]" value="1" %2$s><span class="mfs-switch__ui"></span><span class="mfs-switch__label">نصب خودکار پنل جستجو در فوتر (برای مودال و تاپ‌پنل)</span></label><p class="description">روشن باشد تا فقط با آیکون/شورت‌کد در هدر کار کند و لازم نباشد چیزی در فوتر بگذارید.</p>',
			esc_attr(MFS_OPTION),
			checked((int) MFS_Plugin::get('auto_mount'), 1, false)
		);
	}

	public static function field_placeholder() {
		printf(
			'<input type="text" class="regular-text" name="%1$s[placeholder]" value="%2$s" placeholder="%3$s"><p class="description">شمارنده زنده: <code>{products}</code> و <code>{posts}</code></p>',
			esc_attr(MFS_OPTION),
			esc_attr(MFS_Plugin::get('placeholder')),
			esc_attr__('جستجو در سایت', 'advanced-live-fast-search-wp')
		);
	}

	public static function field_accent() {
		printf(
			'<input type="text" class="mfs-color-field" data-default-color="#8ed557" name="%1$s[accent_color]" value="%2$s"><p class="description">خالی = رنگ پیش‌فرض سبک نمایش. روی چیپ دسته‌ها و هایلایت‌ها اعمال می‌شود. پیشنهاد: <code>#8ed557</code></p>',
			esc_attr(MFS_OPTION),
			esc_attr(MFS_Plugin::get('accent_color'))
		);
	}

	public static function section_results_intro() {
		echo '<p class="description">حداکثر تعداد نتایج هر بخش در پنجرهٔ جستجو.</p>';
	}

	public static function field_limit_items() {
		printf('<input type="number" min="1" max="100" name="%1$s[limit_items]" value="%2$s" class="small-text"> <span class="description">آیتم برای هر پست‌تایپ</span>', esc_attr(MFS_OPTION), esc_attr(MFS_Plugin::get('limit_items')));
	}

	public static function field_limit_terms() {
		printf('<input type="number" min="1" max="100" name="%1$s[limit_terms]" value="%2$s" class="small-text"> <span class="description">آیتم برای هر طبقه‌بندی</span>', esc_attr(MFS_OPTION), esc_attr(MFS_Plugin::get('limit_terms')));
	}

	public static function section_cache_intro() {
		echo '<div class="mfs-cache-help">';
		echo '<p class="description"><strong>کش چطور کار می‌کند؟</strong> نتایج جستجوی زنده از یک فایل ایندکس (JSON) خوانده می‌شوند تا سرعت بالا بماند. این فایل تا وقتی محتوا عوض نشده باشد، قابل استفاده است.</p>';
		echo '<ul class="mfs-mini-list">';
		echo '<li><strong>بازسازی خودکار دقیقاً به تغییر محتوا وابسته است:</strong> وقتی نوشته، محصول یا دستهٔ انتخاب‌شده در منابع ذخیره، منتشر، ویرایش یا حذف شود، بازسازی کش حدود چند ثانیه بعد زمان‌بندی می‌شود (از طریق WP-Cron).</li>';
		echo '<li><strong>مدت اعتبار (TTL) تایمر بازسازی نیست.</strong> فقط مشخص می‌کند تا چه زمانی کش «تازه» محسوب شود. بعد از TTL، دادهٔ قبلی همچنان سرو می‌شود و جستجو قطع نمی‌شود؛ فقط در داشبورد ممکن است اخطار تازگی دیده شود.</li>';
		echo '<li><strong>دکمهٔ «بازسازی کش»</strong> در داشبورد برای ساخت فوری ایندکس است (مثلاً بعد از ایمپورت انبوه یا وقتی Cron روی هاست کم‌بازدید دیر اجرا شود).</li>';
		echo '<li>تغییر منابع جستجو در تنظیمات هم بازسازی را زمان‌بندی می‌کند.</li>';
		echo '</ul>';
		echo '<p class="description mfs-muted">پیشنهاد: برای فروشگاه معمولی همان ۱۸۰۰ ثانیه (۳۰ دقیقه) کافی است. عدد خیلی کوچک فقط اخطار تازگی را زودتر نشان می‌دهد و لزوماً بازسازی بیشتری ایجاد نمی‌کند.</p>';
		echo '</div>';
	}

	public static function field_cache_ttl() {
		printf(
			'<input type="number" min="300" max="604800" step="60" name="%1$s[cache_ttl]" value="%2$s" class="small-text"> <span class="description">ثانیه — پیش‌فرض ۱۸۰۰ (۳۰ دقیقه)</span><p class="description">این مقدار فقط «نشانگر تازگی» است؛ بازنویسی کش با ویرایش محتوا انجام می‌شود، نه با تمام شدن این زمان به‌تنهایی.</p>',
			esc_attr(MFS_OPTION),
			esc_attr(MFS_Plugin::get('cache_ttl'))
		);
	}

	/**
	 * مسیر پوشهٔ آیکون‌های ادمین.
	 *
	 * @return string
	 */
	private static function admin_icons_dir() {
		return trailingslashit(MFS_DIR . 'assets/admin/icons');
	}

	/**
	 * URL لوگوی راست‌چین از assets/admin/icons (فایلی که rtl در نام دارد، یا تنها فایل موجود).
	 *
	 * @return string
	 */
	private static function rtl_logo_url() {
		$dir = self::admin_icons_dir();
		if (!is_dir($dir)) {
			return '';
		}

		$files = self::list_admin_icon_files($dir);
		if (empty($files)) {
			return '';
		}

		foreach ($files as $basename) {
			if (false !== stripos($basename, 'rtl')) {
				return trailingslashit(MFS_URL . 'assets/admin/icons') . rawurlencode($basename);
			}
		}

		if (1 === count($files)) {
			return trailingslashit(MFS_URL . 'assets/admin/icons') . rawurlencode($files[0]);
		}

		return '';
	}

	/**
	 * URL آیکون محصول/افزونه — اولویت با assets/icons/icon-plugin.svg
	 *
	 * @return string
	 */
	private static function product_icon_url() {
		$primary = MFS_Icons::plugin_icon_url();
		if ($primary) {
			return $primary;
		}

		$dir = self::admin_icons_dir();
		if (!is_dir($dir)) {
			return '';
		}

		$preferred = array(
			'plugin-icon.png',
			'plugin-icon.jpg',
			'plugin-icon.webp',
			'icon.png',
			'icon.jpg',
			'product-icon.png',
			'product.png',
		);

		foreach ($preferred as $name) {
			if (is_readable($dir . $name)) {
				return trailingslashit(MFS_URL . 'assets/admin/icons') . rawurlencode($name);
			}
		}

		foreach (self::list_admin_icon_files($dir) as $basename) {
			if (false !== stripos($basename, 'rtl')) {
				continue;
			}
			return trailingslashit(MFS_URL . 'assets/admin/icons') . rawurlencode($basename);
		}

		return '';
	}

	/**
	 * @param string $dir Absolute path.
	 * @return string[] Basenames.
	 */
	private static function list_admin_icon_files($dir) {
		$out   = array();
		$glob  = glob(trailingslashit($dir) . '*');
		if (!is_array($glob)) {
			return $out;
		}
		foreach ($glob as $path) {
			if (!is_file($path)) {
				continue;
			}
			$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
			if (!in_array($ext, array('png', 'jpg', 'jpeg', 'webp', 'svg'), true)) {
				continue;
			}
			$out[] = basename($path);
		}
		return $out;
	}
}
