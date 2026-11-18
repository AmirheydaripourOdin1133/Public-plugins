<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Template: Modal (popup) style.
 *
 * Variables available:
 *   $tabs     - array of tabs (each: id, title, icon, items)
 *   $html_id
 */
$tabs   = DSSG_Builder::tabs();
$html_id = $html_id ?? 'dssg-guide-modal-' . dechex( mt_rand() );
$first_tab = ! empty( $tabs ) ? reset( $tabs ) : array();
$items     = isset( $first_tab['items'] ) && is_array( $first_tab['items'] ) ? $first_tab['items'] : array();
$trigger_id = $html_id . '-trigger';
$modal_id   = $html_id . '-modal';
?>
<div class="dssg dssg-style-modal" id="<?php echo esc_attr( $html_id ); ?>" dir="rtl">
	<button class="dssg-modal-trigger" id="<?php echo esc_attr( $trigger_id ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $modal_id ); ?>">
		<span class="dssg-modal-icon"><?php echo DSSG_Icons::icon( 'info' ); ?></span>
		<span class="dssg-modal-label"><?php esc_html_e( 'راهنمای سایز و مراقبت', 'ds-size-guide' ); ?></span>
		<span class="dssg-modal-chevron" aria-hidden="true"></span>
	</button>

	<div class="dssg-modal-backdrop" id="<?php echo esc_attr( $modal_id ); ?>" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_id . '-title' ); ?>" style="display:none">
		<div class="dssg-modal">
			<div class="dssg-modal-header">
				<h2 id="<?php echo esc_attr( $modal_id . '-title' ); ?>"><?php esc_html_e( 'راهنمای سایز و مراقبت', 'ds-size-guide' ); ?></h2>
				<button class="dssg-modal-close" aria-label="<?php esc_attr_e( 'بستن', 'ds-size-guide' ); ?>">
					<span class="dssg-modal-icon"><?php echo DSSG_Icons::icon( 'delete' ); ?></span>
				</button>
			</div>
			<div class="dssg-modal-body">
				<?php foreach ( $tabs as $index => $tab ) : ?>
					<?php
					$tab_id    = sanitize_html_class( $tab['id'] ?? 'tab-' . $index );
					$is_active = $index === 0 ? ' dssg-is-active' : '';
					$hidden    = $index === 0 ? '' : ' style="display:none"';
					$items     = isset( $tab['items'] ) && is_array( $tab['items'] ) ? $tab['items'] : array();
					?>
					<div class="dssg-modal-tab<?php echo $is_active; ?>" id="<?php echo esc_attr( $tab_id ); ?>"<?php echo $hidden; ?>>
						<?php foreach ( $items as $item ) : ?>
							<?php
							$item_id   = DSSG_Builder::item_html_id( $item, $tab_id );
							$is_open   = ! empty( $item['open'] ) ? ' open' : '';
							$item_icon = DSSG_Builder::item_icon( $item );
							?>
							<details name="<?php echo esc_attr( 'dssg-accordion-modal-' . $tab_id ); ?>" class="dssg-item<?php echo $is_open; ?>" id="<?php echo esc_attr( $item_id ); ?>">
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
		</div>
	</div>
</div>