<?php
/**
 * Custom table schema.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schema
 */
class Schema {

	/**
	 * Items table name.
	 */
	public static function items_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'warranty_items';
	}

	/**
	 * Images table name.
	 */
	public static function images_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'warranty_images';
	}

	/**
	 * Create or update tables via dbDelta.
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$items   = self::items_table();
		$images  = self::images_table();

		$sql_items = "CREATE TABLE {$items} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			serial_number varchar(191) NOT NULL,
			normalized_serial varchar(191) NOT NULL,
			product_name varchar(255) NOT NULL,
			image_key varchar(100) NOT NULL DEFAULT '',
			warranty_type varchar(500) NOT NULL DEFAULT '',
			start_date varchar(10) NOT NULL DEFAULT '',
			end_date varchar(10) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY normalized_serial (normalized_serial),
			KEY image_key (image_key)
		) {$charset};";

		$sql_images = "CREATE TABLE {$images} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			image_key varchar(100) NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY image_key (image_key)
		) {$charset};";

		dbDelta( $sql_items );
		dbDelta( $sql_images );
	}
}
