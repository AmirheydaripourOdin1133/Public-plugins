<?php
/**
 * Warranty items repository.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Repositories;

use AppleWarranty\Database\Schema;
use AppleWarranty\Models\WarrantyItem;

defined( 'ABSPATH' ) || exit;

/**
 * Class WarrantyItemRepository
 */
class WarrantyItemRepository {

	/**
	 * @var string
	 */
	private string $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->table = Schema::items_table();
	}

	/**
	 * Find by normalized serial.
	 */
	public function find_by_normalized_serial( string $normalized ): ?WarrantyItem {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE normalized_serial = %s LIMIT 1",
				$normalized
			)
		);

		return $row ? WarrantyItem::from_row( $row ) : null;
	}

	/**
	 * Find by ID.
	 */
	public function find( int $id ): ?WarrantyItem {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
				$id
			)
		);

		return $row ? WarrantyItem::from_row( $row ) : null;
	}

	/**
	 * Paginated list with optional search.
	 *
	 * @return array{items: WarrantyItem[], total: int}
	 */
	public function list( int $page = 1, int $per_page = 20, string $search = '' ): array {
		global $wpdb;

		$page     = max( 1, $page );
		$per_page = max( 1, min( 100, $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;
		$where    = '1=1';
		$params   = array();

		if ( '' !== $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= ' AND (serial_number LIKE %s OR normalized_serial LIKE %s OR product_name LIKE %s)';
			$params = array_merge( $params, array( $like, $like, $like ) );
		}

		$count_sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where}";
		$list_sql  = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...array_merge( $params, array( $per_page, $offset ) ) ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$total = (int) $wpdb->get_var( $count_sql );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $per_page, $offset ) );
		}

		$items = array_map( static fn( $row ) => WarrantyItem::from_row( $row ), $rows ?: array() );

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Insert record.
	 *
	 * @param array<string, string> $data Row data.
	 * @return int|false Insert ID or false.
	 */
	public function insert( array $data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->table,
			array(
				'serial_number'     => $data['serial_number'],
				'normalized_serial' => $data['normalized_serial'],
				'product_name'      => $data['product_name'],
				'image_key'         => $data['image_key'] ?? '',
				'warranty_type'     => $data['warranty_type'] ?? '',
				'start_date'        => $data['start_date'] ?? '',
				'end_date'          => $data['end_date'] ?? '',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update record.
	 *
	 * @param int                   $id   Record ID.
	 * @param array<string, string> $data Row data.
	 */
	public function update( int $id, array $data ): bool {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			array(
				'serial_number'     => $data['serial_number'],
				'normalized_serial' => $data['normalized_serial'],
				'product_name'      => $data['product_name'],
				'image_key'         => $data['image_key'] ?? '',
				'warranty_type'     => $data['warranty_type'] ?? '',
				'start_date'        => $data['start_date'] ?? '',
				'end_date'          => $data['end_date'] ?? '',
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete by ID.
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		return false !== $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Bulk delete.
	 *
	 * @param int[] $ids IDs.
	 */
	public function delete_many( array $ids ): int {
		global $wpdb;

		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table} WHERE id IN ({$placeholders})", ...$ids ) );
	}

	/**
	 * Truncate table.
	 */
	public function truncate(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "TRUNCATE TABLE {$this->table}" );
	}

	/**
	 * Batch insert rows.
	 *
	 * @param array<int, array<string, string>> $rows Rows.
	 * @return int Inserted count.
	 */
	public function insert_batch( array $rows ): int {
		global $wpdb;

		if ( empty( $rows ) ) {
			return 0;
		}

		$inserted = 0;
		$chunk    = array_chunk( $rows, 100 );

		foreach ( $chunk as $batch ) {
			foreach ( $batch as $row ) {
				$id = $this->insert( $row );
				if ( $id ) {
					++$inserted;
				}
			}
		}

		return $inserted;
	}

	/**
	 * Total count.
	 */
	public function count(): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}
}
