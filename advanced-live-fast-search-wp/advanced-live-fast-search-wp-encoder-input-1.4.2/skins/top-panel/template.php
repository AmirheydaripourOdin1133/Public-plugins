<?php
/**
 * Skin: top-panel — پنل جستجوی نیم‌صفحه (بازشونده از بالا)
 *
 * @var array $mfs
 */
defined('ABSPATH') || exit;

$mfs_show_trigger = !isset($mfs['trigger']) || $mfs['trigger'];
$mfs_show_panel   = !isset($mfs['panel']) || $mfs['panel'];

$mfs_sections = !empty($mfs['sections']) ? $mfs['sections'] : array();
$mfs_terms    = !empty($mfs['terms']) ? $mfs['terms'] : array();

if (!function_exists('mfs_tp_label')) {
	function mfs_tp_label($val, $key = '') {
		if (is_array($val)) {
			return isset($val['label']) ? $val['label'] : $key;
		}
		return (is_string($val) && '' !== $val) ? $val : $key;
	}
}

$mfs_product = array();
if (isset($mfs_sections['product'])) {
	$mfs_product  = array('product' => $mfs_sections['product']);
	$mfs_sections = array_diff_key($mfs_sections, $mfs_product);
}

if ($mfs_show_trigger) {
	echo MFS_Render::trigger_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

if (!$mfs_show_panel) {
	return;
}
?>

<div class="mfs-top-panel-overlay" data-mfs-overlay="top-panel" hidden></div>

<div class="mfs-top-panel" data-mfs="top-panel" hidden <?php echo isset($mfs['accent_style']) ? $mfs['accent_style'] : ''; // phpcs:ignore ?>>
	<div class="mfs-top-panel__head">
		<form id="search-by-json-form" class="mfs-top-panel__form" role="search" onsubmit="return false;">
			<input type="text" id="fast-search-input" class="mfs-top-panel__input mfs-input"
				placeholder="<?php echo esc_attr(isset($mfs['placeholder']) ? $mfs['placeholder'] : 'جستجو در محصولات…'); ?>"
				autocomplete="off"
				aria-label="<?php esc_attr_e('عبارت جستجو', 'advanced-live-fast-search-wp'); ?>">
			<button type="button" class="mfs-panel-close" aria-label="<?php esc_attr_e('بستن جستجو', 'advanced-live-fast-search-wp'); ?>">
				<?php echo MFS_Icons::trash_svg(20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</form>
	</div>

	<div id="fast-search-body" class="fast-search-body mfs-top-panel__body">
		<div class="default">
			<?php echo MFS_Icons::empty_hint_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if (has_nav_menu('mfs-popular')): ?>
				<div class="popular">
					<div class="title-popular"><?php esc_html_e('جستجوهای پرطرفدار:', 'advanced-live-fast-search-wp'); ?></div>
					<?php
					wp_nav_menu(array(
						'container_class' => 'menu-popular',
						'theme_location'  => 'mfs-popular',
						'depth'           => 1,
					));
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php echo MFS_Icons::not_found_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="is-search d-none">
			<?php foreach ($mfs_terms as $mfs_tax => $mfs_term): ?>
				<div class="mfs-section d-none" data-section="<?php echo esc_attr($mfs_tax); ?>">
					<div class="mfs-title"><?php echo esc_html(mfs_tp_label($mfs_term, $mfs_tax)); ?></div>
					<ul class="mfs-list mfs-list--terms"></ul>
				</div>
			<?php endforeach; ?>
			<?php foreach ($mfs_product as $mfs_pt => $mfs_sec): ?>
				<div class="mfs-section d-none" data-section="<?php echo esc_attr($mfs_pt); ?>">
					<div class="mfs-title"><?php echo esc_html(mfs_tp_label($mfs_sec, $mfs_pt)); ?></div>
					<ul class="mfs-list mfs-list--products"></ul>
				</div>
			<?php endforeach; ?>
			<?php foreach ($mfs_sections as $mfs_pt => $mfs_sec): ?>
				<div class="mfs-section d-none" data-section="<?php echo esc_attr($mfs_pt); ?>">
					<div class="mfs-title"><?php echo esc_html(mfs_tp_label($mfs_sec, $mfs_pt)); ?></div>
					<ul class="mfs-list"></ul>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
