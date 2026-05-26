<?php
/**
 * CSV column validation.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Import;

use AppleWarranty\Helpers\DateNormalizer;

defined( 'ABSPATH' ) || exit;

/**
 * Class CsvValidator
 */
class CsvValidator {

	public const REQUIRED_COLUMNS = array(
		'serial_number',
		'product_name',
		'image_key',
		'warranty_type',
		'start_date',
		'end_date',
	);

	/**
	 * Validate header row.
	 *
	 * @param array<int, string> $header Header cells.
	 * @return array{valid: bool, errors: string[]}
	 */
	public function validate_header( array $header ): array {
		$header = array_map( static fn( $c ) => strtolower( trim( $c ) ), $header );
		$errors = array();

		foreach ( self::REQUIRED_COLUMNS as $col ) {
			if ( ! in_array( $col, $header, true ) ) {
				$errors[] = sprintf(
					/* translators: %s: column name */
					__( 'ستون الزامی یافت نشد: %s', 'apple-warranty-inquiry' ),
					$col
				);
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Validate date (YYYY-MM-DD or Excel M/D/YYYY).
	 */
	public function is_valid_date( string $date ): bool {
		return DateNormalizer::is_valid( $date );
	}
}
