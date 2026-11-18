<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Frontend rendering: shortcode, WooCommerce auto-placement, three display styles.
 */
final class DSSG_Render {
	const NONCE = 'dssg_save';

	public static $options = array();

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_shortcode( 'dssg_guide', array( __CLASS__, 'shortcode' ) );

		// Auto placement hooks (only registered when enabled).
		add_action( 'woocommerce_before_single_product', array( __CLASS__, 'maybe_auto_render' ), 20 );
		add_action( 'woocommerce_after_single_product',    array( __CLASS__, 'maybe_auto_render' ), 20 );

		// Per-product switch box on product edit screen.
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_switch_field' ) );
		add_action( 'woocommerce_admin_process_product_object',         array( __CLASS__, 'save_product_switch' ), 30 );
	}

	/**
	 * Enqueue frontend assets only when needed.
	 */
	public static function enqueue_assets() {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( ! DSSG_Settings::is_enabled_for_product( $product_id ) ) {
			return;
		}

		wp_enqueue_style( 'dssg-public', DSSG_URL . 'assets/public.css', array(), DSSG_VERSION );
		wp_enqueue_script( 'dssg-public', DSSG_URL . 'assets/public.js', array(), DSSG_VERSION, true );

		$options = DSSG_Settings::options();
		wp_add_inline_style(
			'dssg-public',
			self::palette_css( $options['palette'] )
		);
	}

	/**
	 * Build palette CSS variables.
	 *
	 * @param array $palette
	 * @return string
	 */
	public static function palette_css( array $palette ) {
		$defaults = DSSG_Install::default_palette();
		$vars     = array();
		foreach ( $defaults as $key => $fallback ) {
			$value = isset( $palette[ $key ] ) ? $palette[ $key ] : $fallback;
			$vars[] = sprintf( '--dssg-%s: %s;', esc_attr( $key ), esc_html( $value ) );
		}
		return ':root { ' . implode( ' ', $vars ) . ' }';
	}

	/**
	 * Auto render on product page if the global setting requests it.
	 */
	public static function maybe_auto_render() {
		if ( ! is_product() ) {
			return;
		}

		$product_id = get_queried_object_id();
		if ( ! DSSG_Settings::is_enabled_for_product( $product_id ) ) {
			return;
		}

		$options = DSSG_Settings::options();
		if ( empty( $options['auto_render'] ) ) {
			return;
		}

		echo self::render( $product_id );
	}

	/**
	 * Shortcode entrypoint.
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts( array(
			'product_id' => '',
			'style'      => '',
			'id'         => '',
		), $atts, 'dssg_guide' );

		$product_id = absint( $atts['product_id'] );
		if ( $product_id <= 0 ) {
			$product_id = get_queried_object_id();
			if ( get_post_type( $product_id ) !== 'product' ) {
				$product_id = 0;
			}
		}

		if ( $product_id > 0 && ! DSSG_Settings::is_enabled_for_product( $product_id ) ) {
			return '';
		}

		return self::render( $product_id, $atts['style'], $atts['id'] );
	}

	/**
	 * Render guide HTML for a product (uses global configured tabs).
	 *
	 * @param int   $product_id
	 * @param string $style Override style: accordion|tabs|modal
	 * @param string $html_id
	 * @return string
	 */
	public static function render( $product_id = 0, $style = '', $html_id = '' ) {
		$options   = DSSG_Settings::options();
		$effective = $style ? $style : $options['style'];
		if ( ! in_array( $effective, DSSG_Settings::STYLES, true ) ) {
			$effective = 'accordion';
		}

		$html_id = $html_id ? sanitize_html_class( $html_id ) : 'dssg-guide-' . dechex( mt_rand() );

		ob_start();
		require DSSG_DIR . 'templates/' . $effective . '.php';
		return ob_get_clean();
	}

	/**
	 * Per-product switch field on product edit screen.
	 */
	public static function product_switch_field() {
		global $post;
		if ( ! $post ) {
			return;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return;
		}

		$state = DSSG_Install::product_state( $product->get_id() );
		?>
		<div class="options_group">
			<p class="form-field dssg-switch-field">
				<label><?php esc_html_e( 'راهنمای سایز بندی', 'ds-size-guide' ); ?></label>
				<select name="dssg_enable_state" id="dssg_enable_state">
					<option value="default" <?php selected( $state, 'default' ); ?>>
						<?php esc_html_e( 'اتبع تنظیمات عمومی افزونه', 'ds-size-guide' ); ?>
					</option>
					<option value="yes" <?php selected( $state, 'yes' ); ?>>
						<?php esc_html_e( 'فعال', 'ds-size-guide' ); ?>
					</option>
					<option value="no" <?php selected( $state, 'no' ); ?>>
						<?php esc_html_e( 'غیرفعال', 'ds-size-guide' ); ?>
					</option>
				</select>
				<span class="description"><?php esc_html_e( 'تنظیم برای این محصول. گزینهٔ "اتبع" از تنظیمات عمومی افزونه استفاده می‌کند.', 'ds-size-guide' ); ?></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Save per-product switch.
	 *
	 * @param \WC_Product $product
	 */
	public static function save_product_switch( $product ) {
		if ( ! isset( $_POST['dssg_enable_state'] ) ) {
			return;
		}
		$value = sanitize_text_field( wp_unslash( $_POST['dssg_enable_state'] ) );
		if ( ! in_array( $value, array( 'default', 'yes', 'no' ), true ) ) {
			return;
		}
		DSSG_Install::set_product_state( $product->get_id(), $value );
	}
}