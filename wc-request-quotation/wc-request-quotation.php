<?php
/**
 * Plugin Name: WooCommerce Request Quotation - رسام سرور
 * Plugin URI: https://rasamserver.com
 * Description: درخواست پیش‌فاکتور از صفحه محصول، تولید PDF فارسی، مدیریت تماس در ادمین. نسخه 2.4.4: رفع قیمت محصول ساده (variation_id=0 دیگر truthy نیست).
 * Version: 2.4.4
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Amir heydaripour
 * Author URI: https://t.me/amir_drwp
 * Text Domain: wc-request-quotation
 * Domain Path: /languages
 *
 * @package WC_Request_Quotation
 * @author  Amir heydaripour
 */

if (!defined('ABSPATH'))
    exit;

if (!defined('WC_RQ_PLUGIN_FILE')) {
    define('WC_RQ_PLUGIN_FILE', __FILE__);
}

/** مدت نگهداری فایل PDF روی هاست (روز) — پیش‌فرض: ۶۰ روز (دو ماه). */
if (!defined('WC_RQ_PDF_RETENTION_DAYS')) {
    define('WC_RQ_PDF_RETENTION_DAYS', 60);
}

/** لینک دانشنامه / مستندات (README در مخزن GitHub). */
if (!defined('WC_RQ_DOCS_URL')) {
    define('WC_RQ_DOCS_URL', 'https://github.com/AmirheydaripourOdin1133/plugins-document/tree/main/wc-request-quotation');
}

require_once plugin_dir_path(__FILE__) . 'includes/wc-rq-loader.php';

$wc_rq_support_file = plugin_dir_path(__FILE__) . 'includes/class-wc-rq-support.php';
wc_rq_load_include_file($wc_rq_support_file);

function convert_to_persian_numerals($string)
{
    $persian_digits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english_digits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english_digits, $persian_digits, $string);
}

function format_number_with_implode($number)
{
    // Ø§Ú¯Ø± ÙˆØ±ÙˆØ¯ÛŒ Ø®Ø§Ù„ÛŒ Ø§Ø³ØªØŒ ØµÙØ± Ø¨Ø±Ú¯Ø±Ø¯Ø§Ù†
    if (empty($number)) {
        return '0';
    }

    // Û±. Ø¹Ø¯Ø¯ Ø±Ø§ Ø¨Ù‡ Ø±Ø´ØªÙ‡ ØªØ¨Ø¯ÛŒÙ„ Ú©Ø±Ø¯Ù‡ Ùˆ Ø¢Ù† Ø±Ø§ Ù…Ø¹Ú©ÙˆØ³ Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ…
    // Ù…Ø«Ø§Ù„: 1400000 -> "0000041"
    $reversed_number_str = strrev((string) $number);

    // Û². Ø±Ø´ØªÙ‡ Ù…Ø¹Ú©ÙˆØ³ Ø´Ø¯Ù‡ Ø±Ø§ Ø¨Ù‡ ØªÚ©Ù‡â€ŒÙ‡Ø§ÛŒ Û³ Ú©Ø§Ø±Ø§Ú©ØªØ±ÛŒ ØªÙ‚Ø³ÛŒÙ… Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ…
    // Ù…Ø«Ø§Ù„: "0000041" -> ["000", "004", "1"]
    $chunks = str_split($reversed_number_str, 3);

    // Û³. ØªÚ©Ù‡â€ŒÙ‡Ø§ Ø±Ø§ Ø¨Ø§ Ø§Ø³ØªÙØ§Ø¯Ù‡ Ø§Ø² implode Ùˆ Ú©Ø§Ø±Ø§Ú©ØªØ± ÙˆÛŒØ±Ú¯ÙˆÙ„ Ø¨Ù‡ Ù‡Ù… Ù…ÛŒâ€ŒÚ†Ø³Ø¨Ø§Ù†ÛŒÙ…
    // Ù…Ø«Ø§Ù„: ["000", "004", "1"] -> "000,004,1"
    $formatted_reversed = implode(',', $chunks);

    // Û´. Ù†ØªÛŒØ¬Ù‡ Ù†Ù‡Ø§ÛŒÛŒ Ø±Ø§ Ø¯ÙˆØ¨Ø§Ø±Ù‡ Ù…Ø¹Ú©ÙˆØ³ Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ… ØªØ§ Ø¨Ù‡ ÙØ±Ù…Øª ØµØ­ÛŒØ­ Ø¨Ø±Ú¯Ø±Ø¯Ø¯
    // Ù…Ø«Ø§Ù„: "000,004,1" -> "1,400,000"
    return strrev($formatted_reversed);
}

