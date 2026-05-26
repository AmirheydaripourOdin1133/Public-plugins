<?php
/**
 * CSV import service.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Services;

use AppleWarranty\Helpers\DateNormalizer;
use AppleWarranty\Helpers\SerialNormalizer;
use AppleWarranty\Import\CsvParser;
use AppleWarranty\Import\CsvValidator;
use AppleWarranty\Repositories\WarrantyImageRepository;
use AppleWarranty\Repositories\WarrantyItemRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ImportService
 */
class ImportService {

	private CsvParser $parser;
	private CsvValidator $validator;
	private WarrantyItemRepository $items;
	private WarrantyImageRepository $images;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->parser    = new CsvParser();
		$this->validator = new CsvValidator();
		$this->items     = new WarrantyItemRepository();
		$this->images    = new WarrantyImageRepository();
	}

	/**
	 * Run full import (truncate then insert).
	 *
	 * @param string $file_path CSV path.
	 * @return array{success: bool, imported: int, errors: string[], skipped: int}
	 */
	public function import( string $file_path ): array {
		$parsed = $this->parser->parse( $file_path );
		$errors = $parsed['errors'];

		if ( empty( $parsed['header'] ) ) {
			return array(
				'success'  => false,
				'imported' => 0,
				'errors'   => array_merge( $errors, array( __( 'فایل CSV خالی است.', 'apple-warranty-inquiry' ) ) ),
				'skipped'  => 0,
			);
		}

		$header_check = $this->validator->validate_header( $parsed['header'] );
		if ( ! $header_check['valid'] ) {
			return array(
				'success'  => false,
				'imported' => 0,
				'errors'   => array_merge( $errors, $header_check['errors'] ),
				'skipped'  => 0,
			);
		}

		$to_insert = array();
		$skipped   = 0;
		$line      = 1;

		foreach ( $parsed['rows'] as $row ) {
			++$line;
			$row_errors = $this->validate_row( $row, $line );

			if ( ! empty( $row_errors ) ) {
				$errors = array_merge( $errors, $row_errors );
				++$skipped;
				continue;
			}

			$normalized = SerialNormalizer::normalize( $row['serial_number'] );

			$to_insert[] = array(
				'serial_number'     => sanitize_text_field( $row['serial_number'] ),
				'normalized_serial' => $normalized,
				'product_name'      => sanitize_text_field( $row['product_name'] ),
				'image_key'         => sanitize_key( $row['image_key'] ),
				'warranty_type'     => sanitize_text_field( $row['warranty_type'] ),
				'start_date'        => DateNormalizer::normalize( $row['start_date'] ),
				'end_date'          => DateNormalizer::normalize( $row['end_date'] ),
			);
		}

		if ( empty( $to_insert ) && ! empty( $parsed['rows'] ) ) {
			return array(
				'success'  => false,
				'imported' => 0,
				'errors'   => array_merge( $errors, array( __( 'هیچ ردیف معتبری برای درون‌ریزی وجود ندارد.', 'apple-warranty-inquiry' ) ) ),
				'skipped'  => $skipped,
			);
		}

		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );

		try {
			$this->items->truncate();
			$imported = $this->items->insert_batch( $to_insert );
			$wpdb->query( 'COMMIT' );
		} catch ( \Throwable $e ) {
			$wpdb->query( 'ROLLBACK' );

			return array(
				'success'  => false,
				'imported' => 0,
				'errors'   => array_merge( $errors, array( $e->getMessage() ) ),
				'skipped'  => $skipped,
			);
		}

		return array(
			'success'  => true,
			'imported' => $imported,
			'errors'   => $errors,
			'skipped'  => $skipped,
		);
	}

	/**
	 * Validate single CSV row.
	 *
	 * @param array<string, string> $row  Row data.
	 * @param int                   $line Line number.
	 * @return string[]
	 */
	private function validate_row( array $row, int $line ): array {
		$errors = array();

		if ( '' === trim( $row['serial_number'] ?? '' ) ) {
			$errors[] = sprintf( __( 'خط %d: شماره سریال خالی است.', 'apple-warranty-inquiry' ), $line );
		}

		$normalized = SerialNormalizer::normalize( $row['serial_number'] ?? '' );
		if ( ! SerialNormalizer::is_valid( $normalized ) ) {
			$errors[] = sprintf( __( 'خط %d: شماره سریال نامعتبر است.', 'apple-warranty-inquiry' ), $line );
		}

		if ( '' === trim( $row['product_name'] ?? '' ) ) {
			$errors[] = sprintf( __( 'خط %d: نام محصول خالی است.', 'apple-warranty-inquiry' ), $line );
		}

		$image_key = sanitize_key( $row['image_key'] ?? '' );
		if ( '' !== $image_key && ! $this->images->find_by_key( $image_key ) ) {
			$errors[] = sprintf(
				/* translators: 1: line number, 2: image key */
				__( 'خط %1$d: شناسه تصویر «%2$s» در بخش تصاویر محصول یافت نشد (ردیف رد شد).', 'apple-warranty-inquiry' ),
				$line,
				$image_key
			);
		}

		if ( ! $this->validator->is_valid_date( $row['start_date'] ?? '' ) ) {
			$errors[] = sprintf( __( 'خط %d: فرمت start_date نامعتبر (مثال: 3/1/2025 یا 2025-03-01).', 'apple-warranty-inquiry' ), $line );
		}

		if ( ! $this->validator->is_valid_date( $row['end_date'] ?? '' ) ) {
			$errors[] = sprintf( __( 'خط %d: فرمت end_date نامعتبر (مثال: 8/23/2026 یا 2026-08-23).', 'apple-warranty-inquiry' ), $line );
		}

		return $errors;
	}

}
