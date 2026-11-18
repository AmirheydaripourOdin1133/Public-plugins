<?php
namespace DSSG;
defined( 'ABSPATH' ) || exit;

/**
 * Activation, deactivation, uninstall and migration helpers.
 */
final class DSSG_Install {
	private const OPTION = 'dssg_options';
	public const DB_VERSION = '1.0.0';
	private const META_KEY = '_dssg_enable'; // 'default' | 'yes' | 'no'

	/**
	 * Register hooks.
	 */
	public static function init() {
		register_activation_hook( DSSG_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( DSSG_FILE, array( __CLASS__, 'deactivate' ) );
		register_uninstall_hook( DSSG_FILE, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Run upgrade logic on every load (idempotent).
	 */
	public static function maybe_upgrade() {
		$stored = get_option( self::OPTION );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$changed = false;

		if ( empty( $stored['version'] ) || $stored['version'] !== self::DB_VERSION ) {
			$stored['version'] = self::DB_VERSION;
			$changed           = true;
		}

		if ( ! isset( $stored['style'] ) ) {
			$stored['style'] = 'accordion';
			$changed         = true;
		}
		if ( ! isset( $stored['enable_mode'] ) ) {
			$stored['enable_mode'] = 'all';
			$changed               = true;
		}
		if ( ! isset( $stored['categories'] ) ) {
			$stored['categories'] = array();
			$changed              = true;
		}
		if ( ! isset( $stored['products'] ) ) {
			$stored['products'] = array();
			$changed            = true;
		}
		if ( ! isset( $stored['palette'] ) ) {
			$stored['palette'] = self::default_palette();
			$changed           = true;
		}
		if ( ! isset( $stored['tabs'] ) || ! is_array( $stored['tabs'] ) ) {
			$stored['tabs'] = self::sample_tabs();
			$changed        = true;
		}

		if ( $changed ) {
			update_option( self::OPTION, $stored, false );
		}
	}

	/**
	 * Activation hook.
	 */
	public static function activate() {
		self::maybe_upgrade();
	}

	/**
	 * Deactivation hook — nothing destructive.
	 */
	public static function deactivate() {
		// No-op by design: settings and product meta are preserved for reactivation.
	}

	/**
	 * Uninstall hook — remove option only, keep product meta (site data).
	 */
	public static function uninstall() {
		delete_option( self::OPTION );
	}

	/**
	 * Default colour palette.
	 *
	 * @return array
	 */
	public static function default_palette() {
		return array(
			'primary'   => '#a94e00',
			'secondary' => '#271f16',
			'bg'        => '#ffffff',
			'text'      => '#271f16',
			'muted'     => '#5e554a',
			'border'    => '#e6e0d6',
		);
	}

	/**
	 * Sample guide content (migrated from the previous static plugin).
	 *
	 * @return array
	 */
	public static function sample_tabs() {
		return array(
			array(
				'id'      => 'tab-' . self::uid(),
				'title'   => 'راهنمای سایز و مراقبت',
				'icon'    => 'ruler',
				'items'   => self::sample_items(),
			),
		);
	}

	/**
	 * Sample items.
	 *
	 * @return array
	 */
	public static function sample_items() {
		return array(
			array(
				'id'    => 'item-' . self::uid(),
				'title' => 'راهنمای اندازه',
				'icon'  => 'ruler',
				'open'  => true,
				'content' => '<p>برای انتخاب سایز مناسب، لطفاً جدول اندازه‌گیری زیر را بررسی کنید. اندازه‌ها براساس دور کمر در حالت تنظیم معمول کمربند تعیین شده‌اند.</p>'
					. '<div class="ssbg-table-wrap"><table><caption class="ssbg-sr">جدول سایز کمربند؛ دور کمر برحسب سانتی‌متر و اینچ</caption>'
					. '<thead><tr><th scope="col">سایز</th><th scope="col">دور کمر (سانتی‌متر)</th><th scope="col">دور کمر (اینچ)</th></tr></thead>'
					. '<tbody>'
					. '<tr><th scope="row"><bdi dir="ltr">XS</bdi></th><td><bdi dir="ltr">57.5 – 70</bdi></td><td><bdi dir="ltr">22.5 – 27.5</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">S</bdi></th><td><bdi dir="ltr">65 – 80</bdi></td><td><bdi dir="ltr">25.5 – 31.5</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">M</bdi></th><td><bdi dir="ltr">72.5 – 90</bdi></td><td><bdi dir="ltr">28.5 – 35.5</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">L</bdi></th><td><bdi dir="ltr">80 – 100</bdi></td><td><bdi dir="ltr">31.5 – 39.5</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">XL</bdi></th><td><bdi dir="ltr">87.5 – 110</bdi></td><td><bdi dir="ltr">34.5 – 43.5</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">2XL</bdi></th><td><bdi dir="ltr">95 – 120</bdi></td><td><bdi dir="ltr">37.5 – 47</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">3XL</bdi></th><td><bdi dir="ltr">102.5 – 130</bdi></td><td><bdi dir="ltr">40.5 – 51</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">4XL</bdi></th><td><bdi dir="ltr">110 – 140</bdi></td><td><bdi dir="ltr">43.5 – 55</bdi></td></tr>'
					. '<tr><th scope="row"><bdi dir="ltr">5XL</bdi></th><td><bdi dir="ltr">117.5 – 150</bdi></td><td><bdi dir="ltr">46.5 – 59</bdi></td></tr>'
					. '</tbody></table></div>',
			),
			array(
				'id'    => 'item-' . self::uid(),
				'title' => 'چگونه اندازه‌گیری کنیم؟',
				'icon'  => 'tape',
				'open'  => false,
				'content' => '<p>اگر از قبل کمربند دارید، توصیه می‌کنیم آن را در محکم‌ترین حالتی که معمولاً استفاده می‌کنید اندازه‌گیری کنید.</p>'
					. '<p>اگر کمربند ندارید، دور کمر خود را در محلی که معمولاً کمربند بسته می‌شود (معمولاً بالاتر از ناف) اندازه‌گیری کنید و شکم را به داخل بدهید.</p>'
					. '<p>از اندازه دور کمر شلوار استفاده نکنید، چون معیار مناسبی برای انتخاب کمربند نیست.</p>'
					. '<p>پیشنهاد می‌کنیم کمربندی انتخاب کنید که در وسط بازه ریگلاژ قرار بگیرد.</p>',
			),
			array(
				'id'    => 'item-' . self::uid(),
				'title' => 'دستورالعمل‌های مراقبت',
				'icon'  => 'care',
				'open'  => false,
				'content' => '<p><strong>مدل‌های پرو (PRO)</strong><br>برای حفظ ظاهر چرم طبیعی و دوخت مدل‌های پرو، توصیه می‌کنیم هر ۱ تا ۳ ماه از روغن مراقبتی مことに استفاده کنید.</p>'
					. '<p><strong>مدل‌های اکو (ECO)</strong><br>در مدل‌های اقتصادی نیز، برای افزایش طول عمر و جلوگیری از آسیب، از پرت کردن یا رها کردن کمربند خودداری کنید.</p>',
			),
		);
	}

	/**
	 * Unique id helper.
	 *
	 * @return string
	 */
	private static function uid() {
		static $counter = 0;
		return dechex( mt_rand() ) . ( ++$counter );
	}

	/**
	 * Get per-product enablement state.
	 *
	 * @param int $product_id
	 * @return string 'default'|'yes'|'no'
	 */
	public static function product_state( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return 'default';
		}
		$value = $product->get_meta( self::META_KEY );
		if ( ! in_array( $value, array( 'default', 'yes', 'no' ), true ) ) {
			return 'default';
		}
		return $value;
	}

	/**
	 * Save per-product enablement state.
	 *
	 * @param int   $product_id
	 * @param string $state
	 */
	public static function set_product_state( $product_id, $state ) {
		if ( ! in_array( $state, array( 'default', 'yes', 'no' ), true ) ) {
			return;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}
		$product->update_meta_data( self::META_KEY, $state );
		$product->save();
	}

	/**
	 * Migrate legacy `_ssbg_enabled` meta to new `_dssg_enable`.
	 *
	 * @return int Number of migrated products.
	 */
	public static function migrate_legacy() {
		global $wpdb;
		$count = 0;
		$ids   = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ssbg_enabled' AND meta_value = 'yes'" );
		foreach ( $ids as $id ) {
			if ( get_post_type( $id ) === 'product' ) {
				self::set_product_state( $id, 'yes' );
				delete_metadata( 'post', $id, '_ssbg_enabled' );
				$count++;
			}
		}
		return $count;
	}
}