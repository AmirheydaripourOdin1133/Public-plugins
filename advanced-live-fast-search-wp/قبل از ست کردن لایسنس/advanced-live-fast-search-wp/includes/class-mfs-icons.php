<?php
/**
 * Shared SVG icons (search packs + UI).
 */
defined('ABSPATH') || exit;

class MFS_Icons {

	const SEARCH_DIR = 'assets/icons/search';

	/**
	 * Brand / plugin icon (assets/icons/icon-plugin.svg).
	 */
	public static function plugin_icon_path() {
		return MFS_DIR . 'assets/icons/icon-plugin.svg';
	}

	public static function plugin_icon_url() {
		$path = self::plugin_icon_path();
		if (!is_readable($path)) {
			return '';
		}
		return MFS_URL . 'assets/icons/icon-plugin.svg';
	}

	/**
	 * Base64 data-URI for WP admin menu (dashicon slot).
	 */
	public static function plugin_icon_menu() {
		$path = self::plugin_icon_path();
		if (!is_readable($path)) {
			return 'dashicons-search';
		}
		$raw = file_get_contents($path);
		if (!is_string($raw) || '' === $raw) {
			return 'dashicons-search';
		}
		return 'data:image/svg+xml;base64,' . base64_encode($raw);
	}

	/**
	 * <img> markup for admin UI.
	 *
	 * @param int    $size Pixel width/height.
	 * @param string $class Extra CSS class.
	 */
	public static function plugin_icon_img($size = 80, $class = '') {
		$url = self::plugin_icon_url();
		if ('' === $url) {
			return '';
		}
		$size  = max(16, min(256, (int) $size));
		$class = trim('mfs-plugin-icon ' . $class);
		return sprintf(
			'<img class="%1$s" src="%2$s" alt="" width="%3$d" height="%3$d" loading="lazy" decoding="async">',
			esc_attr($class),
			esc_url($url),
			$size
		);
	}

	/**
	 * Available search icon packs (files in assets/icons/search/).
	 *
	 * @return array<string, array{label:string,file:string}>
	 */
	public static function packs() {
		return apply_filters('mfs_icon_packs', array(
			'search-1' => array(
				'label' => 'سبک ۱',
				'file'  => 'search-1.svg',
			),
			'search-2' => array(
				'label' => 'سبک ۲',
				'file'  => 'search-2.svg',
			),
			'search-3' => array(
				'label' => 'سبک ۳',
				'file'  => 'search-3.svg',
			),
			'search-4' => array(
				'label' => 'سبک ۴',
				'file'  => 'search-4.svg',
			),
		));
	}

	public static function current_pack() {
		$pack  = (string) MFS_Plugin::get('icon_pack');
		$packs = self::packs();
		if (!isset($packs[$pack])) {
			$pack = 'search-1';
		}
		return $pack;
	}

	/**
	 * Selected search SVG (trigger / input), currentColor-ready.
	 *
	 * @param int $size Pixel size (viewBox stays 24).
	 */
	public static function search_svg($size = 24) {
		$svg = self::load_pack_file(self::current_pack());
		if ('' === $svg) {
			$svg = self::fallback_search_svg();
		}
		return self::prepare_svg($svg, (int) $size, 'mfs-icon mfs-icon--search');
	}

	public static function trash_svg($size = 20) {
		$svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.47 6.015v12.514a2.72 2.72 0 0 0 2.721 2.721h7.618a2.72 2.72 0 0 0 2.72-2.72V6.014m-15.235.001h17.412" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M8.735 6.015V4.382a1.63 1.63 0 0 1 1.633-1.632h3.264a1.63 1.63 0 0 1 1.633 1.632v1.633M9.824 16.992v-5.439m4.353 5.439v-5.439" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		return self::prepare_svg($svg, (int) $size, 'mfs-icon mfs-icon--trash');
	}

