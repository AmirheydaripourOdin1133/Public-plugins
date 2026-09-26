<?php
/**
 * MFS skin: inline
 *
 * @var array $mfs {
 * @type string $skin         Skin slug.
 * @type string $type         Skin type.
 * @type string $skin_url     Skin assets URL.
 * @type string $placeholder  Input placeholder text.
 * @type array  $sections     Post type sections: slug => label.
 * @type array  $terms        Taxonomy sections: slug => label.
 * @type string $accent_style Inline style attribute for accent color.
 * @type bool   $trigger      Whether to render the trigger button.
 * }
 */

defined('ABSPATH') || exit;

$mfs_section_map = array(
	'product' => array('section' => 'products', 'title' => 'title-products', 'list' => 'list-products'),
	'post' => array('section' => 'posts', 'title' => 'title-posts', 'list' => 'list-posts'),
);
?>
<form action="<?php echo esc_url(home_url('/')); ?>" id="search-by-json-form" role="search" data-mfs="inline" <?php echo $mfs['accent_style']; // phpcs:ignore ?>>
	<div class="search-by-json" id="search-by-json">
		<div class="input">
			<input type="text" autocomplete="off" placeholder="<?php echo esc_attr($mfs['placeholder']); ?>" name="s"
				id="fast-search-input" class="mfs-input"
				aria-label="<?php esc_attr_e('عبارت جستجو', 'advanced-live-fast-search-wp'); ?>">
			<div class="empty d-none" role="button" tabindex="0"
				aria-label="<?php esc_attr_e('پاک‌کردن عبارت', 'advanced-live-fast-search-wp'); ?>">
				<?php echo MFS_Icons::trash_svg(18); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="icon" role="button" tabindex="0"
				aria-label="<?php esc_attr_e('جستجو', 'advanced-live-fast-search-wp'); ?>">
				<?php echo MFS_Icons::search_svg(18); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
		<div class="body fast-search-body show-hide fast-hide" id="fast-search-body">
			<div class="default">
				<?php echo apply_filters('mfs_inline_default_banner', ''); ?>
				<?php echo MFS_Icons::empty_hint_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if (has_nav_menu('mfs-popular')): ?>
					<div class="popular">
						<div class="title-popular">
							<?php esc_html_e('جستجوهای پرطرفدار:', 'advanced-live-fast-search-wp'); ?>
						</div>
						<?php
						wp_nav_menu(array(
							'container_class' => 'menu-popular',
							'theme_location' => 'mfs-popular',
							'depth' => 1,
						));
						?>
					</div>
				<?php endif; ?>
			</div>
			<?php echo MFS_Icons::not_found_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="is-search d-none">
				<?php
				// تفکیک بخش محصولات از سایر post type ها برای کنترل ترتیب رندرینگ DOM
				$mfs_product_sections = isset($mfs['sections']['product']) ? array('product' => $mfs['sections']['product']) : array();
				$mfs_other_sections = array_diff_key($mfs['sections'], $mfs_product_sections);
				?>

				<!-- ۱. سکشن محصولات (اولویت اول) -->
				<?php foreach ($mfs_product_sections as $key => $label): ?>
					<?php $map = isset($mfs_section_map[$key]) ? $mfs_section_map[$key] : array('section' => 'products', 'title' => 'title-products', 'list' => 'list-products'); ?>
					<div class="<?php echo esc_attr($map['section']); ?> mfs-section d-none"
						data-section="<?php echo esc_attr($key); ?>">
						<div class="<?php echo esc_attr($map['title']); ?> mfs-title"><?php echo esc_html($label); ?>:</div>
						<div class="<?php echo esc_attr($map['list']); ?> mfs-list"></div>
					</div>
				<?php endforeach; ?>

				<!-- ۳. سایر سکشن‌ها نظیر مقالات/نوشته‌ها و CPTها (اولویت آخر) -->
				<?php foreach ($mfs_other_sections as $key => $label):
					$map = isset($mfs_section_map[$key]) ? $mfs_section_map[$key] : array('section' => 'mfs-type-' . esc_attr($key), 'title' => 'mfs-title', 'list' => 'mfs-list');
					?>
					<div class="<?php echo esc_attr($map['section']); ?> mfs-section d-none"
						data-section="<?php echo esc_attr($key); ?>">
						<div class="<?php echo esc_attr($map['title']); ?> mfs-title"><?php echo esc_html($label); ?>:</div>
						<div class="<?php echo esc_attr($map['list']); ?> mfs-list"></div>
					</div>
				<?php endforeach; ?>
				<!-- ۲. سکشن دسته‌بندی‌ها و تاکسونومی‌ها (اولویت دوم) -->
				<?php foreach ($mfs['terms'] as $key => $label): ?>
					<div class="category mfs-section mfs-terms d-none" data-section="<?php echo esc_attr($key); ?>">
						<div class="title-category mfs-title">
							<?php echo esc_html($label); ?>:
						</div>
						<div class="list-category mfs-list">
							<ul></ul>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</form>
<div class="searchOverlay mfs-inline-overlay" data-mfs-overlay="inline" hidden></div>