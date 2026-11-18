<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Plugin options, defaults and sanitisation.
 */
final class DSSG_Settings {
	private const OPTION = 'dssg_options';

	public const STYLES      = array( 'accordion', 'tabs', 'modal' );
	public const ENABLE_MODES = array( 'all', 'cats', 'products' );

	/**
	 * Full default options.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'version'      => DSSG_Install::DB_VERSION,
			'enabled'      => 1,
			'auto_render'  => 1,
			'style'        => 'accordion',
			'enable_mode'  => 'all',
			'categories'   => array(),
			'products'     => array(),
			'palette'      => DSSG_Install::default_palette(),
			'tabs'         => DSSG_Install::sample_tabs(),
		);
	}

	/**
	 * Get merged options.
	 *
	 * @return array
	 */
	public static function options() {
		$stored = get_option( self::OPTION );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Get a single option with fallback.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$options = self::options();
		return isset( $options[ $key ] ) ? $options[ $key ] : $default;
	}

	/**
	 * Update all options (atomic).
	 *
	 * @param array $next
	 * @return bool
	 */
	public static function save( array $next ) {
		$next = array_merge( self::defaults(), $next );
		$next['version'] = DSSG_Install::DB_VERSION;
		return update_option( self::OPTION, $next, false );
	}

	/**
	 * Sanitise palette array.
	 *
	 * @param array $raw
	 * @return array
	 */
	public static function sanitize_palette( array $raw ) {
		$defaults = DSSG_Install::default_palette();
		$out      = array();
		foreach ( $defaults as $k => $v ) {
			$value = isset( $raw[ $k ] ) && is_string( $raw[ $k ] ) ? trim( $raw[ $k ] ) : $v;
			if ( '' === $value || ! preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value ) ) {
				$value = $v;
			}
			$out[ $k ] = $value;
		}
		return $out;
	}

	/**
	 * Sanitise tabs structure.
	 *
	 * @param array $raw
	 * @return array
	 */
	public static function sanitize_tabs( array $raw ) {
		$tabs = array();
		foreach ( $raw as $tab ) {
			if ( ! is_array( $tab ) ) {
				continue;
			}
			$tid    = isset( $tab['id'] ) && is_string( $tab['id'] ) ? sanitize_key( $tab['id'] ) : 'tab-' . dechex( mt_rand() );
			$title  = isset( $tab['title'] ) && is_string( $tab['title'] ) ? sanitize_text_field( $tab['title'] ) : '';
			$icon   = isset( $tab['icon'] ) && is_string( $tab['icon'] ) ? sanitize_key( $tab['icon'] ) : '';
			$items  = self::sanitize_items( isset( $tab['items'] ) && is_array( $tab['items'] ) ? $tab['items'] : array() );

			if ( '' === $title ) {
				continue;
			}

			$tabs[] = array(
				'id'    => $tid,
				'title' => $title,
				'icon'  => $icon,
				'items' => $items,
			);
		}
		return $tabs;
	}

	/**
	 * Sanitise accordion items.
	 *
	 * @param array $raw
	 * @return array
	 */
	public static function sanitize_items( array $raw ) {
		$items = array();
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id    = isset( $item['id'] ) && is_string( $item['id'] ) ? sanitize_key( $item['id'] ) : 'item-' . dechex( mt_rand() );
			$title = isset( $item['title'] ) && is_string( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$icon  = isset( $item['icon'] ) && is_string( $item['icon'] ) ? sanitize_key( $item['icon'] ) : '';
			$open  = isset( $item['open'] ) ? (bool) $item['open'] : false;
			// Content is raw HTML from the editor; only strip dangerous control chars.
			$content = isset( $item['content'] ) && is_string( $item['content'] ) ? $item['content'] : '';

			if ( '' === $title ) {
				continue;
			}

			$items[] = array(
				'id'      => $id,
				'title'   => $title,
				'icon'    => $icon,
				'open'    => $open,
				'content' => $content,
			);
		}
		return $items;
	}

	/**
	 * Sanitise categories array.
	 *
	 * @param mixed $raw
	 * @return array
	 */
	public static function sanitize_categories( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $id ) {
			$id = absint( $id );
			if ( $id > 0 && taxonomy_exists( 'product_cat' ) && term_exists( $id, 'product_cat' ) ) {
				$out[] = $id;
			}
		}
		return array_unique( $out );
	}

	/**
	 * Sanitise products array.
	 *
	 * @param mixed $raw
	 * @return array
	 */
	public static function sanitize_products( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $id ) {
			$id = absint( $id );
			if ( $id > 0 && get_post_type( $id ) === 'product' ) {
				$out[] = $id;
			}
		}
		return array_unique( $out );
	}

	/**
	 * Sanitise full options POST array.
	 *
	 * @param array $raw
	 * @return array
	 */
	public static function sanitize_options( array $raw ) {
		$next = self::defaults();

		$next['style']       = isset( $raw['style'] ) && in_array( $raw['style'], self::STYLES, true ) ? $raw['style'] : 'accordion';
		$next['enable_mode'] = isset( $raw['enable_mode'] ) && in_array( $raw['enable_mode'], self::ENABLE_MODES, true ) ? $raw['enable_mode'] : 'all';
		$next['enabled']     = isset( $raw['enabled'] ) ? 1 : 0;
		$next['auto_render'] = isset( $raw['auto_render'] ) ? 1 : 0;
		$next['categories']  = self::sanitize_categories( isset( $raw['categories'] ) ? $raw['categories'] : array() );
		$next['products']    = self::sanitize_products( isset( $raw['products'] ) ? $raw['products'] : array() );
		$next['palette']     = self::sanitize_palette( isset( $raw['palette'] ) && is_array( $raw['palette'] ) ? $raw['palette'] : array() );
		$next['tabs']        = self::sanitize_tabs( isset( $raw['tabs'] ) && is_array( $raw['tabs'] ) ? $raw['tabs'] : array() );
		$next['version']     = DSSG_Install::DB_VERSION;

		return $next;
	}

	/**
	 * Check whether the guide should show for a product.
	 *
	 * @param int $product_id
	 * @return bool
	 */
	public static function is_enabled_for_product( $product_id ) {
		$options = self::options();

		// Global master switch.
		if ( empty( $options['enabled'] ) ) {
			return false;
		}

		$mode = $options['enable_mode'];

		// Per-product explicit switch wins.
		$product_state = DSSG_Install::product_state( $product_id );
		if ( 'yes' === $product_state ) {
			return true;
		}
		if ( 'no' === $product_state ) {
			return false;
		}

		switch ( $mode ) {
			case 'all':
				return true;
			case 'cats':
				return self::product_in_categories( $product_id, $options['categories'] );
			case 'products':
				return in_array( $product_id, $options['products'], true );
			default:
				return false;
		}
	}

	/**
	 * Check whether product is in any of the configured categories.
	 *
	 * @param int   $product_id
	 * @param array $category_ids
	 * @return bool
	 */
	public static function product_in_categories( $product_id, array $category_ids ) {
		if ( empty( $category_ids ) ) {
			return false;
		}
		$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
		return count( array_intersect( $terms, $category_ids ) ) > 0;
	}
}