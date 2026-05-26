<?php
/**
 * Main plugin bootstrap.
 *
 * @package AppleWarranty
 */

namespace AppleWarranty;

use AppleWarranty\Admin\PluginMetaLinks;
use AppleWarranty\Admin\Menu;
use AppleWarranty\Ajax\CaptchaHandler;
use AppleWarranty\Ajax\LookupHandler;
use AppleWarranty\Database\Installer;
use AppleWarranty\Frontend\Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 */
final class Plugin {

	/**
	 * Instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get singleton.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		load_plugin_textdomain(
			'apple-warranty-inquiry',
			false,
			dirname( plugin_basename( APPLE_WARRANTY_PLUGIN_FILE ) ) . '/languages'
		);

		Installer::maybe_upgrade();

		( new PluginMetaLinks() )->register();
		( new Menu() )->register();
		( new Shortcode() )->register();
		( new LookupHandler() )->register();
		( new CaptchaHandler() )->register();

		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );
	}

	/**
	 * Register frontend assets (enqueued by shortcode).
	 */
	public function register_frontend_assets(): void {
		wp_register_style(
			'apple-warranty-frontend',
			APPLE_WARRANTY_PLUGIN_URL . 'assets/css/frontend.min.css',
			array(),
			APPLE_WARRANTY_VERSION
		);

		wp_register_script(
			'apple-warranty-frontend',
			APPLE_WARRANTY_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			APPLE_WARRANTY_VERSION,
			true
		);

		wp_localize_script(
			'apple-warranty-frontend',
			'appleWarranty',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apple_warranty_frontend' ),
				'i18n'    => array(
					'checking'     => __( 'در حال بررسی…', 'apple-warranty-inquiry' ),
					'checkId'      => __( 'بررسی شناسه', 'apple-warranty-inquiry' ),
					'serialRequired' => __( 'لطفاً شماره سریال را وارد کنید.', 'apple-warranty-inquiry' ),
					'captchaRequired' => __( 'لطفاً کد امنیتی را وارد کنید.', 'apple-warranty-inquiry' ),
					'genericError' => __( 'خطایی رخ داد. لطفاً دوباره تلاش کنید.', 'apple-warranty-inquiry' ),
					'notFound'     => __( 'شناسه‌ای با این شماره سریال یافت نشد.', 'apple-warranty-inquiry' ),
					'rateLimited'  => __( 'تعداد درخواست‌ها بیش از حد مجاز است. لطفاً چند دقیقه بعد تلاش کنید.', 'apple-warranty-inquiry' ),
				),
			)
		);
	}
}
