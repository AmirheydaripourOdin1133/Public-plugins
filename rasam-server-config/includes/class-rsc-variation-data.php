<?php
/**
 * متای وریشن برای شخصی‌سازی و اتصال به موتور قیمت.
 *
 * قوانین «کدام قطعه برای کدام وریشن» فقط روی پست محصول والد (ریپیتر ACF) تعریف می‌شود:
 * وجود ردیف با همان وریشن و حداقل یک قطعهٔ معتبر = شخصی‌سازی برای آن وریشن فعال است.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Variation_Data {

	/** نام فیلد ریپیتر روی محصول والد (باید با ACF JSON یکی باشد). */
	public const PARENT_FIELD_ALLOWLIST_ROWS = 'rsc_pva_rows';

	/**
	 * @var array<int, int[]>
	 */
	private static $allowed_ids_cache = array();

	/**
	 * آیا این پست یک وریشن معتبر است؟
	 *
	 * @param int $variation_id شناسهٔ پست.
	 * @return bool
	 */
	public static function is_variation( $variation_id ) {
		$variation_id = (int) $variation_id;
		if ( $variation_id <= 0 ) {
			return false;
		}
		return 'product_variation' === get_post_type( $variation_id );
	}

	/**
	 * شناسهٔ محصول والد وریشن.
	 *
	 * @param int $variation_id شناسهٔ وریشن.
	 * @return int
	 */
	public static function get_variation_parent_product_id( $variation_id ) {
		$variation_id = (int) $variation_id;
		if ( ! self::is_variation( $variation_id ) ) {
			return 0;
		}
		$parent = wp_get_post_parent_id( $variation_id );
		return $parent > 0 ? (int) $parent : 0;
	}

	/**
	 * ردیف‌های ریپیتر قوانین از روی محصول والد.
	 *
	 * @param int $variation_id شناسهٔ وریشن (برای تشخیص والد).
	 * @return array<int, array<string, mixed>>
	 */
	private static function get_parent_allowlist_rows( $variation_id ) {
		$parent_id = self::get_variation_parent_product_id( $variation_id );
		if ( $parent_id <= 0 ) {
			return array();
		}
		$rows = null;
		if ( function_exists( 'get_field' ) ) {
			$rows = get_field( self::PARENT_FIELD_ALLOWLIST_ROWS, $parent_id );
		}
		if ( null === $rows || '' === $rows ) {
			$rows = get_post_meta( $parent_id, self::PARENT_FIELD_ALLOWLIST_ROWS, true );
		}
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * شناسهٔ وریشن از مقدار فیلد post_object (عدد یا آرایهٔ تک‌عضوی).
	 *
	 * @param mixed $raw مقدار ذخیره‌شده.
	 * @return int
	 */
	private static function normalize_variation_field_value( $raw ) {
		if ( is_numeric( $raw ) ) {
			return (int) $raw;
		}
		if ( is_array( $raw ) && ! empty( $raw ) ) {
			$first = reset( $raw );
			if ( is_numeric( $first ) ) {
				return (int) $first;
			}
			if ( is_object( $first ) && isset( $first->ID ) ) {
				return (int) $first->ID;
			}
		}
		if ( is_object( $raw ) && isset( $raw->ID ) ) {
			return (int) $raw->ID;
		}
		return 0;
	}

	/**
	 * شناسه‌های قطعه از فیلد relationship.
	 *
	 * @param mixed $raw مقدار ذخیره‌شده.
	 * @return int[]
	 */
	private static function normalize_relationship_component_ids( $raw ) {
		if ( ! is_array( $raw ) || empty( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $item ) {
			$id = 0;
			if ( is_numeric( $item ) ) {
				$id = (int) $item;
			} elseif ( is_object( $item ) && isset( $item->ID ) ) {
				$id = (int) $item->ID;
			}
			if ( $id > 0 && RSC_Component_Data::is_valid_component( $id ) ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * شناسهٔ قطعات مجاز برای این وریشن از ریپیتر والد (بدون کش).
	 *
	 * @param int $variation_id شناسهٔ وریشن.
	 * @return int[]
	 */
	private static function resolve_allowlist_component_ids( $variation_id ) {
		$variation_id = (int) $variation_id;
		foreach ( self::get_parent_allowlist_rows( $variation_id ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$vid = self::normalize_variation_field_value( $row['rsc_pva_variation'] ?? null );
			if ( $vid !== $variation_id ) {
				continue;
			}
			return self::normalize_relationship_component_ids( $row['rsc_pva_components'] ?? array() );
		}
		return array();
	}

	/**
	 * آیا برای این وریشن در محصول والد قانون معتبر (وریشن + حداقل یک قطعه) تعریف شده؟
	 *
	 * @param int $variation_id شناسهٔ وریشن.
	 * @return bool
	 */
	public static function is_customization_enabled( $variation_id ) {
		if ( ! self::is_variation( $variation_id ) ) {
			return false;
		}
		return count( self::resolve_allowlist_component_ids( (int) $variation_id ) ) > 0;
	}

	/**
	 * فهرست شناسهٔ قطعاتی که برای این وریشن در UI شخصی‌سازی مجازند.
	 *
	 * @param int $variation_id شناسهٔ وریشن.
	 * @return int[]
	 */
	public static function get_allowed_component_post_ids( $variation_id ) {
		$variation_id = (int) $variation_id;
		if ( isset( self::$allowed_ids_cache[ $variation_id ] ) ) {
			return self::$allowed_ids_cache[ $variation_id ];
		}
		$ids = self::resolve_allowlist_component_ids( $variation_id );
		self::$allowed_ids_cache[ $variation_id ] = $ids;
		return $ids;
	}

	/**
	 * آیا قطعه برای این وریشن مجاز است؟
	 *
	 * @param int $variation_id  شناسهٔ وریشن.
	 * @param int $component_id شناسهٔ قطعه.
	 * @return bool
	 */
	public static function is_component_allowed_for_variation( $variation_id, $component_id ) {
		$cid = (int) $component_id;
		if ( ! RSC_Component_Data::is_valid_component( $cid ) ) {
			return false;
		}
		return in_array( $cid, self::get_allowed_component_post_ids( (int) $variation_id ), true );
	}

	/**
	 * خطوط انتخاب را به قطعات مجاز این وریشن محدود می‌کند.
	 *
	 * @param int                  $variation_id شناسهٔ وریشن.
	 * @param array<int, mixed>    $lines        خطوط به شکل normalize_lines.
	 * @return array<int, mixed>
	 */
	public static function filter_selection_to_allowed( $variation_id, array $lines ) {
		if ( ! self::is_customization_enabled( $variation_id ) ) {
			return array();
		}
		$allowed = array_flip( self::get_allowed_component_post_ids( (int) $variation_id ) );
		if ( empty( $allowed ) ) {
			return array();
		}
		$out = array();
		foreach ( $lines as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = isset( $row['id'] ) ? (int) $row['id'] : ( isset( $row['component_id'] ) ? (int) $row['component_id'] : 0 );
			if ( $id <= 0 || ! isset( $allowed[ $id ] ) ) {
				continue;
			}
			$qty = isset( $row['qty'] ) ? (int) $row['qty'] : 0;
			if ( $qty <= 0 ) {
				continue;
			}
			$out[] = array(
				'id'  => $id,
				'qty' => $qty,
			);
		}
		return $out;
	}

	/**
	 * قیمت ثابت کانفیگ از ووکامرس (قیمت نمایش/وریشن).
	 *
	 * @param int $variation_id شناسهٔ وریشن.
	 * @return float تومان؛ در صورت خطا ۰.
	 */
	public static function get_wc_variation_price( $variation_id ) {
		if ( ! class_exists( 'WooCommerce' ) || ! self::is_variation( $variation_id ) ) {
			return 0.0;
		}
		$product = wc_get_product( $variation_id );
		if ( ! $product || ! $product->is_type( 'variation' ) ) {
			return 0.0;
		}
		return (float) wc_get_price_to_display( $product );
	}

	/**
	 * قیمت نهایی = قیمت ثابت وریشن + مجموع قطعات انتخابی (فقط خطوط مجاز).
	 *
	 * @param int                   $variation_id    شناسهٔ وریشن.
	 * @param array<int, mixed>     $selection_lines انتخاب کاربر (همان فرمت normalize_lines).
	 * @param RSC_Price_Engine|null $engine          برای استفادهٔ مجدد از همان نمونه در صورت نیاز.
	 * @return array{fixed:float,parts:float,total:float,engine:RSC_Price_Engine}
	 */
	public static function calculate_totals( $variation_id, array $selection_lines, $engine = null ) {
		$variation_id = (int) $variation_id;
		$fixed        = self::get_wc_variation_price( $variation_id );

		if ( ! self::is_customization_enabled( $variation_id ) ) {
			return array(
				'fixed'  => (float) $fixed,
				'parts'  => 0.0,
				'total'  => (float) $fixed,
				'engine' => $engine ? $engine : new RSC_Price_Engine(),
			);
		}

		$filtered = self::filter_selection_to_allowed( $variation_id, $selection_lines );

		$engine = $engine ? $engine : new RSC_Price_Engine();
		$parts  = $engine->calculate_parts_subtotal( $filtered );
		$total  = (float) $fixed + (float) $parts;

		return array(
			'fixed'  => (float) $fixed,
			'parts'  => (float) $parts,
			'total'  => (float) $total,
			'engine' => $engine,
		);
	}
}
