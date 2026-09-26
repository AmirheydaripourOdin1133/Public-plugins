<?php
/**
 * Plugin Name:       Advanced Live Fast Search
 * Plugin URI:        https://www.rtl-theme.com
 * Description:       جستجوی زنده و فوق‌سریع وردپرس؛ کش JSON سمت سرور + جستجوی سمت کاربر، با اسکین مودال و خطی، پنل تنظیمات فارسی و سازگار با پیشخوان وردپرس.
 * Version:           1.2.4
 * Author:            Amir Heydaripur
 * Author URI:        https://wp-amir.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       advanced-live-fast-search-wp
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

defined('ABSPATH') || exit;

define('MFS_VERSION', '1.2.4');
define('MFS_FILE', __FILE__);
define('MFS_DIR', plugin_dir_path(__FILE__));
define('MFS_URL', plugin_dir_url(__FILE__));
define('MFS_OPTION', 'mfs_settings');
define('MFS_CACHE_HOOK', 'mfs_refresh_event');
define('MFS_CACHE_LOCK', 'mfs_build_lock');
define('MFS_CACHE_INFO', 'mfs_cache_info');
define('MFS_CACHE_NAME', 'mfs_cache_name');

require_once MFS_DIR . 'includes/class-mfs-cache.php';
require_once MFS_DIR . 'includes/class-mfs-rest.php';
require_once MFS_DIR . 'includes/class-mfs-icons.php';
require_once MFS_DIR . 'includes/class-mfs-assets.php';
require_once MFS_DIR . 'includes/class-mfs-render.php';
require_once MFS_DIR . 'includes/class-mfs-settings.php';
require_once MFS_DIR . 'includes/class-mfs-plugin.php';

MFS_Plugin::instance();

add_action('elementor/loaded', static function () {
	require_once MFS_DIR . 'includes/elementor/class-mfs-elementor.php';
	MFS_Elementor::init();
});

register_activation_hook(__FILE__, array('MFS_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('MFS_Plugin', 'deactivate'));
