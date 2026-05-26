<?php
/**
 * Warranty records admin page.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Admin;

use AppleWarranty\Helpers\SerialNormalizer;
use AppleWarranty\Repositories\WarrantyItemRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class RecordsPage
 */
class RecordsPage {

	private WarrantyItemRepository $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new WarrantyItemRepository();
	}

	/**
	 * Render page and handle actions.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->handle_actions();

		$page    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$search  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

		$list   = $this->repo->list( $page, 20, $search );
		$edit   = $edit_id ? $this->repo->find( $edit_id ) : null;
		$adding = ( 'add' === $action );

		include APPLE_WARRANTY_PLUGIN_DIR . 'templates/admin/records.php';
	}

	/**
	 * Handle POST/GET actions.
	 */
	private function handle_actions(): void {
		if ( isset( $_POST['apple_warranty_record_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apple_warranty_record_nonce'] ) ), 'apple_warranty_save_record' ) ) {
				wp_die( esc_html__( 'خطای امنیتی.', 'apple-warranty-inquiry' ) );
			}

			$this->save_record();
		}

		if ( isset( $_GET['delete'] ) && isset( $_GET['_wpnonce'] ) ) {
			$id = absint( $_GET['delete'] );
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_record_' . $id ) ) {
				$this->repo->delete( $id );
				add_settings_error( 'apple_warranty', 'deleted', __( 'سریال حذف شد.', 'apple-warranty-inquiry' ), 'success' );
			}
		}

		if ( isset( $_POST['bulk_delete_nonce'] ) && isset( $_POST['record_ids'] ) ) {
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bulk_delete_nonce'] ) ), 'apple_warranty_bulk_delete' ) ) {
				$ids = array_map( 'absint', (array) wp_unslash( $_POST['record_ids'] ) );
				$n   = $this->repo->delete_many( $ids );
				add_settings_error( 'apple_warranty', 'bulk_deleted', sprintf( __( '%d سریال حذف شد.', 'apple-warranty-inquiry' ), $n ), 'success' );
			}
		}
	}

	/**
	 * Save or update record.
	 */
	private function save_record(): void {
		$id            = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$serial        = isset( $_POST['serial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['serial_number'] ) ) : '';
		$normalized    = SerialNormalizer::normalize( $serial );
		$product_name  = isset( $_POST['product_name'] ) ? sanitize_text_field( wp_unslash( $_POST['product_name'] ) ) : '';
		$image_key     = isset( $_POST['image_key'] ) ? sanitize_key( wp_unslash( $_POST['image_key'] ) ) : '';
		$warranty_type = isset( $_POST['warranty_type'] ) ? sanitize_text_field( wp_unslash( $_POST['warranty_type'] ) ) : '';
		$start_date    = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date      = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

		if ( ! SerialNormalizer::is_valid( $normalized ) || '' === $product_name ) {
			add_settings_error( 'apple_warranty', 'invalid', __( 'داده‌های فرم نامعتبر است.', 'apple-warranty-inquiry' ), 'error' );
			return;
		}

		$data = array(
			'serial_number'     => $serial,
			'normalized_serial' => $normalized,
			'product_name'      => $product_name,
			'image_key'         => $image_key,
			'warranty_type'     => $warranty_type,
			'start_date'        => $start_date,
			'end_date'          => $end_date,
		);

		if ( $id > 0 ) {
			$this->repo->update( $id, $data );
			add_settings_error( 'apple_warranty', 'updated', __( 'سریال به‌روزرسانی شد.', 'apple-warranty-inquiry' ), 'success' );
		} else {
			$this->repo->insert( $data );
			add_settings_error( 'apple_warranty', 'created', __( 'سریال ثبت شد.', 'apple-warranty-inquiry' ), 'success' );
		}
	}
}
