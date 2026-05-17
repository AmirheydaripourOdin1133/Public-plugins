<?php
/**
 * Plugin Name: Rasam Server Configurator - شخصی سازی محصولات متغیر برای رسام سرور
 * Description: شخصی‌سازی کانفیگ سرور، قطعات، موتور قیمت و یکپارچگی با ووکامرس. وابستگی‌های الزامی: Advanced Custom Fields (ACF) Pro، افزونهٔ جدول وریشن Woo Variations Table Grid، و WC Request Quotation؛ علاوه بر آن WooCommerce باید فعال باشد.
 * Version: 1.9.3
 * Author: AmirHossein Haidaripour
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: advanced-custom-fields-pro/acf.php, woo-variations-table-grid/woo-variations-table.php, wc-request-quotation/wc-request-quotation.php
 * Text Domain: rasam-server-config
 *
 * Developer documentation: DEVELOPMENT.md (در همین پوشهٔ افزونه).
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RSC_VERSION', '1.9.3' );
define( 'RSC_PLUGIN_FILE', __FILE__ );
define( 'RSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RSC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once RSC_PLUGIN_DIR . 'includes/class-rsc-plugin.php';

/**
 * Bootstrap.
 *
 * @return RSC_Plugin
 */
function rsc_plugin() {
	return RSC_Plugin::instance();
}

rsc_plugin();

register_activation_hook(
	RSC_PLUGIN_FILE,
	static function () {
		if ( ! get_option( 'rsc_flush_rewrite_rules' ) ) {
			add_option( 'rsc_flush_rewrite_rules', '1' );
		}
	}
);
