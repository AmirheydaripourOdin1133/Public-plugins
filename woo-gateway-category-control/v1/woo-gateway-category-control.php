<?php
/*
Plugin Name: Woo Gateway Category Control
Description: کنترل درگاه‌های پرداخت ووکامرس بر اساس دسته‌بندی محصولات
Version: 1.1
Author: نسا جعفری
*/

if (!defined('ABSPATH')) exit;

class Woo_Gateway_Category_Control {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('woocommerce_available_payment_gateways', [$this, 'filter_gateways_by_category']);
    }

    /** -------------------------------
     *  ایجاد صفحه تنظیمات در پیشخوان
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
     *  ثبت تنظیمات در دیتابیس
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
    }

    public function sanitize_array($input) {
        return array_map('sanitize_text_field', (array) $input);
    }

    /** -------------------------------
     *  صفحه تنظیمات پلاگین
     * ------------------------------- */
    public function settings_page() {
        // اعلان موفقیت یا خطا با استایل پیش‌فرض وردپرس
        if (isset($_GET['settings-updated'])) {
            add_settings_error('wgcc_messages', 'wgcc_message', 'تنظیمات با موفقیت ذخیره شد.', 'updated');
        }
        settings_errors('wgcc_messages');

        $mode = get_option('wgcc_mode', 'disable_selected');
        $selected_cats = (array) get_option('wgcc_selected_cats', []);
        $selected_gateways = (array) get_option('wgcc_selected_gateways', []);
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        $gateways = WC()->payment_gateways()->payment_gateways();
        ?>

        <div class="wrap">
            <h1>کنترل درگاه پرداخت ووکامرس</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wgcc_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr valign="top">
                        <th scope="row">حالت عملکرد:</th>
                        <td>
                            <label>
                                <input type="radio" name="wgcc_mode" value="disable_all" <?php checked($mode, 'disable_all'); ?> />
                                غیرفعال کردن همه درگاه‌ها مگر برای دسته‌های انتخاب‌شده
                            </label><br>
                            <label>
                                <input type="radio" name="wgcc_mode" value="disable_selected" <?php checked($mode, 'disable_selected'); ?> />
                                فعال نگه داشتن همه درگاه‌ها، به جز برای دسته‌های انتخاب‌شده
                            </label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">انتخاب دسته‌بندی‌ها:</th>
                        <td>
                            <select name="wgcc_selected_cats[]" multiple style="min-width:300px; height:200px;">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, $selected_cats)); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">برای انتخاب چند دسته، کلید Ctrl یا Cmd را نگه دارید.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">انتخاب درگاه‌های پرداخت:</th>
                        <td>
                            <select name="wgcc_selected_gateways[]" multiple style="min-width:300px; height:150px;">
                                <?php foreach ($gateways as $gateway_id => $gateway): ?>
                                    <option value="<?php echo esc_attr($gateway_id); ?>" <?php selected(in_array($gateway_id, $selected_gateways)); ?>>
                                        <?php echo esc_html($gateway->get_title()); ?> (<?php echo esc_html($gateway_id); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">درگاه‌هایی که باید غیرفعال شوند یا فقط برای دسته‌های انتخاب‌شده فعال باشند.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>

        <?php
    }

    /** -------------------------------
     *  فیلتر اصلی ووکامرس برای کنترل درگاه‌ها
     * ------------------------------- */
    public function filter_gateways_by_category($available_gateways) {
        if (!is_checkout()) return $available_gateways;

        $mode = get_option('wgcc_mode', 'disable_selected');
        $selected_cats = (array) get_option('wgcc_selected_cats', []);
        $selected_gateways = (array) get_option('wgcc_selected_gateways', []);

        // اگر هیچ درگاهی انتخاب نشده بود، کاری نکن
        if (empty($selected_gateways)) return $available_gateways;

        $in_cart_cats = [];

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
            $in_cart_cats = array_merge($in_cart_cats, $terms);
        }

        $in_cart_cats = array_unique($in_cart_cats);

        // حالت ۱: disable_all → فقط برای دسته‌های انتخاب‌شده، درگاه‌ها مجازند
        if ($mode === 'disable_all') {
            $allow = array_intersect($selected_cats, $in_cart_cats);
            if (empty($allow)) {
                foreach ($selected_gateways as $gw) {
                    unset($available_gateways[$gw]);
                }
            }
        }

        // حالت ۲: disable_selected → برای دسته‌های انتخاب‌شده، درگاه‌ها غیرفعال می‌شوند
        if ($mode === 'disable_selected') {
            $deny = array_intersect($selected_cats, $in_cart_cats);
            if (!empty($deny)) {
                foreach ($selected_gateways as $gw) {
                    unset($available_gateways[$gw]);
                }
            }
        }

        return $available_gateways;
    }
}

// اجرای پلاگین
new Woo_Gateway_Category_Control();
