<?php
/**
 * Ajax warranty lookup handler.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Ajax;

use AppleWarranty\Helpers\PersianDigits;
use AppleWarranty\Services\CaptchaService;
use AppleWarranty\Services\LookupService;
use AppleWarranty\Services\RateLimiter;

defined( 'ABSPATH' ) || exit;

/**
 * Class LookupHandler
 */
class LookupHandler {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_ajax_apple_warranty_lookup', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_apple_warranty_lookup', array( $this, 'handle' ) );
	}

	/**
	 * Handle lookup request.
	 */
	public function handle(): void {
		check_ajax_referer( 'apple_warranty_frontend', 'nonce' );

		$limiter = new RateLimiter();

		if ( ! $limiter->is_allowed( 'lookup' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً چند دقیقه بعد تلاش کنید.', 'apple-warranty-inquiry' ) ),
				429
			);
		}

		$limiter->hit( 'lookup' );

		$serial = isset( $_POST['serial'] ) ? PersianDigits::to_ascii( sanitize_text_field( wp_unslash( $_POST['serial'] ) ) ) : '';
		$captcha = isset( $_POST['captcha'] ) ? PersianDigits::to_ascii( sanitize_text_field( wp_unslash( $_POST['captcha'] ) ) ) : '';
		$token   = isset( $_POST['captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['captcha_token'] ) ) : '';

		if ( '' === $serial ) {
			wp_send_json_error( array( 'message' => __( 'لطفاً شماره سریال را وارد کنید.', 'apple-warranty-inquiry' ) ) );
		}

		if ( '' === $captcha || '' === $token ) {
			wp_send_json_error( array( 'message' => __( 'لطفاً کد امنیتی را وارد کنید.', 'apple-warranty-inquiry' ) ) );
		}

		$captcha_service = new CaptchaService();
		if ( ! $captcha_service->validate( $token, $captcha ) ) {
			wp_send_json_error( array( 'message' => __( 'کد امنیتی نادرست است.', 'apple-warranty-inquiry' ) ) );
		}

		$service = new LookupService();
		$result  = $service->lookup( $serial );

		if ( ! $result['success'] ) {
			wp_send_json_error( array( 'message' => $result['error'] ?? __( 'خطا در استعلام.', 'apple-warranty-inquiry' ) ) );
		}

		ob_start();
		$data = $result['data'];
		include APPLE_WARRANTY_PLUGIN_DIR . 'templates/result.php';
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html' => $html,
				'data' => $data,
			)
		);
	}
}
