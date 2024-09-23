<?php
/*
Plugin Name: Wholesale Pricing - افزونه سفارشی افزودن قیمت عمده

Description: افزونه اختصاصی افزودن قابلیت قیمت گذاری عمده در ووکامرس - اضافه شئن قابلیت ویرایش قیمت محصول عمده Ajax - اضافه شدن مدیریت مشتریان عمده. 

Version: 1.1.0

Author: Amir Heydaripour , Amir.h.heydaripour22@gmail.com 

*/



if (!defined('ABSPATH')) {

    exit;

}



// Add the wholesale customer role on plugin activation

function wp_add_wholesale_customer_role() {

    add_role('wholesale_customer', __('Wholesale Customer'), array(

        'read' => true,

        'edit_posts' => false,

        'delete_posts' => false,

    ));

}

register_activation_hook(__FILE__, 'wp_add_wholesale_customer_role');



// Remove the wholesale customer role on plugin deactivation

function wp_remove_wholesale_customer_role() {

    remove_role('wholesale_customer');

}

register_deactivation_hook(__FILE__, 'wp_remove_wholesale_customer_role');



// Add wholesale price field to WooCommerce products

function wp_add_wholesale_price_field() {

    woocommerce_wp_text_input(array(

        'id' => '_wholesale_price',

        'label' => __('قیمت عمده: ', 'woocommerce'),

        'desc_tip' => true,

        'description' => __('جهت فعال سازی فروش عمده برای این محصول، فیلد قیمت عمده را وارد کنید.', 'woocommerce'),

        'type' => 'text',

    ));

}

add_action('woocommerce_product_options_pricing', 'wp_add_wholesale_price_field');



// Save the wholesale price field



// Add wholesale price field to variations

function wp_add_wholesale_price_to_variations($loop, $variation_data, $variation) {

    woocommerce_wp_text_input(array(

        'id' => 'wholesale_price_' . $variation->ID,

        'label' => __('قیمت عمده: ', 'woocommerce'),

        'desc_tip' => true,

        'description' => __('جهت فعال سازی فروش عمده برای این محصول، فیلد قیمت عمده را وارد کنید.', 'woocommerce'),

        'type' => 'text',

        'value' => get_post_meta($variation->ID, '_wholesale_price', true),

    ));

}

add_action('woocommerce_variation_options_pricing', 'wp_add_wholesale_price_to_variations', 10, 3);



// ذخیره فیلد قیمت عمده

function wp_save_wholesale_price_field($post_id) {

    $wholesale_price = isset($_POST['_wholesale_price']) ? $_POST['_wholesale_price'] : '';

    update_post_meta($post_id, '_wholesale_price', esc_attr($wholesale_price));

    

    // ذخیره تاریخ بروز رسانی

    update_post_meta($post_id, '_wholesale_price_last_updated', current_time('mysql'));

}

add_action('woocommerce_process_product_meta', 'wp_save_wholesale_price_field');



// ذخیره فیلد قیمت عمده برای محصولات متغیر

function wp_save_wholesale_price_variation($variation_id) {

    $wholesale_price = isset($_POST['wholesale_price_' . $variation_id]) ? $_POST['wholesale_price_' . $variation_id] : '';

    update_post_meta($variation_id, '_wholesale_price', esc_attr($wholesale_price));

    

    // ذخیره تاریخ بروز رسانی

    update_post_meta($variation_id, '_wholesale_price_last_updated', current_time('mysql'));

}

add_action('woocommerce_save_product_variation', 'wp_save_wholesale_price_variation', 10, 2);





// Check if the user is a wholesale customer

function wp_is_wholesale_customer() {

    return is_user_logged_in() && current_user_can('wholesale_customer');

}



// Display wholesale price for wholesale customers

