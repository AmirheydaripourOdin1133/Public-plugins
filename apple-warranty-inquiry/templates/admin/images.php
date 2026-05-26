<?php
/**
 * Admin images template.
 *
 * @package AppleWarranty
 * @var \AppleWarranty\Models\WarrantyImage[] $images
 * @var \AppleWarranty\Models\WarrantyImage|null $edit
 * @var bool $adding
 */

defined( 'ABSPATH' ) || exit;

settings_errors( 'apple_warranty' );
$attachment_id = $edit?->attachment_id ?? 0;
$preview_url   = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
?>
<div class="wrap">
	<h1><?php esc_html_e( 'تصاویر محصولات', 'apple-warranty-inquiry' ); ?></h1>

	<?php if ( $adding || $edit ) : ?>
		<h2><?php echo $edit ? esc_html__( 'ویرایش تصویر محصول', 'apple-warranty-inquiry' ) : esc_html__( 'افزودن تصویر محصول', 'apple-warranty-inquiry' ); ?></h2>
		<form method="post" class="awi-admin-image-form">
			<?php wp_nonce_field( 'apple_warranty_save_image', 'apple_warranty_image_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo $edit ? esc_attr( (string) $edit->id ) : '0'; ?>" />
			<input type="hidden" name="attachment_id" id="awi-attachment-id" value="<?php echo esc_attr( (string) $attachment_id ); ?>" />
			<table class="form-table">
				<tr>
					<th>
						<label for="image_key">
							<?php esc_html_e( 'شناسه تصویر', 'apple-warranty-inquiry' ); ?>
							<span
								class="awi-help-tip dashicons dashicons-editor-help"
								tabindex="0"
								role="button"
								aria-label="<?php esc_attr_e( 'راهنمای شناسه تصویر', 'apple-warranty-inquiry' ); ?>"
								data-tip="<?php echo esc_attr( __( 'شناسه یکتای انگلیسی برای اتصال تصویر به محصول. باید دقیقاً با مقدار ستون image_key در فایل CSV یکسان باشد. مثال: iphone-16-pro-max', 'apple-warranty-inquiry' ) ); ?>"
							></span>
						</label>
					</th>
					<td>
						<input name="image_key" id="image_key" type="text" class="regular-text" dir="ltr" value="<?php echo esc_attr( $edit?->image_key ?? '' ); ?>" placeholder="iphone-16-pro-max" required />
					</td>
				</tr>
				<tr>
					<th><label for="title"><?php esc_html_e( 'عنوان', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="title" id="title" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->title ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'تصویر', 'apple-warranty-inquiry' ); ?></th>
					<td>
						<div id="awi-image-preview">
							<?php if ( $preview_url ) : ?>
								<img src="<?php echo esc_url( $preview_url ); ?>" style="max-width:120px;height:auto;" alt="" />
							<?php endif; ?>
						</div>
						<p>
							<button type="button" class="button" id="awi-select-image"><?php esc_html_e( 'انتخاب از Media Library', 'apple-warranty-inquiry' ); ?></button>
							<button type="button" class="button" id="awi-remove-image"><?php esc_html_e( 'حذف تصویر', 'apple-warranty-inquiry' ); ?></button>
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'ذخیره', 'apple-warranty-inquiry' ) ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apple-warranty-images' ) ); ?>"><?php esc_html_e( 'انصراف', 'apple-warranty-inquiry' ); ?></a>
		</form>
		<hr />
	<?php endif; ?>

	<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=apple-warranty-images&action=add' ) ); ?>"><?php esc_html_e( 'افزودن تصویر محصول', 'apple-warranty-inquiry' ); ?></a></p>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'شناسه تصویر', 'apple-warranty-inquiry' ); ?></th>
				<th><?php esc_html_e( 'عنوان', 'apple-warranty-inquiry' ); ?></th>
				<th><?php esc_html_e( 'پیش‌نمایش', 'apple-warranty-inquiry' ); ?></th>
				<th><?php esc_html_e( 'عملیات', 'apple-warranty-inquiry' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $images ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'تصویری ثبت نشده است.', 'apple-warranty-inquiry' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $images as $img ) : ?>
					<tr>
						<td>
							<button
								type="button"
								class="awi-image-key-copy"
								data-key="<?php echo esc_attr( $img->image_key ); ?>"
								data-copied="<?php esc_attr_e( 'کپی شد!', 'apple-warranty-inquiry' ); ?>"
								title="<?php esc_attr_e( 'کلیک برای کپی شناسه', 'apple-warranty-inquiry' ); ?>"
								aria-label="<?php esc_attr_e( 'کپی شناسه تصویر', 'apple-warranty-inquiry' ); ?>"
							>
								<code dir="ltr"><?php echo esc_html( $img->image_key ); ?></code>
							</button>
						</td>
						<td><?php echo esc_html( $img->title ); ?></td>
						<td>
							<?php
							$url = $img->get_url( 'thumbnail' );
							if ( $url ) {
								echo '<img src="' . esc_url( $url ) . '" style="max-width:60px;height:auto;" alt="" />';
							}
							?>
						</td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=apple-warranty-images&edit=' . $img->id ) ); ?>"><?php esc_html_e( 'ویرایش', 'apple-warranty-inquiry' ); ?></a>
							|
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=apple-warranty-images&delete=' . $img->id ), 'delete_image_' . $img->id ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'حذف شود؟', 'apple-warranty-inquiry' ) ); ?>');"><?php esc_html_e( 'حذف', 'apple-warranty-inquiry' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
