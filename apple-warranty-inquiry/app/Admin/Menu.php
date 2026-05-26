<?php
/**
 * Admin menu registration.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Menu
 */
class Menu {

	/**
	 * Register admin menu.
	 */
	public function register(): void {
		ImportPage::register_hooks();
		add_action( 'admin_menu', array( $this, 'add_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Add menu pages.
	 */
	public function add_menus(): void {
		$records_page = new RecordsPage();

		add_menu_page(
			__( 'گارانتی اپل', 'apple-warranty-inquiry' ),
			__( 'گارانتی اپل', 'apple-warranty-inquiry' ),
			'manage_options',
			'apple-warranty',
			array( $records_page, 'render' ),
			self::menu_icon_data_uri(),
			58
		);

		// Same slug as parent — callback must be omitted to avoid rendering the page twice.
		add_submenu_page(
			'apple-warranty',
			__( 'لیست شماره سریال‌های گارانتی', 'apple-warranty-inquiry' ),
			__( 'شماره سریال‌ها', 'apple-warranty-inquiry' ),
			'manage_options',
			'apple-warranty'
		);

		add_submenu_page(
			'apple-warranty',
			__( 'تصاویر محصولات', 'apple-warranty-inquiry' ),
			__( 'تصاویر محصول', 'apple-warranty-inquiry' ),
			'manage_options',
			'apple-warranty-images',
			array( new ImagesPage(), 'render' )
		);

		add_submenu_page(
			'apple-warranty',
			__( 'درون‌ریزی داده‌ها', 'apple-warranty-inquiry' ),
			__( 'درون‌ریزی', 'apple-warranty-inquiry' ),
			'manage_options',
			'apple-warranty-import',
			array( new ImportPage(), 'render' )
		);

		add_submenu_page(
			'apple-warranty',
			__( 'تنظیمات', 'apple-warranty-inquiry' ),
			__( 'تنظیمات', 'apple-warranty-inquiry' ),
			'manage_options',
			'apple-warranty-settings',
			array( new SettingsPage(), 'render' )
		);
	}

	/**
	 * Custom Apple logo for wp-admin menu (SVG data URI).
	 */
	private static function menu_icon_data_uri(): string {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">'
			. '<path fill="currentColor" d="M19.89 8.748a4.22 4.22 0 0 0-2.227 4.086 4.46 4.46 0 0 0 2.744 3.828.4.4 0 0 1 0 .094 11.8 11.8 0 0 1-2.45 4.11 3.12 3.12 0 0 1-1.66 1.06 2.9 2.9 0 0 1-1.448-.118c-.495-.153-.978-.353-1.484-.495a4.23 4.23 0 0 0-2.661.236q-.644.235-1.308.4a2 2 0 0 1-1.566-.294 5.9 5.9 0 0 1-1.413-1.284 12.4 12.4 0 0 1-2.673-5.994 7.54 7.54 0 0 1 .435-4.44 5.06 5.06 0 0 1 3.734-3.062 4.26 4.26 0 0 1 2.272.189l1.555.53c.273.105.575.105.848 0a19 19 0 0 1 2.014-.648 4.86 4.86 0 0 1 5.11 1.59z"/>'
			. '<path fill="currentColor" d="M16.191 2a3.9 3.9 0 0 1-.235 1.814 4.93 4.93 0 0 1-2.143 2.496 3.1 3.1 0 0 1-1.614.436c-.153 0-.188 0-.2-.2a4.18 4.18 0 0 1 .907-2.709 4.7 4.7 0 0 1 3.05-1.813z"/>'
			. '</svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Enqueue admin assets only on matching plugin admin screens.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		$records_hooks = array(
			'toplevel_page_apple-warranty',
		);

		$images_hooks = array(
			'apple-warranty_page_apple-warranty-images',
		);

		$import_hooks = array(
			'apple-warranty_page_apple-warranty-import',
		);

		if ( in_array( $hook, $records_hooks, true ) ) {
			wp_enqueue_style(
				'apple-warranty-admin',
				APPLE_WARRANTY_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				APPLE_WARRANTY_VERSION
			);
			wp_enqueue_script(
				'apple-warranty-admin-records',
				APPLE_WARRANTY_PLUGIN_URL . 'assets/js/admin-records.js',
				array( 'jquery' ),
				APPLE_WARRANTY_VERSION,
				true
			);
			return;
		}

		if ( in_array( $hook, $images_hooks, true ) ) {
			wp_enqueue_media();
			wp_enqueue_style(
				'apple-warranty-admin',
				APPLE_WARRANTY_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				APPLE_WARRANTY_VERSION
			);
			wp_enqueue_script(
				'apple-warranty-admin-images',
				APPLE_WARRANTY_PLUGIN_URL . 'assets/js/admin-images.js',
				array( 'jquery' ),
				APPLE_WARRANTY_VERSION,
				true
			);
			return;
		}

		if ( in_array( $hook, $import_hooks, true ) ) {
			wp_enqueue_style(
				'apple-warranty-admin-import',
				APPLE_WARRANTY_PLUGIN_URL . 'assets/css/admin-import.css',
				array(),
				APPLE_WARRANTY_VERSION
			);
			wp_enqueue_script(
				'apple-warranty-admin-import',
				APPLE_WARRANTY_PLUGIN_URL . 'assets/js/admin-import.js',
				array(),
				APPLE_WARRANTY_VERSION,
				true
			);
			wp_localize_script(
				'apple-warranty-admin-import',
				'appleWarrantyAdmin',
				array(
					'import' => array(
						'previewing' => __( 'در حال آماده‌سازی پیش‌نمایش…', 'apple-warranty-inquiry' ),
						'importing'  => __( 'در حال درون‌ریزی داده‌ها…', 'apple-warranty-inquiry' ),
					),
				)
			);
		}
	}
}
