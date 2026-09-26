<?php
defined('ABSPATH') || exit;

class MFS_Render {

	/** @var bool Panel HTML already printed (avoid double mount). */
	private static $panel_mounted = false;

	/** @var bool A trigger/field requested the panel for this request. */
	private static $needs_panel = false;

	/** @var array Config script printed per skin. */
	private static $config_printed = array();

	public static function init() {
		add_shortcode('mfs_search', array(__CLASS__, 'shortcode'));
		add_action('after_setup_theme', array(__CLASS__, 'declare_compat_functions'));
		add_action('wp_enqueue_scripts', array('MFS_Assets', 'maybe_enqueue_auto'), 20);
		add_action('wp_footer', array(__CLASS__, 'auto_mount_panel'), 5);
	}

	public static function declare_compat_functions() {
		if (!function_exists('payamava_fast_search_field')) {
			function payamava_fast_search_field() {
				mfs_field();
			}
		}

		if (!function_exists('websima_fast_search_field')) {
			function websima_fast_search_field() {
				mfs_field();
			}
		}
	}

	/**
	 * Shortcode: [mfs_search skin="" mode="auto" trigger="yes"]
	 *
	 * mode:
	 * - auto    → overlay: trigger only (panel auto-mounted); inline: full field
	 * - trigger → icon only (overlay panel will auto-mount)
	 * - field   → full inline/field UI (or modal field without trigger)
	 * - panel   → panel shell only
	 * - full    → trigger + panel (legacy one-shot)
	 */
	public static function shortcode($atts) {
		$atts = shortcode_atts(array(
			'skin'    => '',
			'mode'    => 'auto',
			'trigger' => 'yes',
		), $atts, 'mfs_search');

		$skin = $atts['skin'] ? $atts['skin'] : MFS_Plugin::get('skin');
		$mode = strtolower((string) $atts['mode']);

		// Legacy: trigger="no" with mode auto → field/panel without icon.
		if ('auto' === $mode && 'no' === strtolower((string) $atts['trigger'])) {
			$mode = MFS_Plugin::is_overlay_skin($skin) ? 'panel' : 'field';
		}

		return self::render_by_mode($skin, $mode);
	}

	public static function render_by_mode($skin, $mode = 'auto') {
		$skin = $skin ? $skin : MFS_Plugin::get('skin');
		$mode = $mode ? strtolower((string) $mode) : 'auto';

		if ('auto' === $mode) {
			$mode = MFS_Plugin::is_overlay_skin($skin) ? 'trigger' : 'field';
		}

		switch ($mode) {
			case 'trigger':
				return self::get_trigger($skin);
			case 'panel':
				return self::get_panel($skin);
			case 'full':
				return self::get_search($skin, true, true);
			case 'field':
			default:
				if (MFS_Plugin::is_overlay_skin($skin)) {
					// Field mode on overlay ≈ panel without trigger (compat with mfs_field).
					return self::get_panel($skin);
				}
				return self::get_search($skin, false, true);
		}
	}

	public static function get_search($skin = '', $show_trigger = true, $show_panel = true) {
		$skin = $skin ? $skin : MFS_Plugin::get('skin');
		$info = MFS_Plugin::skin_info($skin);

		MFS_Assets::enqueue_skin($skin);

		if ($show_panel && MFS_Plugin::is_overlay_skin($skin)) {
			self::$panel_mounted = true;
		}

		$context = self::context($info, $show_trigger, $show_panel);
		return self::render_template($info, $context);
	}

	public static function get_trigger($skin = '') {
		$skin = $skin ? $skin : MFS_Plugin::get('skin');
		$info = MFS_Plugin::skin_info($skin);

		MFS_Assets::enqueue_skin($skin);
		self::$needs_panel = true;

		// Inline has no separate trigger — fall back to field.
		if (!MFS_Plugin::is_overlay_skin($skin)) {
			return self::get_search($skin, false, true);
		}

		$context = self::context($info, true, false);
		return self::config_script($info['slug']) . self::trigger_markup();
	}

	public static function get_panel($skin = '') {
		$skin = $skin ? $skin : MFS_Plugin::get('skin');
		$info = MFS_Plugin::skin_info($skin);

		if (self::$panel_mounted && MFS_Plugin::is_overlay_skin($skin)) {
			return '';
		}

		MFS_Assets::enqueue_skin($skin);

		if (MFS_Plugin::is_overlay_skin($skin)) {
			self::$panel_mounted = true;
		}

		$context = self::context($info, false, true);
		return self::render_template($info, $context);
	}

	public static function get_field() {
		$skin = MFS_Plugin::get('skin');
		if (MFS_Plugin::is_overlay_skin($skin)) {
			return self::get_panel($skin);
		}
		return self::get_search($skin, false, true);
	}

