<?php
/**
 * CSV import admin page.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Admin;

use AppleWarranty\Import\CsvParser;
use AppleWarranty\Services\ImportService;

defined( 'ABSPATH' ) || exit;

/**
 * Class ImportPage
 */
class ImportPage {

	/**
	 * Path to bundled sample CSV (UTF-8 with BOM).
	 */
	public const SAMPLE_CSV_FILE = 'assets/samples/warranty-sample.csv';

	/**
	 * Register early hooks (sample download before admin HTML output).
	 */
	public static function register_hooks(): void {
		add_action( 'admin_init', array( self::class, 'maybe_download_sample' ) );
	}

	/**
	 * Stream sample CSV on admin_init — must run before any HTML is sent.
	 */
	public static function maybe_download_sample(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- checked below.
		if ( ! isset( $_GET['page'] ) || 'apple-warranty-import' !== $_GET['page'] ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['download_sample'] ) || '1' !== $_GET['download_sample'] ) {
			return;
		}

		check_admin_referer( 'apple_warranty_sample_csv' );

		$instance = new self();
		$instance->download_sample();
	}

	/**
	 * Render import page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$preview = null;

		if ( isset( $_POST['apple_warranty_import_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apple_warranty_import_nonce'] ) ), 'apple_warranty_import' ) ) {
				wp_die( esc_html__( 'خطای امنیتی.', 'apple-warranty-inquiry' ) );
			}

			$step = isset( $_POST['import_step'] ) ? sanitize_key( wp_unslash( $_POST['import_step'] ) ) : '';

			if ( 'preview' === $step ) {
				$preview = $this->handle_preview();
			} elseif ( 'import' === $step ) {
				$this->handle_import();
			}
		}

		include APPLE_WARRANTY_PLUGIN_DIR . 'templates/admin/import.php';
	}

	/**
	 * Download bundled sample CSV file.
	 */
	private function download_sample(): void {
		$path = APPLE_WARRANTY_PLUGIN_DIR . self::SAMPLE_CSV_FILE;

		if ( ! is_readable( $path ) ) {
			wp_die( esc_html__( 'فایل نمونه یافت نشد.', 'apple-warranty-inquiry' ) );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="warranty-sample.csv"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		readfile( $path );
		exit;
	}

	/**
	 * Handle preview upload.
	 *
	 * @return array<string, mixed>|null
	 */
	private function handle_preview(): ?array {
		$file = $this->get_uploaded_file();
		if ( ! $file ) {
			add_settings_error( 'apple_warranty', 'upload', __( 'لطفاً فایل CSV انتخاب کنید.', 'apple-warranty-inquiry' ), 'error' );
			return null;
		}

		$parser  = new CsvParser();
		$preview = $parser->preview( $file, 10 );

		set_transient( 'apple_warranty_import_file', $file, HOUR_IN_SECONDS );

		return $preview;
	}

	/**
	 * Run import from stored temp file.
	 */
	private function handle_import(): void {
		if ( empty( $_POST['confirm_replace'] ) ) {
			add_settings_error( 'apple_warranty', 'confirm', __( 'برای درون‌ریزی باید جایگزینی کامل را تأیید کنید.', 'apple-warranty-inquiry' ), 'error' );
			return;
		}

		$file = get_transient( 'apple_warranty_import_file' );

		if ( ! $file || ! is_readable( $file ) ) {
			add_settings_error( 'apple_warranty', 'expired', __( 'فایل پیش‌نمایش منقضی شده است. لطفاً دوباره آپلود کنید.', 'apple-warranty-inquiry' ), 'error' );
			return;
		}

		$service = new ImportService();
		$result  = $service->import( $file );

		delete_transient( 'apple_warranty_import_file' );
		@unlink( $file );

		if ( $result['success'] ) {
			$msg = sprintf(
				__( 'درون‌ریزی انجام شد. %1$d شماره سریال ثبت شد. %2$d ردیف رد شد.', 'apple-warranty-inquiry' ),
				$result['imported'],
				$result['skipped']
			);
			add_settings_error( 'apple_warranty', 'import_ok', $msg, 'success' );
		} else {
			add_settings_error( 'apple_warranty', 'import_fail', __( 'درون‌ریزی ناموفق بود.', 'apple-warranty-inquiry' ), 'error' );
		}

		if ( ! empty( $result['errors'] ) ) {
			$shown = array_slice( $result['errors'], 0, 20 );
			foreach ( $shown as $err ) {
				add_settings_error( 'apple_warranty', 'import_err', esc_html( $err ), 'warning' );
			}
		}
	}

	/**
	 * Handle file upload.
	 */
	private function get_uploaded_file(): ?string {
		if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
			return null;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'csv' => 'text/csv',
				'txt' => 'text/plain',
			),
		);

		$upload = wp_handle_upload( $_FILES['csv_file'], $overrides );

		if ( isset( $upload['error'] ) ) {
			add_settings_error( 'apple_warranty', 'upload_err', esc_html( $upload['error'] ), 'error' );
			return null;
		}

		$ext = strtolower( pathinfo( $upload['file'], PATHINFO_EXTENSION ) );
		if ( 'csv' !== $ext ) {
			@unlink( $upload['file'] );
			add_settings_error( 'apple_warranty', 'ext', __( 'فقط فایل CSV مجاز است.', 'apple-warranty-inquiry' ), 'error' );
			return null;
		}

		return $upload['file'];
	}
}
