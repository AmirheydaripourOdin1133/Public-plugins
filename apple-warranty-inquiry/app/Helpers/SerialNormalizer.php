<?php
/**
 * Serial number normalization.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class SerialNormalizer
 */
class SerialNormalizer {

	/**
	 * Normalize serial for lookup/storage.
	 *
	 * @param string $serial Raw serial input.
	 * @return string Normalized serial (uppercase alphanumeric only).
	 */
	public static function normalize( string $serial ): string {
		$serial = sanitize_text_field( wp_unslash( $serial ) );
		$serial = strtoupper( $serial );
		$serial = str_replace( array( ' ', '-', '_', '–', '—' ), '', $serial );
		$serial = preg_replace( '/[^A-Z0-9]/', '', $serial );

		return $serial ?? '';
	}

	/**
	 * Check if normalized serial is non-empty.
	 *
	 * @param string $normalized Normalized serial.
	 */
	public static function is_valid( string $normalized ): bool {
		return '' !== $normalized && (bool) preg_match( '/^[A-Z0-9]+$/', $normalized );
	}
}
