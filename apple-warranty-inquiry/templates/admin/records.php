<?php
/**
 * Admin records template.
 *
 * @package AppleWarranty
 * @var array{items: \AppleWarranty\Models\WarrantyItem[], total: int} $list
 * @var \AppleWarranty\Models\WarrantyItem|null $edit
 * @var bool $adding
 * @var int $page
 * @var string $search
 */

defined( 'ABSPATH' ) || exit;

use AppleWarranty\Helpers\AdminPagination;

settings_errors( 'apple_warranty' );

$per_page         = 20;
$total_items      = (int) $list['total'];
$total_pages      = (int) ceil( $total_items / $per_page );
$pagination_query = array( 'page' => 'apple-warranty' );
if ( '' !== $search ) {
	$pagination_query['s'] = $search;
}

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'لیست شماره سریال‌های گارانتی', 'apple-warranty-inquiry' ); ?></h1>
	<?php if ( ! $adding && ! $edit ) : ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=apple-warranty&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'افزودن سریال', 'apple-warranty-inquiry' ); ?></a>
	<?php endif; ?>
	<hr class="wp-header-end" />

	<?php include APPLE_WARRANTY_PLUGIN_DIR . 'templates/admin/shortcode-notice.php'; ?>

	<?php if ( $adding || $edit ) : ?>
		<h2><?php echo $edit ? esc_html__( 'ویرایش شماره سریال', 'apple-warranty-inquiry' ) : esc_html__( 'افزودن شماره سریال', 'apple-warranty-inquiry' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'apple_warranty_save_record', 'apple_warranty_record_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo $edit ? esc_attr( (string) $edit->id ) : '0'; ?>" />
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="serial_number"><?php esc_html_e( 'شماره سریال', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="serial_number" id="serial_number" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->serial_number ?? '' ); ?>" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="product_name"><?php esc_html_e( 'نام محصول', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="product_name" id="product_name" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->product_name ?? '' ); ?>" required /></td>
				</tr>
				<tr>
					<th scope="row"><label for="image_key"><?php esc_html_e( 'شناسه تصویر', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="image_key" id="image_key" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->image_key ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="warranty_type"><?php esc_html_e( 'نوع گارانتی', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="warranty_type" id="warranty_type" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->warranty_type ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="start_date"><?php esc_html_e( 'تاریخ شروع (1404-01-01)', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="start_date" id="start_date" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->start_date ?? '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="end_date"><?php esc_html_e( 'تاریخ پایان', 'apple-warranty-inquiry' ); ?></label></th>
					<td><input name="end_date" id="end_date" type="text" class="regular-text" value="<?php echo esc_attr( $edit?->end_date ?? '' ); ?>" /></td>
				</tr>
			</table>
			<?php submit_button( __( 'ذخیره', 'apple-warranty-inquiry' ) ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apple-warranty' ) ); ?>" class="button"><?php esc_html_e( 'انصراف', 'apple-warranty-inquiry' ); ?></a>
		</form>
		<hr class="wp-header-end" />
	<?php endif; ?>

	<?php if ( ! $adding && ! $edit ) : ?>
	<form method="get" id="awi-records-filter">
		<input type="hidden" name="page" value="apple-warranty" />
		<p class="search-box">
			<label class="screen-reader-text" for="record-search"><?php esc_html_e( 'جستجوی سریال', 'apple-warranty-inquiry' ); ?></label>
			<input type="search" id="record-search" name="s" value="<?php echo esc_attr( $search ); ?>" />
			<?php submit_button( __( 'جستجو', 'apple-warranty-inquiry' ), '', '', false ); ?>
		</p>
	</form>
	<?php endif; ?>

	<form method="post" id="awi-records-form">
		<?php wp_nonce_field( 'apple_warranty_bulk_delete', 'bulk_delete_nonce' ); ?>

		<?php if ( $total_items > 0 ) : ?>
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<?php submit_button( __( 'حذف گروهی', 'apple-warranty-inquiry' ), 'delete', '', false ); ?>
			</div>
			<?php AdminPagination::render( 'top', $total_items, $total_pages, $page, $pagination_query ); ?>
			<br class="clear" />
		</div>
		<?php endif; ?>

		<div class="awi-records-table-wrap">
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column">
						<input id="cb-select-all-1" type="checkbox" />
					</td>
					<th scope="col" class="manage-column"><?php esc_html_e( 'سریال', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'محصول', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'شناسه تصویر', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'گارانتی', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'شروع', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'پایان', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'عملیات', 'apple-warranty-inquiry' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
				<?php if ( empty( $list['items'] ) ) : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="8"><?php esc_html_e( 'سریالی یافت نشد.', 'apple-warranty-inquiry' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $list['items'] as $item ) : ?>
						<tr>
							<th scope="row" class="check-column">
								<input type="checkbox" name="record_ids[]" value="<?php echo esc_attr( (string) $item->id ); ?>" />
							</th>
							<td>
								<button
									type="button"
									class="awi-image-key-copy"
									data-key="<?php echo esc_attr( $item->serial_number ); ?>"
									data-copied="<?php esc_attr_e( 'کپی شد!', 'apple-warranty-inquiry' ); ?>"
									title="<?php esc_attr_e( 'کلیک برای کپی شماره سریال', 'apple-warranty-inquiry' ); ?>"
									aria-label="<?php esc_attr_e( 'کپی شماره سریال', 'apple-warranty-inquiry' ); ?>"
								>
									<code dir="ltr"><?php echo esc_html( $item->serial_number ); ?></code>
								</button>
							</td>
							<td><?php echo esc_html( $item->product_name ); ?></td>
							<td>
								<?php if ( '' !== (string) $item->image_key ) : ?>
									<button
										type="button"
										class="awi-image-key-copy"
										data-key="<?php echo esc_attr( $item->image_key ); ?>"
										data-copied="<?php esc_attr_e( 'کپی شد!', 'apple-warranty-inquiry' ); ?>"
										title="<?php esc_attr_e( 'کلیک برای کپی شناسه', 'apple-warranty-inquiry' ); ?>"
										aria-label="<?php esc_attr_e( 'کپی شناسه تصویر', 'apple-warranty-inquiry' ); ?>"
									>
										<code dir="ltr"><?php echo esc_html( $item->image_key ); ?></code>
									</button>
								<?php else : ?>
									<span aria-hidden="true">—</span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( wp_trim_words( $item->warranty_type, 8 ) ); ?></td>
							<td><?php echo esc_html( $item->start_date ); ?></td>
							<td><?php echo esc_html( $item->end_date ); ?></td>
							<td class="column-actions">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=apple-warranty&edit=' . $item->id ) ); ?>"><?php esc_html_e( 'ویرایش', 'apple-warranty-inquiry' ); ?></a>
								|
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=apple-warranty&delete=' . $item->id ), 'delete_record_' . $item->id ) ); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js( __( 'حذف شود؟', 'apple-warranty-inquiry' ) ); ?>');"><?php esc_html_e( 'حذف', 'apple-warranty-inquiry' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td class="manage-column column-cb check-column">
						<input id="cb-select-all-2" type="checkbox" />
					</td>
					<th scope="col" class="manage-column"><?php esc_html_e( 'سریال', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'محصول', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'شناسه تصویر', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'گارانتی', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'شروع', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'پایان', 'apple-warranty-inquiry' ); ?></th>
					<th scope="col" class="manage-column"><?php esc_html_e( 'عملیات', 'apple-warranty-inquiry' ); ?></th>
				</tr>
			</tfoot>
		</table>
		</div>

		<?php if ( $total_items > 0 ) : ?>
		<div class="tablenav bottom">
			<div class="alignleft actions bulkactions">
				<?php submit_button( __( 'حذف گروهی', 'apple-warranty-inquiry' ), 'delete', '', false ); ?>
			</div>
			<?php AdminPagination::render( 'bottom', $total_items, $total_pages, $page, $pagination_query ); ?>
			<br class="clear" />
		</div>
		<?php endif; ?>
	</form>
</div>
