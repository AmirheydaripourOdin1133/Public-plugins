<?php
/**
 * خواندن متای قطعات برای موتور قیمت (بدون وابستگی مستقیم به ACF در محاسبه).
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Component_Data {

	public const META_UNIT_PRICE    = 'rsc_unit_price';
	public const META_INTERNAL_SKU  = 'rsc_internal_sku';
	public const META_UPGRADE_FAMILY = 'rsc_upgrade_family';

	/**
	 * آیا پست یک قطعهٔ معتبر و منتشرشده است؟
	 *
	 * @param int $post_id شناسه پست.
	 * @return bool
	 */
	public static function is_valid_component( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return false;
		}
		$post = get_post( $post_id );
		return $post
			&& RSC_Post_Types::CPT_COMPONENT === $post->post_type
			&& 'publish' === $post->post_status;
	}

	/**
	 * قیمت واحد تومان (عدد ساده).
	 *
	 * @param int $post_id شناسه قطعه.
	 * @return float مطمئن برای محاسبات.
	 */
	/**
	 * نام نمایشی اولین ترم تاکسونومی «نوع قطعه» (برای گروه‌بندی در UI).
	 *
	 * @param int $post_id شناسهٔ قطعه.
	 * @return string خالی اگر ترمی نباشد.
	 */
	public static function get_primary_type_label( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! self::is_valid_component( $post_id ) ) {
			return '';
		}
		$terms = get_the_terms( $post_id, RSC_Post_Types::TAX_COMPONENT_TYPE );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}
		$term = $terms[0];
		return isset( $term->name ) ? (string) $term->name : '';
	}

	/**
	 * اسلاگ اولین ترم نوع قطعه (کلید پایدار برای گروه‌بندی).
	 *
	 * @param int $post_id شناسهٔ قطعه.
	 * @return string
	 */
	public static function get_primary_type_slug( $post_id ) {
		$post_id = (int) $post_id;
		if ( ! self::is_valid_component( $post_id ) ) {
			return '';
		}
		$terms = get_the_terms( $post_id, RSC_Post_Types::TAX_COMPONENT_TYPE );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '_other';
		}
		$term = $terms[0];
		return isset( $term->slug ) && $term->slug !== '' ? (string) $term->slug : '_other';
	}

	public static function get_unit_price( $post_id ) {
		if ( ! self::is_valid_component( $post_id ) ) {
			return 0.0;
		}
		$raw = null;
		if ( function_exists( 'get_field' ) ) {
			$raw = get_field( self::META_UNIT_PRICE, $post_id );
		}
		if ( null === $raw || '' === $raw ) {
			$raw = get_post_meta( $post_id, self::META_UNIT_PRICE, true );
		}
		if ( '' === $raw || null === $raw ) {
			return 0.0;
		}
		return max( 0, (float) $raw );
	}
}
