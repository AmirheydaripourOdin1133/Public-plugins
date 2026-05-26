<?php
/**
 * Warranty item model.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class WarrantyItem
 */
class WarrantyItem {

	/**
	 * @param int    $id               Record ID.
	 * @param string $serial_number    Original serial.
	 * @param string $normalized_serial Normalized serial.
	 * @param string $product_name     Product name.
	 * @param string $image_key        Image mapping key.
	 * @param string $warranty_type    Warranty description.
	 * @param string $start_date       Start date.
	 * @param string $end_date         End date.
	 * @param string $created_at       Created timestamp.
	 */
	public function __construct(
		public int $id = 0,
		public string $serial_number = '',
		public string $normalized_serial = '',
		public string $product_name = '',
		public string $image_key = '',
		public string $warranty_type = '',
		public string $start_date = '',
		public string $end_date = '',
		public string $created_at = ''
	) {}

	/**
	 * Create from DB row object.
	 *
	 * @param object $row Database row.
	 */
	public static function from_row( object $row ): self {
		return new self(
			(int) $row->id,
			(string) $row->serial_number,
			(string) $row->normalized_serial,
			(string) $row->product_name,
			(string) $row->image_key,
			(string) $row->warranty_type,
			(string) $row->start_date,
			(string) $row->end_date,
			isset( $row->created_at ) ? (string) $row->created_at : ''
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'serial_number'     => $this->serial_number,
			'normalized_serial' => $this->normalized_serial,
			'product_name'      => $this->product_name,
			'image_key'         => $this->image_key,
			'warranty_type'     => $this->warranty_type,
			'start_date'        => $this->start_date,
			'end_date'          => $this->end_date,
			'created_at'        => $this->created_at,
		);
	}
}