function wp_display_wholesale_price($price, $product) {

    if (wp_is_wholesale_customer()) {

        $wholesale_price = get_post_meta($product->get_id(), '_wholesale_price', true);

        if ($wholesale_price) {

            $price .= '<br><span class="wholesale-price" style="display: flex; width: 100%; font-size: 22px; justify-content: space-between; align-items: center;">' . __('قیمت عمده: ', 'woocommerce') . wc_price($wholesale_price) . '</span>';

        }

    }

    return $price;

}

add_filter('woocommerce_get_price_html', 'wp_display_wholesale_price', 10, 2);



// Apply wholesale price in cart for wholesale customers

function wp_apply_wholesale_price_in_cart($cart_object) {

    if (wp_is_wholesale_customer()) {

        foreach ($cart_object->get_cart() as $cart_item) {

            if ($cart_item['data']->is_type('variation')) {

                $variation_wholesale_price = get_post_meta($cart_item['variation_id'], '_wholesale_price', true);

                if (!empty($variation_wholesale_price)) {

                    $cart_item['data']->set_price($variation_wholesale_price);

                }

            } else {

                $wholesale_price = get_post_meta($cart_item['product_id'], '_wholesale_price', true);

                if (!empty($wholesale_price)) {

                    $cart_item['data']->set_price($wholesale_price);

                }

            }

        }

    }

}

add_action('woocommerce_before_calculate_totals', 'wp_apply_wholesale_price_in_cart');



// Display the wholesale price in the mini cart for wholesale customers

function wp_display_wholesale_price_in_mini_cart($cart_item_price, $cart_item, $cart_item_key) {

    if (wp_is_wholesale_customer()) {

        $wholesale_price = get_post_meta($cart_item['variation_id'] ?? $cart_item['product_id'], '_wholesale_price', true);

        if (!empty($wholesale_price)) {

            $cart_item_price = wc_price($wholesale_price);

        }

    }

    return $cart_item_price;

}

add_filter('woocommerce_cart_item_price', 'wp_display_wholesale_price_in_mini_cart', 10, 3);

add_filter('woocommerce_cart_item_subtotal', 'wp_display_wholesale_price_in_mini_cart', 10, 3);



// Add admin menu for wholesale products

function wp_add_wholesale_products_menu() {

    add_menu_page(

        __('محصولات عمده', 'woocommerce'),

        __('محصولات عمده', 'woocommerce'),

        'manage_options',

        'wholesale-products',

        'wp_display_wholesale_products_page',

        'dashicons-store', // This can be replaced with an SVG icon

        56

    );
    add_menu_page(

        'مشتریان عمده',
        'مشتریان عمده', 
        'manage_options', 
        'wholesale-customers', 
        'wp_display_wholesale_customers_page', 
        'data:image/svg+xml;base64,' . base64_encode(
            '<svg width="34" height="34" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.257 2H8.753A6.765 6.765 0 0 0 2 8.75v6.5a6.73 6.73 0 0 0 3.122 5.68 6 6 0 0 0 1.06.56 6.7 6.7 0 0 0 2.561.51h6.504c.9 0 1.791-.18 2.62-.53a6.5 6.5 0 0 0 1.131-.62A6.71 6.71 0 0 0 22 15.26v-6.5A6.76 6.76 0 0 0 15.257 2m-3.252 4.58a3.143 3.143 0 0 1 3.081 3.753 3.14 3.14 0 0 1-4.283 2.288 3.14 3.14 0 0 1-1.94-2.901 3.15 3.15 0 0 1 3.142-3.14m5.002 13.63a5 5 0 0 1-1.7.29H8.803a5.26 5.26 0 0 1-3.391-1.25 6.53 6.53 0 0 1 2.1-2.56 7.176 7.176 0 0 1 9.085 0 6.9 6.9 0 0 1 2.151 2.52c-.523.45-.828.698-1.486.907z" fill="#fff"/></svg>'),
        56

    );


}

add_action('admin_menu', 'wp_add_wholesale_products_menu');



// Display wholesale products page

