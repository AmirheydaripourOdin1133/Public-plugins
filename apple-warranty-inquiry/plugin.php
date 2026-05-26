<?php
/**
 * Plugin Name:       Apple Warranty Inquiry
 * Plugin URI:        https://github.com/AmirheydaripourOdin1133/plugins-document/tree/main/apple-warranty-inquiry
 * Description:       سیستم استعلام گارانتی محصولات اپل بر اساس شماره سریال
 * Version:           1.1.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Payam Ava - Heydaripour
 * Author URI:        https://payamava.net/
 * Text Domain:       apple-warranty-inquiry
 * Domain Path:       /languages
 *
 * @package AppleWarranty
 */

defined( 'ABSPATH' ) || exit;

define( 'APPLE_WARRANTY_VERSION', '1.1.1' );
define( 'APPLE_WARRANTY_PLUGIN_FILE', __FILE__ );
define( 'APPLE_WARRANTY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APPLE_WARRANTY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APPLE_WARRANTY_DB_VERSION', '1.0.0' );

require_once APPLE_WARRANTY_PLUGIN_DIR . 'app/Autoloader.php';

AppleWarranty\Autoloader::register();

register_activation_hook( __FILE__, array( AppleWarranty\Database\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( AppleWarranty\Database\Installer::class, 'deactivate' ) );

add_action( 'plugins_loaded', static function (): void {
	AppleWarranty\Plugin::instance()->init();
} );