class WC_Request_Quotation
{
    public function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'render_request_button'], 20);
        add_action('woocommerce_single_variation', [$this, 'render_request_button'], 25);
        add_action('wp_footer', [$this, 'maybe_render_popup_html']);

        add_filter('plugin_row_meta', [$this, 'add_plugin_row_meta'], 10, 2);

        add_action('wp_ajax_wc_rq_submit_form', [$this, 'handle_form']);
        add_action('wp_ajax_nopriv_wc_rq_submit_form', [$this, 'handle_form']);
        add_action('admin_menu', [$this, 'add_settings_page']);

        add_action('add_meta_boxes', [$this, 'add_pdf_metabox']);
        add_action('add_meta_boxes', [$this, 'add_quotation_details_metabox']);
        add_action('admin_init', [$this, 'handle_pdf_actions']);
        add_action('before_delete_post', [$this, 'delete_pdf_with_post']);

        add_action('init', [$this, 'maybe_schedule_pdf_cleanup']);
        add_action('wc_rq_cleanup_old_pdfs', [$this, 'cleanup_old_pdfs']);

        if ( class_exists( 'WC_RQ_Support' ) ) {
            new WC_RQ_Support();
        }

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_support_assets' ), 5 );
    }

    /**
     * اطمینان از لود CSS/JS ادمین (مسیر صحیح assets در ریشهٔ افزونه).
     */
    public function enqueue_admin_support_assets( $hook ) {
        if ( 'edit.php' !== $hook && 'post.php' !== $hook ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'quotation_request' !== $screen->post_type ) {
            return;
        }
        $ver = '2.4.4';
        wp_enqueue_style(
            'wc-rq-admin-support',
            plugin_dir_url( WC_RQ_PLUGIN_FILE ) . 'assets/admin-support.css',
            array(),
            $ver
        );
        if ( 'post.php' === $hook ) {
            wp_enqueue_script(
                'wc-rq-admin-support',
                plugin_dir_url( WC_RQ_PLUGIN_FILE ) . 'assets/admin-support.js',
                array( 'jquery' ),
                $ver,
                true
            );
        }
    }

    public function register_cpt()
    {
        register_post_type('quotation_request', [
            'labels' => [
                'name' => 'درخواست‌های پیش‌فاکتور',
                'singular_name' => 'درخواست پیش‌فاکتور'
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-media-document',
            'supports' => ['title'],
        ]);
    }
    public function add_pdf_metabox()
    {
        add_meta_box(
            'rq_pdf_actions',
            'مدیریت فایل PDF',
            [$this, 'render_pdf_metabox'],
            'quotation_request',
            'side',
            'high'
        );
    }

    /**
     * متاباکس «اطلاعات پیش‌فاکتور» در بدنهٔ صفحه ویرایش (هماهنگ با یکپارچگی قبلی).
     * افزونهٔ Rasam Server Config در صورت فعال بودن این جعبه را با نسخهٔ RTL جایگزین می‌کند.
     */
    public function add_quotation_details_metabox()
    {
        add_meta_box(
            'rq_meta_box',
            'اطلاعات پیش‌فاکتور',
            [$this, 'render_quotation_details_metabox'],
            'quotation_request',
            'normal',
            'default'
        );
    }

    /**
     * @param WP_Post $post پست درخواست پیش‌فاکتور.
     */
    public function render_quotation_details_metabox($post)
    {
        $name        = get_post_meta($post->ID, 'rq_name', true);
        $phone       = get_post_meta($post->ID, 'rq_phone', true);
        $national_id = get_post_meta($post->ID, 'rq_national_id', true);
        $company     = get_post_meta($post->ID, 'rq_company', true);
        $items       = get_post_meta($post->ID, 'rq_items', true);
        if (!is_array($items)) {
            $items = [];
        }

        if (class_exists('WC_RQ_Support')) {
            $needs_support = (int) get_post_meta($post->ID, WC_RQ_Support::META_NEEDS_SUPPORT, true);
            $called        = (int) get_post_meta($post->ID, WC_RQ_Support::META_SUPPORT_CALLED, true);
            $report        = get_post_meta($post->ID, WC_RQ_Support::META_SUPPORT_REPORT, true);

            if ($needs_support && !$called) {
                echo '<div class="rq-support-highlight"><strong>⚠ مشتری درخواست تماس کارشناس داده است.</strong></div>';
            }

            echo '<p><strong>نام و نام خانوادگی :</strong> ' . esc_html($name) . '</p>';
            echo '<p><strong>کد ملی / کد اقتصادی :</strong> ' . esc_html($national_id) . '</p>';
            echo '<p><strong>شرکت :</strong> ' . esc_html($company) . '</p>';
            echo '<p><strong>شماره تماس :</strong> <a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></p>';

            if ($needs_support) {
                echo '<p><strong>درخواست تماس :</strong> بله';
                if ($called) {
                    echo ' — <span style="color:#00a32a;">تماس گرفته شد</span>';
                } else {
                    echo ' — <span style="color:#d63638;font-weight:600;">در انتظار تماس</span>';
                }
                echo '</p>';
                if ($called && $report) {
                    echo '<p><strong>گزارش تماس :</strong><br>' . nl2br(esc_html($report)) . '</p>';
                }
            }
        } else {
            echo '<p><strong>نام و نام خانوادگی :</strong> ' . esc_html($name) . '</p>';
            echo '<p><strong>کد ملی / کد اقتصادی :</strong> ' . esc_html($national_id) . '</p>';
            echo '<p><strong>شرکت :</strong> ' . esc_html($company) . '</p>';
            echo '<p><strong>شماره تماس :</strong> <a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></p>';
        }

        echo '<hr><ul>';
        foreach ($items as $item) {
            echo '<li style="direction: ltr;">' . esc_html($item['name']) . ' | ' . number_format($item['total']) . ' تومان</li>';
        }
        echo '</ul>';
    }

    // For rendering the PDF metabox Admin
    public function render_pdf_metabox($post)
    {
        $file_path = get_post_meta($post->ID, 'rq_pdf_path', true);
        $file_exists = $file_path && file_exists($file_path);

        if ($file_exists) {
            $file_url = content_url(str_replace(WP_CONTENT_DIR, '', $file_path));
            echo '<p><a href="' . esc_url($file_url) . '" class="button button-primary" target="_blank">دانلود PDF</a></p>';
        } else {
            echo '<p style="color:red;">PDF موجود نیست.</p>';
            echo '<p class="description">فایل‌های قدیمی‌تر از ' . esc_html((string) $this->get_pdf_retention_days()) . ' روز به‌صورت خودکار از هاست حذف می‌شوند؛ درخواست در پنل باقی می‌ماند.</p>';
        }

        echo '<p class="description">پس از حذف خودکار یا دستی، با «ساخت مجدد PDF» می‌توانید فایل را دوباره بسازید.</p>';

        $regen_url = add_query_arg([
            'action' => 'regenerate_pdf',
            'post_id' => $post->ID,
        ], admin_url('admin.php'));

        $delete_url = add_query_arg([
            'action' => 'delete_pdf',
            'post_id' => $post->ID,
        ], admin_url('admin.php'));

        echo '<p><a href="' . esc_url($regen_url) . '" class="button">ساخت مجدد PDF</a></p>';
        echo '<p><a href="' . esc_url($delete_url) . '" class="button button-secondary" style="color:red;">حذف PDF</a></p>';
    }
    // for rendering the PDF metabox in admin
    public function handle_pdf_actions()
    {
        if (!current_user_can('edit_posts'))
            return;

        if (isset($_GET['action'], $_GET['post_id'])) {
            $post_id = intval($_GET['post_id']);

            if ($_GET['action'] === 'regenerate_pdf') {
                $this->generate_pdf_invoice($post_id);
                wp_redirect(admin_url("post.php?post=$post_id&action=edit&pdf_action=regenerated"));
                exit;
            }

            if ($_GET['action'] === 'delete_pdf') {
                $this->remove_pdf_file_for_post($post_id);
                wp_redirect(admin_url("post.php?post=$post_id&action=edit&pdf_action=deleted"));
                exit;
            }
        }
    }

    /**
     * روزهای نگهداری PDF (قابل فیلتر).
     *
     * @return int
     */
    public function get_pdf_retention_days()
    {
        $days = (int) apply_filters('wc_rq_pdf_retention_days', WC_RQ_PDF_RETENTION_DAYS);
        return max(1, $days);
    }

    /**
     * زمان‌بندی روزانهٔ پاک‌سازی PDF در صورت نبودن Cron.
     */
    public function maybe_schedule_pdf_cleanup()
    {
        if (wp_next_scheduled('wc_rq_cleanup_old_pdfs')) {
            return;
        }
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wc_rq_cleanup_old_pdfs');
    }

    /**
     * حذف فایل PDF یک درخواست (پست حذف نمی‌شود).
     *
     * @param int $post_id
     * @return bool آیا فایلی حذف شد.
     */
    public function remove_pdf_file_for_post($post_id)
    {
        $post_id = (int) $post_id;
        if ($post_id <= 0 || get_post_type($post_id) !== 'quotation_request') {
            return false;
        }

        $removed = false;
        $file = get_post_meta($post_id, 'rq_pdf_path', true);
        if ($file && file_exists($file)) {
            if (@unlink($file)) {
                $removed = true;
            }
        }

        $upload_dir = wp_upload_dir();
        $default_path = $upload_dir['basedir'] . '/quotations/quotation-' . $post_id . '.pdf';
        if (file_exists($default_path)) {
            if (@unlink($default_path)) {
                $removed = true;
            }
        }

        delete_post_meta($post_id, 'rq_pdf_path');
        return $removed;
    }

    /**
     * Cron: حذف PDFهای قدیمی‌تر از مدت نگهداری (فقط فایل؛ رکورد درخواست می‌ماند).
     */
    public function cleanup_old_pdfs()
    {
        $days = $this->get_pdf_retention_days();
        $cutoff_local = wp_date('Y-m-d H:i:s', strtotime('-' . $days . ' days', current_time('timestamp')));
        $cutoff_ts = strtotime('-' . $days . ' days', current_time('timestamp'));

        $post_ids = get_posts([
            'post_type' => 'quotation_request',
            'post_status' => 'any',
            'posts_per_page' => 200,
            'fields' => 'ids',
            'no_found_rows' => true,
            'date_query' => [
                [
                    'before' => $cutoff_local,
                    'column' => 'post_date',
                    'inclusive' => false,
                ],
            ],
            'meta_query' => [
                [
                    'key' => 'rq_pdf_path',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        foreach ($post_ids as $post_id) {
            $this->remove_pdf_file_for_post($post_id);
        }

        $this->cleanup_orphan_pdf_files($cutoff_ts);
    }

    /**
     * حذف فایل‌های PDF یتیم در uploads/quotations قدیمی‌تر از cutoff.
     *
     * @param int $cutoff_ts برچسب زمانی GMT.
     */
    private function cleanup_orphan_pdf_files($cutoff_ts)
    {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/quotations';
        if (!is_dir($pdf_dir)) {
            return;
        }

        $files = glob($pdf_dir . '/quotation-*.pdf');
        if (!is_array($files)) {
            return;
        }

        foreach ($files as $file) {
            if (!is_file($file) || filemtime($file) >= $cutoff_ts) {
                continue;
            }

            if (preg_match('/quotation-(\d+)\.pdf$/', basename($file), $matches)) {
                $post_id = (int) $matches[1];
                if ($post_id > 0 && get_post_type($post_id) === 'quotation_request') {
                    delete_post_meta($post_id, 'rq_pdf_path');
                }
            }

            @unlink($file);
        }
    }

    public function delete_pdf_with_post($post_id)
    {
        $post_type = get_post_type($post_id);
        if ($post_type !== 'quotation_request') {
            return;
        }

        $this->remove_pdf_file_for_post($post_id);
    }

    /**
     * فعال‌سازی: زمان‌بندی Cron پاک‌سازی PDF.
     */
    public static function activate()
    {
        if (!wp_next_scheduled('wc_rq_cleanup_old_pdfs')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'wc_rq_cleanup_old_pdfs');
        }
    }

    /**
     * غیرفعال‌سازی: لغو Cron.
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('wc_rq_cleanup_old_pdfs');
    }


    // for adding settings page in admin
    public function add_settings_page()
    {
        add_menu_page(
            'تنظیمات پیش‌فاکتور',
            'تنظیمات پیش‌فاکتور',
            'manage_options',
            'wc-rq-settings',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic',
            56
        );
    }

    // for rendering the settings page in admin
    public function render_settings_page()
    {
        if (isset($_POST['wc_rq_save_settings']) && check_admin_referer('wc_rq_settings_nonce')) {
            $data = [
                'store_address' => sanitize_text_field($_POST['store_address']),
                'store_phone' => sanitize_text_field($_POST['store_phone']),
                'post_code' => sanitize_text_field($_POST['post_code']),
                'bussi_code' => sanitize_text_field($_POST['bussi_code']),
                'store_url' => sanitize_text_field($_POST['store_url']),
                'txt_store_none' => sanitize_text_field($_POST['txt_store_none']),
				 'bank_num' => sanitize_text_field($_POST['bank_num']),
				 'bank_cart' => sanitize_text_field($_POST['bank_cart']),
				 'bank_name' => sanitize_text_field($_POST['bank_name']),
                'tax_percent' => floatval($_POST['tax_percent']),
                'support_checkbox_label' => sanitize_text_field(wp_unslash($_POST['support_checkbox_label'] ?? '')),
            ];
            update_option('wc_rq_settings', $data);
            echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
        }

        $settings = get_option('wc_rq_settings', [
            'store_address' => '',
            'store_phone' => '',
            'store_url' => '',
            'txt_store_none' => '',
            'post_code' => '',
            'bussi_code' => '',
			 'bank_num' => '',
			 'bank_name' => '',
			 'bank_cart' => '',
            'tax_percent' => 0,
            'support_checkbox_label' => class_exists( 'WC_RQ_Support' ) ? WC_RQ_Support::get_default_checkbox_label() : 'نیاز به راهنمایی و تماس کارشناس دارم',
        ]);

        ?>
        <div class="wrap">
            <h1>تنظیمات پیش‌فاکتور</h1>
            <form method="POST">
                <?php wp_nonce_field('wc_rq_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="store_address">آدرس فروشگاه</label></th>
                        <td><input name="store_address" type="text" id="store_address"
                                value="<?php echo esc_attr($settings['store_address']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="txt_store_none">متن توضیحات فاکتور</label></th>
                        <td><input name="txt_store_none" type="text" id="txt_store_none"
                                value="<?php echo esc_attr($settings['txt_store_none']); ?>" class="regular-text"></td>
                    </tr>
					<tr>
                        <th><label for="bank_num">شماره شبا</label></th>
                        <td><input name="bank_num" type="text" id="bank_num"
                                value="<?php echo esc_attr($settings['bank_num']); ?>" class="regular-text"></td>
                    </tr>
										<tr>
                        <th><label for="bank_cart">شماره کارت</label></th>
                        <td><input name="bank_cart" type="text" id="bank_cart"
                                value="<?php echo esc_attr($settings['bank_cart']); ?>" class="regular-text"></td>
                    </tr>
										<tr>
                        <th><label for="bank_name">اطلاعات و جزئیات حساب</label></th>
                        <td><input name="bank_name" type="text" id="bank_name"
                                value="<?php echo esc_attr($settings['bank_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="store_url">URL وب‌سایت</label></th>
                        <td><input name="store_url" type="text" id="store_url"
                                value="<?php echo esc_attr($settings['store_url']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="store_phone">شماره تماس فروشگاه</label></th>
                        <td><input name="store_phone" type="text" id="store_phone"
                                value="<?php echo esc_attr($settings['store_phone']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="post_code">کد پستی</label></th>
                        <td><input name="post_code" type="text" id="post_code"
                                value="<?php echo esc_attr($settings['post_code']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="bussi_code">کد اقتصادی</label></th>
                        <td><input name="bussi_code" type="text" id="bussi_code"
                                value="<?php echo esc_attr($settings['bussi_code']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="tax_percent">درصد مالیات</label></th>
                        <td><input name="tax_percent" type="number" id="tax_percent"
                                value="<?php echo esc_attr($settings['tax_percent']); ?>" step="0.01" min="0" max="100"> %</td>
                    </tr>
                    <tr>
                        <th><label for="support_checkbox_label">متن تیک «نیاز به تماس»</label></th>
                        <td>
                            <input name="support_checkbox_label" type="text" id="support_checkbox_label"
                                value="<?php echo esc_attr($settings['support_checkbox_label'] ?? ''); ?>" class="regular-text">
                            <p class="description">متن نمایشی کنار چک‌باکس در پاپ‌آپ درخواست پیش‌فاکتور.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('ذخیره تنظیمات', 'primary', 'wc_rq_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * آیا در فرانت باید CSS/JS و پاپ‌آپ پیش‌فاکتور لود شود؟ (فقط صفحهٔ تک‌محصول)
     *
     * @return bool
     */
    public function is_single_product_frontend()
    {
        if (is_admin()) {
            return false;
        }
        return function_exists('is_product') && is_product();
    }

    /**
     * ثبت handleهای CSS/JS فرانت (برای وابستگی افزونه‌های دیگر مثل rasam-server-config).
     */
    private function register_frontend_assets()
    {
        $base_url = plugin_dir_url(__FILE__);
        $ver = '2.4.4';

        if (!wp_style_is('wc-rq-style', 'registered')) {
            wp_register_style('wc-rq-style', $base_url . 'assets/style.css', [], $ver);
        }
        if (!wp_script_is('wc-rq-script', 'registered')) {
            wp_register_script('wc-rq-script', $base_url . 'assets/script.js', ['jquery'], $ver, true);
            wp_localize_script('wc-rq-script', 'wc_rq_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_rq_nonce'),
                'support_label' => class_exists('WC_RQ_Support') ? WC_RQ_Support::get_checkbox_label() : 'نیاز به راهنمایی و تماس کارشناس دارم',
            ]);
        }
    }

    /**
     * بارگذاری assetهای فرانت فقط در صفحهٔ تک‌محصول ووکامرس.
     */
    public function enqueue_assets()
    {
        if (!$this->is_single_product_frontend()) {
            return;
        }

        $this->register_frontend_assets();
        wp_enqueue_style('wc-rq-style');
        wp_enqueue_script('wc-rq-script');
    }

    /**
     * لینک «دانشنامه» در ردیف افزونه (صفحهٔ افزونه‌ها)، کنار نویسنده و نسخه.
     *
     * @param string[] $links
     * @param string   $file
     * @return string[]
     */
    public function add_plugin_row_meta($links, $file)
    {
        if (plugin_basename(WC_RQ_PLUGIN_FILE) !== $file) {
            return $links;
        }

        $links[] = '<a href="' . esc_url(WC_RQ_DOCS_URL) . '" target="_blank" rel="noopener noreferrer">'
            . esc_html__('دانشنامه', 'wc-request-quotation') . '</a>';

        return $links;
    }

    /**
     * HTML پاپ‌آپ فقط در صفحهٔ تک‌محصول.
     */
    public function maybe_render_popup_html()
    {
        if (!$this->is_single_product_frontend()) {
            return;
        }
        $this->render_popup_html();
    }
    public function render_request_button()
    {
        global $product;

        if (!$product || !$product->is_type(['simple', 'variable'])) {
            return;
        }

        $id = $product->get_id();
        $name = $product->get_name();
        $price = $product->get_price();

        echo '<button 
        type="button" 
        style="background: #5472d2 !important;"
        class="wc-rq-btn button alt"
        data-id="' . esc_attr($id) . '"
        data-name="' . esc_attr($name) . '"
        data-price="' . esc_attr($price) . '">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.186 2.753v3.596c0 .487.194.955.54 1.3a1.85 1.85 0 0 0 1.306.539h4.125" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M20.25 8.568v8.568a4.25 4.25 0 0 1-1.362 2.97 4.28 4.28 0 0 1-3.072 1.14h-7.59a4.3 4.3 0 0 1-3.1-1.124 4.26 4.26 0 0 1-1.376-2.986V6.862a4.25 4.25 0 0 1 1.362-2.97 4.28 4.28 0 0 1 3.072-1.14h5.714a3.5 3.5 0 0 1 2.361.905l2.96 2.722a2.97 2.97 0 0 1 1.031 2.189" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17.273v-6.774" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="m8.894 14.42 2.665 2.666a.62.62 0 0 0 .882 0l2.665-2.665" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
         درخواست پیش‌فاکتور
        </button>';
    }



// for rendering the popup HTML
public function render_popup_html()
{
    ?>
    <div id="wc-rq-popup-overlay">
        <div id="wc-rq-popup">
            <h3>درخواست پیش‌فاکتور</h3>
            <input type="hidden" id="wc-rq-product-id">
            <input type="hidden" id="wc-rq-product-name">
            <input type="hidden" id="wc-rq-product-price">
            <input type="hidden" id="wc-rq-is-variable" value="0">
            <input type="hidden" id="wc-rq-price-from-vartable" value="0">

            <!-- honeypot anti-spam field (for bots) -->
            <input type="text"
                   name="website"
                   id="wc-rq-website"
                   autocomplete="off"
                   tabindex="-1"
                   style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;">

            <div class="divGrids">
                <p id="noticFormsMy"></p>
                <label>نام و نام خانوادگی :
                    <input type="text" id="wc-rq-name" placeholder="مثلاً محمد احمدی" required>
                </label>
                <label>شماره تماس :
                    <input type="text" id="wc-rq-phone" placeholder="0912XXXXXXX" required>
                </label>
                <label>نام شرکت :
                    <input type="text" id="wc-rq-company" placeholder="اختیاری">
                </label>
                <label>کد ملی / کد اقتصادی :
                    <input type="text" id="wc-rq-national-id" placeholder="ضروری">
                </label>
                <label class="wc-rq-support-field" for="wc-rq-needs-support">
                    <input type="checkbox" id="wc-rq-needs-support" value="1">
                    <span id="wc-rq-support-label"></span>
                </label>
            </div>
            <div class="LastRow">
                <p>تعداد مورد نظر خود را وارد کنید . <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.244 17.738a7.494 7.494 0 1 0 0-14.988 7.494 7.494 0 0 0 0 14.988m5.318-2.176 5.688 5.688M10.244 6.245v7.998m3.999-3.999H6.245"
                            stroke="#37953a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg></p>
                <div class="wc-rq-qty-wrapper">
                    <button type="button" id="wc-rq-qty-minus">-</button>
                    <input type="number" id="wc-rq-qty" value="1" min="1">
                    <button type="button" id="wc-rq-qty-plus">+</button>
                </div>
            </div>

            <div class="actions">
                <button id="wc-rq-submit"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 15.238V3.213" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" />
                        <path d="m7.375 10.994 3.966 3.966a.937.937 0 0 0 1.318 0l3.966-3.966" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M2.75 13.85v4.625a2.313 2.313 0 0 0 2.313 2.313h13.874a2.313 2.313 0 0 0 2.313-2.313V13.85"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg> ثبت درخواست و دریافت پیش‌فاکتور </button>
                <button id="wc-rq-cancel">انصراف</button>
            </div>
        </div>
    </div>
    <?php
}



    // for getting the PDF URL
    private function get_pdf_url($post_id)
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . "/quotations/quotation-{$post_id}.pdf";
    }

// for handling the form submission
public function handle_form()
{
    // Ø¨Ø±Ø§ÛŒ Ø§Ø¯Ù…ÛŒÙ†â€ŒÙ‡Ø§ Ùˆ Ú©Ø§Ø±Ø¨Ø±Ø§Ù† Ù„Ø§Ú¯ÛŒÙ†ØŒ Ù†Ø§Ù†Ø³ Ú©Ø§Ù…Ù„ Ú†Ú© Ø´ÙˆØ¯
    if ( is_user_logged_in() ) {
        check_ajax_referer('wc_rq_nonce', 'nonce');
    } else {
        // Ø¨Ø±Ø§ÛŒ Ù…Ù‡Ù…Ø§Ù†â€ŒÙ‡Ø§ØŒ Ø¨Ù‡ Ø®Ø§Ø·Ø± Ù…Ø´Ú©Ù„Ø§Øª Ú©Ø´ (Ù…Ø«Ù„Ø§Ù‹ WP Rocket) ÙØ¹Ù„Ø§Ù‹ Ø§Ø² Ù†Ø§Ù†Ø³ Ø¹Ø¨ÙˆØ± Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ…
        // Ø§Ú¯Ø± Ø¨Ø¹Ø¯Ø§Ù‹ Ú©Ø´ Ø±Ø§ Ø¨Ø±Ø§ÛŒ Ø§ÛŒÙ† ØµÙØ­Ø§Øª ØºÛŒØ±ÙØ¹Ø§Ù„ Ú©Ø±Ø¯ÛŒØŒ Ù…ÛŒâ€ŒØªÙˆÙ†ÛŒ Ø§ÛŒÙ†Ø¬Ø§ Ø±Ùˆ Ø³Ø®Øªâ€ŒÚ¯ÛŒØ±ØªØ± Ú©Ù†ÛŒ
        if ( isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'wc_rq_nonce') === false ) {
            // Ø§Ú¯Ø± Ø®ÛŒÙ„ÛŒ Ø®ÙˆØ§Ø³ØªÛŒ Ø³Ø®Øªâ€ŒÚ¯ÛŒØ±ÛŒ Ú©Ù†ÛŒØŒ Ù…ÛŒâ€ŒØªÙˆÙ†ÛŒ Ø§ÛŒÙ†Ø¬Ø§ Ø®Ø·Ø§ Ø¨Ø±Ú¯Ø±Ø¯ÙˆÙ†ÛŒ
            // wp_send_json_error(['message' => 'Ø®Ø·Ø§ÛŒ Ø§Ù…Ù†ÛŒØªÛŒØŒ ØµÙØ­Ù‡ Ø±Ø§ Ø±ÙØ±Ø´ Ú©Ù†ÛŒØ¯.'], 403);
        }
    }

    // ðŸ honeypot â€“ Ù‡Ø±Ú©ÛŒ Ø§ÛŒÙ†Ùˆ Ù¾Ø± Ú©Ø±Ø¯Ù‡ Ø§Ø³Ù¾Ù…Ù‡
    if (!empty($_POST['website'])) {
        wp_send_json_error(['message' => 'درخواست مشکوک شناسایی شد.']);
    }

    $name = sanitize_text_field($_POST['name']);
    $phone = sanitize_text_field($_POST['phone']);
    $company = sanitize_text_field($_POST['company']);
    $national_id = sanitize_text_field($_POST['national_id']);
    $qty = max(1, intval($_POST['qty'])); // Ù¾ÛŒØ´â€ŒÙØ±Ø¶ Ø­Ø¯Ø§Ù‚Ù„ Û±
    $product_id = intval($_POST['product_id']);
    $product_name = sanitize_text_field($_POST['product_name']);
    $is_variable = !empty($_POST['is_variable']) && (int) $_POST['is_variable'] === 1;
    $needs_support = !empty($_POST['needs_support']);
    $product_price = floatval(preg_replace('/[^\d.]/', '', (string) wp_unslash($_POST['product_price'] ?? '')));

    $price_from_vartable = !empty($_POST['price_from_vartable']);
    if (
        $price_from_vartable
        && $product_price > 1000
        && fmod($product_price, 10) === 0.0
    ) {
        $product_price = $product_price / 10;
    }

    if (!$name || !$phone || !$product_id || !$product_name || $product_price <= 0) {
        wp_send_json_error(['message' => 'اطلاعات ناقص است']);
    }

    $items = [
        [
            'id' => $product_id,
            'name' => $product_name,
            'qty' => $qty,
            'price' => $product_price,
            'total' => $product_price * $qty
        ]
    ];

    $post_id = wp_insert_post([
        'post_type' => 'quotation_request',
        'post_title' => 'پیش‌فاکتور ' . $name,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($post_id) || !$post_id) {
        wp_send_json_error(['message' => 'خطا در ثبت درخواست. لطفاً دوباره تلاش کنید.']);
    }

    update_post_meta($post_id, 'rq_name', $name);
    update_post_meta($post_id, 'rq_phone', $phone);
    update_post_meta($post_id, 'rq_company', $company);
    update_post_meta($post_id, 'rq_national_id', $national_id);
    update_post_meta($post_id, 'rq_items', $items);
    update_post_meta($post_id, 'rq_is_variable', $is_variable ? 1 : 0);
    if ( class_exists( 'WC_RQ_Support' ) ) {
        update_post_meta($post_id, WC_RQ_Support::META_NEEDS_SUPPORT, $needs_support ? 1 : 0);
        if ($needs_support) {
            update_post_meta($post_id, WC_RQ_Support::META_SUPPORT_SEEN, 0);
            update_post_meta($post_id, WC_RQ_Support::META_SUPPORT_CALLED, 0);
        }
    }

    try {
        $this->generate_pdf_invoice($post_id);
    } catch (Throwable $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WC Request Quotation PDF: ' . $e->getMessage());
        }
        wp_send_json_error(['message' => 'درخواست ثبت شد اما ساخت PDF با خطا مواجه شد. با پشتیبانی تماس بگیرید.']);
    }

    $pdf_path = get_post_meta($post_id, 'rq_pdf_path', true);
    if (!$pdf_path || !file_exists($pdf_path)) {
        wp_send_json_error(['message' => 'فایل PDF ساخته نشد. لطفاً دوباره تلاش کنید.']);
    }

    $pdf_url = $this->get_pdf_url($post_id);
    wp_send_json_success([
        'message' => 'درخواست ثبت شد',
        'pdf_url' => $pdf_url
    ]);
}



    // Ù‚Ø±Ø§Ø± Ø¨Ø¯Ù‡ Ø¯Ø§Ø®Ù„ Ú©Ù„Ø§Ø³ WC_Request_Quotation
    private function format_number_for_pdf($number)
    {
        $num = floatval($number);
        // Ø§Ú¯Ø± Ø§Ø¹Ø´Ø§Ø± ÙˆØ§Ù‚Ø¹ÛŒ Ø¯Ø§Ø±Ù‡ Ø¯Ùˆ Ø±Ù‚Ù… Ø§Ø¹Ø´Ø§Ø±ØŒ Ø¯Ø± ØºÛŒØ± Ø§ÛŒÙ†ØµÙˆØ±Øª Ø¨Ø¯ÙˆÙ† Ø§Ø¹Ø´Ø§Ø±
        $decimals = (floor($num) != $num) ? 2 : 0;
        $formatted = number_format($num, $decimals, '.', ',');
        // Ø¬Ù„ÙˆÚ¯ÛŒØ±ÛŒ Ø§Ø² Ù…Ø¹Ú©ÙˆØ³â€ŒØ´Ø¯Ù† Ø¯Ø± Ù…Ø­ÛŒØ· RTL Ø¨Ø§ wrap LTR
        return '<span dir="ltr" style="display:inline-block; direction:ltr; unicode-bidi:embed;">' . esc_html($formatted) . '</span>';
    }





    // for Generating PDF Invoice 
    public function generate_pdf_invoice($post_id)
    {
        require_once plugin_dir_path(__FILE__) . 'lib/tcpdf/tcpdf.php';

        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/quotations';
        if (!file_exists($pdf_dir))
            wp_mkdir_p($pdf_dir);

        $post = get_post($post_id);
        $name = get_post_meta($post_id, 'rq_name', true);
        $phone = get_post_meta($post_id, 'rq_phone', true);
        $company = get_post_meta($post_id, 'rq_company', true);
        $national_id = get_post_meta($post_id, 'rq_national_id', true);
        $items = get_post_meta($post_id, 'rq_items', true);
        if (!is_array($items))
            $items = [];

        $show_config_col = (bool) get_post_meta($post_id, 'rq_is_variable', true);

        $store_title = get_bloginfo('name');
        $settings = get_option('wc_rq_settings', []);
        $store_address = $settings['store_address'] ?? '---';
        $store_phone = $settings['store_phone'] ?? '---';
        $store_url = $settings['store_url'] ?? '---';
		        $bank_num = $settings['bank_num'] ?? '---';
		$bank_cart = $settings['bank_cart'] ?? '---';
		$bank_name = $settings['bank_name'] ?? '---';
        $post_code = $settings['post_code'] ?? '---';
        $txt_store_none = $settings['txt_store_none'] ?? '---';
        $bussi_code = $settings['bussi_code'] ?? '---';
        $tax_percent = floatval($settings['tax_percent'] ?? 0);

        $logo_path = plugin_dir_path(__FILE__) . 'assets/img/rasamserver.com-logo.png';

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('WooCommerce');
        $pdf->SetAuthor($store_title);
        $pdf->SetTitle('پیش‌فاکتور');
        $pdf->SetRTL(true);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 10, 15, true);

        // ÙÙˆÙ†Øªâ€ŒÙ‡Ø§ (ÙØ±Ø¶ Ø§ÛŒÙ†Ú©Ù‡ Ù‚Ø¨Ù„Ø§Ù‹ Ø³Ø§Ø®ØªÙ‡ Ø´Ø¯Ù‡â€ŒØ§Ù†Ø¯)
        $font_path_regular = plugin_dir_path(__FILE__) . 'fonts/IRANYekanRegularFaNum.ttf';
        $font_path_bold = plugin_dir_path(__FILE__) . 'fonts/IRANYekanBoldFaNum.ttf';
        $font_path_en = plugin_dir_path(__FILE__) . 'fonts/IRANYekanEn.ttf';
        $font_regular = TCPDF_FONTS::addTTFfont($font_path_regular, 'TrueTypeUnicode', '', 32);
        $font_bold = TCPDF_FONTS::addTTFfont($font_path_bold, 'TrueTypeUnicode', '', 32);
        $font_en = TCPDF_FONTS::addTTFfont($font_path_en, 'TrueTypeUnicode', '', 32);

        $pdf->SetFont($font_regular, '', 12);
        $pdf->AddPage();

        // ØªØ§Ø±ÛŒØ® (Ø´Ù…Ø³ÛŒ Ø¯Ø± ØµÙˆØ±Øª Ù†ØµØ¨ Ù¾Ù„Ø§Ú¯ÛŒÙ† Ù…Ù†Ø§Ø³Ø¨Ø› Ø¨Ø±Ø§ÛŒ Ø­Ø§Ù„Ø§ Ø§Ø² date_i18n Ø§Ø³ØªÙØ§Ø¯Ù‡ Ø´Ø¯Ù‡)
        $date = date_i18n('Y/m/d');

        // Ù…Ø­Ø§Ø³Ø¨Ø§Øª
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += floatval($item['total']);
        }
        $tax_amount = round(($subtotal * $tax_percent) / 100, 2);
        $grand_total = round($subtotal + $tax_amount, 2);

        // helper Ù…Ø­Ù„ÛŒ: ÙØ±Ù…Øª Ú©Ø±Ø¯Ù† Ø¨Ø±Ø§ÛŒ PDF (LTR wrapper) â€” Ø§Ø² Ù…ØªØ¯ Ú©Ù„Ø§Ø³ Ø§Ø³ØªÙØ§Ø¯Ù‡ Ù…ÛŒâ€ŒÚ©Ù†ÛŒÙ…
        $fmt = function ($num) {
            return $this->format_number_for_pdf($num);
        };

        // Ø³Ø§Ø®Øª HTML (Ø¨Ø§ Ø§Ø³ØªÙØ§Ø¯Ù‡ Ø§Ø² fmt Ø¨Ø±Ø§ÛŒ Ø§Ø¹Ø¯Ø§Ø¯)
        $html = '<div dir="rtl" style="text-align: right; font-size:12px;">';

        // Ø³Ø±Ø¨Ø±Ú¯
        // ðŸ”¹ Ù„ÙˆÚ¯Ùˆ Ùˆ Ù…Ø´Ø®ØµØ§Øª ÙØ±ÙˆØ´Ú¯Ø§Ù‡
        $html .= '<table width="100%" cellpadding="5" cellspacing="2" style="margin-bottom:200px;vertical-align: middle;">
		 <tr><td style="text-align:center; font-size:11.5px; width:100%;vertical-align: middle;font-family: ' . $font_bold . ';">پیش فاکتور رسمی</td>
                </tr>
                    <tr>
                        <td style="text-align:center; font-size:11px; width:100%;vertical-align: middle;">';
        if (file_exists($logo_path)) {
            $logo_url = plugin_dir_url(__FILE__) . 'assets/img/rasamserver.com-logo.png';
            $html .= '<img src="' . $logo_url . '" style="height: 60px;">';
        }
        $html .= '</td></tr>
                <tr>
                    <td style="width:25%;text-align:right; font-size:11px;vertical-align: middle; direction: rtl;">' . esc_html($store_url) . '</td>
                    <td style="width:25%; text-align:right; font-size:11px;vertical-align: middle;">تلفن :' . esc_html($store_phone) . '</td>
                    <td style="width:25%; text-align:right; font-size:11px;vertical-align: middle;">کد پستی :' . esc_html($post_code) . '</td>
                    <td style="width:25%; text-align:right; font-size:11px;vertical-align: middle;">کد اقتصادی :' . esc_html($bussi_code) . '</td>
					
                </tr>
            <tr style="width:100%;margin-bottom:100px;vertical-align: middle;">
            
                <td  style="width:100%;font-size:11px;vertical-align: middle;">آدرس: ' . esc_html($store_address) . '</td>
            </tr>
			<tr>				<td style="text-align:right; font-size:11px;vertical-align: middle;width:35%;font-family:' . $font_en . ';">شماره شبا  :' . esc_html($bank_num) . '</td><td style=" text-align:right; font-size:12px;vertical-align: middle;">شماره کارت  :' . esc_html($bank_cart) . '</td><td style=" text-align:right; font-size:11px;vertical-align: middle;">' . esc_html($bank_name) . '</td></tr>
    </table><br><hr>';

        // Ø§Ø·Ù„Ø§Ø¹Ø§Øª Ù…Ø´ØªØ±ÛŒ / ÙØ§Ú©ØªÙˆØ±
        $html .= '
                <table cellpadding="12" cellspacing="4" border="0" style="width:100%; text-align:center;">
                    <tr>
                        <td style="width:15%; font-size:11px;font-family:' . $font_bold . ';"> تاریخ :' . $date . '</td>
                        <td style="width:20%; font-size:11px;font-family:' . $font_bold . ';"> ' . esc_html($name) . '</td>
                        <td style="width:28%; font-size:11px;font-family:' . $font_bold . ';">کد ملی / کد اقتصادی: ' . esc_html($national_id) . '</td>
                        <td style="width:15%; font-size:11px;font-family:' . $font_bold . ';">تلفن: ' . esc_html($phone) . '</td>
                        <td style="width:22%; font-size:11px;font-family:' . $font_bold . ';"> ' . ($company ? 'شرکت: ' . esc_html($company) : '') . '</td>
                                
                    </tr>

                </table><br>';


        // Product table
        $name_col_w = $show_config_col ? '25%' : '65%';
        $html .= '<br><table border="1" cellpadding="6" cellspacing="1" style="font-size:10px;width:100%; border-collapse:collapse;margin-bottom:15px;">';
        $html .= '<thead><tr style="background:#f5f5f5;">';
        $html .= '<th style="width:' . $name_col_w . '">نام محصول</th>';
        if ($show_config_col) {
            $html .= '<th style="width:40%">کانفیگ</th>';
        }
        $html .= '<th style="width:5%">تعداد</th><th style="width:15%">قیمت واحد</th><th style="width:15%">جمع کل</th>';
        $html .= '</tr></thead><tbody>';

        $wrap_latin = function ($str) use ($font_en) {
            $safe = esc_html($str);
            return preg_replace_callback(
                '/[A-Za-z0-9][A-Za-z0-9\s\-\+\/\.\,]*/u',
                function ($m) use ($font_en) {
                    return '<span dir="ltr" style="font-family:' . $font_en . ';">' . $m[0] . '</span>';
                },
                $safe
            );
        };

        foreach ($items as $item) {
            if ($show_config_col) {
                $name_parts    = explode('(', $item['name'], 2);
                $product_title = trim($name_parts[0]);
                $config        = isset($name_parts[1]) ? rtrim(trim($name_parts[1]), ')') : '';
            } else {
                $product_title = $item['name'];
                $config        = '';
            }

            $product_title_fixed = $wrap_latin($product_title);
            $config_display = $config
                ? '<bdo dir="ltr"><span style="font-family:' . $font_en . ';">' . esc_html($config) . '</span></bdo>'
                : '-';

            $html .= '<tr>';
            $html .= '<td style="width:' . $name_col_w . ';direction:rtl;text-align:right;font-size:10px;">' . $product_title_fixed . '</td>';
            if ($show_config_col) {
                $html .= '<td style="width:40%;direction:ltr;text-align:right;font-size:10px;">' . $config_display . '</td>';
            }
            $html .= '<td style="width:5%;font-size:9px;text-align:center;">' . intval($item['qty']) . '</td>';
            $html .= '<td style="width:15%;font-size:10px;text-align:right;">' . $fmt($item['price']) . ' تومان</td>';
            $html .= '<td style="width:15%;font-size:10px;text-align:right;">' . $fmt($item['total']) . ' تومان</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table><br><br>';


        // Ù…Ø­Ø§Ø³Ø¨Ù‡ Ù†Ù‡Ø§ÛŒÛŒ (Ø¬Ù…Ø¹ØŒ Ù…Ø§Ù„ÛŒØ§Øª Ùˆ Ú©Ù„)
        $html .= '<br><table cellpadding="6" cellspacing="2" style="width:40%; text-align:right; border:1px solid #eee;margin-top:200px;">';
        $html .= '<tr style="font-size:10px;"><td>جمع کل بدون مالیات</td><td>' . $fmt($subtotal) . ' تومان</td></tr>';
        $html .= '<tr style="font-size:10px;"><td>مالیات (' . esc_html($tax_percent) . '%)</td><td>' . $fmt($tax_amount) . ' تومان</td></tr>';
        $html .= '<tr style="font-weight:bold;"><td>مبلغ نهایی</td><td>' . $fmt($grand_total) . ' تومان</td></tr>';
        $html .= '</table>';

        // ÙÙˆØªØ± ØªÙˆØ¶ÛŒØ­Ø§Øª
        $html .= '<br><br><div style="margin-top:180px; text-align:center;font-size:10px;font-family:' . $font_bold . ';">' . esc_html($txt_store_none) . '</div>';

        $html .= '</div>'; // end container

        $pdf->writeHTML($html, true, false, true, false, '');

        $file_path = $pdf_dir . "/quotation-{$post_id}.pdf";
        $pdf->Output($file_path, 'F');
        update_post_meta($post_id, 'rq_pdf_path', $file_path);
    }



}