// نمایش صفحه محصولات عمده

function wp_display_wholesale_products_page() {

    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';



    ?>

    <div class="wrap">

        <h1><?php _e('محصولات عمده', 'woocommerce'); ?></h1>

        <form style="margin:20px 0;" method="get">

            <input type="hidden" name="page" value="wholesale-products" />

            <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="جستجو محصولات..." />

            <input type="submit" value="جستجو" class="button" />

        </form>

        <table class="wp-list-table widefat fixed striped products">

            <thead>

                <tr>

                    <th><?php _e('نام محصول', 'woocommerce'); ?></th>

                    <th><?php _e('قیمت عادی', 'woocommerce'); ?></th>

                    <th><?php _e('قیمت عمده', 'woocommerce'); ?></th>

                    <th><?php _e('آخرین بروز رسانی قیمت عمده', 'woocommerce'); ?></th>

                    <th><?php _e('ویرایش', 'woocommerce'); ?></th>

                </tr>

            </thead>

            <tbody>

                <?php

                $args = array(

                    'post_type' => array('product', 'product_variation'),

                    'posts_per_page' => 32,

                    'paged' => $paged,

                    'meta_query' => array(

                        array(

                            'key' => '_wholesale_price',

                            'value' => '',

                            'compare' => '!='

                        )

                    )

                );



                if ($search_query) {

                    $args['s'] = $search_query;

                }



                $query = new WP_Query($args);

                if ($query->have_posts()) {

                    while ($query->have_posts()) {

                        $query->the_post();

                        $product = wc_get_product(get_the_ID());

                        $wholesale_price = get_post_meta(get_the_ID(), '_wholesale_price', true);

                        $last_updated = get_post_meta(get_the_ID(), '_wholesale_price_last_updated', true);

                        $edit_link = $product->is_type('variation') ? get_edit_post_link($product->get_parent_id()) : get_edit_post_link($product->get_id());

                        ?>

                        <tr>

                            <td><?php echo esc_html($product->get_name()); ?></td>

                            <td><?php echo $product->get_price_html(); ?></td>

                            <td>

                                <?php echo wc_price($wholesale_price); ?>

                                <a href="#" class="edit-wholesale-price" data-product-id="<?php echo esc_attr(get_the_ID()); ?>">

                                    <span class="dashicons dashicons-edit"></span>

                                </a>

                                <form method="post" class="wholesale-price-form" style="display: none;">

                                    <input type="hidden" name="product_id" value="<?php echo esc_attr(get_the_ID()); ?>">

                                    <input type="text" name="new_wholesale_price" value="<?php echo esc_attr($wholesale_price); ?>">

                                    <input type="submit" name="save_wholesale_price" value="ذخیره" class="button">

                                </form>

                            </td>

                            <td class="last-updated"><?php echo $last_updated ? esc_html(date_i18n('j F - Y - g:i A', strtotime($last_updated))) : __('هرگز', 'woocommerce'); ?></td>

                            <td><a href="<?php echo esc_url($edit_link); ?>" target="_blank"><?php _e('ویرایش', 'woocommerce'); ?></a></td>

                        </tr>

                        <?php

                    }

                    wp_reset_postdata();

                } else {

                    ?>

                    <tr>

                        <td colspan="5"><?php _e('هیچ محصولی یافت نشد.', 'woocommerce'); ?></td>

                    </tr>

                    <?php

                }

                ?>

            </tbody>

        </table>

        <div class="tablenav-pages">

            <?php

            $big = 999999999;

            echo paginate_links(array(

                'base' => str_replace($big, '%#%', esc_url(add_query_arg('paged', '%#%'))),

                'format' => '?paged=%#%',

                'current' => max(1, $paged),

                'total' => $query->max_num_pages

            ));

            ?>

        </div>

    </div>

    <?php

}





// Apply styles to the wholesale products page

