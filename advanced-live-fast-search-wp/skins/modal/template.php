<?php
/**
 * MFS skin: modal
 *
 * @var array $mfs
 */

defined('ABSPATH') || exit;

$mfs_show_trigger = !isset($mfs['trigger']) || $mfs['trigger'];
$mfs_show_panel   = !isset($mfs['panel']) || $mfs['panel'];

$mfs_section_map = array(
	'product' => array('section' => 'products games', 'title' => 'title-games', 'list' => 'list-products list-games'),
	'post'    => array('section' => 'posts', 'title' => 'title-posts', 'list' => 'list-posts'),
);

if ($mfs_show_trigger) {
	echo MFS_Render::trigger_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

if (!$mfs_show_panel) {
	return;
}
?>
<div class="search-body-box" <?php echo $mfs['accent_style']; // phpcs:ignore ?> role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e('جستجو در سایت', 'advanced-live-fast-search-wp'); ?>">
	<button type="button" class="close" aria-label="<?php esc_attr_e('بستن', 'advanced-live-fast-search-wp'); ?>"><svg
			width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="m15.958 8.042-7.916 7.916m7.916 0L8.042 8.042M12 21.5a9.5 9.5 0 1 0 0-19 9.5 9.5 0 0 0 0 19"
				stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
		</svg></button>

	<form action="<?php echo esc_url(home_url('/')); ?>" id="search-by-json-form" role="search">
		<div class="search-by-json" id="search-by-json">
			<div class="input">
				<input type="text" autocomplete="off" placeholder="<?php echo esc_attr($mfs['placeholder']); ?>"
					name="s" id="fast-search-input" class="mfs-input"
					aria-label="<?php esc_attr_e('عبارت جستجو', 'advanced-live-fast-search-wp'); ?>">
				<div class="empty d-none" role="button" tabindex="0"
					aria-label="<?php esc_attr_e('پاک‌کردن عبارت', 'advanced-live-fast-search-wp'); ?>">
					<?php echo MFS_Icons::trash_svg(20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="icon-search">
					<?php echo MFS_Icons::search_svg(20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<div class="body fast-search-body show-hide fast-hide" id="fast-search-body">
				<div class="default">
					<?php echo MFS_Icons::empty_hint_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if (has_nav_menu('mfs-popular')): ?>
						<div class="popular">
							<div class="title-popular">
								<?php esc_html_e('جستجوهای پرطرفدار:', 'advanced-live-fast-search-wp'); ?>
							</div>
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
					<?php
					$mfs_product_sections = isset($mfs['sections']['product']) ? array('product' => $mfs['sections']['product']) : array();
					$mfs_other_sections   = array_diff_key($mfs['sections'], $mfs_product_sections);
					?>
					<?php foreach ($mfs_product_sections as $key => $label): ?>
						<?php $map = isset($mfs_section_map[$key]) ? $mfs_section_map[$key] : array('section' => 'products', 'title' => 'title-products', 'list' => 'list-products'); ?>
						<div class="<?php echo esc_attr($map['section']); ?> mfs-section d-none"
							data-section="<?php echo esc_attr($key); ?>">
							<div class="<?php echo esc_attr($map['title']); ?> mfs-title">
								<?php echo esc_html($label); ?>:
							</div>
							<div class="<?php echo esc_attr($map['list']); ?> mfs-list"></div>
						</div>
					<?php endforeach; ?>
					<?php foreach ($mfs['terms'] as $key => $label): ?>
						<div class="category mfs-section mfs-terms d-none" data-section="<?php echo esc_attr($key); ?>">
							<div class="title-category mfs-title"><?php echo esc_html($label); ?>:</div>
							<div class="list-category mfs-list">
								<ul></ul>
							</div>
						</div>
					<?php endforeach; ?>
					<?php foreach ($mfs_other_sections as $key => $label):
						$map = isset($mfs_section_map[$key]) ? $mfs_section_map[$key] : array('section' => 'mfs-type-' . esc_attr($key), 'title' => 'mfs-title', 'list' => 'mfs-list');
						?>
						<div class="<?php echo esc_attr($map['section']); ?> mfs-section d-none"
							data-section="<?php echo esc_attr($key); ?>">
							<div class="<?php echo esc_attr($map['title']); ?> mfs-title"><?php echo esc_html($label); ?>:
							</div>
							<div class="<?php echo esc_attr($map['list']); ?> mfs-list"></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</form>
</div>