register_activation_hook(WC_RQ_PLUGIN_FILE, ['WC_Request_Quotation', 'activate']);
register_deactivation_hook(WC_RQ_PLUGIN_FILE, ['WC_Request_Quotation', 'deactivate']);

new WC_Request_Quotation;

// Add Fild Admin Panel
add_filter('manage_quotation_request_posts_columns', function ($columns) {
    return [
        'cb' => $columns['cb'],
        'title' => 'عنوان',
        'rq_name' => 'نام مشتری',
        'rq_phone' => 'شماره تماس',
        'rq_total' => 'مبلغ کل',
        'date' => 'تاریخ'
    ];
});


// Add custom columns to the quotation request list in admin

add_action('manage_quotation_request_posts_custom_column', function ($column, $post_id) {
    if ($column === 'rq_name') {
        echo esc_html(get_post_meta($post_id, 'rq_name', true));
    } elseif ($column === 'rq_phone') {
        echo esc_html(get_post_meta($post_id, 'rq_phone', true));
    } elseif ($column === 'rq_company') {
        echo esc_html(get_post_meta($post_id, 'rq_company', true));
    } elseif ($column === 'rq_national_id') {
        echo esc_html(get_post_meta($post_id, 'rq_national_id', true));
    } elseif ($column === 'rq_total') {
        $items = get_post_meta($post_id, 'rq_items', true);
        $total = array_sum(array_column($items, 'total'));
        echo number_format($total) . ' تومان';
    }
}, 10, 2);

