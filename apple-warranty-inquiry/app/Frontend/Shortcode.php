<?php
/**
 * Frontend shortcode.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Frontend;

use AppleWarranty\Ajax\CaptchaHandler;
use AppleWarranty\Services\CaptchaService;

defined( 'ABSPATH' ) || exit;

/**
 * Class Shortcode
 */
class Shortcode {

	public const TAG = 'apple_warranty_inquiry';

	/**
	 * Shortcode string for display/copy in admin.
	 */
	public static function code(): string {
		return '[' . self::TAG . ']';
	}

	/**
	 * Register shortcode.
	 */
	public function register(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Render inquiry form.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 */
	public function render( $atts = array() ): string {
		wp_enqueue_style( 'apple-warranty-frontend' );
		wp_enqueue_script( 'apple-warranty-frontend' );

		$service = new CaptchaService();
		$captcha = $service->create();

		$captcha_token = $captcha['token'];
		$captcha_url   = CaptchaHandler::image_url( $captcha_token );
		$help_url      = get_option( 'apple_warranty_serial_help_url', '' );

		ob_start();
		include APPLE_WARRANTY_PLUGIN_DIR . 'templates/form.php';
		return (string) ob_get_clean();
	}
}
