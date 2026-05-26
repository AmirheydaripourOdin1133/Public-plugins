<?php
/**
 * Persian digit conversion helpers.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class PersianDigits
 */
class PersianDigits {

	/**
	 * Convert ASCII digits to Persian.
	 *
	 * @param string $value Input string.
	 */
	public static function to_persian( string $value ): string {
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );

		return str_replace( $en, $fa, $value );
	}

	/**
	 * Convert Persian/Arabic digits to ASCII.
	 *
	 * @param string $value Input string.
	 */
	public static function to_ascii( string $value ): string {
		$map = array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		);

		return strtr( $value, $map );
	}
}
