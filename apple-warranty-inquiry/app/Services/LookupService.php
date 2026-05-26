<?php
/**
 * Warranty lookup service.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty\Services;

use AppleWarranty\Helpers\DateFormatter;
use AppleWarranty\Helpers\PersianDigits;
use AppleWarranty\Helpers\SerialNormalizer;
use AppleWarranty\Models\WarrantyItem;
use AppleWarranty\Repositories\WarrantyImageRepository;
use AppleWarranty\Repositories\WarrantyItemRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Class LookupService
 */
class LookupService {

	private WarrantyItemRepository $items;
	private WarrantyImageRepository $images;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->items  = new WarrantyItemRepository();
		$this->images = new WarrantyImageRepository();
	}

	/**
	 * Lookup warranty by serial.
	 *
	 * @param string $serial Raw serial.
	 * @return array{success: bool, data?: array<string, mixed>, error?: string}
	 */
	public function lookup( string $serial ): array {
		$normalized = SerialNormalizer::normalize( $serial );

		if ( ! SerialNormalizer::is_valid( $normalized ) ) {
			return array(
				'success' => false,
				'error'   => __( 'شماره سریال نامعتبر است.', 'apple-warranty-inquiry' ),
			);
		}

		$item = $this->items->find_by_normalized_serial( $normalized );

		if ( ! $item ) {
			return array(
				'success' => false,
				'error'   => __( 'شناسه‌ای با این شماره سریال یافت نشد.', 'apple-warranty-inquiry' ),
			);
		}

		$data = $this->build_result( $item );

		/**
		 * Filter lookup result data.
		 *
		 * @param array<string, mixed> $data Result data.
		 * @param WarrantyItem       $item Item model.
		 */
		$data = apply_filters( 'apple_warranty/lookup_result', $data, $item );

		/**
		 * Fires after successful warranty lookup.
		 *
		 * @param WarrantyItem $item   Item.
		 * @param string       $serial Normalized serial.
		 */
		do_action( 'apple_warranty/after_lookup', $item, $normalized );

		return array(
			'success' => true,
			'data'    => $data,
		);
	}

	/**
	 * Build display data array.
	 *
	 * @return array<string, mixed>
	 */
	private function build_result( WarrantyItem $item ): array {
		$image_url = '';
		$image_alt = $item->product_name;

		if ( '' !== $item->image_key ) {
			$mapping = $this->images->find_by_key( $item->image_key );
			if ( $mapping ) {
				$image_url = $mapping->get_url( 'medium' );
				if ( '' !== $mapping->title ) {
					$image_alt = $mapping->title;
				}
			}
		}

		$description = get_option( 'apple_warranty_description', '' );

		return array(
			'product_name'      => $item->product_name,
			'serial_number'     => PersianDigits::to_persian( $item->serial_number ),
			'warranty_type'     => $item->warranty_type,
			'start_date'        => DateFormatter::display( $item->start_date ),
			'end_date'          => DateFormatter::display( $item->end_date ),
			'image_url'         => $image_url,
			'image_alt'         => $image_alt,
			'description_html'  => wp_kses_post( $description ),
		);
	}
}