	/**
	 * Footer auto-mount for overlay skins.
	 * - auto_mount on  → always inject panel (any .search-js works)
	 * - auto_mount off → inject only if a trigger was rendered this request
	 */
	public static function auto_mount_panel() {
		if (self::$panel_mounted) {
			return;
		}

		$skin = MFS_Plugin::get('skin');
		if (!MFS_Plugin::is_overlay_skin($skin)) {
			return;
		}

		$auto = (int) MFS_Plugin::get('auto_mount');
		if (!$auto && !self::$needs_panel) {
			return;
		}

		echo self::get_panel($skin); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	private static function context($info, $show_trigger, $show_panel = true) {
		$settings = MFS_Plugin::settings();

		$sections = array();
		foreach ((array) $settings['post_types'] as $post_type) {
			if (post_type_exists($post_type)) {
				$object = get_post_type_object($post_type);
				$sections[$post_type] = $object ? $object->labels->name : $post_type;
			}
		}

		$terms = array();
		foreach ((array) $settings['taxonomies'] as $taxonomy) {
			if (taxonomy_exists($taxonomy)) {
				$object = get_taxonomy($taxonomy);
				$terms[$taxonomy] = $object ? $object->labels->name : $taxonomy;
			}
		}

		$accent = '';
		$hex    = !empty($settings['accent_color']) ? sanitize_hex_color($settings['accent_color']) : '';
		if ($hex) {
			$r = hexdec(substr($hex, 1, 2));
			$g = hexdec(substr($hex, 3, 2));
			$b = hexdec(substr($hex, 5, 2));
			$accent = sprintf(
				' style="--mfs-accent: %1$s; --mfs-accent-soft: rgba(%2$d, %3$d, %4$d, 0.16); --mfs-accent-soft-strong: rgba(%2$d, %3$d, %4$d, 0.28);"',
				esc_attr($hex),
				$r,
				$g,
				$b
			);
		}

		$placeholder = '' !== trim((string) $settings['placeholder']) ? $settings['placeholder'] : MFS_Plugin::defaults()['placeholder'];

		return array(
			'skin'         => $info['slug'],
			'type'         => $info['type'],
			'skin_url'     => $info['url'],
			'placeholder'  => self::placeholder($placeholder),
			'sections'     => $sections,
			'terms'        => $terms,
			'accent_style' => $accent,
			'trigger'      => (bool) $show_trigger,
			'panel'        => (bool) $show_panel,
		);
	}

	private static function placeholder($text) {
		$info  = MFS_Cache::info();
		$counts = isset($info['counts']['items']) && is_array($info['counts']['items']) ? $info['counts']['items'] : array();
		$total  = array_sum($counts);

		$products = isset($counts['product']) ? $counts['product'] : $total;
		$posts    = isset($counts['post']) ? $counts['post'] : '';

		$text = str_replace('{products}', $products ? number_format_i18n($products) : '', $text);
		$text = str_replace('{posts}', $posts ? number_format_i18n($posts) : '', $text);

		return $text;
	}

	public static function trigger_markup() {
		ob_start();
		?>
		<div class="SearchMobileIcon btn-search justDeskop search-js mfs-trigger" role="button" tabindex="0"
			aria-label="<?php esc_attr_e('جستجو', 'advanced-live-fast-search-wp'); ?>">
			<?php echo MFS_Icons::search_svg(24); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private static function render_template($info, $context) {
		$theme_template = locate_template('advanced-live-fast-search/' . $info['slug'] . '/template.php');
		$mfs = $context;

		if ($theme_template) {
			ob_start();
			include $theme_template;
			$html = ob_get_clean();
		} else {
			$file = $info['dir'] . '/template.php';
			if (!file_exists($file)) {
				return '';
			}
			ob_start();
			include $file;
			$html = ob_get_clean();
		}

		return self::config_script($info['slug']) . $html;
	}

	private static function config_script($skin) {
		if (isset(self::$config_printed[$skin])) {
			return '';
		}
		self::$config_printed[$skin] = true;

		$config         = MFS_Assets::config($skin);
		$config['file'] = esc_url_raw(home_url('/search-data.json'));

		return '<script>window.mfsConfig = ' . wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';</script>';
	}
}

function mfs_search($args = array()) {
	$args = wp_parse_args($args, array(
		'skin'    => '',
		'mode'    => 'auto',
		'trigger' => true,
	));

	$mode = $args['mode'];
	if ('auto' === $mode && empty($args['trigger'])) {
		$mode = 'field';
	}

	echo MFS_Render::render_by_mode($args['skin'], $mode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function mfs_field() {
	echo MFS_Render::get_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function mfs_trigger($skin = '') {
	echo MFS_Render::get_trigger($skin); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
