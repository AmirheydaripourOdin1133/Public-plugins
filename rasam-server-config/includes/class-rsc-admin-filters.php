<?php
/**
 * فیلترهای پیشخوان: محصولات دارای شخصی‌سازی و سفارشات دارای کانفیگ رسام.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Admin_Filters {

	public const PRODUCT_QUERY_VAR = 'rsc_product_filter';

	public const ORDER_QUERY_VAR = 'rsc_order_filter';

	public const PRODUCT_VALUE_CUSTOMIZABLE = 'customizable';

	public const ORDER_VALUE_CONFIGURED = 'configured';

	private const TRANSIENT_PARENT_IDS = 'rsc_admin_parent_ids_with_customization';

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
		add_action( 'restrict_manage_posts', array( $this, 'render_product_filter' ), 20, 2 );
		add_action( 'parse_query', array( $this, 'filter_products_admin_query' ) );

		add_action( 'restrict_manage_posts', array( $this, 'render_legacy_order_filter' ), 20, 2 );
		add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'render_hpos_order_filter' ), 20, 2 );

		add_filter( 'posts_clauses', array( $this, 'filter_legacy_orders_clauses' ), 10, 2 );
		add_filter( 'woocommerce_orders_table_query_clauses', array( $this, 'filter_hpos_orders_clauses' ), 10, 3 );

		add_action( 'save_post_product', array( $this, 'bust_product_parent_cache' ) );
		add_action( 'save_post_product_variation', array( $this, 'bust_product_parent_cache' ) );
	}

	/**
	 * @param int $post_id Unused.
	 */
	public function bust_product_parent_cache( $post_id = 0 ) {
		unset( $post_id );
		delete_transient( self::TRANSIENT_PARENT_IDS );
	}

	/**
	 * شناسهٔ محصولات والدی که حداقل یک وریشن با قطعهٔ قابل شخصی‌سازی دارند.
	 *
	 * @return int[]
	 */
	private function get_parent_product_ids_with_customization() {
		$cached = get_transient( self::TRANSIENT_PARENT_IDS );
		if ( false !== $cached && is_array( $cached ) ) {
			return array_map( 'intval', $cached );
		}

		global $wpdb;
		// محصولاتی که ریپیتر ACF حداقل یک ردیف دارد (سریع‌تر از پیمایش همهٔ وریشن‌ها).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders below.
		$sql = "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm
			WHERE pm.meta_key = %s
			AND pm.meta_value IS NOT NULL
			AND pm.meta_value != ''
			AND pm.meta_value != '0'
			AND pm.meta_value != 'a:0:{}'";
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$candidate_parents = $wpdb->get_col( $wpdb->prepare( $sql, RSC_Variation_Data::PARENT_FIELD_ALLOWLIST_ROWS ) );
		if ( ! is_array( $candidate_parents ) ) {
			$candidate_parents = array();
		}

		$out = array();
		foreach ( $candidate_parents as $pid ) {
			$pid = (int) $pid;
			if ( $pid <= 0 ) {
				continue;
			}
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $pid ) : null;
			if ( ! $product || ! $product->is_type( 'variable' ) ) {
				continue;
			}
			foreach ( $product->get_children() as $vid ) {
				$vid = (int) $vid;
				if ( $vid > 0 && RSC_Variation_Data::is_customization_enabled( $vid ) ) {
					$out[] = $pid;
					break;
				}
			}
		}

		$out = array_values( array_unique( array_filter( $out ) ) );
		set_transient( self::TRANSIENT_PARENT_IDS, $out, HOUR_IN_SECONDS );

		return $out;
	}

	/**
	 * @param string $post_type نوع پست لیست.
	 * @param string $which     top|bottom.
	 */
	public function render_product_filter( $post_type, $which ) {
		if ( 'top' !== $which || 'product' !== $post_type ) {
			return;
		}
		if ( ! current_user_can( 'edit_products' ) ) {
			return;
		}
		$current = isset( $_GET[ self::PRODUCT_QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::PRODUCT_QUERY_VAR ] ) ) : '';
		?>
		<select name="<?php echo esc_attr( self::PRODUCT_QUERY_VAR ); ?>" id="rsc-product-customization-filter">
			<option value=""><?php esc_html_e( 'همه محصولات', 'rasam-server-config' ); ?></option>
			<option value="<?php echo esc_attr( self::PRODUCT_VALUE_CUSTOMIZABLE ); ?>" <?php selected( $current, self::PRODUCT_VALUE_CUSTOMIZABLE ); ?>>
				<?php esc_html_e( 'فقط با شخصی‌سازی تنظیمات (رسام)', 'rasam-server-config' ); ?>
			</option>
		</select>
		<?php
	}

	/**
	 * @param \WP_Query $query کوئری اصلی لیست محصولات.
	 */
	public function filter_products_admin_query( $query ) {
		if ( ! is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'edit-product' !== $screen->id ) {
			return;
		}
		if ( ! current_user_can( 'edit_products' ) ) {
			return;
		}
		if ( empty( $_GET[ self::PRODUCT_QUERY_VAR ] ) || self::PRODUCT_VALUE_CUSTOMIZABLE !== sanitize_text_field( wp_unslash( $_GET[ self::PRODUCT_QUERY_VAR ] ) ) ) {
			return;
		}

		$ids = $this->get_parent_product_ids_with_customization();
		if ( empty( $ids ) ) {
			$query->set( 'post__in', array( 0 ) );
		} else {
			$query->set( 'post__in', $ids );
		}
	}

	/**
	 * @param string $post_type نوع پست.
	 * @param string $which     top|bottom.
	 */
	public function render_legacy_order_filter( $post_type, $which ) {
		if ( 'top' !== $which || 'shop_order' !== $post_type ) {
			return;
		}
		if ( self::is_hpos_orders() ) {
			return;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$this->echo_order_filter_select();
	}

	/**
	 * @param string $order_type نوع سفارش.
	 * @param string $which      top|bottom.
	 */
	public function render_hpos_order_filter( $order_type, $which ) {
		if ( 'top' !== $which || 'shop_order' !== $order_type ) {
			return;
		}
		if ( ! self::is_hpos_orders() ) {
			return;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$this->echo_order_filter_select();
	}

	private function echo_order_filter_select() {
		$current = isset( $_GET[ self::ORDER_QUERY_VAR ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ORDER_QUERY_VAR ] ) ) : '';
		?>
		<select name="<?php echo esc_attr( self::ORDER_QUERY_VAR ); ?>" id="rsc-order-config-filter">
			<option value=""><?php esc_html_e( 'همه سفارشات', 'rasam-server-config' ); ?></option>
			<option value="<?php echo esc_attr( self::ORDER_VALUE_CONFIGURED ); ?>" <?php selected( $current, self::ORDER_VALUE_CONFIGURED ); ?>>
				<?php esc_html_e( 'فقط با کانفیگ شخصی‌سازی‌شده (رسام)', 'rasam-server-config' ); ?>
			</option>
		</select>
		<?php
	}

	/**
	 * @return bool
	 */
	private static function is_hpos_orders() {
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
			return false;
		}
		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * @param string[] $clauses compact: fields, join, where, groupby, orderby, limits.
	 * @param mixed    $query   OrdersTableQuery.
	 * @param array    $args    آرگومان‌های کوئری.
	 * @return string[]
	 */
	public function filter_hpos_orders_clauses( $clauses, $query, $args ) {
		unset( $query, $args );
		if ( ! is_admin() || ! $this->should_filter_orders() ) {
			return $clauses;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return $clauses;
		}

		global $wpdb;
		$fields = isset( $clauses['fields'] ) ? trim( (string) $clauses['fields'] ) : '';
		if ( '' === $fields || false === strpos( $fields, '.id' ) ) {
			return $clauses;
		}

		$oi_table  = $wpdb->prefix . 'woocommerce_order_items';
		$oim_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$lines_key = esc_sql( RSC_Order_Item_Meta::LINES_JSON );

		$exists = " AND EXISTS (
			SELECT 1 FROM {$oi_table} rsc_rqi
			INNER JOIN {$oim_table} rsc_rqm ON rsc_rqm.order_item_id = rsc_rqi.order_item_id
			WHERE rsc_rqi.order_id = {$fields}
			AND rsc_rqm.meta_key = '{$lines_key}'
			AND TRIM( COALESCE( rsc_rqm.meta_value, '' ) ) NOT IN ( '', '[]', 'null' )
			AND CHAR_LENGTH( TRIM( rsc_rqm.meta_value ) ) > 2
		)";

		$clauses['where'] = isset( $clauses['where'] ) ? (string) $clauses['where'] . $exists : '1=1' . $exists;

		return $clauses;
	}

	/**
	 * @param string[] $clauses posts_clauses.
	 * @param \WP_Query $query  WP_Query.
	 * @return string[]
	 */
	public function filter_legacy_orders_clauses( $clauses, $query ) {
		if ( ! is_admin() || ! $query instanceof \WP_Query || ! $query->is_main_query() ) {
			return $clauses;
		}
		if ( self::is_hpos_orders() ) {
			return $clauses;
		}
		if ( ! $this->should_filter_orders() ) {
			return $clauses;
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return $clauses;
		}

		$pt = $query->get( 'post_type' );
		if ( 'shop_order' !== $pt && ( ! is_array( $pt ) || ! in_array( 'shop_order', $pt, true ) ) ) {
			return $clauses;
		}

		global $wpdb;
		$oi_table  = $wpdb->prefix . 'woocommerce_order_items';
		$oim_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$lines_key = esc_sql( RSC_Order_Item_Meta::LINES_JSON );

		$exists = " AND EXISTS (
			SELECT 1 FROM {$oi_table} rsc_rqi
			INNER JOIN {$oim_table} rsc_rqm ON rsc_rqm.order_item_id = rsc_rqi.order_item_id
			WHERE rsc_rqi.order_id = {$wpdb->posts}.ID
			AND rsc_rqm.meta_key = '{$lines_key}'
			AND TRIM( COALESCE( rsc_rqm.meta_value, '' ) ) NOT IN ( '', '[]', 'null' )
			AND CHAR_LENGTH( TRIM( rsc_rqm.meta_value ) ) > 2
		)";

		$clauses['where'] = isset( $clauses['where'] ) ? $clauses['where'] . $exists : '1=1' . $exists;

		return $clauses;
	}

	/**
	 * @return bool
	 */
	private function should_filter_orders() {
		return ! empty( $_GET[ self::ORDER_QUERY_VAR ] )
			&& self::ORDER_VALUE_CONFIGURED === sanitize_text_field( wp_unslash( $_GET[ self::ORDER_QUERY_VAR ] ) );
	}
}
