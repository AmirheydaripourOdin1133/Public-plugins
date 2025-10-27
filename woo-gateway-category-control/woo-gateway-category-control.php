<?php
/*
Plugin Name: Woo Gateway Category Control
Description: کنترل درگاه‌های پرداخت ووکامرس بر اساس دسته‌بندی محصولات - نسخه نهایی
Version: 1.2
Author: Amir Heydaripour , Amir.h.heydaripour22@gmail.com 
*/

if (!defined('ABSPATH')) exit;

class Woo_Gateway_Category_Control {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_gateways_by_category']);
        add_action('woocommerce_checkout_process', [$this, 'prevent_checkout_if_restricted']);
		add_action('woocommerce_before_checkout_form', [$this, 'render_checkout_restriction_notice']);

    }

    /** -------------------------------
     *  افزودن صفحه به منوی اصلی پیشخوان
     * ------------------------------- */
    public function add_admin_menu() {
        add_menu_page(
            'کنترل درگاه پرداخت',
            'کنترل درگاه پرداخت',
            'manage_options',
            'woo-gateway-category-control',
            [$this, 'settings_page'],
            'dashicons-filter',
            56
        );
    }

    /** -------------------------------
     *  ثبت تنظیمات
     * ------------------------------- */
    public function register_settings() {
        register_setting('wgcc_settings_group', 'wgcc_mode', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'disable_selected',
        ]);

        register_setting('wgcc_settings_group', 'wgcc_selected_cats', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_array'],
            'default' => [],
        ]);

        register_setting('wgcc_settings_group', 'wgcc_selected_gateways', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_array'],
            'default' => [],
        ]);

        register_setting('wgcc_settings_group', 'wgcc_restricted_message', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'محصولاتی در سبد شما وجود دارند که امکان پرداخت آنلاین برای آن‌ها وجود ندارد. لطفاً آن‌ها را حذف کنید.',
        ]);
    }

    public function sanitize_array($input) {
        return array_map('sanitize_text_field', (array) $input);
    }

    /** -------------------------------
     *  لود استایل و اسکریپت ادمین (برای Select2)
     * ------------------------------- */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_woo-gateway-category-control') return;
        wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], null, true);
        wp_add_inline_script('select2', 'jQuery(document).ready(function($){$(".wgcc-select").select2({width:"100%"});});');
    }

    /** -------------------------------
     *  صفحه تنظیمات
     * ------------------------------- */
    public function settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('wgcc_messages', 'wgcc_message', 'تنظیمات با موفقیت ذخیره شد.', 'updated');
        }
        settings_errors('wgcc_messages');

        $mode = get_option('wgcc_mode', 'disable_selected');
        $selected_cats = (array) get_option('wgcc_selected_cats', []);
        $selected_gateways = (array) get_option('wgcc_selected_gateways', []);
        $restricted_message = get_option('wgcc_restricted_message', '');
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

        // فقط درگاه‌های فعال
        $gateways = [];
        foreach (WC()->payment_gateways()->payment_gateways() as $id => $gateway) {
            if ($gateway->enabled === 'yes') {
                $gateways[$id] = $gateway;
            }
        }
        ?>

        <div class="wrap">
            <h1>⚙️ کنترل درگاه‌های پرداخت ووکامرس</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wgcc_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">حالت عملکرد:</th>
                        <td>
                            <label><input type="radio" name="wgcc_mode" value="disable_all" <?php checked($mode, 'disable_all'); ?>> غیرفعال کردن همه درگاه‌ها مگر برای دسته‌های انتخاب‌شده</label><br>
                            <label><input type="radio" name="wgcc_mode" value="disable_selected" <?php checked($mode, 'disable_selected'); ?>> فعال نگه داشتن همه درگاه‌ها به‌جز دسته‌های انتخاب‌شده</label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">دسته‌بندی محصولات:</th>
                        <td>
                            <select class="wgcc-select" name="wgcc_selected_cats[]" multiple>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, $selected_cats)); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">برای جستجو و انتخاب چند دسته از جعبه بالا استفاده کنید.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">درگاه‌های پرداخت فعال:</th>
                        <td>
                            <select class="wgcc-select" name="wgcc_selected_gateways[]" multiple>
                                <?php foreach ($gateways as $gateway_id => $gateway): ?>
                                    <option value="<?php echo esc_attr($gateway_id); ?>" <?php selected(in_array($gateway_id, $selected_gateways)); ?>>
                                        <?php echo esc_html($gateway->get_title()); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">تنها درگاه‌های فعال در سایت نمایش داده می‌شوند.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">پیغام محدودیت درگاه:</th>
                        <td>
                            <input type="text" name="wgcc_restricted_message" value="<?php echo esc_attr($restricted_message); ?>" class="regular-text" style="width:100%;">
                            <p class="description">این پیام زمانی نمایش داده می‌شود که محصولی با محدودیت درگاه در سبد خرید وجود داشته باشد.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('💾 ذخیره تنظیمات'); ?>
            </form>
        </div>

        <?php
    }

    /** -------------------------------
     *  فیلتر اصلی ووکامرس
     * ------------------------------- */
    public function filter_gateways_by_category($available_gateways) {
        if (!is_checkout()) return $available_gateways;

        $mode = get_option('wgcc_mode', 'disable_selected');
        $selected_cats = (array) get_option('wgcc_selected_cats', []);
        $selected_gateways = (array) get_option('wgcc_selected_gateways', []);
        if (empty($selected_gateways)) return $available_gateways;

        $in_cart_cats = [];
        foreach (WC()->cart->get_cart() as $item) {
            $terms = wp_get_post_terms($item['product_id'], 'product_cat', ['fields' => 'ids']);
            $in_cart_cats = array_merge($in_cart_cats, $terms);
        }
        $in_cart_cats = array_unique($in_cart_cats);

        $restricted = false;

        if ($mode === 'disable_all') {
            $allow = array_intersect($selected_cats, $in_cart_cats);
            if (empty($allow)) {
                foreach ($selected_gateways as $gw) unset($available_gateways[$gw]);
                $restricted = true;
            }
        } elseif ($mode === 'disable_selected') {
            $deny = array_intersect($selected_cats, $in_cart_cats);
            if (!empty($deny)) {
                foreach ($selected_gateways as $gw) unset($available_gateways[$gw]);
                $restricted = true;
            }
        }

        // ذخیره فلگ برای جلوگیری از پرداخت
        WC()->session->set('wgcc_restricted', $restricted);
        return $available_gateways;
    }

    /** -------------------------------
     *  جلوگیری از ادامه پرداخت در صورت وجود محدودیت
     * ------------------------------- */