// add download and regenerate PDF links to the post row actions in admin

add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type === 'quotation_request') {
        $url = get_post_meta($post->ID, 'rq_pdf_path', true);
        if ($url && file_exists($url)) {
            $download_url = content_url(str_replace(WP_CONTENT_DIR, '', $url));
            $actions['download_pdf'] = '<a href="' . esc_url($download_url) . '" target="_blank">دانلود PDF</a>';
        }

        $regenerate_url = add_query_arg([
            'action' => 'regenerate_pdf',
            'post_id' => $post->ID,
        ], admin_url('admin.php'));

        $actions['regenerate_pdf'] = '<a href="' . esc_url($regenerate_url) . '">ساخت مجدد PDF</a>';
    }
    return $actions;
}, 10, 2);



add_action('admin_init', function () {
    if (isset($_GET['action'], $_GET['post_id']) && $_GET['action'] === 'regenerate_pdf') {
        $post_id = intval($_GET['post_id']);
        if (current_user_can('edit_post', $post_id)) {
            (new WC_Request_Quotation())->generate_pdf_invoice($post_id);
            wp_redirect(admin_url('edit.php?post_type=quotation_request&regenerated=1'));
            exit;
        }
    }
});


// add admin notice for PDF regeneration success

add_action('admin_notices', function () {
    if (isset($_GET['regenerated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>PDF با موفقیت ساخته شد.</p></div>';
    }
});

add_action('admin_notices', function () {
    if (!isset($_GET['post'], $_GET['pdf_action']))
        return;

    $msg = '';
    if ($_GET['pdf_action'] === 'regenerated') {
        $msg = 'فایل PDF با موفقیت ساخته شد.';
    } elseif ($_GET['pdf_action'] === 'deleted') {
        $msg = 'فایل PDF با موفقیت حذف شد.';
    }

    if ($msg) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
    }
});