function wp_enqueue_admin_styles() {

    echo '<style>

        .wholesale-price {

            color: green;

            font-weight: bold;

        }

    </style>';

}

add_action('admin_head', 'wp_enqueue_admin_styles');

        







// Check product price and apply bulk price if any

function wp_check_and_apply_wholesale_price($price, $product) {



	if (wp_is_wholesale_customer()) {

        $wholesale_price = get_post_meta($product->get_id(), '_wholesale_price', true);



        if (!empty($wholesale_price)) {

            return wc_price($wholesale_price);

        }

    }



// Check if the product price is empty and set to call

	

    if (empty($price)) {

        return __('تماس بگیرید', 'woocommerce');

    }



    return $price;

}

add_filter('woocommerce_get_price_html', 'wp_check_and_apply_wholesale_price', 10, 2);



// Display add to cart button for wholesale users

function wp_show_add_to_cart_button($is_purchasable, $product) {

    if (wp_is_wholesale_customer()) {

        $wholesale_price = get_post_meta($product->get_id(), '_wholesale_price', true);

        if (!empty($wholesale_price)) {

            return true; // دکمه افزودن به سبد خرید را نمایش بده

        }

    }

    return $is_purchasable;

}

add_filter('woocommerce_is_purchasable', 'wp_show_add_to_cart_button', 10, 2);





// 




function wp_save_new_wholesale_price() {

    check_ajax_referer('wp-wholesale-edit-nonce', 'nonce');



    $product_id = intval($_POST['product_id']);

    $new_wholesale_price = sanitize_text_field($_POST['new_wholesale_price']);



    if ($product_id && $new_wholesale_price) {

        update_post_meta($product_id, '_wholesale_price', esc_attr($new_wholesale_price));

        update_post_meta($product_id, '_wholesale_price_last_updated', current_time('mysql'));



        wp_send_json_success(array(

            'message' => __('قیمت عمده با موفقیت ذخیره شد.', 'woocommerce'),

            'last_updated' => date_i18n('Y-m-d H:i:s', strtotime(current_time('mysql')))

        ));

    } else {

        wp_send_json_error(__('خطایی رخ داد. لطفا دوباره تلاش کنید.', 'woocommerce'));

    }

}

add_action('wp_ajax_save_wholesale_price', 'wp_save_new_wholesale_price');








function wp_enqueue_admin_scripts($hook) {

    if ($hook !== 'toplevel_page_wholesale-products') {

        return;

    }

    wp_enqueue_script('wp-wholesale-edit', plugin_dir_url(__FILE__) . 'js/wholesale-edit.js', array('jquery'), null, true);

    wp_localize_script('wp-wholesale-edit', 'wp_wholesale_edit', array(

        'ajax_url' => admin_url('admin-ajax.php'),

        'nonce' => wp_create_nonce('wp-wholesale-edit-nonce')

    ));

}

add_action('admin_enqueue_scripts', 'wp_enqueue_admin_scripts');



// Page User Wholesale Customer 

