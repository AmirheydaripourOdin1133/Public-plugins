<?php
/**
 * Admin settings template.
 *
 * @package AppleWarranty
 * @var string $description
 * @var string $help_url
 */

defined( 'ABSPATH' ) || exit;

settings_errors( 'apple_warranty' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'تنظیمات گارانتی', 'apple-warranty-inquiry' ); ?></h1>
	<form method="post">
		<?php wp_nonce_field( 'apple_warranty_save_settings', 'apple_warranty_settings_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="serial_help_url"><?php esc_html_e( 'لینک راهنمای سریال', 'apple-warranty-inquiry' ); ?></label></th>
				<td>
					<input type="url" name="serial_help_url" id="serial_help_url" class="large-text" value="<?php echo esc_attr( $help_url ); ?>" placeholder="https://..." />
					<p class="description"><?php esc_html_e( 'در فرم استعلام نمایش داده می‌شود (اختیاری).', 'apple-warranty-inquiry' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'متن توضیحات گارانتی', 'apple-warranty-inquiry' ); ?></th>
				<td>
					<?php
					wp_editor(
						$description,
						'warranty_description',
						array(
							'textarea_name' => 'warranty_description',
							'textarea_rows' => 12,
							'media_buttons' => false,
							'teeny'         => false,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'این متن در نتیجه استعلام نمایش داده می‌شود (ثابت برای همه محصولات).', 'apple-warranty-inquiry' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'ذخیره تنظیمات', 'apple-warranty-inquiry' ) ); ?>
	</form>
</div>
