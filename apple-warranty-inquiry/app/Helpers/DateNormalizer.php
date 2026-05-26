<?php
/**
 * Normalize import dates from Excel/CSV formats to YYYY-MM-DD.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class DateNormalizer
 */
class DateNormalizer {

	/**
	 * Parse and normalize a date string for DB storage.
	 *
	 * Accepts: YYYY-MM-DD, YYYY/MM/DD, M/D/YYYY, MM/DD/YYYY, M-D-YYYY.
	 *
	 * @param string $date Raw date from CSV.
	 * @return string Normalized YYYY-MM-DD or empty if invalid/empty.
	 */
	public static function normalize( string $date ): string {
		$date = trim( $date );

		if ( '' === $date ) {
			return '';
		}

		// Already YYYY-MM-DD or YYYY/MM/DD.
		if ( preg_match( '/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})$/', $date, $m ) ) {
			return self::pad( (int) $m[1], (int) $m[2], (int) $m[3] );
		}

		// Excel US style: M/D/YYYY or MM/DD/YYYY.
		if ( preg_match( '/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $date, $m ) ) {
			return self::pad( (int) $m[3], (int) $m[1], (int) $m[2] );
		}

		return '';
	}

	/**
	 * Check if date can be normalized.
	 */
	public static function is_valid( string $date ): bool {
		$date = trim( $date );

		if ( '' === $date ) {
			return true;
		}

		$normalized = self::normalize( $date );

		if ( '' === $normalized ) {
			return false;
		}

		$parts = explode( '-', $normalized );
		return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
	}

	/**
	 * Build zero-padded YYYY-MM-DD.
	 */
	private static function pad( int $year, int $month, int $day ): string {
		if ( $year < 1000 || $month < 1 || $month > 12 || $day < 1 || $day > 31 ) {
			return '';
		}

		if ( ! checkdate( $month, $day, $year ) ) {
			return '';
		}

		return sprintf( '%04d-%02d-%02d', $year, $month, $day );
	}
}
