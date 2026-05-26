<?php
/**
 * Ajax result partial template.
 *
 * @package AppleWarranty
 * @var array<string, mixed> $data
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $data ) ) {
	return;
}

$badge_path = APPLE_WARRANTY_PLUGIN_DIR . 'assets/images/warranty-badge.webp';
$badge_url  = file_exists( $badge_path )
	? APPLE_WARRANTY_PLUGIN_URL . 'assets/images/warranty-badge.webp'
	: '';
$has_product_image = ! empty( $data['image_url'] );
?>
<div class="awi-result<?php echo $has_product_image ? ' awi-result--has-product-image' : ''; ?>" dir="rtl">
	<div class="awi-result__header">
		<?php if ( $has_product_image ) : ?>
			<div class="awi-result__image">
				<div class="awi-result__image-slot" aria-hidden="true"></div>
				<img
					class="awi-result__product-img"
					src="<?php echo esc_url( $data['image_url'] ); ?>"
					alt="<?php echo esc_attr( $data['image_alt'] ?? '' ); ?>"
					width="120"
					height="120"
					decoding="async"
				/>
			</div>
		<?php endif; ?>
		<div class="awi-result__meta">
			<h3 class="awi-result__title"><?php echo esc_html( $data['product_name'] ?? '' ); ?></h3>
			<dl class="awi-result__dl">
				<div class="awi-result__row">
					<dt><?php esc_html_e( 'شماره سریال:', 'apple-warranty-inquiry' ); ?></dt>
					<dd dir="ltr"><?php echo esc_html( $data['serial_number'] ?? '' ); ?></dd>
				</div>
				<?php if ( ! empty( $data['start_date'] ) ) : ?>
				<div class="awi-result__row">
					<dt><?php esc_html_e( 'شروع پوشش گارانتی:', 'apple-warranty-inquiry' ); ?></dt>
					<dd><?php echo esc_html( $data['start_date'] ); ?></dd>
				</div>
				<?php endif; ?>
			</dl>
		</div>
	</div>
	<?php if ( ! empty( $data['warranty_type'] ) || ! empty( $data['end_date'] ) ) : ?>
	<div class="awi-result__warranty">
		<div class="awi-result__warranty-badge"<?php echo '' === $badge_url ? ' aria-hidden="true"' : ''; ?>>
			<?php if ( '' !== $badge_url ) : ?>
				<img src="<?php echo esc_url( $badge_url ); ?>" alt="" width="56" height="56" decoding="async" />
			<?php endif; ?>
		</div>
		<div class="awi-result__warranty-body">
			<?php if ( ! empty( $data['warranty_type'] ) ) : ?>
				<p class="awi-result__warranty-type"><?php echo esc_html( $data['warranty_type'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $data['end_date'] ) ) : ?>
				<p class="awi-result__end">
					<?php esc_html_e( 'اتمام پوشش گارانتی:', 'apple-warranty-inquiry' ); ?>
					<strong><?php echo esc_html( $data['end_date'] ); ?></strong>
				</p>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
	<?php if ( ! empty( $data['description_html'] ) ) : ?>
	<div class="awi-result__description">
		<?php echo $data['description_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post applied in service. ?>
	</div>
	<?php endif; ?>
</div>
