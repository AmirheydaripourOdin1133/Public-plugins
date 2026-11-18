<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Elementor integration: widget + auto-injection into Atomic Tabs product groups.
 */
final class DSSG_Elementor {
	const WIDGET_ID = 'dssg_guide';

	/**
	 * Register Elementor hooks.
	 */
	public static function init() {
		add_action( 'elementor/widgets/widgets_registered', array( __CLASS__, 'register_widget' ) );
		add_action( 'elementor/frontend/the_content', array( __CLASS__, 'inject_tabs' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_frontend_assets' ) );
	}

	/**
	 * Register the DSSG guide widget.
	 */
	public static function register_widget() {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}
		require_once DSSG_DIR . 'includes/class-dssg-elementor-widget.php';
		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \DSSG_Elementor_Widget() );
	}

	/**
	 * Inject the guide into Elementor Atomic Tabs product groups.
	 *
	 * Mirrors the proven strategy from the previous version but uses the new
	 * dynamic settings (group id filterable, product enablement checked).
	 *
	 * @param string $html Rendered Elementor content.
	 * @return string
	 */
	public static function inject_tabs( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		if ( self::is_elementor_editor() ) {
			return $html;
		}

		if ( ! is_product() ) {
			return $html;
		}

		$product_id = get_queried_object_id();
		if ( ! DSSG_Settings::is_enabled_for_product( $product_id ) ) {
			return $html;
		}

		// Only inject into the known product-information group.
		$group = apply_filters( 'dssg_elementor_group_id', '3d04054' );
		if ( false === strpos( $html, 'data-id="' . esc_attr( $group ) . '"' ) ) {
			return $html;
		}
		if ( false !== strpos( $html, 'data-dssg-tab' ) ) {
			return $html;
		}
		if ( ! class_exists( '\\DOMDocument' ) ) {
			return $html;
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new \DOMDocument( '1.0', 'UTF-8' );
		$loaded   = $dom->loadHTML(
			'<?xml encoding="UTF-8"?><div id="dssg-fragment">' . $html . '</div>',
			LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NONET
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return $html;
		}

		$xpath = new \DOMXPath( $dom );
		$group_node = $xpath->query( '//*[@data-id="' . $group . '" and @data-element_type="e-tabs"]' )->item( 0 );
		if ( ! $group_node ) {
			return $html;
		}

		$menu = $xpath->query( './*[@data-element_type="e-tabs-menu"]', $group_node )->item( 0 );
		$area = $xpath->query( './*[@data-element_type="e-tabs-content-area"]', $group_node )->item( 0 );
		if ( ! $menu || ! $area ) {
			return $html;
		}

		$tabs   = $xpath->query( './*[@data-element_type="e-tab"]', $menu );
		$panels = $xpath->query( './*[@data-element_type="e-tab-content"]', $area );
		if ( ! $tabs->length || $tabs->length !== $panels->length ) {
			return $html;
		}

		$index = $tabs->length;

		$button = $dom->createElement( 'button' );
		$button->setAttribute( 'class', trim( str_replace( 'e--selected', '', $tabs->item( 0 )->getAttribute( 'class' ) ) ) );

		foreach ( array(
			'type'             => 'button',
			'role'             => 'tab',
			'data-element_type' => 'e-tab',
			'data-e-type'      => 'e-tab',
			'data-dssg-tab'    => 'true',
			'x-bind'           => 'tab',
			'tabindex'         => '-1',
			'aria-selected'    => 'false',
			'id'               => $group . '-tab-' . $index,
			'aria-controls'    => $group . '-tab-content-' . $index,
		) as $name => $value ) {
			$button->setAttribute( $name, $value );
		}

		$label          = $dom->createElement( 'span' );
		$original_label = $xpath->query( './*', $tabs->item( 0 ) )->item( 0 );
		if ( $original_label ) {
			$label->setAttribute( 'class', $original_label->getAttribute( 'class' ) );
		}
		$label->appendChild( $dom->createTextNode( apply_filters( 'dssg_tab_title', __( 'راهنمای سایز و مراقبت', 'ds-size-guide' ) ) ) );
		$button->appendChild( $label );

		$panel = $dom->createElement( 'div' );
		$panel->setAttribute( 'class', trim( str_replace( 'e--selected', '', $panels->item( 0 )->getAttribute( 'class' ) ) ) . ' dssg-tab-panel' );

		foreach ( array(
			'role'             => 'tabpanel',
			'data-element_type' => 'e-tab-content',
			'data-e-type'      => 'e-tab-content',
			'x-bind'           => 'tabContent',
			'id'               => $group . '-tab-content-' . $index,
			'aria-labelledby'  => $group . '-tab-' . $index,
			'style'            => 'display:none',
		) as $name => $value ) {
			$panel->setAttribute( $name, $value );
		}

		$guide = DSSG_Render::render( $product_id );
		if ( empty( $guide ) ) {
			return $html;
		}

		$guide_dom = new \DOMDocument( '1.0', 'UTF-8' );
		$previous  = libxml_use_internal_errors( true );
		$guide_dom->loadHTML( '<?xml encoding="UTF-8">' . $guide, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$section = $guide_dom->getElementsByTagName( 'div' )->item( 0 );
		if ( ! $section ) {
			return $html;
		}

		$panel->appendChild( $dom->importNode( $section, true ) );
		$menu->appendChild( $button );
		$area->appendChild( $panel );

		$root = $dom->getElementById( 'dssg-fragment' );
		if ( ! $root ) {
			return $html;
		}

		$output = '';
		foreach ( $root->childNodes as $child ) {
			$output .= $dom->saveHTML( $child );
		}

		return $output;
	}

	/**
	 * Enqueue public assets for Elementor pages that contain the guide.
	 */
	public static function maybe_frontend_assets() {
		if ( ! is_product() ) {
			return;
		}
		$product_id = get_queried_object_id();
		if ( ! DSSG_Settings::is_enabled_for_product( $product_id ) ) {
			return;
		}
		DSSG_Render::enqueue_assets();
	}

	/**
	 * Editor assets (block editor / Elementor editor notice).
	 */
	public static function editor_assets( $hook ) {
		global $pagenow;
		if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
			return;
		}
		$post = get_post();
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}
		wp_enqueue_style( 'dssg-admin', DSSG_URL . 'assets/admin.css', array( 'dashicons' ), DSSG_VERSION );
	}

	/**
	 * True inside the Elementor editor (iframe preview or editor mode).
	 *
	 * @return bool
	 */
	private static function is_elementor_editor() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}
		$elementor = \Elementor\Plugin::$instance;
		if ( $elementor && ! empty( $elementor->editor ) && is_object( $elementor->editor )
			&& method_exists( $elementor->editor, 'is_edit_mode' ) && $elementor->editor->is_edit_mode() ) {
			return true;
		}
		return false;
	}
}