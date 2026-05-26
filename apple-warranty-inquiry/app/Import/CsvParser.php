<?php
/**
 * CSV file parser.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Class CsvParser
 */
class CsvParser {

	/**
	 * Parse CSV file path.
	 *
	 * @param string $file_path Absolute path.
	 * @return array{header: string[], rows: array<int, array<string, string>>, errors: string[]}
	 */
	public function parse( string $file_path ): array {
		$errors = array();
		$rows   = array();
		$header = array();

		if ( ! is_readable( $file_path ) ) {
			return array(
				'header' => array(),
				'rows'   => array(),
				'errors' => array( __( 'فایل قابل خواندن نیست.', 'apple-warranty-inquiry' ) ),
			);
		}

		$handle = fopen( $file_path, 'rb' );

		if ( false === $handle ) {
			return array(
				'header' => array(),
				'rows'   => array(),
				'errors' => array( __( 'خطا در باز کردن فایل CSV.', 'apple-warranty-inquiry' ) ),
			);
		}

		// Strip UTF-8 BOM if present.
		$bom = fread( $handle, 3 );
		if ( $bom !== "\xEF\xBB\xBF" ) {
			rewind( $handle );
		}

		$line_num = 0;

		while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== false ) {
			++$line_num;

			if ( 1 === count( $data ) && ( null === $data[0] || '' === trim( (string) $data[0] ) ) ) {
				continue;
			}

			$data = array_map( static fn( $v ) => trim( (string) $v ), $data );

			if ( 1 === $line_num ) {
				$header = array_map( static fn( $c ) => strtolower( trim( $c ) ), $data );
				continue;
			}

			if ( empty( array_filter( $data ) ) ) {
				continue;
			}

			$row = array();
			foreach ( CsvValidator::REQUIRED_COLUMNS as $col ) {
				$index      = array_search( $col, $header, true );
				$row[ $col ] = false !== $index && isset( $data[ $index ] ) ? $data[ $index ] : '';
			}

			$rows[] = $row;
		}

		fclose( $handle );

		return array(
			'header' => $header,
			'rows'   => $rows,
			'errors' => $errors,
		);
	}

	/**
	 * Preview first N rows.
	 *
	 * @param string $file_path File path.
	 * @param int    $limit     Max rows.
	 */
	public function preview( string $file_path, int $limit = 10 ): array {
		$parsed = $this->parse( $file_path );

		return array(
			'header' => $parsed['header'],
			'rows'   => array_slice( $parsed['rows'], 0, $limit ),
			'errors' => $parsed['errors'],
			'total'  => count( $parsed['rows'] ),
		);
	}
}
