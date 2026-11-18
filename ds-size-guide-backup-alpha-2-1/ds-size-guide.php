<?php
/**
 * Plugin Name:       راهنمای سایز بندی محصولات سفارشی
 * Plugin URI:        https://www.rtl-theme.com
 * Description:       راهنمای سایز و مراقبت داینامیک و شخصی‌سازی شده برای محصولات ووکامرس با صفحه تنظیمات اختصاصی، شورت‌کد، ویجت المنتور و تزریق تب المنتور.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Update URI:        false
 * Text Domain:       ds-size-guide
 * Author:            Amir Heydaripur
 * Author URI:        https://www.rtl-theme.com/author/amir.h.heydaripour/products/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DSSG
 */

namespace DSSG;

defined( 'ABSPATH' ) || exit;

define( 'DSSG_VERSION', '1.0.0' );
define( 'DSSG_FILE', __FILE__ );
define( 'DSSG_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSSG_URL', plugin_dir_url( __FILE__ ) );

require_once DSSG_DIR . 'includes/class-dssg-install.php';
require_once DSSG_DIR . 'includes/class-dssg-settings.php';
require_once DSSG_DIR . 'includes/class-dssg-render.php';
require_once DSSG_DIR . 'includes/class-dssg-builder.php';
require_once DSSG_DIR . 'includes/class-dssg-icons.php';

if ( is_admin() ) {
	require_once DSSG_DIR . 'includes/class-dssg-admin.php';
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\dssg_boot', 10 );

/**
 * Bootstrap the plugin.
 */
function dssg_boot() {
	\DSSG\DSSG_Install::maybe_upgrade();

	if ( class_exists( 'WooCommerce' ) || is_admin() ) {
		\DSSG\DSSG_Render::init();
	}

	if ( is_admin() ) {
		\DSSG\DSSG_Admin::init();
	}

	if ( class_exists( '\Elementor\Plugin' ) ) {
		require_once DSSG_DIR . 'includes/class-dssg-elementor.php';
		\DSSG\DSSG_Elementor::init();
	}
}

/**
 * Shortcode wrapper.
 *
 * @param array $atts
 * @return string
 */
function dssg_shortcode( $atts = array() ) {
	return \DSSG\DSSG_Render::shortcode( $atts );
}

add_shortcode( 'dssg_guide', __NAMESPACE__ . '\dssg_shortcode' );