<?php
/**
 * یکپارچگی ACF 6: بارگذاری JSON افزونه و ذخیرهٔ همان گروه در پوشهٔ افزونه.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_ACF {

	const FIELD_GROUP_KEY_COMPONENT = 'group_rsc_server_component';

	const FIELD_GROUP_KEY_PRODUCT_ALLOWLIST = 'group_rsc_product_allowlist';

	/**
	 * گروه‌هایی که ذخیرهٔ JSON آن‌ها در پوشهٔ افزونه انجام می‌شود.
	 *
	 * @return string[]
	 */
	private static function plugin_json_group_keys() {
		return array(
			self::FIELD_GROUP_KEY_COMPONENT,
			self::FIELD_GROUP_KEY_PRODUCT_ALLOWLIST,
		);
	}

	/**
	 * @var RSC_ACF|null
	 */
	private static $instance = null;

	/**
	 * @return RSC_ACF
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** گروه حذف‌شده؛ اگر فایل JSON قدیمی روی سرور مانده باشد از حافظهٔ ACF حذف می‌شود. */
	const DEPRECATED_FIELD_GROUP_KEY_VARIATION = 'group_rsc_variation';

	private function __construct() {
		add_filter( 'acf/json/load_paths', array( $this, 'append_load_paths' ) );
		add_filter( 'acf/json/save_paths', array( $this, 'prefer_plugin_save_path_for_group' ), 10, 2 );
		add_action( 'acf/include_fields', array( $this, 'unregister_deprecated_variation_field_group' ), 999 );
	}

	/**
	 * جلوگیری از بارگذاری مجدد گروه وریشن حذف‌شده (باقی‌ماندهٔ JSON یا کش).
	 */
	public function unregister_deprecated_variation_field_group() {
		if ( function_exists( 'acf_remove_local_field_group' ) ) {
			acf_remove_local_field_group( self::DEPRECATED_FIELD_GROUP_KEY_VARIATION );
		}
	}

	/**
	 * مسیر JSON قطعات رسام را به مسیرهای بارگذاری ACF اضافه می‌کند (بدون حذف تم/افزونه‌های دیگر).
	 *
	 * @param array $paths مسیرهای موجود.
	 * @return array
	 */
	public function append_load_paths( $paths ) {
		$paths   = is_array( $paths ) ? $paths : array();
		$paths[] = RSC_PLUGIN_DIR . 'acf-json';
		return $paths;
	}

	/**
	 * ذخیرهٔ گروه «قطعه سرور» در پوشهٔ acf-json همین افزونه؛ بقیهٔ گروه‌ها مسیر پیش‌فرض را حفظ می‌کنند.
	 *
	 * @param array $paths مسیرهای پیشنهادی ACF.
	 * @param array $post  آرایهٔ گروه فیلد یا سایر پست‌های داخلی ACF.
	 * @return array
	 */
	public function prefer_plugin_save_path_for_group( $paths, $post ) {
		if ( empty( $post['key'] ) || ! in_array( (string) $post['key'], self::plugin_json_group_keys(), true ) ) {
			return $paths;
		}
		$plugin_dir = RSC_PLUGIN_DIR . 'acf-json';
		array_unshift( $paths, $plugin_dir );
		return array_values( array_unique( array_filter( $paths ) ) );
	}
}
