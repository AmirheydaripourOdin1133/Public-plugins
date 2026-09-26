<?php
/**
 * MFS skin: split-panel
 * Inline header input → wide dual-column overlay panel.
 *
 * @var array $mfs
 */
defined('ABSPATH') || exit;

$mfs_sections = !empty($mfs['sections']) ? $mfs['sections'] : array();
$mfs_terms    = !empty($mfs['terms']) ? $mfs['terms'] : array();

$mfs_product_sections = array();
if (isset($mfs_sections['product'])) {
	$mfs_product_sections = array('product' => $mfs_sections['product']);
	$mfs_sections         = array_diff_key($mfs_sections, $mfs_product_sections);
}
?>
<div class="mfs-sp" data-mfs="split-panel" <?php echo $mfs['accent_style']; // phpcs:ignore ?>>
	<form action="<?php echo esc_url(home_url('/')); ?>" class="mfs-sp__form" role="search" id="search-by-json-form">
		<div class="mfs-sp__field">
			<span class="mfs-sp__icon-search" aria-hidden="true"><?php echo MFS_Icons::search_svg(20); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<input type="text"
				class="mfs-sp__input mfs-input"
				id="fast-search-input"
				name="s"
				autocomplete="off"
				placeholder="<?php echo esc_attr($mfs['placeholder']); ?>"
				aria-label="<?php esc_attr_e('عبارت جستجو', 'advanced-live-fast-search-wp'); ?>"
				aria-expanded="false"
				aria-controls="fast-search-body">
			<button type="button" class="mfs-sp__clear d-none" aria-label="<?php esc_attr_e('پاک‌کردن عبارت', 'advanced-live-fast-search-wp'); ?>">
				<?php echo MFS_Icons::trash_svg(18); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		</div>
	</form>

	<div class="mfs-sp__panel" id="fast-search-body" hidden>
		<div class="default mfs-sp__default">
			<?php echo MFS_Icons::empty_hint_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if (has_nav_menu('mfs-popular')) : ?>
				<div class="popular mfs-sp__popular">
					<div class="title-popular mfs-sp__label"><?php esc_html_e('جستجوهای پرطرفدار:', 'advanced-live-fast-search-wp'); ?></div>
					<?php
					wp_nav_menu(array(
						'container'       => 'div',
						'container_class' => 'menu-popular mfs-sp__chips',
						'theme_location'  => 'mfs-popular',
						'depth'           => 1,
						'items_wrap'      => '<ul>%3$s</ul>',
					));
					?>
				</div>
			<?php endif; ?>
		</div>

		<div class="is-search mfs-sp__results d-none">
			<?php if (!empty($mfs_terms)) : ?>
				<div class="mfs-sp__terms-row">
					<span class="mfs-sp__label"><?php esc_html_e('دسته‌بندی‌ها:', 'advanced-live-fast-search-wp'); ?></span>
					<div class="mfs-sp__terms">
						<?php foreach ($mfs_terms as $tax => $label) : ?>
							<div class="mfs-section d-none" data-section="<?php echo esc_attr($tax); ?>">
								<ul class="mfs-list mfs-sp__chips"></ul>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="mfs-sp__cols">
				<?php foreach ($mfs_product_sections as $key => $label) : ?>
					<section class="mfs-sp__col mfs-section d-none" data-section="<?php echo esc_attr($key); ?>">
						<div class="mfs-sp__col-head">
							<strong class="mfs-sp__col-title"><?php echo esc_html($label); ?></strong>
							<a class="mfs-sp__view-all" href="<?php echo esc_url(home_url('/')); ?>" data-mfs-view-all>
								<span><?php esc_html_e('مشاهده همه', 'advanced-live-fast-search-wp'); ?></span>
								<span class="mfs-sp__view-all-icon" aria-hidden="true">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</span>
							</a>
						</div>
						<div class="mfs-list mfs-sp__col-list"></div>
					</section>
				<?php endforeach; ?>

				<?php foreach ($mfs_sections as $key => $label) : ?>
					<section class="mfs-sp__col mfs-section d-none" data-section="<?php echo esc_attr($key); ?>">
						<div class="mfs-sp__col-head">
							<strong class="mfs-sp__col-title"><?php echo esc_html($label); ?></strong>
							<a class="mfs-sp__view-all" href="<?php echo esc_url(home_url('/')); ?>" data-mfs-view-all>
								<span><?php esc_html_e('مشاهده همه', 'advanced-live-fast-search-wp'); ?></span>
								<span class="mfs-sp__view-all-icon" aria-hidden="true">
									<svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 6 9 12l6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</span>
							</a>
						</div>
						<div class="mfs-list mfs-sp__col-list"></div>
					</section>
				<?php endforeach; ?>
			</div>
		</div>

		<?php echo MFS_Icons::not_found_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
<div class="mfs-sp-overlay" data-mfs-overlay="split-panel" hidden></div>
