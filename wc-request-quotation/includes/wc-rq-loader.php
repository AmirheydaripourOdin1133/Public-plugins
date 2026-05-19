<?php
/**
 * بارگذاری امن فایل‌های PHP افزونه (بدون خروجی BOM).
 *
 * @package WC_Request_Quotation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * require_once با حذف خودکار UTF-8 BOM از ابتدای فایل.
 * BOM سه بایت خروجی می‌دهد و همهٔ AJAX سایت (پیش‌فاکتور، پیکربندی رسام و …) را می‌شکند.
 *
 * @param string $absolute_path مسیر کامل فایل.
 * @return bool
 */
function wc_rq_load_include_file( $absolute_path ) {
	if ( ! is_readable( $absolute_path ) ) {
		return false;
	}

	$contents = file_get_contents( $absolute_path );
	if ( false === $contents ) {
		return false;
	}

	if ( strncmp( $contents, "\xEF\xBB\xBF", 3 ) === 0 ) {
		$contents = substr( $contents, 3 );
		// یک‌بار روی دیسک اصلاح می‌شود تا ویرایشگر دوباره BOM نگذارد، درخواست بعدی هم سالم بماند.
		if ( is_writable( $absolute_path ) ) {
			file_put_contents( $absolute_path, $contents, LOCK_EX );
		}
	}

	$tmp = function_exists( 'wp_tempnam' ) ? wp_tempnam( 'wc-rq-load' ) : tempnam( sys_get_temp_dir(), 'wc-rq-load' );
	if ( ! $tmp ) {
		return false;
	}

	$written = file_put_contents( $tmp, $contents, LOCK_EX );
	if ( false === $written ) {
		@unlink( $tmp );
		return false;
	}

	require_once $tmp;
	@unlink( $tmp );

	return true;
}
