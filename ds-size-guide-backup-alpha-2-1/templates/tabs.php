<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Template: Tabs style.
 *
 * Variables available:
 *   $tabs     - array of tabs (each: id, title, icon, items)
 *   $html_id
 *   $palette_css
 */
$tabs   = DSSG_Builder::tabs();
$html_id = $html_id ?? 'dssg-guide-tabs-' . dechex( mt_rand() );
$has_multiple = count( $tabs ) > 1;
?>
<div class="dssg dssg-style-tabs" id="<?php echo esc_attr( $html_id ); ?>" dir="rtl">
	<?php if ( $has_multiple ) : ?>
		<nav class="dssg-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'تب‌های راهنمای سایز بندی', 'ds-size-guide' ); ?>">
			<?php foreach ( $tabs as $index => $tab ) : ?>
				<?php
				$tab_id    = sanitize_html_class( $tab['id'] ?? 'tab-' . $index );
				$tab_icon  = DSSG_Builder::item_icon( $tab );
				$is_active = $index === 0 ? ' dssg-is-active' : '';
				$aria      = $index === 0 ? 'true' : 'false';
				$tabpanel_id = $tab_id . '-panel';
				?>
				<button class="dssg-tab-button<?php echo $is_active; ?>"
						type="button"
						role="tab"
						aria-selected="<?php echo esc_attr( $aria ); ?>"
						aria-controls="<?php echo esc_attr( $tabpanel_id ); ?>"
						id="<?php echo esc_attr( $tab_id ); ?>"
						data-target="<?php echo esc_attr( $tabpanel_id ); ?>">
					<span class="dssg-tab-icon"><?php echo $tab_icon; ?></span>
					<span class="dssg-tab-title"><?php echo esc_html( $tab['title'] ?? '' ); ?></span>
				</button>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php foreach ( $tabs as $index => $tab ) : ?>
		<?php
		$tab_id      = sanitize_html_class( $tab['id'] ?? 'tab-' . $index );
		$tabpanel_id = $tab_id . '-panel';
		$is_active   = $index === 0 ? ' dssg-is-active' : '';
		$hidden      = $index === 0 ? '' : ' style="display:none"';
		$items       = isset( $tab['items'] ) && is_array( $tab['items'] ) ? $tab['items'] : array();
		?>
		<div class="dssg-tab-panel<?php echo $is_active; ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $tab_id ); ?>" id="<?php echo esc_attr( $tabpanel_id ); ?>"<?php echo $hidden; ?>>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$item_id   = DSSG_Builder::item_html_id( $item, $tab_id );
				$is_open   = ! empty( $item['open'] ) ? ' open' : '';
				$item_icon = DSSG_Builder::item_icon( $item );
				?>
				<details name="<?php echo esc_attr( 'dssg-accordion-' . $tab_id ); ?>" class="dssg-item<?php echo $is_open; ?>" id="<?php echo esc_attr( $item_id ); ?>">
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
	<?php endforeach; ?>
</div>