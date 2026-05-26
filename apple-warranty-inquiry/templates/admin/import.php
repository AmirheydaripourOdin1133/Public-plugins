<?php
/**
 * Admin import template.
 *
 * @package AppleWarranty
 * @var array<string, mixed>|null $preview
 */

defined( 'ABSPATH' ) || exit;

settings_errors( 'apple_warranty' );

$sample_url = wp_nonce_url( admin_url( 'admin.php?page=apple-warranty-import&download_sample=1' ), 'apple_warranty_sample_csv' );

$column_labels = array(
	'serial_number' => __( 'شماره سریال', 'apple-warranty-inquiry' ),
	'product_name'  => __( 'نام محصول', 'apple-warranty-inquiry' ),
	'image_key'     => __( 'شناسه تصویر', 'apple-warranty-inquiry' ),
	'warranty_type' => __( 'نوع گارانتی', 'apple-warranty-inquiry' ),
	'start_date'    => __( 'تاریخ شروع', 'apple-warranty-inquiry' ),
	'end_date'      => __( 'تاریخ پایان', 'apple-warranty-inquiry' ),
);
?>
<div class="wrap awi-import-wrap">
	<h1 class="awi-import-wrap__title"><?php esc_html_e( 'درون‌ریزی داده‌ها', 'apple-warranty-inquiry' ); ?></h1>

	<div class="awi-import-guide">
		<div class="awi-import-guide__head">
			<span class="awi-import-guide__label"><?php esc_html_e( 'راهنما', 'apple-warranty-inquiry' ); ?></span>
			<a class="button button-secondary button-small" href="<?php echo esc_url( $sample_url ); ?>">
				<?php esc_html_e( 'دانلود فایل نمونه CSV', 'apple-warranty-inquiry' ); ?>
			</a>
		</div>
		<ul class="awi-import-guide__list">
			<li>
				<strong><?php esc_html_e( 'ستون‌ها', 'apple-warranty-inquiry' ); ?>:</strong>
				<code dir="ltr" class="awi-import-guide__code">serial_number, product_name, image_key, warranty_type, start_date, end_date</code>
			</li>
			<li>
				<strong><?php esc_html_e( 'تاریخ', 'apple-warranty-inquiry' ); ?>:</strong>
				<?php esc_html_e( 'فرمت اکسل (مثال 3/1/2025) یا YYYY-MM-DD — فایل را UTF-8 ذخیره کنید.', 'apple-warranty-inquiry' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'تصاویر', 'apple-warranty-inquiry' ); ?>:</strong>
				<?php esc_html_e( 'شناسه تصویر باید از بخش «تصاویر محصول» از قبل ثبت شده باشد.', 'apple-warranty-inquiry' ); ?>
			</li>
			<li class="awi-import-guide__warn">
				<?php esc_html_e( 'هر درون‌ریزی، تمام شماره سریال‌های قبلی را جایگزین می‌کند.', 'apple-warranty-inquiry' ); ?>
			</li>
		</ul>
	</div>

	<div class="awi-import-panel" id="awi-import-panel">
		<ol class="awi-import-steps">
			<li class="awi-import-steps__item<?php echo empty( $preview ) ? ' is-active' : ' is-done'; ?>">
				<span class="awi-import-steps__num">۱</span>
				<span class="awi-import-steps__text"><?php esc_html_e( 'آپلود و پیش‌نمایش', 'apple-warranty-inquiry' ); ?></span>
			</li>
			<li class="awi-import-steps__item<?php echo ! empty( $preview ) ? ' is-active' : ''; ?>">
				<span class="awi-import-steps__num">۲</span>
				<span class="awi-import-steps__text"><?php esc_html_e( 'تأیید و درون‌ریزی نهایی', 'apple-warranty-inquiry' ); ?></span>
			</li>
		</ol>

		<div class="awi-import-step-card">
			<h2 class="awi-import-step-card__title"><?php esc_html_e( '۱. انتخاب فایل CSV', 'apple-warranty-inquiry' ); ?></h2>

			<form method="post" enctype="multipart/form-data" id="awi-import-preview-form" class="awi-import-upload-form">
				<?php wp_nonce_field( 'apple_warranty_import', 'apple_warranty_import_nonce' ); ?>
				<input type="hidden" name="import_step" value="preview" />

				<label class="awi-import-drop" for="awi-csv-file">
					<span class="awi-import-drop__icon" aria-hidden="true"></span>
					<span class="awi-import-drop__title"><?php esc_html_e( 'فایل CSV را انتخاب کنید', 'apple-warranty-inquiry' ); ?></span>
					<span class="awi-import-drop__hint"><?php esc_html_e( 'فقط پسوند .csv — حداکثر مطابق محدودیت آپلود سرور', 'apple-warranty-inquiry' ); ?></span>
					<span class="awi-import-drop__file" id="awi-file-name" dir="ltr"></span>
					<input type="file" name="csv_file" id="awi-csv-file" class="awi-import-drop__input" accept=".csv,text/csv" required />
				</label>

				<p class="awi-import-upload-form__actions">
					<button type="submit" class="button button-primary" id="awi-preview-submit">
						<span class="awi-import-btn__label"><?php esc_html_e( 'نمایش پیش‌نمایش', 'apple-warranty-inquiry' ); ?></span>
						<span class="awi-import-btn__spinner" aria-hidden="true"></span>
					</button>
				</p>
			</form>
		</div>

		<?php if ( ! empty( $preview ) ) : ?>
			<div class="awi-import-step-card awi-import-step-card--preview">
				<h2 class="awi-import-step-card__title"><?php esc_html_e( 'پیش‌نمایش', 'apple-warranty-inquiry' ); ?></h2>
				<p class="awi-import-preview-meta">
					<?php
					printf(
						esc_html__( 'نمایش %1$d ردیف اول از %2$d ردیف کل فایل.', 'apple-warranty-inquiry' ),
						min( 10, (int) ( $preview['total'] ?? 0 ) ),
						(int) ( $preview['total'] ?? 0 )
					);
					?>
				</p>

				<?php if ( ! empty( $preview['errors'] ) ) : ?>
					<div class="notice notice-error inline"><p><?php echo esc_html( implode( ' | ', $preview['errors'] ) ); ?></p></div>
				<?php endif; ?>

				<div class="awi-import-table-wrap">
					<table class="wp-list-table widefat fixed striped awi-import-table">
						<thead>
							<tr>
								<?php foreach ( (array) ( $preview['header'] ?? array() ) as $col ) : ?>
									<?php
									$key   = strtolower( trim( (string) $col ) );
									$label = $column_labels[ $key ] ?? $col;
									?>
									<th scope="col"><?php echo esc_html( $label ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( (array) ( $preview['rows'] ?? array() ) as $row ) : ?>
								<tr>
									<?php foreach ( $row as $cell ) : ?>
										<td><?php echo esc_html( $cell ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="awi-import-step-card awi-import-step-card--confirm">
					<h2 class="awi-import-step-card__title"><?php esc_html_e( '۲. درون‌ریزی نهایی', 'apple-warranty-inquiry' ); ?></h2>
					<form method="post" id="awi-import-final-form" class="awi-import-final-form">
						<?php wp_nonce_field( 'apple_warranty_import', 'apple_warranty_import_nonce' ); ?>
						<input type="hidden" name="import_step" value="import" />

						<label class="awi-import-confirm">
							<input type="checkbox" name="confirm_replace" value="1" required />
							<span><?php esc_html_e( 'تأیید می‌کنم که تمام شماره سریال‌های قبلی حذف و با داده‌های این فایل جایگزین شوند.', 'apple-warranty-inquiry' ); ?></span>
						</label>

						<p class="awi-import-final-form__actions">
							<button type="submit" class="button button-primary button-hero" id="awi-import-submit">
								<span class="awi-import-btn__label"><?php esc_html_e( 'شروع درون‌ریزی', 'apple-warranty-inquiry' ); ?></span>
								<span class="awi-import-btn__spinner" aria-hidden="true"></span>
							</button>
						</p>
					</form>
				</div>
			</div>
		<?php endif; ?>

		<div class="awi-import-loading" id="awi-import-loading" hidden aria-hidden="true">
			<span class="awi-import-loading__spinner"></span>
			<span class="awi-import-loading__text" id="awi-import-loading-text"><?php esc_html_e( 'در حال پردازش…', 'apple-warranty-inquiry' ); ?></span>
		</div>
	</div>
</div>
