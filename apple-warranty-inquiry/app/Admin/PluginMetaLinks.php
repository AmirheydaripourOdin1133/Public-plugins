<?php
/**
 * Adds useful plugin links in wp-admin plugins list.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Admin;

defined( 'ABSPATH' ) || exit;

final class PluginMetaLinks {

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Add docs and author links in plugins list.
	 *
	 * @param string[] $links Existing links.
	 * @param string   $file  Plugin file (plugin basename).
	 * @return string[]
	 */
	public function plugin_row_meta( array $links, string $file ): array {
		if ( plugin_basename( APPLE_WARRANTY_PLUGIN_FILE ) !== $file ) {
			return $links;
		}

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://github.com/AmirheydaripourOdin1133/plugins-document/tree/main/apple-warranty-inquiry' ),
			esc_html__( 'دانشنامه / مستندات', 'apple-warranty-inquiry' )
		);

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://payamava.net/' ),
			esc_html__( 'Payam Ava', 'apple-warranty-inquiry' )
		);

		$links[] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( 'https://github.com/AmirheydaripourOdin1133' ),
			esc_html__( 'Heydaripour', 'apple-warranty-inquiry' )
		);

		return $links;
	}
}

