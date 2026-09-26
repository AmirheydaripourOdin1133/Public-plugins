<?php
/**
 * Plugin Name:       جستجوی زنده پیشرفته وردپرس
 * Plugin URI:        https://www.rtl-theme.com
 * Description:       جستجوی زنده و فوق‌سریع وردپرس (Advanced Live Fast Search WP) با کش هوشمند JSON، چهار سبک نمایش، پشتیبانی از ووکامرس و پنل تنظیمات فارسی.
 * Version:           1.4.2
 * Author:            Amir Heydaripur
 * Author URI:        https://wp-amir.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advanced-live-fast-search-wp
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

defined('ABSPATH') || exit;

define('MFS_VERSION', '1.4.2');
define('MFS_FILE', __FILE__);
define('MFS_DIR', plugin_dir_path(__FILE__));
define('MFS_URL', plugin_dir_url(__FILE__));
define('MFS_OPTION', 'mfs_settings');
define('MFS_CACHE_HOOK', 'mfs_refresh_event');
define('MFS_CACHE_LOCK', 'mfs_build_lock');
define('MFS_CACHE_INFO', 'mfs_cache_info');
define('MFS_CACHE_NAME', 'mfs_cache_name');

$mfs_license_file   = MFS_DIR . 'RTL_License_df44093b0d904fd2.php';
$mfs_license_hash   = 'debbb9d05a3b8fb63bbb71e614384ed7504d42a6';
$mfs_license_class  = 'RTL_License_df44093b0d904fd2';
$mfs_license_active = false;
$mfs_license_reason = 'inactive';

if (!extension_loaded('ionCube Loader')) {
	$mfs_license_reason = 'ioncube_missing';
} elseif (!is_readable($mfs_license_file)) {
	$mfs_license_reason = 'file_missing';
} else {
	$mfs_actual_hash = sha1_file($mfs_license_file);

	if (!is_string($mfs_actual_hash) || !hash_equals($mfs_license_hash, strtolower($mfs_actual_hash))) {
		$mfs_license_reason = 'file_invalid';
	} else {
		require_once $mfs_license_file;

		if (!class_exists($mfs_license_class, false)) {
			$mfs_license_reason = 'class_missing';
		} else {
			try {
				$mfs_rtl_license = new $mfs_license_class();

				if (!is_callable(array($mfs_rtl_license, 'isActive'))) {
					$mfs_license_reason = 'api_invalid';
				} else {
					$mfs_license_active = (true === $mfs_rtl_license->isActive());
					$mfs_license_reason = $mfs_license_active ? 'active' : 'inactive';
				}
			} catch (Throwable $mfs_license_error) {
				unset($mfs_license_error);
				$mfs_license_reason = 'check_failed';
			}
		}
	}
}

if ($mfs_license_active) {
	require_once MFS_DIR . 'includes/class-mfs-cache.php';
	require_once MFS_DIR . 'includes/class-mfs-rest.php';
	require_once MFS_DIR . 'includes/class-mfs-icons.php';
	require_once MFS_DIR . 'includes/class-mfs-assets.php';
	require_once MFS_DIR . 'includes/class-mfs-render.php';
	require_once MFS_DIR . 'includes/class-mfs-settings.php';
	require_once MFS_DIR . 'includes/class-mfs-plugin.php';

	// A site may activate the plugin before registering its RTL license.
	// Initialize product defaults on the first licensed request as well.
	if (false === get_option(MFS_OPTION, false) || false === get_option(MFS_CACHE_NAME, false)) {
		MFS_Plugin::activate();
	}

	MFS_Plugin::instance();

	add_action('elementor/loaded', static function () {
		require_once MFS_DIR . 'includes/elementor/class-mfs-elementor.php';
		MFS_Elementor::init();
	});

	register_activation_hook(__FILE__, array('MFS_Plugin', 'activate'));
	register_deactivation_hook(__FILE__, array('MFS_Plugin', 'deactivate'));
} else {
	require_once MFS_DIR . 'includes/class-mfs-license.php';
	MFS_License::register_locked_admin($mfs_license_reason);
}

unset(
	$mfs_license_file,
	$mfs_license_hash,
	$mfs_license_class,
	$mfs_license_active,
	$mfs_license_reason,
	$mfs_actual_hash,
	$mfs_rtl_license
);
