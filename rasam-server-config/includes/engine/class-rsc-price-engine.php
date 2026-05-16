<?php
/**
 * موتور قیمت‌گذاری: قیمت ثابت کانفیگ + مجموع خطوط قطعات.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Price_Engine {

	/**
	 * خطوط نرمال‌شدهٔ انتخاب (بعد از normalize_lines).
	 *
	 * @var array<int, array{id:int,qty:int}>
	 */
	private $normalized_selection = array();

	/**
	 * یک خط به ازای هر قطعهٔ معتبر در انتخاب.
	 *
	 * @var array<int, array{id:int,qty:int,unit_price:float,line_total:float}>
	 */
	private $lines = array();

	/**
	 * @var string[]
	 */
	private $errors = array();

	/**
	 * از آرایهٔ ورودی خطوط را نرمال می‌کند و بر اساس شناسه ادغام می‌کند.
	 *
	 * @param array<int, mixed> $lines هر عنصر: ['id'=>int,'qty'=>int] یا ['component_id'=>int,'qty'=>int].
	 * @return array<int, array{id:int,qty:int}>
	 */
	public static function normalize_lines( array $lines ) {
		$merged = array();
		foreach ( $lines as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = isset( $row['id'] ) ? (int) $row['id'] : ( isset( $row['component_id'] ) ? (int) $row['component_id'] : 0 );
			$qty = isset( $row['qty'] ) ? (int) $row['qty'] : 0;
			if ( $id <= 0 || $qty <= 0 ) {
				continue;
			}
			if ( ! isset( $merged[ $id ] ) ) {
				$merged[ $id ] = array(
					'id'  => $id,
					'qty' => 0,
				);
			}
			$merged[ $id ]['qty'] += $qty;
		}
		return array_values( $merged );
	}

	/**
	 * مجموع قیمت قطعات (تعداد × قیمت واحد) برای انتخاب کاربر.
	 *
	 * @param array<int, mixed> $selection_lines خطوط انتخاب.
	 * @return float جمع تومان فقط بخش قطعات (بدون قیمت ثابت کانفیگ).
	 */
	public function calculate_parts_subtotal( array $selection_lines ) {
		$this->errors               = array();
		$this->lines                = array();
		$this->normalized_selection = self::normalize_lines( $selection_lines );

		$total = 0.0;

		foreach ( $this->normalized_selection as $row ) {
			$component_id = (int) $row['id'];
			$qty          = (int) $row['qty'];

			if ( ! RSC_Component_Data::is_valid_component( $component_id ) ) {
				if ( $qty > 0 ) {
					$this->errors[] = sprintf(
						/* translators: %d component id */
						__( 'قطعه معتبر نیست (شناسه %d).', 'rasam-server-config' ),
						$component_id
					);
				}
				continue;
			}

			$unit = RSC_Component_Data::get_unit_price( $component_id );
			$line = $qty * $unit;
			$total += $line;

			$this->lines[] = array(
				'id'         => $component_id,
				'qty'        => $qty,
				'unit_price' => $unit,
				'line_total' => $line,
			);
		}

		return $total;
	}

	/**
	 * قیمت نهایی = قیمت ثابت کانفیگ + مجموع قطعات.
	 *
	 * @param float             $fixed_config_price قیمت ثابت (مثلاً قیمت وریشن در ووکامرس).
	 * @param array<int, mixed> $selection_lines    انتخاب کاربر.
	 * @return float
	 */
	public function calculate_total( $fixed_config_price, array $selection_lines ) {
		$parts = $this->calculate_parts_subtotal( $selection_lines );
		return (float) $fixed_config_price + (float) $parts;
	}

	/**
	 * @return array<int, array{id:int,qty:int,unit_price:float,line_total:float}>
	 */
	public function get_lines() {
		return $this->lines;
	}

	/**
	 * @return string[]
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * @return bool
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}
}
