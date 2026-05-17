<?php
/**
 * فرانت: دکمهٔ پیکربندی + مودال برای جدول woo-variations-table-grid + بار اول سبد.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Frontend_Vartable {

	const SESSION_KEY_PREFIX = 'rsc_cfg_';

	/**
	 * حداکثر تعداد خط انتخاب برای هر گروه تاکسونومی (چند ردیف RAM و …).
	 *
	 * @var int
	 */
	const MAX_GROUP_SELECTION_LINES = 8;

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 30 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_cart_styles' ), 31 );
		add_action( 'wp_ajax_rsc_variation_config_payload', array( $this, 'ajax_config_payload' ) );
		add_action( 'wp_ajax_nopriv_rsc_variation_config_payload', array( $this, 'ajax_config_payload' ) );

		add_action( 'wp_ajax_add_variation_to_cart', array( $this, 'stash_config_before_vartable_cart' ), 5 );
		add_action( 'wp_ajax_nopriv_add_variation_to_cart', array( $this, 'stash_config_before_vartable_cart' ), 5 );

		add_action( 'wp_ajax_wc_rq_submit_form', array( $this, 'sanitize_rq_product_name_post' ), 0 );
		add_action( 'wp_ajax_nopriv_wc_rq_submit_form', array( $this, 'sanitize_rq_product_name_post' ), 0 );

		/** همان نقطهٔ افزونهٔ vartable / wc-request-quotation؛ آرگومان دوم = دادهٔ ردیف وریشن. */
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'render_configure_button' ), 25, 2 );

		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data_from_session' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'restore_cart_item_from_session' ), 10, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_config_price_to_cart' ), 20, 1 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_config_in_cart' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'copy_rsc_lines_to_order_item' ), 10, 4 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_internal_rsc_order_meta' ) );
		add_filter( 'woocommerce_order_item_display_meta_key', array( $this, 'filter_order_item_meta_display_key' ), 10, 3 );
	}

	/**
	 * @return int[]
	 */
	private function get_customizable_variation_ids_for_product( WC_Product $product ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return array();
		}
		$out = array();
		foreach ( $product->get_children() as $vid ) {
			$vid = (int) $vid;
			if ( $vid > 0 && RSC_Variation_Data::is_customization_enabled( $vid ) ) {
				$out[] = $vid;
			}
		}
		return $out;
	}

	/**
	 * متن نمایشی (عنوان پست، ترم، برچسب وریشن): حذف تگ و تبدیل موجودیت‌های HTML به UTF-8.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private function decode_display_text( $value ) {
		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		$stripped = wp_strip_all_tags( $value, true );
		return html_entity_decode(
			wp_specialchars_decode( $stripped, ENT_QUOTES ),
			ENT_QUOTES | ENT_HTML5,
			'UTF-8'
		);
	}

	/**
	 * متای داخلی JSON — فقط برای ذخیرهٔ ساختاری / گزارش؛ در جدول متای سفارش ادمین نمایش داده نشود.
	 *
	 * @param string[] $keys کلیدهای مخفی پیش‌فرض ووکامرس + افزونه‌ها.
	 * @return string[]
	 */
	public function hide_internal_rsc_order_meta( $keys ) {
		if ( ! is_array( $keys ) ) {
			return $keys;
		}
		$keys[] = RSC_Order_Item_Meta::LINES_JSON;
		foreach ( RSC_Order_Item_Meta::legacy_meta_keys() as $legacy_key ) {
			$keys[] = $legacy_key;
		}
		return $keys;
	}

	/**
	 * برچسب فارسی برای کلیدهای ثابت رسام در جزئیات سفارش.
	 *
	 * @param string              $display_key
	 * @param WC_Meta_Data        $meta
	 * @param WC_Order_Item_Product $item
	 * @return string
	 */
	public function filter_order_item_meta_display_key( $display_key, $meta, $item ) {
		unset( $item );
		if ( ! $meta instanceof WC_Meta_Data ) {
			return $display_key;
		}
		$label = RSC_Order_Item_Meta::get_display_label( $meta->key );
		return null !== $label ? $label : $display_key;
	}

	public function enqueue() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		global $product;
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_queried_object_id() );
		}
		if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$customizable = $this->get_customizable_variation_ids_for_product( $product );
		if ( empty( $customizable ) ) {
			return;
		}

		wp_enqueue_style(
			'rsc-vartable-config',
			RSC_PLUGIN_URL . 'assets/css/style-rsc-config.min.css',
			array(),
			RSC_VERSION
		);
		$script_deps = array( 'jquery' );
		if ( wp_script_is( 'wc-rq-script', 'registered' ) ) {
			$script_deps[] = 'wc-rq-script';
		}

		wp_enqueue_script(
			'rsc-vartable-config',
			RSC_PLUGIN_URL . 'assets/js/rsc-vartable-config.js',
			$script_deps,
			RSC_VERSION,
			true
		);

		$currency = get_woocommerce_currency_symbol();
		$fmt      = array(
			'symbol'      => html_entity_decode( wp_strip_all_tags( $currency ), ENT_QUOTES, 'UTF-8' ),
			'decimals'    => wc_get_price_decimals(),
			'thousand'    => wc_get_price_thousand_separator(),
			'decimal'     => wc_get_price_decimal_separator(),
			'symbol_pos' => get_option( 'woocommerce_currency_pos', 'left' ),
			'suffix'      => get_option( 'woocommerce_price_display_suffix', '' ),
		);

		wp_localize_script(
			'rsc-vartable-config',
			'rscVartable',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'rsc_vartable_config' ),
				'customizableIds'  => $customizable,
				'productId'        => (int) $product->get_id(),
				'priceFormat'      => $fmt,
				'maxGroupLines'    => self::MAX_GROUP_SELECTION_LINES,
				'i18n'             => array(
					'configure'            => __( 'پیکربندی', 'rasam-server-config' ),
					'modalTitle'           => __( 'محصول سفارشی', 'rasam-server-config' ),
					'close'                => __( 'بستن', 'rasam-server-config' ),
					'submitAddToCart'      => __( 'ثبت و افزودن به سبد خرید', 'rasam-server-config' ),
					'requestQuotation'     => __( 'دریافت پیش‌فاکتور', 'rasam-server-config' ),
					'adding'               => __( 'در حال افزودن…', 'rasam-server-config' ),
					'loading'              => __( 'در حال بارگذاری…', 'rasam-server-config' ),
					'loadError'            => __( 'خطا در دریافت لیست قطعات.', 'rasam-server-config' ),
					'noComponents'         => __( 'قطعه‌ای برای این وریشن تعریف نشده است.', 'rasam-server-config' ),
					'summaryHeading'       => __( 'محصول انتخابی', 'rasam-server-config' ),
					'summaryEmpty'         => __( 'هنوز قطعه‌ای انتخاب نشده؛ فقط قیمت پایهٔ این محصول اعمال می‌شود.', 'rasam-server-config' ),
					'priceBase'            => __( 'قیمت پایه محصول', 'rasam-server-config' ),
					'priceParts'           => __( 'قطعات اضافه', 'rasam-server-config' ),
					'priceTotal'           => __( 'جمع این محصول', 'rasam-server-config' ),
					'perUnitHint'          => __( 'قیمت هر واحد قبل از ضرب در تعداد سبد', 'rasam-server-config' ),
					'typeOther'            => __( 'سایر قطعات', 'rasam-server-config' ),
					'resetSelection'       => __( 'بازنشانی', 'rasam-server-config' ),
					'validationNeedPart'   => __( 'برای شخصی‌سازی، حداقل یک قطعه را با تعداد بیش از صفر انتخاب کنید، یا این پنجره را ببندید و با «افزودن به سبد خرید» محصول پایه را ثبت کنید.', 'rasam-server-config' ),
					'validationSelectModel' => __( 'برای خطوطی که تعداد دارند، مدل قطعه را از فهرست انتخاب کنید.', 'rasam-server-config' ),
					'selectPlaceholder'  => __( 'انتخاب مدل…', 'rasam-server-config' ),
					'addLine'            => __( 'افزودن ردیف', 'rasam-server-config' ),
					'removeLine'         => __( 'حذف ردیف', 'rasam-server-config' ),
					'summaryAccordionHead'   => __( 'جمع و جزئیات', 'rasam-server-config' ),
					'summaryAccordionExpand' => __( 'نمایش جزئیات انتخاب و قیمت', 'rasam-server-config' ),
					'summaryAccordionCollapse' => __( 'بستن جزئیات', 'rasam-server-config' ),
					/* translators: %d: total quantity of selected add-on pieces */
					'summaryLineCount'   => __( '%d واحد قطعهٔ اضافه', 'rasam-server-config' ),
					'summaryAccordionZero' => __( 'بدون قطعهٔ اضافه', 'rasam-server-config' ),
					'configLabel'          => __( 'کانفیگ', 'rasam-server-config' ),
					'addedPartsLabel'      => __( 'قطعات اضافه', 'rasam-server-config' ),
				),
			)
		);
	}

	/**
	 * استایل خلاصهٔ کانفیگ در سبد و تسویه.
	 */
	public function enqueue_cart_styles() {
		if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) {
			return;
		}
		wp_enqueue_style(
			'rsc-vartable-config',
			RSC_PLUGIN_URL . 'assets/css/style-rsc-config.min.css',
			array(),
			RSC_VERSION
		);
	}

	/**
	 * دکمهٔ «پیکربندی» داخل فرم هر ردیف vartable (بعد از دکمهٔ افزودن به سبد).
	 * ووکامرس پیش‌فرض: بدون آرگومان — اینجا چیزی چاپ نمی‌کنیم (فقط محصول متغیر + ردیف vartable).
	 *
	 * @param int|null   $product_id      شناسهٔ محصول والد (vartable می‌فرستد).
	 * @param array|null $variation_row   آرایهٔ وریشن vartable شامل variation_id.
	 */
	public function render_configure_button( $product_id = null, $variation_row = null ) {
		if ( ! is_array( $variation_row ) || empty( $variation_row['variation_id'] ) ) {
			return;
		}
		$parent_id = $product_id ? (int) $product_id : 0;
		if ( $parent_id <= 0 ) {
			return;
		}
		$parent = wc_get_product( $parent_id );
		if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
			return;
		}
		$vid = (int) $variation_row['variation_id'];
		if ( $vid <= 0 || ! RSC_Variation_Data::is_customization_enabled( $vid ) ) {
			return;
		}
		printf(
			'<input type="hidden" name="rsc_config_json" value="" class="rsc-vt-config-json" data-rsc-variation="%1$d" />',
			$vid
		);
		printf(
			'<button type="button" class="button rsc-vt-config-btn" data-rsc-variation-id="%1$d">' .
			'<svg class="rsc-vt-config-btn__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' .
			'<path d="M21.25 12H8.895m-4.361 0H2.75m18.5 6.607h-5.748m-4.361 0H2.75m18.5-13.214h-3.105m-4.361 0H2.75m13.214 2.18a2.18 2.18 0 1 0 0-4.36 2.18 2.18 0 0 0 0 4.36Zm-9.25 6.607a2.18 2.18 0 1 0 0-4.36 2.18 2.18 0 0 0 0 4.36Zm6.607 6.608a2.18 2.18 0 1 0 0-4.361 2.18 2.18 0 0 0 0 4.36Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/>' .
			'</svg><span class="rsc-vt-config-btn__label">%2$s</span></button>',
			$vid,
			esc_html__( 'پیکربندی', 'rasam-server-config' )
		);
	}

	public function ajax_config_payload() {
		check_ajax_referer( 'rsc_vartable_config', 'nonce' );
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		if ( $variation_id <= 0 || ! RSC_Variation_Data::is_variation( $variation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'آیتم نامعتبر است.', 'rasam-server-config' ) ), 400 );
		}
		if ( ! RSC_Variation_Data::is_customization_enabled( $variation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'این آیتم قابل شخصی‌سازی نیست.', 'rasam-server-config' ) ), 400 );
		}

		$posted_product = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( $posted_product > 0 ) {
			$parent = RSC_Variation_Data::get_variation_parent_product_id( $variation_id );
			if ( $parent !== $posted_product ) {
				wp_send_json_error( array( 'message' => __( 'محصول با آیتم همخوانی ندارد.', 'rasam-server-config' ) ), 403 );
			}
		}

		$ids = RSC_Variation_Data::get_allowed_component_post_ids( $variation_id );
		$components = array();
		foreach ( $ids as $cid ) {
			$post = get_post( $cid );
			if ( ! $post ) {
				continue;
			}
			$type_label = RSC_Component_Data::get_primary_type_label( $cid );
			$type_slug  = RSC_Component_Data::get_primary_type_slug( $cid );
			$components[] = array(
				'id'          => (int) $cid,
				'title'       => $this->decode_display_text( get_the_title( $cid ) ),
				'unit_price'  => RSC_Component_Data::get_unit_price( $cid ),
				'type_label'  => $this->decode_display_text( $type_label ),
				'type_slug'   => $type_slug,
			);
		}

		$buckets = array();
		foreach ( $components as $c ) {
			$key = isset( $c['type_slug'] ) && '' !== (string) $c['type_slug'] ? (string) $c['type_slug'] : '_other';
			if ( ! isset( $buckets[ $key ] ) ) {
				$buckets[ $key ] = array(
					'key'        => $key,
					'label'      => '',
					'multi'      => false,
					'components' => array(),
				);
			}
			$buckets[ $key ]['components'][] = $c;
		}
		$groups = array();
		foreach ( $buckets as $key => $grp ) {
			usort(
				$grp['components'],
				static function ( $a, $b ) {
					return ( (int) ( $a['id'] ?? 0 ) ) <=> ( (int) ( $b['id'] ?? 0 ) );
				}
			);
			$n                = count( $grp['components'] );
			$grp['multi']     = $n > 1;
			$first_label      = isset( $grp['components'][0]['type_label'] ) ? trim( (string) $grp['components'][0]['type_label'] ) : '';
			$grp['label']     = '' !== $first_label ? $first_label : __( 'سایر قطعات', 'rasam-server-config' );
			$grp['key']       = $key;
			$groups[]         = $grp;
		}
		usort(
			$groups,
			static function ( $a, $b ) {
				return strcmp( (string) ( $a['label'] ?? '' ), (string) ( $b['label'] ?? '' ) );
			}
		);

		$parent_id = RSC_Variation_Data::get_variation_parent_product_id( $variation_id );
		$product_title     = $parent_id > 0 ? $this->decode_display_text( get_the_title( $parent_id ) ) : '';
		$variation_label   = '';
		$variation_name    = $this->decode_display_text( get_the_title( $variation_id ) );
		$variation_obj     = wc_get_product( $variation_id );
		if ( $variation_obj && $variation_obj->is_type( 'variation' ) ) {
			// پارامتر چهارم false تا همهٔ ویژگی‌ها در رشتهٔ تخت بیایند (true ویژگی‌های تکراری با نام وریشن را حذف می‌کرد).
			$variation_label = $this->decode_display_text(
				wc_get_formatted_variation( $variation_obj, true, true, false )
			);
		}

		wp_send_json_success(
			array(
				'components'          => $components,
				'groups'              => $groups,
				'max_group_lines'     => self::MAX_GROUP_SELECTION_LINES,
				'fixed_price'         => RSC_Variation_Data::get_wc_variation_price( $variation_id ),
				'product_title'       => $product_title,
				'variation_name'      => $variation_name,
				'variation_label'     => $variation_label,
				'product_config_line' => RSC_Display_Text::build_product_config_line( $product_title, $variation_name, $variation_label ),
				'variation_spec'      => RSC_Display_Text::get_variation_spec( $product_title, $variation_name, $variation_label ),
			)
		);
	}

	/**
	 * قبل از wc-request-quotation: ستون کانفیگ فقط لاتین و بدون برچسب «قطعات اضافه».
	 */
	public function sanitize_rq_product_name_post() {
		if ( empty( $_POST['product_name'] ) || ! is_string( $_POST['product_name'] ) ) {
			return;
		}
		$raw = wp_unslash( $_POST['product_name'] );
		$_POST['product_name'] = RSC_Display_Text::normalize_quotation_product_name( $raw );
	}

	/**
	 * قبل از هندلر vartable، JSON پیکربندی را در سشن می‌گذاریم (درخواست AJAX فقط فیلدهای محدود می‌فرستد).
	 */
	public function stash_config_before_vartable_cart() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		if ( $variation_id <= 0 ) {
			return;
		}
		if ( empty( $_POST['rsc_config_json'] ) ) {
			WC()->session->set( self::SESSION_KEY_PREFIX . $variation_id, null );
			return;
		}
		$raw = wp_unslash( $_POST['rsc_config_json'] );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return;
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return;
		}
		$lines = array();
		foreach ( $decoded as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id  = isset( $row['id'] ) ? (int) $row['id'] : 0;
			$qty = isset( $row['qty'] ) ? (int) $row['qty'] : 0;
			if ( $id > 0 && $qty > 0 ) {
				$lines[] = array( 'id' => $id, 'qty' => $qty );
			}
		}
		if ( empty( $lines ) ) {
			WC()->session->set( self::SESSION_KEY_PREFIX . $variation_id, null );
			return;
		}
		WC()->session->set( self::SESSION_KEY_PREFIX . $variation_id, wp_json_encode( $lines ) );
	}

	/**
	 * @param array 
	 * @param int  
	 * @param int   
	 * @return array
	 */
	public function add_cart_item_data_from_session( $cart_item_data, $product_id, $variation_id ) {
		unset( $product_id );
		if ( ! $variation_id || ! function_exists( 'WC' ) || ! WC()->session ) {
			return $cart_item_data;
		}
		$key = self::SESSION_KEY_PREFIX . (int) $variation_id;
		$raw = WC()->session->get( $key );
		if ( ! is_string( $raw ) || '' === $raw ) {
			return $cart_item_data;
		}
		WC()->session->set( $key, null );

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return $cart_item_data;
		}
		$filtered = RSC_Variation_Data::filter_selection_to_allowed( (int) $variation_id, $decoded );
		if ( empty( $filtered ) ) {
			return $cart_item_data;
		}
		$cart_item_data['rsc_lines'] = wp_json_encode( $filtered );
		return $cart_item_data;
	}

	/**
	 * بازگردانی کلیدهای سفارشی از سشن سبد.
	 *
	 * @param array $cart_item آیتم سبد.
	 * @param array $values    مقادیر ذخیره‌شده در سشن.
	 * @param string $key      کلید خط.
	 * @return array
	 */
	public function restore_cart_item_from_session( $cart_item, $values, $key ) {
		unset( $key );
		if ( ! empty( $values['rsc_lines'] ) && is_string( $values['rsc_lines'] ) ) {
			$cart_item['rsc_lines'] = $values['rsc_lines'];
		}
		return $cart_item;
	}

	/**
	 * قیمت واحد خط سبد = قیمت وریشن + قطعات (طبق انتخاب).
	 *
	 * @param WC_Cart $cart سبد.
	 */
	public function apply_config_price_to_cart( $cart ) {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		foreach ( $cart->get_cart() as $_cart_key => $cart_item ) {
			if ( empty( $cart_item['variation_id'] ) || empty( $cart_item['rsc_lines'] ) ) {
				continue;
			}
			$vid = (int) $cart_item['variation_id'];
			$decoded = json_decode( $cart_item['rsc_lines'], true );
			if ( ! is_array( $decoded ) || empty( $decoded ) ) {
				continue;
			}
			$res   = RSC_Variation_Data::calculate_totals( $vid, $decoded );
			$price = isset( $res['total'] ) ? (float) $res['total'] : 0.0;
			if ( $price < 0 ) {
				$price = 0.0;
			}
			$data = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
			if ( $data && is_object( $data ) && is_callable( array( $data, 'set_price' ) ) ) {
				$data->set_price( $price );
			}
		}
	}

	/**
	 * کپی پیکربندی به متای آیتم سفارش.
	 *
	 * @param WC_Order_Item_Product $item      آیتم.
	 * @param string                $cart_key کلید سبد.
	 * @param array                 $values   مقادیر خط سبد.
	 * @param WC_Order              $order    سفارش.
	 */
	public function copy_rsc_lines_to_order_item( $item, $cart_key, $values, $order ) {
		unset( $order, $cart_key );
		if ( empty( $values['rsc_lines'] ) || ! is_string( $values['rsc_lines'] ) ) {
			return;
		}
		$item->add_meta_data( RSC_Order_Item_Meta::LINES_JSON, $values['rsc_lines'], true );

		$variation_id = isset( $values['variation_id'] ) ? (int) $values['variation_id'] : 0;
		if ( $variation_id > 0 ) {
			$parent_id = RSC_Variation_Data::get_variation_parent_product_id( $variation_id );
			$product_title   = $parent_id > 0 ? $this->decode_display_text( get_the_title( $parent_id ) ) : '';
			$variation_name  = $this->decode_display_text( get_the_title( $variation_id ) );
			$variation_label = '';
			$variation_obj   = wc_get_product( $variation_id );
			if ( $variation_obj && $variation_obj->is_type( 'variation' ) ) {
				$variation_label = $this->decode_display_text(
					wc_get_formatted_variation( $variation_obj, true, true, false )
				);
			}
			$base_spec = RSC_Display_Text::get_variation_spec( $product_title, $variation_name, $variation_label );
			if ( '' !== $base_spec ) {
				$item->add_meta_data( RSC_Order_Item_Meta::BASE_CONFIG, $base_spec, false );
			}
		}

		$decoded = json_decode( $values['rsc_lines'], true );
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return;
		}
		$filtered = $variation_id > 0
			? RSC_Variation_Data::filter_selection_to_allowed( $variation_id, $decoded )
			: array();
		if ( empty( $filtered ) ) {
			return;
		}
		$parts_text = RSC_Display_Text::format_parts_compact( $filtered );
		if ( '' !== $parts_text ) {
			$item->add_meta_data( RSC_Order_Item_Meta::CUSTOM_PARTS, $parts_text, false );
		}
	}

	/**
	 * نمایش خلاصه در سبد و تسویه.
	 *
	 * @param array $item_data داده‌های نمایشی.
	 * @param array $cart_item آیتم سبد (آرایهٔ خط).
	 * @return array
	 */
	public function display_config_in_cart( $item_data, $cart_item ) {
		if ( empty( $cart_item['rsc_lines'] ) ) {
			return $item_data;
		}
		$decoded = json_decode( $cart_item['rsc_lines'], true );
		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return $item_data;
		}
		$vid = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
		if ( $vid <= 0 ) {
			return $item_data;
		}
		$line_qty = isset( $cart_item['quantity'] ) ? max( 1, (int) $cart_item['quantity'] ) : 1;
		$res      = RSC_Variation_Data::calculate_totals( $vid, $decoded );

		$item_data[] = array(
			'key'     => '',
			'name'    => '',
			'value'   => '',
			'display' => RSC_Display_Text::format_cart_meta_html(
				$decoded,
				(float) $res['fixed'],
				(float) $res['parts'],
				$line_qty
			),
		);
		return $item_data;
	}
}
