<?php
/**
 * Database installation and upgrades.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class Installer
 */
class Installer {

	/**
	 * Activation hook.
	 */
	public static function activate(): void {
		Schema::create_tables();
		update_option( 'apple_warranty_db_version', APPLE_WARRANTY_DB_VERSION );

		if ( false === get_option( 'apple_warranty_description', false ) ) {
			update_option( 'apple_warranty_description', '' );
		}
	}

	/**
	 * Deactivation hook.
	 */
	public static function deactivate(): void {
		// Reserved for scheduled events cleanup.
	}

	/**
	 * Run migrations when version changes.
	 */
	public static function maybe_upgrade(): void {
		$installed = get_option( 'apple_warranty_db_version', '' );

		if ( APPLE_WARRANTY_DB_VERSION !== $installed ) {
			Schema::create_tables();
			update_option( 'apple_warranty_db_version', APPLE_WARRANTY_DB_VERSION );
		}
	}
}
