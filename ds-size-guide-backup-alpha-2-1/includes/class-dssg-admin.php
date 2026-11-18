<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Admin settings page styled like ds-woo-tools (DS design system).
 */
final class DSSG_Admin {
	const PAGE_SLUG = 'ds-size-guide';

	/**
	 * Register admin hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_dssg_save', array( __CLASS__, 'save_settings' ) );
	}

	/**
	 * Add submenu under WooCommerce.
	 */
	public static function register_menu() {
		add_submenu_page(
			'woocommerce',
			esc_html__( 'راهنمای سایز بندی محصولات', 'ds-size-guide' ),
			esc_html__( 'راهنمای سایز بندی', 'ds-size-guide' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( __CLASS__, 'settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_ds-size-guide' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'dssg-admin', DSSG_URL . 'assets/admin.css', array( 'dashicons' ), DSSG_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'dssg-admin', DSSG_URL . 'assets/admin.js', array( 'jquery', 'wp-color-picker' ), DSSG_VERSION, true );
		wp_localize_script( 'dssg-admin', 'dssgAdmin', array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'dssg_admin' ),
			'saveNonce' => wp_create_nonce( 'dssg_save' ),
			'i18n'      => array(
				'saved'  => __( 'تنظیمات ذخیره شد.', 'ds-size-guide' ),
				'error'  => __( 'خطا در ذخیره تنظیمات.', 'ds-size-guide' ),
				'copy'   => __( 'کپی شد.', 'ds-size-guide' ),
				'delete' => __( 'آیا مطمئن هستید که می‌خواهید این مورد را حذف کنید؟', 'ds-size-guide' ),
			),
		) );
	}

