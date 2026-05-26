<?php
/**
 * Custom captcha generation and validation.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class CaptchaService
 */
class CaptchaService {

	private const TRANSIENT_PREFIX = 'apple_warranty_captcha_';
	private const TTL              = 600; // 10 minutes.

	/**
	 * Create new captcha token and code.
	 *
	 * @return array{token: string, code: string}
	 */
	public function create(): array {
		$token = wp_generate_password( 32, false, false );
		$code  = (string) wp_rand( 10000, 99999 );

		set_transient( self::TRANSIENT_PREFIX . $token, $code, self::TTL );

		return array(
			'token' => $token,
			'code'  => $code,
		);
	}

	/**
	 * Validate and consume captcha (one-time use).
	 *
	 * @param string $token Captcha token.
	 * @param string $input User input.
	 */
	public function validate( string $token, string $input ): bool {
		$token = sanitize_text_field( $token );
		$input = sanitize_text_field( $input );
		$input = preg_replace( '/\D/', '', $input );

		if ( '' === $token || '' === $input ) {
			return false;
		}

		$key     = self::TRANSIENT_PREFIX . $token;
		$stored  = get_transient( $key );
		delete_transient( $key );

		if ( false === $stored ) {
			return false;
		}

		return hash_equals( (string) $stored, $input );
	}

	/**
	 * Get code for token without consuming (image generation).
	 *
	 * @param string $token Token.
	 */
	public function get_code( string $token ): ?string {
		$token = sanitize_text_field( $token );

		if ( '' === $token ) {
			return null;
		}

		$stored = get_transient( self::TRANSIENT_PREFIX . $token );

		return false !== $stored ? (string) $stored : null;
	}

	/**
	 * Output captcha PNG image.
	 *
	 * @param string $code 5-digit code.
	 */
	public function output_image( string $code ): void {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			status_header( 500 );
			exit;
		}

		$width  = 120;
		$height = 44;
		$image  = imagecreatetruecolor( $width, $height );

		$bg     = imagecolorallocate( $image, 245, 245, 247 );
		$text   = imagecolorallocate( $image, 29, 29, 31 );
		$noise1 = imagecolorallocate( $image, 200, 200, 205 );
		$noise2 = imagecolorallocate( $image, 180, 180, 190 );

		imagefilledrectangle( $image, 0, 0, $width, $height, $bg );

		// Diagonal hatch noise.
		for ( $i = 0; $i < 80; $i++ ) {
			$color = ( 0 === $i % 2 ) ? $noise1 : $noise2;
			imageline( $image, wp_rand( 0, $width ), 0, wp_rand( 0, $width ), $height, $color );
		}

		for ( $i = 0; $i < 40; $i++ ) {
			imagesetpixel( $image, wp_rand( 0, $width - 1 ), wp_rand( 0, $height - 1 ), $noise2 );
		}

		$font = 5;
		$x    = 18;

		for ( $i = 0; $i < strlen( $code ); $i++ ) {
			$char = $code[ $i ];
			$y    = wp_rand( 10, 16 );
			imagestring( $image, $font, $x, $y, $char, $text );
			$x += 18;
		}

		// Slight wave distortion via pixel shift.
		$copy = imagecreatetruecolor( $width, $height );
		imagecopy( $copy, $image, 0, 0, 0, 0, $width, $height );
		imagedestroy( $image );

		header( 'Content-Type: image/png' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		imagepng( $copy );
		imagedestroy( $copy );
		exit;
	}
}
