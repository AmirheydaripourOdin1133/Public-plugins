<?php
/**
 * کلیدهای متای خط سفارش ووکامرس (ثابت برای ACF و گزارش).
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Order_Item_Meta {

	/** کانفیگ پیش‌فرض وریشن (مثلاً 4LFF, 2124, 16GB) — بدون تکرار عنوان محصول. */
	public const BASE_CONFIG = 'rsc_base_config';

	/** قطعات سفارشی انتخاب‌شده (نام × تعداد). */
	public const CUSTOM_PARTS = 'rsc_custom_parts';

	/** JSON ساختاری خطوط انتخاب؛ مخفی در UI. */
	public const LINES_JSON = '_rsc_lines_json';

	/**
	 * برچسب نمایشی فارسی برای کلید متا در صورتحساب/حساب کاربری.
	 *
	 * @param string $meta_key
	 * @return string|null برچسب یا null اگر مربوط به رسام نباشد.
	 */
	public static function get_display_label( $meta_key ) {
		if ( self::BASE_CONFIG === $meta_key ) {
			return __( 'کانفیگ', 'rasam-server-config' );
		}
		if ( self::CUSTOM_PARTS === $meta_key ) {
			return __( 'قطعات اضافه', 'rasam-server-config' );
		}
		return null;
	}

	/**
	 * کلیدهای قدیمی (قبل از 1.9.3) — فقط برای مخفی‌سازی در UI.
	 *
	 * @return string[]
	 */
	public static function legacy_meta_keys() {
		return array(
			__( 'کانفیگ', 'rasam-server-config' ),
			__( 'قطعات اضافه', 'rasam-server-config' ),
		);
	}
}