function wp_display_wholesale_customers_page() {
    // Get search query
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Pagination variables
    $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
    $per_page = 24;
    $offset = ($paged - 1) * $per_page;

    // WP_User_Query arguments
    $args = array(
        'role'    => 'wholesale_customer',
        'number'  => $per_page,
        'offset'  => $offset,
        'search'  => '*' . esc_attr($search_query) . '*',
        'search_columns' => array('user_login', 'user_email', 'user_nicename'),
    );

    // Create the WP_User_Query object
    $user_query = new WP_User_Query($args);

    // Pagination calculations
    $total_users = $user_query->get_total();
    $total_pages = ceil($total_users / $per_page);

    // Display the table
    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">مشتریان عمده</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="wholesale-customers" />';
    echo '<p class="search-box">';
    echo '<label class="screen-reader-text" for="user-search-input">جستجو کاربران:</label>';
    echo '<input type="search" id="user-search-input" placeholder="جستجو" name="s" value="' . esc_attr($search_query) . '" />';
    echo '<input type="submit" id="search-submit" class="button" value="جستجو کاربران" />';
    echo '</p>';
    echo '</form>';

    if (!empty($user_query->get_results())) {
        echo '<table class="wp-list-table widefat fixed striped users">';
        echo '<thead><tr><th>نام کاربر</th><th>شماره همراه</th><th>تعداد کل سفارشات</th><th>تعداد سفارش‌های فعال</th><th>تعداد سفارش‌های تکمیل شده</th><th>مشاهده سفارشات</th></tr></thead>';
        echo '<tbody>';

        foreach ($user_query->get_results() as $user) {
            $user_id = $user->ID;
            $user_name = $user->first_name ;
            $user_phone = get_user_meta($user_id, 'billing_phone', true);

            // Get user orders
            $args = array(
                'customer_id' => $user_id,
                'limit' => -24,
                'return' => 'ids',
                
                'posts_per_page' => 32,

                'paged' => $paged,
            );
            $orders = wc_get_orders($args);
            $total_orders = count($orders);
            $completed_orders = 0;
            $active_orders = 0;

            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                if ($order->get_status() == 'completed') {
                    $completed_orders++;
                } else {
                    $active_orders++;
                }
            }

            echo '<tr>';
            echo '<td>' . esc_html($user_name) . '</td>';
            echo '<td>' . esc_html($user_phone) . '</td>';
            echo '<td>' . esc_html($total_orders) . '</td>';
            echo '<td>' . esc_html($active_orders) . '</td>';
            echo '<td>' . esc_html($completed_orders) . '</td>';
            echo '<td><a target="_blank" href="' . esc_url(admin_url('edit.php?post_status=all&post_type=shop_order&_customer_user=' . $user_id)) . '">مشاهده سفارشات</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Pagination
        if ($total_pages > 1) {
            $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $current_url = remove_query_arg(array('paged', 's'), $current_url);
            if ($paged > 1) {
                echo '<a class="button" href="' . esc_url(add_query_arg('paged', $paged - 1, $current_url)) . '">صفحه قبلی</a> ';
            }
            if ($paged < $total_pages) {
                echo '<a class="button" href="' . esc_url(add_query_arg('paged', $paged + 1, $current_url)) . '">صفحه بعدی</a>';
            }
        }

    } else {
        echo '<p>هیچ کاربری پیدا نشد.</p>';
    }

    echo '</div>';
}
add_filter('woocommerce_get_price_html', 'show_both_regular_and_wholesale_prices', 10, 2);

function show_both_regular_and_wholesale_prices($price, $product) {
    if (current_user_can('wholesale_customer')) {
        $regular_price = wc_get_price_to_display($product, array('price' => $product->get_regular_price()));
        $wholesale_price = get_post_meta(get_the_ID(), '_wholesale_price', true);

        $regular_price_html = '';
        if ($product->get_regular_price() !== '') {
            $regular_price_html = wc_price($regular_price) . ' ' . __( 'قیمت معمولی', 'your-text-domain' );
        }

        $wholesale_price_html = '';
        if (!empty($wholesale_price)) {
            $wholesale_price_html = wc_price($wholesale_price) . ' ' . __( 'قیمت عمده', 'your-text-domain' );
        }
        
        // اگر هر دو قیمت معمولی و عمده وجود داشته باشند، هر دو را نشان دهید
        if ($regular_price_html !== '' && $wholesale_price_html !== '') {
            return $regular_price_html . '<br>' . $wholesale_price_html;
        } 
        // در صورت نبودن قیمت معمولی، فقط قیمت عمده را نشان دهید
        elseif ($regular_price_html === '' && $wholesale_price_html !== '') {
        }
        // در صورت نبودن قیمت عمده، فقط قیمت معمولی را نشان دهید
        elseif ($regular_price_html !== '' && $wholesale_price_html === '') {
        }
    }

    return $price;
}

