<?php
/**
 * Ajax captcha handlers.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Ajax;

use AppleWarranty\Services\CaptchaService;

defined( 'ABSPATH' ) || exit;

/**
 * Class CaptchaHandler
 */
class CaptchaHandler {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'wp_ajax_apple_warranty_captcha_refresh', array( $this, 'refresh' ) );
		add_action( 'wp_ajax_nopriv_apple_warranty_captcha_refresh', array( $this, 'refresh' ) );
		add_action( 'wp_ajax_apple_warranty_captcha_image', array( $this, 'image' ) );
		add_action( 'wp_ajax_nopriv_apple_warranty_captcha_image', array( $this, 'image' ) );
	}

	/**
	 * Refresh captcha token.
	 */
	public function refresh(): void {
		check_ajax_referer( 'apple_warranty_frontend', 'nonce' );

		$service = new CaptchaService();
		$captcha = $service->create();

		wp_send_json_success(
			array(
				'token'     => $captcha['token'],
				'image_url' => $this->image_url( $captcha['token'] ),
			)
		);
	}

	/**
	 * Output captcha image.
	 */
	public function image(): void {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

		if ( '' === $token ) {
			status_header( 400 );
			exit;
		}

		$service = new CaptchaService();
		$code    = $service->get_code( $token );

		if ( null === $code ) {
			status_header( 404 );
			exit;
		}

		$service->output_image( $code );
	}

	/**
	 * Build captcha image URL.
	 */
	public static function image_url( string $token ): string {
		return add_query_arg(
			array(
				'action' => 'apple_warranty_captcha_image',
				'token'  => $token,
				'_t'     => time(),
			),
			admin_url( 'admin-ajax.php' )
		);
	}
}
