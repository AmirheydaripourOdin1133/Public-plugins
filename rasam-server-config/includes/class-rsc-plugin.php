<?php
/**
 * Core plugin loader.
 *
 * @package Rasam_Server_Config
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class RSC_Plugin {

	/**
	 * مسیر فایل اصلی نسبت به پوشهٔ plugins (برای is_plugin_active).
	 *
	 * @var array<string, string> plugin basename => برچسب نمایشی
	 */
	private const REQUIRED_PLUGINS = array(
		'advanced-custom-fields-pro/acf.php'       => 'Advanced Custom Fields (ACF) Pro',
		'woo-variations-table-grid/woo-variations-table.php' => 'Woo Variations Table Grid',
		'wc-request-quotation/wc-request-quotation.php'      => 'WC Request Quotation',
	);

	/**
	 * Singleton.
	 *
	 * @var RSC_Plugin|null
	 */
	private static $instance = null;

	/**
	 * آیا هر سه افزونهٔ وابسته فعال هستند.
	 *
	 * @var bool
	 */
	private $dependencies_ok = false;

	/**
	 * @return RSC_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->dependencies_ok = $this->are_required_plugins_active();
		$this->load_dependencies();
		add_action( 'plugins_loaded', array( $this, 'init' ), 5 );
		add_action( 'admin_init', array( $this, 'maybe_flush_rewrite_rules' ) );
		add_action( 'admin_notices', array( $this, 'maybe_woocommerce_notice' ) );
		if ( ! $this->dependencies_ok ) {
			add_action( 'admin_notices', array( $this, 'required_plugin_dependencies_notice' ), 5 );
		}
	}

	/**
	 * @return bool
	 */
	private function are_required_plugins_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( array_keys( self::REQUIRED_PLUGINS ) as $basename ) {
			if ( ! is_plugin_active( $basename ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * اعلان ادمین در صورت نبود یکی از افزونه‌های الزامی.
	 */
	public function required_plugin_dependencies_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( $this->dependencies_ok ) {
			return;
		}
		$missing = array();
		if ( function_exists( 'is_plugin_active' ) ) {
			foreach ( self::REQUIRED_PLUGINS as $basename => $label ) {
				if ( ! is_plugin_active( $basename ) ) {
					$missing[] = $label . ' <code>' . esc_html( $basename ) . '</code>';
				}
			}
		}
		if ( empty( $missing ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'افزونهٔ Rasam Server Configurator بدون افزونه‌های زیر به‌درستی کار نمی‌کند؛ لطفاً هر سه را نصب و فعال کنید:', 'rasam-server-config' );
		echo '</p><ul style="list-style:disc;padding-inline-start:1.5em;">';
		foreach ( $missing as $line ) {
			echo '<li>' . wp_kses( $line, array( 'code' => array() ) ) . '</li>';
		}
		echo '</ul></div>';
	}

	private function load_dependencies() {
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-post-types.php';
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-acf.php';
		/**
		 * ثبت مسیر JSON بلافاصله بعد از require (قبل از init وردپرس) تا ACF هنگام اسکن،
		 * پوشهٔ acf-json افزونه را از دست ندهد؛ در غیر این صورت ستون «Local JSON» خالی می‌ماند.
		 */
		RSC_ACF::instance();
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-component-data.php';
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-product-allowlist-acf.php';
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-variation-data.php';
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-display-text.php';
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-admin-filters.php';
		require_once RSC_PLUGIN_DIR . 'includes/engine/class-rsc-price-engine.php';
		require_once RSC_PLUGIN_DIR . 'includes/class-rsc-frontend-vartable.php';
	}

	public function init() {
		if ( ! $this->dependencies_ok ) {
			return;
		}
		RSC_Post_Types::instance();
		RSC_Product_Allowlist_ACF::instance();
		if ( class_exists( 'WooCommerce', false ) ) {
			RSC_Frontend_Vartable::instance();
			RSC_Admin_Filters::instance();
		}
	}

	public function maybe_flush_rewrite_rules() {
		if ( get_option( 'rsc_flush_rewrite_rules' ) ) {
			flush_rewrite_rules();
			delete_option( 'rsc_flush_rewrite_rules' );
		}
	}

	public function maybe_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Rasam Server Configurator نیاز به فعال بودن WooCommerce دارد تا یکپارچگی سبد و قیمت‌گذاری کامل شود.', 'rasam-server-config' );
		echo '</p></div>';
	}
}
