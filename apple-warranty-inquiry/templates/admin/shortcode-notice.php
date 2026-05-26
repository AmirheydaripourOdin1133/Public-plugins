<?php
/**
 * Shortcode usage notice for admin.
 *
 * @package AppleWarranty
 */

defined( 'ABSPATH' ) || exit;

use AppleWarranty\Frontend\Shortcode;

$shortcode = Shortcode::code();
?>
<div class="postbox awi-shortcode-box">
	<div class="postbox-header">
		<h2 class="hndle"><?php esc_html_e( 'شورت‌کد فرم استعلام گارانتی', 'apple-warranty-inquiry' ); ?></h2>
	</div>
	<div class="inside">
		<p class="description">
			<?php esc_html_e( 'برای نمایش فرم استعلام گارانتی در سایت، شورت‌کد زیر را در برگه یا نوشته مورد نظر قرار دهید.', 'apple-warranty-inquiry' ); ?>
		</p>
		<div class="awi-shortcode-row">
			<input
				type="text"
				id="awi-shortcode-value"
				class="large-text code"
				readonly
				dir="ltr"
				value="<?php echo esc_attr( $shortcode ); ?>"
				onclick="this.select();"
			/>
			<button type="button" class="button button-secondary" id="awi-copy-shortcode" data-copied="<?php esc_attr_e( 'کپی شد!', 'apple-warranty-inquiry' ); ?>">
				<?php esc_html_e( 'کپی شورت‌کد', 'apple-warranty-inquiry' ); ?>
			</button>
			<span class="awi-copy-feedback" id="awi-copy-feedback" aria-live="polite" hidden></span>
		</div>
		<ul class="awi-shortcode-help">
			<li><?php esc_html_e( 'در ویرایشگر برگه یا نوشته، یک بلوک «شورت‌کد» اضافه کنید و کد بالا را paste کنید.', 'apple-warranty-inquiry' ); ?></li>
			<li><?php esc_html_e( 'یا در ویرایشگر کلاسیک، مستقیماً در محتوای متن قرار دهید.', 'apple-warranty-inquiry' ); ?></li>
			<li><?php esc_html_e( 'پس از انتشار، کاربران با وارد کردن شماره سریال و کد امنیتی، نتیجه گارانتی را همان‌جا می‌بینند.', 'apple-warranty-inquiry' ); ?></li>
			<li><?php esc_html_e( 'اگر از LiteSpeed Cache استفاده می‌کنید، صفحهٔ حاوی این شورت‌کد را از کش صفحه مستثنی کنید.', 'apple-warranty-inquiry' ); ?></li>
		</ul>
	</div>
</div>
