<?php
/**
 * گروه ACF «قطعات قابل شخصی‌سازی» روی محصول + هوک‌های کمکی ادمین.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Product_Allowlist_ACF {

	const FIELD_VARIATION_POST_OBJECT = 'field_rsc_pva_variation';

	/**
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'acf/fields/post_object/query/key=' . self::FIELD_VARIATION_POST_OBJECT, array( $this, 'filter_variation_query_to_current_product' ), 10, 3 );
		add_action( 'add_meta_boxes', array( $this, 'hide_allowlist_metabox_unless_variable_product' ), 100, 2 );

		add_action( 'acf/save_post', array( $this, 'strip_variation_buckets_before_parent_acf_save' ), 5 );
		add_filter( 'acf/validate_value/key=field_rsc_pva_rows', array( $this, 'validate_allowlist_repeater' ), 10, 4 );
		add_action( 'load-post.php', array( $this, 'maybe_raise_inp_limits_for_variable_product' ) );
		add_action( 'admin_init', array( $this, 'maybe_raise_inp_limits_on_product_save' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_acf_on_product_screen' ), 20 );
	}

	/**
	 * فقط وریشن‌هایی که `post_parent` برابر محصول در حال ویرایش است.
	 *
	 * @param array      $args    آرگومان‌های WP_Query.
	 * @param array      $field   آرایهٔ فیلد ACF.
	 * @param int|string $post_id شناسهٔ پست صفحهٔ فعلی (محصول).
	 * @return array
	 */
	public function filter_variation_query_to_current_product( $args, $field, $post_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		unset( $field );
		$product_id = (int) $post_id;
		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return $args;
		}
		$args['post_type']    = 'product_variation';
		$args['post_parent']  = $product_id;
		if ( empty( $args['post_status'] ) ) {
			$args['post_status'] = array( 'publish', 'private' );
		}
		return $args;
	}

	/**
	 * هر ردیف باید وریشن معتبر + حداقل یک قطعه داشته باشد؛ وریشن تکراری ممنوع.
	 *
	 * @param bool|string $valid   true یا پیام خطا.
	 * @param mixed       $value   مقدار ریپیتر.
	 * @param array       $field   آرایهٔ فیلد.
	 * @param string      $input   نام input.
	 * @return bool|string
	 */
	public function validate_allowlist_repeater( $valid, $value, $field, $input ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		unset( $field, $input );
		if ( true !== $valid && '' !== $valid ) {
			return $valid;
		}
		if ( ! is_array( $value ) || empty( $value ) ) {
			return $valid;
		}
		$seen = array();
		$n    = 0;
		foreach ( $value as $row ) {
			++$n;
			if ( ! is_array( $row ) ) {
				continue;
			}
			$vid = 0;
			if ( isset( $row['rsc_pva_variation'] ) ) {
				$vid = (int) $row['rsc_pva_variation'];
			} elseif ( isset( $row['field_rsc_pva_variation'] ) ) {
				$vid = (int) $row['field_rsc_pva_variation'];
			}
			if ( $vid <= 0 ) {
				return sprintf(
					/* translators: %d row number */
					__( 'ردیف %d: وریشن را انتخاب کنید.', 'rasam-server-config' ),
					$n
				);
			}
			if ( isset( $seen[ $vid ] ) ) {
				return sprintf(
					/* translators: %d variation id */
					__( 'وریشن #%d در بیش از یک ردیف تکراری است؛ هر وریشن فقط یک ردیف داشته باشد.', 'rasam-server-config' ),
					$vid
				);
			}
			$seen[ $vid ] = true;

			$comps = array();
			if ( isset( $row['rsc_pva_components'] ) && is_array( $row['rsc_pva_components'] ) ) {
				$comps = $row['rsc_pva_components'];
			} elseif ( isset( $row['field_rsc_pva_components'] ) && is_array( $row['field_rsc_pva_components'] ) ) {
				$comps = $row['field_rsc_pva_components'];
			}
			$count = 0;
			foreach ( $comps as $cid ) {
				$cid = is_numeric( $cid ) ? (int) $cid : 0;
				if ( $cid > 0 ) {
					++$count;
				}
			}
			if ( $count < 1 ) {
				return sprintf(
					/* translators: 1: row number, 2: variation id */
					__( 'ردیف %1$d (وریشن #%2$d): حداقل یک قطعهٔ معتبر انتخاب کنید؛ بدون قطعه ذخیره نمی‌شود.', 'rasam-server-config' ),
					$n,
					$vid
				);
			}
		}
		return $valid;
	}

	/**
	 * باکس گروه allowlist فقط برای محصول متغیر (WooCommerce).
	 *
	 * @param string  $post_type نوع پست.
	 * @param WP_Post $post      پست.
	 */
	public function hide_allowlist_metabox_unless_variable_product( $post_type, $post ) {
		if ( 'product' !== $post_type || ! $post instanceof WP_Post ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_product' ) ) {
			$this->remove_allowlist_metabox();
			return;
		}
		$product = wc_get_product( $post->ID );
		if ( $product && $product->is_type( 'variable' ) ) {
			return;
		}
		$this->remove_allowlist_metabox();
	}

	/**
	 * حذف metabox گروه allowlist از همهٔ contextها.
	 */
	private function remove_allowlist_metabox() {
		$box_id = 'acf-' . RSC_ACF::FIELD_GROUP_KEY_PRODUCT_ALLOWLIST;
		foreach ( array( 'normal', 'side', 'advanced', 'acf_after_title' ) as $context ) {
			remove_meta_box( $box_id, 'product', $context );
		}
	}

	/**
	 * هنگام ذخیرهٔ «محصول»، سطل‌های عددی acf[variation_id] را از POST حذف می‌کنیم تا ACF والد خراب نشود.
	 *
	 * @param int|string $post_id شناسهٔ پست در hook acf/save_post.
	 */
	public function strip_variation_buckets_before_parent_acf_save( $post_id ) {
		if ( empty( $_POST['post_ID'] ) || (int) $_POST['post_ID'] !== (int) $post_id ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		if ( 'product' !== get_post_type( (int) $post_id ) ) {
			return;
		}
		if ( empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		foreach ( array_keys( $_POST['acf'] ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( is_numeric( $key ) ) {
				unset( $_POST['acf'][ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputUnsanitized
			}
		}
	}

	/**
	 * سقف max_input_vars روی ویرایش محصول متغیر (صفحهٔ GET).
	 */
	public function maybe_raise_inp_limits_for_variable_product() {
		if ( ! isset( $_GET['post'], $_GET['action'] ) || 'edit' !== $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id <= 0 || 'product' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( function_exists( 'wc_get_product' ) ) {
			$p = wc_get_product( $post_id );
			if ( ! $p || ! $p->is_type( 'variable' ) ) {
				return;
			}
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		@ini_set( 'max_input_vars', '10000' );
		if ( defined( 'PHP_VERSION_ID' ) && PHP_VERSION_ID >= 80400 ) {
			@ini_set( 'max_multipart_body_parts', '5000' );
		}
	}

	/**
	 * همان سقف روی POST ذخیرهٔ محصول / ذخیرهٔ متغیرها.
	 */
	public function maybe_raise_inp_limits_on_product_save() {
		$apply = false;
		if ( ! empty( $_POST['action'] ) && 'woocommerce_save_variations' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$apply = true;
		}
		if ( ! $apply && ! empty( $_POST['post_ID'] ) && 'product' === get_post_type( (int) $_POST['post_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$apply = true;
		}
		if ( ! $apply ) {
			return;
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		@ini_set( 'max_input_vars', '10000' );
		if ( defined( 'PHP_VERSION_ID' ) && PHP_VERSION_ID >= 80400 ) {
			@ini_set( 'max_multipart_body_parts', '5000' );
		}
	}

	/**
	 * اسکریپت‌های ACF روی صفحهٔ ویرایش محصول (repeater / relationship).
	 *
	 * @param string $hook_suffix صفحهٔ ادمین.
	 */
	public function enqueue_acf_on_product_screen( $hook_suffix ) {
		if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
			return;
		}
		global $post;
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}
		if ( ! function_exists( 'acf_enqueue_scripts' ) ) {
			return;
		}
		acf_enqueue_scripts(
			array(
				'uploader' => true,
			)
		);
	}
}