	/**
	 * Current active section.
	 *
	 * @return string
	 */
	public static function current_section() {
		$section = isset( $_GET['section'] ) && is_string( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'guide';
		$allowed = array( 'guide', 'tabs', 'style', 'enable', 'help' );
		return in_array( $section, $allowed, true ) ? $section : 'guide';
	}

	/**
	 * Render the settings page.
	 */
	public static function settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'شما اجازه مدیریت این تنظیمات را ندارید.', 'ds-size-guide' ) );
		}

		$options = DSSG_Settings::options();
		$section = self::current_section();
		$tabs    = $options['tabs'];

		$sections = array(
			'guide'  => array( 'title' => __( 'راهنمای سایز بندی', 'ds-size-guide' ), 'icon' => 'dashicons-chart-bar' ),
			'tabs'   => array( 'title' => __( 'تب و آکاردئون‌ها', 'ds-size-guide' ), 'icon' => 'dashicons-editor-code' ),
			'style'  => array( 'title' => __( 'استایل و ظاهر', 'ds-size-guide' ), 'icon' => 'dashicons-palettes' ),
			'enable' => array( 'title' => __( 'فعال‌سازی و شرایط', 'ds-size-guide' ), 'icon' => 'dashicons-toggle-on' ),
			'help'   => array( 'title' => __( 'راهنمای استفاده', 'ds-size-guide' ), 'icon' => 'dashicons-editor-help' ),
		);

		?>
		<div class="wrap dssg-admin" dir="rtl">
			<h1 class="screen-reader-text"><?php esc_html_e( 'راهنمای سایز بندی محصولات سفارشی', 'ds-size-guide' ); ?></h1>
			<header class="dssg-header">
				<div class="dssg-brand">
					<span class="dashicons dashicons-store" aria-hidden="true"></span>
					<div>
						<div class="dssg-title" aria-hidden="true">راهنمای سایز بندی محصولات سفارشی</div>
						<p><?php esc_html_e( 'تنظیمات görün، محتوای داینامیک و شرایط نمایش راهنمای سایز برای فروشگاه ووکامرس شما.', 'ds-size-guide' ); ?></p>
					</div>
				</div>
				<nav class="dssg-tabs" aria-label="<?php esc_attr_e( 'بخش‌های تنظیمات', 'ds-size-guide' ); ?>">
					<?php foreach ( $sections as $key => $item ) : ?>
						<a class="<?php echo $section === $key ? 'is-active' : ''; ?>"
						   href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'section' => $key ), admin_url( 'admin.php' ) ) ); ?>"
						   <?php echo $section === $key ? 'aria-current="page"' : ''; ?>>
							<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span><?php echo esc_html( $item['title'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</header>

			<?php if ( isset( $_GET['saved'] ) && '1' === $_GET['saved'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تنظیمات ذخیره شد.', 'ds-size-guide' ); ?></p></div>
			<?php endif; ?>

			<?php if ( 'guide' === $section ) : ?>
				<?php self::render_guide_section( $options ); ?>
			<?php elseif ( 'tabs' === $section ) : ?>
				<?php self::render_tabs_section( $tabs ); ?>
			<?php elseif ( 'style' === $section ) : ?>
				<?php self::render_style_section( $options ); ?>
			<?php elseif ( 'enable' === $section ) : ?>
				<?php self::render_enable_section( $options ); ?>
			<?php elseif ( 'help' === $section ) : ?>
				<?php self::render_help_section(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* --------------------------------------------------------- sections */

	private static function render_guide_section( array $options ) {
		?>
		<section class="dssg-card">
			<div class="dssg-card-heading">
				<div>
					<h2><?php esc_html_e( 'فعال‌سازی کلی راهنمای سایز بندی', 'ds-size-guide' ); ?></h2>
					<p><?php esc_html_e( 'راهنمای سایز بندی را در صفحهٔ محصولات ووکامرس نمایش دهید. محیط نمایش در صفحهٔ «استایل و ظاهر» قابل تغییر است.', 'ds-size-guide' ); ?></p>
				</div>
				<span class="dssg-status <?php echo ! empty( $options['enabled'] ) ? 'is-on' : 'is-off'; ?>">
					<?php echo ! empty( $options['enabled'] ) ? esc_html__( 'فعال', 'ds-size-guide' ) : esc_html__( 'غیرفعال', 'ds-size-guide' ); ?>
				</span>
			</div>
			<form class="dssg-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dssg_save">
				<input type="hidden" name="dssg_section" value="guide">
				<?php wp_nonce_field( 'dssg_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dssg_enabled"><?php esc_html_e( 'فعال‌سازی کلی راهنمای سایز بندی', 'ds-size-guide' ); ?></label></th>
						<td>
							<label><input type="checkbox" name="dssg[enabled]" value="1" <?php checked( ! empty( $options['enabled'] ), true ); ?>>
							<?php esc_html_e( 'راهنمای سایز بندی در سایت فعال باشد', 'ds-size-guide' ); ?></label>
						</td>
					</tr>
				</table>
				<div class="dssg-card-footer">
					<span>کد میانبر: <code>[dssg_guide product_id="123"]</code></span>
					<?php submit_button( __( 'ذخیره تنظیمات', 'ds-size-guide' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</section>
		<?php
	}

	private static function render_tabs_section( array $tabs ) {
		?>
		<section class="dssg-card">
			<div class="dssg-card-heading">
				<div>
					<h2><?php esc_html_e( 'تب و آکاردئون‌ها', 'ds-size-guide' ); ?></h2>
					<p><?php esc_html_e( 'هر تب می‌تواند حاوی چند آکاردئون باشد. عنوان، آیکون، محتوای ویرایشی و حالت باز/بسته را تنظیم کنید.', 'ds-size-guide' ); ?></p>
				</div>
				<button type="button" class="button button-primary dssg-add-tab" id="dssg-add-tab">
					<span class="dashicons dashicons-plus"></span><?php esc_html_e( 'افزودن تب جدید', 'ds-size-guide' ); ?>
				</button>
			</div>
			<form class="dssg-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dssg_save">
				<input type="hidden" name="dssg_section" value="tabs">
				<?php wp_nonce_field( 'dssg_save' ); ?>

				<div id="dssg-tabs-list">
					<?php $tabIndex = 0; foreach ( $tabs as $tab ) : ?>
						<?php self::render_tab_editor( $tab, $tabIndex++ ); ?>
					<?php endforeach; ?>
				</div>

				<div class="dssg-card-footer">
					<?php submit_button( __( 'ذخیره ساختار تب‌ها', 'ds-size-guide' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</section>

		<template id="dssg-tab-template">
			<?php self::render_tab_editor( array( 'id' => '__NEW__', 'title' => '', 'icon' => 'info', 'items' => array() ), '__INDEX__' ); ?>
		</template>
		<?php
	}

	/**
	 * Render a single tab editor block.
	 *
	 * @param array $tab
	 * @param int|string $index
	 */
	public static function render_tab_editor( array $tab, $index ) {
		$id     = isset( $tab['id'] ) ? esc_attr( $tab['id'] ) : '';
		$title  = isset( $tab['title'] ) ? esc_attr( $tab['title'] ) : '';
		$icon   = isset( $tab['icon'] ) ? esc_attr( $tab['icon'] ) : 'info';
		$items  = isset( $tab['items'] ) && is_array( $tab['items'] ) ? $tab['items'] : array();
		?>
		<div class="dssg-tab-block" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="dssg-tab-header">
				<h4><?php esc_html_e( 'تب', 'ds-size-guide' ); ?></h4>
				<button type="button" class="button link dssg-duplicate-tab"><?php esc_html_e( 'کپی', 'ds-size-guide' ); ?></button>
				<button type="button" class="button link dssg-remove-tab"><?php esc_html_e( 'حذف', 'ds-size-guide' ); ?></button>
			</div>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label><?php esc_html_e( 'شناسه (id)', 'ds-size-guide' ); ?></label></th>
					<td><input type="text" name="dssg[tabs][<?php echo esc_attr( $index ); ?>][id]" value="<?php echo $id; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'عنوان', 'ds-size-guide' ); ?></label></th>
					<td><input type="text" name="dssg[tabs][<?php echo esc_attr( $index ); ?>][title]" value="<?php echo $title; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'آیکون پیش‌فرض', 'ds-size-guide' ); ?></label></th>
					<td>
						<select name="dssg[tabs][<?php echo esc_attr( $index ); ?>][icon]" class="dssg-icon-select">
							<?php foreach ( DSSG_Icons::library() as $slug => $svg ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $icon, $slug ); ?>>
									<span class="dssg-opt-icon"><?php echo $svg; ?></span><?php echo esc_html( ucfirst( $slug ) ); ?>
								</option>
							<?php endforeach; ?>
							<option value="custom" <?php selected( $icon, 'custom' ); ?>><?php esc_html_e('آیکون سفارشی (SVG)', 'ds-size-guide'); ?></option>
						</select>
					</td>
				</tr>
				<?php if ( $icon === 'custom' && isset( $tab['custom_icon'] ) ) : ?>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'آیکون آپلود شده', 'ds-size-guide' ); ?></label></th>
						<td>
							<input type="hidden" name="dssg[tabs][<?php echo esc_attr( $index ); ?>][custom_icon]" value="<?php echo esc_attr( $tab['custom_icon'] ); ?>">
							<img src="<?php echo esc_url( $tab['custom_icon'] ); ?>" style="max-width:60px;height:auto;border:1px solid #ddd;border-radius:4px;">
							<button type="button" class="button link dssg-remove-custom-icon"><?php esc_html_e( 'حذف آیکون', 'ds-size-guide' ); ?></button>
						</td>
					</tr>
				<?php endif; ?>
			</table>

			<h5><?php esc_html_e( 'آکاردئون‌های این تب', 'ds-size-guide' ); ?></h5>
			<div id="dssg-items-list-<?php echo esc_attr( $index ); ?>">
				<?php $itemIndex = 0; foreach ( $items as $item ) : ?>
					<?php self::render_item_editor( $index, $item, $itemIndex++ ); ?>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button button-secondary dssg-add-item" data-tab="<?php echo esc_attr( $index ); ?>">
				<span class="dashicons dashicons-plus"></span><?php esc_html_e( 'افزودن آکاردئون', 'ds-size-guide' ); ?>
			</button>

			<template id="dssg-item-template-<?php echo esc_attr( $index ); ?>">
				<?php self::render_item_editor( $index, array( 'id' => '__NEW__', 'title' => '', 'icon' => 'info', 'open' => false, 'content' => '' ), '__ITEM__' ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * Render a single accordion item editor.
	 *
	 * @param int|string $tabIndex
	 * @param array $item
	 * @param int|string $itemIndex
	 */
	public static function render_item_editor( $tabIndex, array $item, $itemIndex ) {
		$id      = isset( $item['id'] ) ? esc_attr( $item['id'] ) : '';
		$title   = isset( $item['title'] ) ? esc_attr( $item['title'] ) : '';
		$icon    = isset( $item['icon'] ) ? esc_attr( $item['icon'] ) : 'info';
		$open    = isset( $item['open'] ) ? (bool) $item['open'] : false;
		$content = isset( $item['content'] ) ? esc_textarea( $item['content'] ) : '';
		$custom  = isset( $item['custom_icon'] ) ? esc_url_raw( $item['custom_icon'] ) : '';
		?>
		<div class="dssg-item-block" data-index="<?php echo esc_attr( $itemIndex ); ?>">
			<div class="dssg-item-header">
				<h5><?php esc_html_e( 'آکاردئون', 'ds-size-guide' ); ?></h5>
				<button type="button" class="button link dssg-duplicate-item"><?php esc_html_e( 'کپی', 'ds-size-guide' ); ?></button>
				<button type="button" class="button link dssg-remove-item"><?php esc_html_e( 'حذف', 'ds-size-guide' ); ?></button>
			</div>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label><?php esc_html_e( 'شناسه (id)', 'ds-size-guide' ); ?></label></th>
					<td><input type="text" name="dssg[tabs][<?php echo esc_attr( $tabIndex ); ?>][items][<?php echo esc_attr( $itemIndex ); ?>][id]" value="<?php echo $id; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'عنوان', 'ds-size-guide' ); ?></label></th>
					<td><input type="text" name="dssg[tabs][<?php echo esc_attr( $tabIndex ); ?>][items][<?php echo esc_attr( $itemIndex ); ?>][title]" value="<?php echo $title; ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'آیکون', 'ds-size-guide' ); ?></label></th>
					<td>
						<select name="dssg[tabs][<?php echo esc_attr( $tabIndex ); ?>][items][<?php echo esc_attr( $itemIndex ); ?>][icon]" class="dssg-icon-select">
							<?php foreach ( DSSG_Icons::library() as $slug => $svg ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $icon, $slug ); ?>>
									<span class="dssg-opt-icon"><?php echo $svg; ?></span><?php echo esc_html( ucfirst( $slug ) ); ?>
								</option>
							<?php endforeach; ?>
							<option value="custom" <?php selected( $icon, 'custom' ); ?>><?php esc_html_e('آیکون سفارشی (SVG)', 'ds-size-guide'); ?></option>
						</select>
					</td>
				</tr>
				<?php if ( $icon === 'custom' && $custom ) : ?>
					<tr>
						<th scope="row"><label><?php esc_html_e( 'آیکون آپلود شده', 'ds-size-guide' ); ?></label></th>
						<td>
							<input type="hidden" name="dssg[tabs][<?php echo esc_attr( $tabIndex ); ?>][items][<?php echo esc_attr( $itemIndex ); ?>][custom_icon]" value="<?php echo esc_attr( $custom ); ?>">
							<img src="<?php echo esc_url( $custom ); ?>" style="max-width:60px;height:auto;border:1px solid #ddd;border-radius:4px;">
							<button type="button" class="button link dssg-remove-custom-icon"><?php esc_html_e( 'حذف آیکون', 'ds-size-guide' ); ?></button>
						</td>
					</tr>
				<?php endif; ?>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'آغاز باز؟', 'ds-size-guide' ); ?></label></th>
					<td><label><input type="checkbox" name="dssg[tabs][<?php echo esc_attr( $tabIndex ); ?>][items][<?php echo esc_attr( $itemIndex ); ?>][open]" value="1" <?php checked( $open ); ?>><?php esc_html_e( 'آکاردئون در ابتدا باز باشد', 'ds-size-guide' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e( 'محتوای HTML', 'ds-size-guide' ); ?></label></th>
					<td>
						<textarea name="dssg[tabs][<?php echo esc_attr( $tabIndex ); ?>][items][<?php echo esc_attr( $itemIndex ); ?>][content]" class="dssg-editor" rows="6"><?php echo $content; ?></textarea>
						<p class="description"><?php esc_html_e( 'محتوا را می‌توان با تگ‌های HTML، جدول و لیست نوشت.', 'ds-size-guide' ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	private static function render_style_section( array $options ) {
		$palette = $options['palette'];
		$style   = $options['style'];
		?>
		<section class="dssg-card">
			<div class="dssg-card-heading">
				<div>
					<h2><?php esc_html_e( 'سبک نمایش و پالت رنگی', 'ds-size-guide' ); ?></h2>
					<p><?php esc_html_e('انتخاب کنید که راهنمای سایز بندی به چه شکلی نمایش داده شود و رنگ‌ها را با برند سایت خود هماهنگ کنید.', 'ds-size-guide'); ?></p>
				</div>
			</div>
			<form class="dssg-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dssg_save">
				<input type="hidden" name="dssg_section" value="style">
				<?php wp_nonce_field( 'dssg_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dssg_style"><?php esc_html_e( 'سبک نمایش', 'ds-size-guide' ); ?></label></th>
						<td>
							<select name="dssg[style]" id="dssg_style">
								<option value="accordion" <?php selected( $style, 'accordion' ); ?>><?php esc_html_e( 'آکاردئون (باز و بسته)', 'ds-size-guide' ); ?></option>
								<option value="tabs" <?php selected( $style, 'tabs' ); ?>><?php esc_html_e( 'تب (بسته به محتوای هر تب)', 'ds-size-guide' ); ?></option>
								<option value="modal" <?php selected( $style, 'modal' ); ?>><?php esc_html_e( 'مودال (پاپ آپ)', 'ds-size-guide' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<section class="dssg-style-section" aria-labelledby="dssg-palette-heading">
					<div class="dssg-style-heading">
						<h3 id="dssg-palette-heading"><?php esc_html_e( 'پالت رنگی', 'ds-size-guide' ); ?></h3>
						<p><?php esc_html_e( 'رنگ‌های خالی یا نامعتبر به مقدار پیش‌فرض بازگردانده می‌شوند.', 'ds-size-guide' ); ?></p>
					</div>
					<div class="dssg-style-grid">
						<?php foreach ( DSSG_Install::default_palette() as $key => $fallback ) : ?>
							<div class="dssg-style-field">
								<label for="dssg_palette_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucfirst( $key ) ); ?></label>
								<input id="dssg_palette_<?php echo esc_attr( $key ); ?>"
								       name="dssg[palette][<?php echo esc_attr( $key ); ?>]"
								       type="text"
								       value="<?php echo esc_attr( $palette[ $key ] ?? $fallback ); ?>"
								       class="dssg-color-picker"
								       placeholder="<?php echo esc_attr( $fallback ); ?>">
							</div>
						<?php endforeach; ?>
					</div>
				</section>

				<div class="dssg-card-footer">
					<?php submit_button( __( 'ذخیره استایل و رنگ', 'ds-size-guide' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</section>
		<?php
	}

	private static function render_enable_section( array $options ) {
		$mode      = $options['enable_mode'];
		$cats      = $options['categories'];
		$products  = $options['products'];
		$all_cats  = array();
		if ( taxonomy_exists( 'product_cat' ) ) {
			$all_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'all' ) );
		}
		$all_products = array();
		if ( post_type_exists( 'product' ) ) {
			$all_products = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids' ) );
		}
		?>
		<section class="dssg-card">
			<div class="dssg-card-heading">
				<div>
					<h2><?php esc_html_e( 'شرط نمایش برای محصولات', 'ds-size-guide' ); ?></h2>
					<p><?php esc_html_e( 'تentukan کنید راهنمای سایز بندی برای کدام محصولات نمایش داده شود. تنظیم محصولی خاص (صفحهٔ ویرایش محصول) بر روی تنظیمات کلی غلبه می‌کند.', 'ds-size-guide' ); ?></p>
				</div>
			</div>
			<form class="dssg-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dssg_save">
				<input type="hidden" name="dssg_section" value="enable">
				<?php wp_nonce_field( 'dssg_save' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dssg_enable_mode"><?php esc_html_e( 'حالت فعال‌سازی', 'ds-size-guide' ); ?></label></th>
						<td>
							<select name="dssg[enable_mode]" id="dssg_enable_mode">
								<option value="all" <?php selected( $mode, 'all' ); ?>><?php esc_html_e( 'همه محصولات', 'ds-size-guide' ); ?></option>
								<option value="cats" <?php selected( $mode, 'cats' ); ?>><?php esc_html_e( 'بر اساس دسته‌بندی', 'ds-size-guide' ); ?></option>
								<option value="products" <?php selected( $mode, 'products' ); ?>><?php esc_html_e( 'بر اساس محصول انتخاب‌شده', 'ds-size-guide' ); ?></option>
							</select>
						</td>
					</tr>
					<?php if ( $mode === 'cats' ) : ?>
						<tr>
							<th scope="row"><label><?php esc_html_e( 'دسته‌بندی‌های انتخاب‌شده', 'ds-size-guide' ); ?></label></th>
							<td>
								<select name="dssg[categories][]" multiple="multiple" style="width:100%;" size="8">
									<?php foreach ( $all_cats as $term ) : ?>
										<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $cats, true ) ); ?>>
											<?php echo esc_html( $term->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'هر محصولی که در یک دسته‌بندی انتخاب‌شده باشد، راهنمای سایز بندی را نمایش می‌دهد.', 'ds-size-guide' ); ?></p>
							</td>
						</tr>
					<?php elseif ( $mode === 'products' ) : ?>
						<tr>
							<th scope="row"><label><?php esc_html_e( 'محصولات انتخاب‌شده', 'ds-size-guide' ); ?></label></th>
							<td>
								<select name="dssg[products][]" multiple="multiple" style="width:100%;" size="8">
									<?php foreach ( $all_products as $pid ) : ?>
										<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( in_array( $pid, $products, true ) ); ?>>
											<?php echo esc_html( get_the_title( $pid ) ?: '#' . $pid ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'فقط محصولات انتخاب‌شده راهنمای سایز بندی را نمایش می‌دهند.', 'ds-size-guide' ); ?></p>
							</td>
						</tr>
					<?php endif; ?>
				</table>
				<div class="dssg-card-footer">
					<?php submit_button( __( 'ذخیره شرایط نمایش', 'ds-size-guide' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</section>
		<?php
	}

	private static function render_help_section() {
		?>
		<section class="dssg-card">
			<div class="dssg-card-heading">
				<div>
					<h2><?php esc_html_e( 'راهنمای استفاده', 'ds-size-guide' ); ?></h2>
					<p><?php esc_html_e( 'چگونگی نمایش راهنمای سایز بندی در سایت شما.', 'ds-size-guide' ); ?></p>
				</div>
			</div>
			<div class="dssg-code-grid">
				<div>
					<span><?php esc_html_e( 'شورت‌کد', 'ds-size-guide' ); ?></span>
					<code>[dssg_guide product_id="123"]</code>
				</div>
				<div>
					<span><?php esc_html_e( 'اولویت هوک خودکار', 'ds-size-guide' ); ?></span>
					<code>woocommerce_before_single_product</code>
				</div>
			</div>
			<p><?php esc_html_e( 'اگر Product ID در شورت‌کد حذف شود، در صفحهٔ محصول از محصول فعلی استفاده می‌شود.', 'ds-size-guide' ); ?></p>
		</section>
		<?php
	}

	/**
	 * Save settings from POST.
	 */
	public static function save_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'ds-size-guide' ) );
		}
		check_admin_referer( 'dssg_save' );

		$section = isset( $_POST['dssg_section'] ) && is_string( $_POST['dssg_section'] ) ? sanitize_key( wp_unslash( $_POST['dssg_section'] ) ) : '';
		$allowed = array( 'guide', 'tabs', 'style', 'enable' );
		if ( ! in_array( $section, $allowed, true ) ) {
			wp_die( esc_html__( 'بخش نامعتبر است.', 'ds-size-guide' ) );
		}

		$raw = isset( $_POST['dssg'] ) && is_array( $_POST['dssg'] ) ? wp_unslash( $_POST['dssg'] ) : array();
		$old = DSSG_Settings::options();
		$next = $old;

		switch ( $section ) {
			case 'guide':
				$next['enabled'] = isset( $raw['enabled'] ) ? 1 : 0;
				break;
			case 'tabs':
				$next['tabs'] = DSSG_Settings::sanitize_tabs( isset( $raw['tabs'] ) && is_array( $raw['tabs'] ) ? $raw['tabs'] : array() );
				break;
			case 'style':
				$next['style'] = isset( $raw['style'] ) && in_array( $raw['style'], DSSG_Settings::STYLES, true ) ? $raw['style'] : 'accordion';
				$next['palette'] = DSSG_Settings::sanitize_palette( isset( $raw['palette'] ) && is_array( $raw['palette'] ) ? $raw['palette'] : array() );
				break;
			case 'enable':
				$next['enable_mode'] = isset( $raw['enable_mode'] ) && in_array( $raw['enable_mode'], DSSG_Settings::ENABLE_MODES, true ) ? $raw['enable_mode'] : 'all';
				$next['categories'] = DSSG_Settings::sanitize_categories( isset( $raw['categories'] ) ? $raw['categories'] : array() );
				$next['products']   = DSSG_Settings::sanitize_products( isset( $raw['products'] ) ? $raw['products'] : array() );
				break;
		}

		DSSG_Settings::save( $next );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'section' => $section, 'saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}