public function prevent_checkout_if_restricted() {
    if (!is_checkout()) return;

    $mode = get_option('wgcc_mode', 'disable_selected');
    $selected_cats = (array) get_option('wgcc_selected_cats', []);
    $selected_gateways = (array) get_option('wgcc_selected_gateways', []);
    
    if (empty($selected_gateways)) return;

    $restricted = false;

    foreach (WC()->cart->get_cart() as $item) {
        $terms = wp_get_post_terms($item['product_id'], 'product_cat', ['fields' => 'ids']);
        
        if ($mode === 'disable_all') {
            $allow = array_intersect($selected_cats, $terms);
            if (empty($allow)) {
                $restricted = true;
                break;
            }
        } elseif ($mode === 'disable_selected') {
            $deny = array_intersect($selected_cats, $terms);
            if (!empty($deny)) {
                $restricted = true;
                break;
            }
        }
    }

    // ذخیره فلگ برای JS
    WC()->session->set('wgcc_restricted', $restricted);

    if ($restricted) {
        $msg = get_option('wgcc_restricted_message');
        wc_add_notice($msg, 'error');
    }
}

	// نمایش پیام و غیرفعال کردن دکمه پرداخت در checkout
public function render_checkout_restriction_notice() {
    // فقط در فرانت‌اند و صفحه checkout
    if (is_admin() || !is_checkout()) return;

    $restricted = WC()->session->get('wgcc_restricted');
    if (!$restricted) return;

    $msg = get_option('wgcc_restricted_message', 'لطفاً محصولات نامعتبر را حذف کنید.');
    
    // نمایش پیام در بالای فرم
    wc_print_notice( wp_kses_post($msg), 'error' );

    // اضافه کردن JS برای غیرفعال کردن دکمه پرداخت
    add_action('wp_footer', function() use ($msg) {
        if (!is_checkout()) return;
        ?>
        <script type="text/javascript">
        (function($){
            $(function(){
                var $btn = $('#place_order, .checkout .button[name="woocommerce_checkout_place_order"]');
                if ($btn.length) {
                    $btn.prop('disabled', true).addClass('disabled');
                    if (!$('#wgcc-restrict-note').length) {
                        $btn.after('<div id="wgcc-restrict-note" style="color:#b12a1b; margin-top:10px;"><?php echo esc_js($msg); ?></div>');
                    }
                }
            });
        })(jQuery);
        </script>
        <?php
    }, 99);
}

}

new Woo_Gateway_Category_Control();




