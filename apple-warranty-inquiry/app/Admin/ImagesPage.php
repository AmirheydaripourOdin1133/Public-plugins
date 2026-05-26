<?php
/**
 * Image mapping admin page.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Admin;

use AppleWarranty\Repositories\WarrantyImageRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class ImagesPage
 */
class ImagesPage {

	private WarrantyImageRepository $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new WarrantyImageRepository();
	}

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->handle_actions();

		$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$edit    = $edit_id ? $this->repo->find( $edit_id ) : null;
		$adding  = isset( $_GET['action'] ) && 'add' === sanitize_key( wp_unslash( $_GET['action'] ) );
		$images  = $this->repo->all();

		include APPLE_WARRANTY_PLUGIN_DIR . 'templates/admin/images.php';
	}

	/**
	 * Handle form actions.
	 */
	private function handle_actions(): void {
		if ( isset( $_POST['apple_warranty_image_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apple_warranty_image_nonce'] ) ), 'apple_warranty_save_image' ) ) {
				wp_die( esc_html__( 'خطای امنیتی.', 'apple-warranty-inquiry' ) );
			}

			$id            = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
			$image_key     = isset( $_POST['image_key'] ) ? sanitize_key( wp_unslash( $_POST['image_key'] ) ) : '';
			$title         = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;

			if ( '' === $image_key ) {
				add_settings_error( 'apple_warranty', 'invalid', __( 'شناسه تصویر الزامی است.', 'apple-warranty-inquiry' ), 'error' );
				return;
			}

			if ( $this->repo->key_exists( $image_key, $id ) ) {
				add_settings_error( 'apple_warranty', 'duplicate', __( 'این شناسه تصویر قبلاً ثبت شده است.', 'apple-warranty-inquiry' ), 'error' );
				return;
			}

			$data = array(
				'image_key'     => $image_key,
				'title'         => $title,
				'attachment_id' => $attachment_id,
			);

			if ( $id > 0 ) {
				$this->repo->update( $id, $data );
				add_settings_error( 'apple_warranty', 'updated', __( 'تصویر به‌روزرسانی شد.', 'apple-warranty-inquiry' ), 'success' );
			} else {
				$this->repo->insert( $data );
				add_settings_error( 'apple_warranty', 'created', __( 'تصویر ثبت شد.', 'apple-warranty-inquiry' ), 'success' );
			}
		}

		if ( isset( $_GET['delete'] ) && isset( $_GET['_wpnonce'] ) ) {
			$id = absint( $_GET['delete'] );
			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'delete_image_' . $id ) ) {
				$this->repo->delete( $id );
				add_settings_error( 'apple_warranty', 'deleted', __( 'تصویر محصول حذف شد.', 'apple-warranty-inquiry' ), 'success' );
			}
		}
	}
}