	public static function hint_svg($size = 24) {
		$svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.783 18.828a8.05 8.05 0 0 0 7.439-4.955 8.03 8.03 0 0 0-1.737-8.765 8.045 8.045 0 0 0-13.735 5.68c0 2.131.846 4.174 2.352 5.681a8.05 8.05 0 0 0 5.68 2.359m5.706-2.337 4.762 4.759" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		return self::prepare_svg($svg, (int) $size, 'mfs-icon mfs-icon--hint');
	}

	public static function not_found_svg($size = 24) {
		$svg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.367 4.462 2.752 17.655a1.885 1.885 0 0 0 1.634 2.827h15.228a1.885 1.885 0 0 0 1.633-2.827L13.633 4.462a1.885 1.885 0 0 0-3.266 0m1.628 3.116v6.277" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12.043 17.326h-.009" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		return self::prepare_svg($svg, (int) $size, 'mfs-icon mfs-icon--not-found');
	}

	/**
	 * Empty-state hint (before typing).
	 */
	public static function empty_hint_html() {
		return '<p class="mfs-empty-hint">' . self::hint_svg(24) . '<span>' . esc_html__('برای جستجو، نام کالا یا دسته بندی را وارد کنید...', 'advanced-live-fast-search-wp') . '</span></p>';
	}

	/**
	 * No-results markup.
	 */
	public static function not_found_html() {
		return '<div class="not-found mfs-not-found d-none">' . self::not_found_svg(24) . '<span>' . esc_html__('نتیجه ای یافت نشد.', 'advanced-live-fast-search-wp') . '</span></div>';
	}

	/**
	 * Preview SVG for admin (specific pack).
	 */
	public static function pack_preview_svg($pack_id, $size = 28) {
		$svg = self::load_pack_file($pack_id);
		if ('' === $svg) {
			$svg = self::fallback_search_svg();
		}
		return self::prepare_svg($svg, (int) $size, 'mfs-icon mfs-icon--preview');
	}

	private static function load_pack_file($pack_id) {
		$packs = self::packs();
		if (!isset($packs[$pack_id]['file'])) {
			return '';
		}
		$path = trailingslashit(MFS_DIR . self::SEARCH_DIR) . $packs[$pack_id]['file'];
		if (!is_readable($path)) {
			return '';
		}
		$raw = file_get_contents($path);
		return is_string($raw) ? $raw : '';
	}

	private static function fallback_search_svg() {
		return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11.5" cy="11.5" r="9.5" stroke="currentColor" stroke-width="1.5"/><path d="m20 20 2 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
	}

	/**
	 * Normalize SVG: size, class, currentColor strokes/fills.
	 */
	private static function prepare_svg($svg, $size, $class) {
		$svg = trim($svg);
		if ('' === $svg) {
			return '';
		}

		// Strip XML/doctype noise.
		$svg = preg_replace('/<\?xml[^>]*\?>/i', '', $svg);
		$svg = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg);
		$svg = trim($svg);

		if (!preg_match('/<svg\b/i', $svg)) {
			return '';
		}

		$svg = preg_replace('/\sstroke="(?!none)[^"]*"/i', ' stroke="currentColor"', $svg);
		$svg = preg_replace("/\sstroke='(?!none)[^']*'/i", " stroke='currentColor'", $svg);
		$svg = preg_replace('/\sfill="(?!none)[^"]*"/i', ' fill="currentColor"', $svg);
		$svg = preg_replace("/\sfill='(?!none)[^']*'/i", " fill='currentColor'", $svg);

		$size = max(12, min(64, (int) $size));
		$class = esc_attr($class);

		$svg = preg_replace_callback(
			'/<svg\b([^>]*)>/i',
			static function ($m) use ($size, $class) {
				$attrs = $m[1];
				$attrs = preg_replace('/\s(width|height|class)="[^"]*"/i', '', $attrs);
				$attrs = preg_replace("/\s(width|height|class)='[^']*'/i", '', $attrs);
				if (!preg_match('/\sviewBox=/i', $attrs)) {
					$attrs .= ' viewBox="0 0 24 24"';
				}
				return '<svg width="' . $size . '" height="' . $size . '" class="' . $class . '" aria-hidden="true"' . $attrs . '>';
			},
			$svg,
			1
		);

		return $svg;
	}
}
