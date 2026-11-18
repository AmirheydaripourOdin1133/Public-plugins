<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Template: Accordion style (rtl-ready, native <details> with JS fallback).
 *
 * Variables available:
 *   $tabs   - array of tabs (each: id, title, icon, items)
 *   $items  - current tab items (first tab used if single)
 *   $html_id
 *   $palette_css
 */
$tabs      = DSSG_Builder::tabs();
$first_tab = ! empty( $tabs ) ? reset( $tabs ) : array();
$items     = isset( $first_tab['items'] ) && is_array( $first_tab['items'] ) ? $first_tab['items'] : array();
$tab_id    = isset( $first_tab['id'] ) ? sanitize_html_class( $first_tab['id'] ) : 'dssg-tab';
$html_id   = $html_id ?? 'dssg-guide-' . dechex( mt_rand() );
$group     = 'dssg-accordion-' . $html_id;
?>
<div class="dssg dssg-style-accordion" id="<?php echo esc_attr( $html_id ); ?>" dir="rtl">
	<?php foreach ( $items as $item ) : ?>
		<?php
		$item_id   = DSSG_Builder::item_html_id( $item );
		$is_open   = ! empty( $item['open'] ) ? ' open' : '';
		$item_icon = DSSG_Builder::item_icon( $item );
		?>
		<details name="<?php echo esc_attr( $group ); ?>" class="dssg-item<?php echo $is_open; ?>" id="<?php echo esc_attr( $item_id ); ?>">
			<summary class="dssg-summary">
				<span class="dssg-icon"><?php echo $item_icon; ?></span>
				<span class="dssg-title"><?php echo esc_html( $item['title'] ?? '' ); ?></span>
				<span class="dssg-chevron" aria-hidden="true"></span>
			</summary>
			<div class="dssg-content">
				<?php echo DSSG_Builder::item_content( $item['content'] ?? '' ); ?>
			</div>
		</details>
	<?php endforeach; ?>
</div>