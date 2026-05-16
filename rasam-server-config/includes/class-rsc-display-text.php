<?php
/**
 * قالب‌بندی یکسان عنوان محصول، کانفیگ وریشن و قطعات اضافه (مودال، سبد، پیش‌فاکتور).
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Display_Text {

	/**
	 * متن ویژگی‌های وریشن بدون تکرار نام محصول و «سفارشی».
	 *
	 * @param string $product_title  عنوان محصول والد.
	 * @param string $variation_name عنوان پست وریشن.
	 * @param string $variation_label خروجی wc_get_formatted_variation.
	 * @return string
	 */
	public static function get_variation_spec( $product_title, $variation_name, $variation_label ) {
		$parent = trim( (string) $product_title );
		$label  = trim( (string) $variation_label );
		$name   = trim( (string) $variation_name );

		$spec = '' !== $label ? $label : $name;
		if ( '' === $spec ) {
			return '';
		}

		if ( '' !== $parent && 0 === strpos( $spec, $parent ) ) {
			$spec = substr( $spec, strlen( $parent ) );
		}
		if ( '' !== $name && $name !== $parent && 0 === strpos( $spec, $name ) ) {
			$spec = substr( $spec, strlen( $name ) );
		}

		$spec = preg_replace( '/^[\s,،\-–—:|]+/u', '', $spec );
		$spec = preg_replace( '/سفارشی/u', '', $spec );
		$spec = preg_replace( '/کانفیگ\s*پیشنهادی\s*[:：]?\s*/u', '', $spec );
		$spec = preg_replace( '/کانفیگ\s*سفارشی\s*[:：]?\s*/u', '', $spec );
		$spec = preg_replace( '/^[\s\-–—]+/u', '', $spec );
		$spec = preg_replace( '/\s{2,}/u', ' ', trim( $spec ) );

		return $spec;
	}

	/**
	 * @param string $product_title عنوان والد.
	 * @param string $variation_name  نام وریشن.
	 * @param string $variation_label برچسب وریشن.
	 * @return string مثال: سرور X - کانفیگ: 8SFF, 32GB
	 */
	public static function build_product_config_line( $product_title, $variation_name, $variation_label ) {
		$base = trim( (string) $product_title );
		$spec = self::get_variation_spec( $product_title, $variation_name, $variation_label );
		if ( '' === $base ) {
			return $spec;
		}
		if ( '' === $spec ) {
			return $base;
		}
		if ( preg_match( '/^کانفیگ\s*[:：]/u', $spec ) ) {
			return $base . ' - ' . $spec;
		}
		return $base . ' - ' . __( 'کانفیگ', 'rasam-server-config' ) . ': ' . $spec;
	}

	/**
	 * قطعات اضافه — فقط نام × تعداد.
	 *
	 * @param array<int, array{id:int,qty:int}> $lines
	 * @return string
	 */
	public static function format_parts_compact( array $lines ) {
		$parts = self::collect_part_lines( $lines );
		return implode( '، ', $parts );
	}

	/**
	 * @param array<int, array{id:int,qty:int}> $lines
	 * @return string[]
	 */
	private static function collect_part_lines( array $lines ) {
		$parts = array();
		foreach ( $lines as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) || empty( $row['qty'] ) ) {
				continue;
			}
			$id    = (int) $row['id'];
			$qty   = (int) $row['qty'];
			$title = trim( wp_strip_all_tags( get_the_title( $id ) ) );
			if ( '' === $title ) {
				continue;
			}
			$parts[] = sprintf(
				/* translators: 1: part title, 2: quantity */
				__( '%1$s × %2$d', 'rasam-server-config' ),
				$title,
				$qty
			);
		}
		return $parts;
	}

	/**
	 * لیست قطعات برای سبد — هر قطعه یک خط.
	 *
	 * @param array<int, array{id:int,qty:int}> $lines
	 * @return string HTML
	 */
	public static function format_parts_cart_list_html( array $lines ) {
		$parts = self::collect_part_lines( $lines );
		if ( empty( $parts ) ) {
			return '';
		}
		$html = '<ul class="rsc-cart-meta__parts-list">';
		foreach ( $parts as $part ) {
			$html .= '<li>' . esc_html( $part ) . '</li>';
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * نام ارسالی به wc-request-quotation: قبل از «(» فقط عنوان محصول؛ داخل پرانتز کانفیگ پیش‌فرض + قطعات.
	 *
	 * @param string                            $product_title
	 * @param string                            $variation_name
	 * @param string                            $variation_label
	 * @param array<int, array{id:int,qty:int}> $lines
	 * @return string
	 */
	public static function build_quotation_product_name( $product_title, $variation_name, $variation_label, array $lines ) {
		$title    = trim( (string) $product_title );
		$sections = array();
		$spec     = self::get_variation_spec( $product_title, $variation_name, $variation_label );
		if ( '' !== $spec ) {
			$sections[] = $spec;
		}
		$parts = self::format_parts_compact( $lines );
		if ( '' !== $parts ) {
			$sections[] = __( 'قطعات اضافه', 'rasam-server-config' ) . ': ' . $parts;
		}
		if ( empty( $sections ) ) {
			return $title;
		}
		return $title . ' (' . implode( ' / ', $sections ) . ')';
	}

	/**
	 * HTML خلاصهٔ سبد: قطعات ریز + قیمت‌های ریز.
	 *
	 * @param array<int, array{id:int,qty:int}> $lines
	 * @param float                             $base_price  قیمت پایه (یک واحد).
	 * @param float                             $parts_price جمع قطعات (یک واحد).
	 * @param int                               $line_qty    تعداد خط سبد.
	 * @return string
	 */
	public static function format_cart_meta_html( array $lines, $base_price, $parts_price, $line_qty = 1 ) {
		$line_qty = max( 1, (int) $line_qty );
		$parts_html = self::format_parts_cart_list_html( $lines );
		$html     = '<div class="rsc-cart-meta">';

		if ( '' !== $parts_html ) {
			$html .= '<div class="rsc-cart-meta__parts">' . $parts_html . '</div>';
		}

		$html .= '<div class="rsc-cart-meta__prices">';
		$html .= self::format_cart_price_row_html( __( 'قیمت پایه', 'rasam-server-config' ), (float) $base_price * $line_qty );

		if ( (float) $parts_price > 0 ) {
			$html .= self::format_cart_price_row_html( __( 'قطعات اضافه', 'rasam-server-config' ), (float) $parts_price * $line_qty );
		}
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * یک ردیف قیمت: برچسب و مبلغ پشت‌سرهم.
	 *
	 * @param string $label
	 * @param float  $amount
	 * @return string
	 */
	private static function format_cart_price_row_html( $label, $amount ) {
		$html  = '<div class="rsc-cart-meta__price-row">';
		$html .= '<span class="rsc-cart-meta__price-label">' . esc_html( $label ) . '</span>';
		$html .= '<span class="rsc-cart-meta__price-sep" aria-hidden="true">:</span>';
		$html .= '<span class="rsc-cart-meta__price-value">' . wp_kses_post( wc_price( $amount ) ) . '</span>';
		$html .= '</div>';
		return $html;
	}
}
