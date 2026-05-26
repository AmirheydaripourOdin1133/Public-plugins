<?php
/**
 * Warranty image mapping model.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Models;

defined( 'ABSPATH' ) || exit;

/**
 * Class WarrantyImage
 */
class WarrantyImage {

	/**
	 * @param int    $id            Record ID.
	 * @param string $image_key     Unique key.
	 * @param string $title         Display title.
	 * @param int    $attachment_id Media attachment ID.
	 * @param string $created_at    Created timestamp.
	 */
	public function __construct(
		public int $id = 0,
		public string $image_key = '',
		public string $title = '',
		public int $attachment_id = 0,
		public string $created_at = ''
	) {}

	/**
	 * Create from DB row.
	 *
	 * @param object $row Database row.
	 */
	public static function from_row( object $row ): self {
		return new self(
			(int) $row->id,
			(string) $row->image_key,
			(string) $row->title,
			(int) $row->attachment_id,
			isset( $row->created_at ) ? (string) $row->created_at : ''
		);
	}

	/**
	 * Get image URL from attachment.
	 */
	public function get_url( string $size = 'medium' ): string {
		if ( $this->attachment_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $this->attachment_id, $size );

		return $url ? $url : '';
	}
}
