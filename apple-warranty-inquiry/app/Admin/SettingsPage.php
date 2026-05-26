<?php
/**
 * Plugin settings page.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Class SettingsPage
 */
class SettingsPage {

	/**
	 * Render settings page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( isset( $_POST['apple_warranty_settings_nonce'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apple_warranty_settings_nonce'] ) ), 'apple_warranty_save_settings' ) ) {
				$description = isset( $_POST['warranty_description'] ) ? wp_kses_post( wp_unslash( $_POST['warranty_description'] ) ) : '';
				$help_url    = isset( $_POST['serial_help_url'] ) ? esc_url_raw( wp_unslash( $_POST['serial_help_url'] ) ) : '';
				update_option( 'apple_warranty_description', $description );
				update_option( 'apple_warranty_serial_help_url', $help_url );
				add_settings_error( 'apple_warranty', 'saved', __( 'تنظیمات ذخیره شد.', 'apple-warranty-inquiry' ), 'success' );
			}
		}

		$description = get_option( 'apple_warranty_description', '' );
		$help_url    = get_option( 'apple_warranty_serial_help_url', '' );

		include APPLE_WARRANTY_PLUGIN_DIR . 'templates/admin/settings.php';
	}
}
