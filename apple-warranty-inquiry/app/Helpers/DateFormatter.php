<?php
/**
 * Jalali date display formatting.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateFormatter
 */
class DateFormatter {

	/**
	 * Format stored date (YYYY-MM-DD) for display (YYYY/MM/DD Persian digits).
	 *
	 * @param string $date Stored date.
	 */
	public static function display( string $date ): string {
		$date = trim( $date );

		if ( '' === $date ) {
			return '';
		}

		$date = str_replace( '/', '-', $date );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return PersianDigits::to_persian( esc_html( $date ) );
		}

		$parts = explode( '-', $date );
		$out   = $parts[0] . '/' . $parts[1] . '/' . $parts[2];

		return PersianDigits::to_persian( $out );
	}
}
