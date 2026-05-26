<?php
/**
 * Warranty images repository.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Repositories;

use AppleWarranty\Database\Schema;
use AppleWarranty\Models\WarrantyImage;

defined( 'ABSPATH' ) || exit;

/**
 * Class WarrantyImageRepository
 */
class WarrantyImageRepository {

	/**
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->table = Schema::images_table();
	}

	/**
	 * Find by image key.
	 */
	public function find_by_key( string $image_key ): ?WarrantyImage {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE image_key = %s LIMIT 1",
				$image_key
			)
		);

		return $row ? WarrantyImage::from_row( $row ) : null;
	}

	/**
	 * Find by ID.
	 */
	public function find( int $id ): ?WarrantyImage {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
				$id
			)
		);

		return $row ? WarrantyImage::from_row( $row ) : null;
	}

	/**
	 * List all mappings.
	 *
	 * @return WarrantyImage[]
	 */
	public function all(): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY id DESC" );

		return array_map( static fn( $row ) => WarrantyImage::from_row( $row ), $rows ?: array() );
	}

	/**
	 * Check if image key exists.
	 */
	public function key_exists( string $image_key, int $exclude_id = 0 ): bool {
		global $wpdb;

		if ( $exclude_id > 0 ) {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE image_key = %s AND id != %d",
					$image_key,
					$exclude_id
				)
			);
		} else {
			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->table} WHERE image_key = %s",
					$image_key
				)
			);
		}

		return $count > 0;
	}

	/**
	 * Insert mapping.
	 *
	 * @param array<string, mixed> $data Data.
	 * @return int|false
	 */
	public function insert( array $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table,
			array(
				'image_key'     => $data['image_key'],
				'title'         => $data['title'] ?? '',
				'attachment_id' => (int) ( $data['attachment_id'] ?? 0 ),
			),
			array( '%s', '%s', '%d' )
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update mapping.
	 *
	 * @param int                  $id   ID.
	 * @param array<string, mixed> $data Data.
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			array(
				'image_key'     => $data['image_key'],
				'title'         => $data['title'] ?? '',
				'attachment_id' => (int) ( $data['attachment_id'] ?? 0 ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete mapping.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		return false !== $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}
}
