<?php
/**
 * Simple IP-based rate limiting.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class RateLimiter
 */
class RateLimiter {

	private const LIMIT     = 10;
	private const WINDOW    = 300; // 5 minutes.
	private const TRANSIENT = 'apple_warranty_rl_';

	/**
	 * Check if request is allowed.
	 *
	 * @param string $action Action key.
	 */
	public function is_allowed( string $action = 'lookup' ): bool {
		$key   = self::TRANSIENT . $action . '_' . $this->client_hash();
		$count = (int) get_transient( $key );

		return $count < self::LIMIT;
	}

	/**
	 * Record a request attempt.
	 *
	 * @param string $action Action key.
	 */
	public function hit( string $action = 'lookup' ): void {
		$key   = self::TRANSIENT . $action . '_' . $this->client_hash();
		$count = (int) get_transient( $key );

		if ( $count <= 0 ) {
			set_transient( $key, 1, self::WINDOW );
			return;
		}

		set_transient( $key, $count + 1, self::WINDOW );
	}

	/**
	 * Client identifier hash.
	 */
	private function client_hash(): string {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$parts = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip    = trim( $parts[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return md5( $ip . '|' . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ) );
	}
}
