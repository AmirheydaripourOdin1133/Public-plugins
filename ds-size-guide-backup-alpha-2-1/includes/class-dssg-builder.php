<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Builder helpers for tab/accordion content (shared between admin and render).
 */
final class DSSG_Builder {
	/**
	 * Prepare tabs data for rendering.
	 *
	 * @return array
	 */
	public static function tabs() {
		$options = DSSG_Settings::options();
		$tabs    = $options['tabs'];
		if ( ! is_array( $tabs ) || empty( $tabs ) ) {
			$tabs = DSSG_Install::sample_tabs();
		}
		return $tabs;
	}

	/**
	 * Get icon HTML for an item (custom uploaded or library).
	 *
	 * @param array $item
	 * @return string
	 */
	public static function item_icon( array $item ) {
		$icon = isset( $item['icon'] ) ? $item['icon'] : 'info';
		if ( 'custom' === $icon && ! empty( $item['custom_icon'] ) ) {
			return sprintf( '<img src="%s" class="dssg-custom-icon" alt="" width="20" height="20">', esc_url( $item['custom_icon'] ) );
		}
		return DSSG_Icons::icon( $icon );
	}

	/**
	 * Escape item content for output (keeps HTML from editor).
	 *
	 * @param string $content
	 * @return string
	 */
	public static function item_content( $content ) {
		return wp_kses_post( $content );
	}

	/**
	 * Build a unique HTML id for an item.
	 *
	 * @param array $item
	 * @param string $prefix
	 * @return string
	 */
	public static function item_html_id( array $item, $prefix = 'dssg' ) {
		$id = isset( $item['id'] ) ? $item['id'] : dechex( mt_rand() );
		return sanitize_html_class( $prefix . '-' . $id );
	}
}