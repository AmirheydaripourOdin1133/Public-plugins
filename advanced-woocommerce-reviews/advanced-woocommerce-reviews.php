<?php
/**
 * Plugin Name: WooCommerce AJAX Comments - پیام آوا 
 * Description: سفارشی‌سازی کامنت‌های ووکامرس با AJAX (مرتب‌سازی و ارسال کامنت). <br> رفع مشکلات Replay ، مشکلات ظاهری و مرتب سازی و بهیه سازی js و 
 * Version: 2.1.1
 * Author: Amir.H.Heydaripour 
 */


//  $file_url_svg = plugin_dir_url( __FILE__ ) . 'assets/img/star.svg'; // آدرس URL به فایل SVG


// disable Def Reviews WC After Install
add_filter('woocommerce_product_tabs', 'remove_woocommerce_reviews_tab', 98);
function remove_woocommerce_reviews_tab($tabs)
{
    if (isset($tabs['reviews'])) {
        // تغییر کال‌بک تب نظرات
        $tabs['reviews']['callback'] = 'custom_comments_section';
    }
    return $tabs;
}

// اضافه کردن تابع نمایش تعداد کامنت‌های تایید شده
function display_approved_comments_count()
{
    global $product;

    // اطمینان از وجود محصول در صفحه محصول
    if (!isset($product) || !is_a($product, 'WC_Product')) {
        return;
    }

    // شناسه محصول فعلی
    $product_id = $product->get_id();

    // دریافت تعداد کامنت‌های تایید شده
    $approved_comments_count = get_comments([
        'post_id' => $product_id,
        'status' => 'approve', // فقط کامنت‌های تایید شده
        'count' => true, // فقط تعداد کامنت‌ها
    ]);

    // نمایش تعداد کامنت‌ها
    if ($approved_comments_count > 0) {
        echo '<p class="comments-count">' . $approved_comments_count . ' دیدگاه</p>';
    } else {
        echo '<p class="comments-count">هنوز دیدگاهی ثبت نشده است.</p>';
    }
}

// Add custom Comments for woo
// add_action('woocommerce_after_single_product_summary', 'custom_comments_section', 20);
function custom_comments_section()
{
    global $product;
    $user = wp_get_current_user();
    $is_logged_in = is_user_logged_in();
    ?>
    <div id="custom-comments" class="ParentMCommentsList">
        <div class="rightscol">
            <h3> امتیاز و دیدگاه کاربران </h3>
            <div class="div">
                <?php echo get_product_rating_and_reviews(); ?>
                <p>شما هم درباره این محصول ، دیدگاه خود را ثبت کنید . </p>
                <button id="open-comment-popup">افزودن دیدگاه</button>
            </div>

        </div>

        <!-- sorting-->
        <div class="CommentCustomParents">
            <div id="comment-sorting" class="SortCommentLists">
                <span><svg fill="#282828" viewBox="0 0 512 512" height="18">
                        <path
                            d="m229 407.3-79 54-15 10.2-15-10.2-79-54a15 15 0 1 1 17-24.7l62 42.4V39a15 15 0 0 1 30 0v386l62-42.4a15 15 0 1 1 17 24.7M444.3 56H216.5c-8.1 0-15.1 6.2-15.5 14.3A15 15 0 0 0 216 86h228.8a15.1 15.1 0 0 0 15-15.7c-.4-8.1-7.4-14.3-15.5-14.3m0 120.7H266.5c-8.1 0-15.1 6.2-15.5 14.3a15 15 0 0 0 15 15.7h178.8a15.1 15.1 0 0 0 15-15.7c-.4-8.1-7.4-14.3-15.5-14.3m0 120.6H296.5c-8.1 0-15.1 6.3-15.5 14.3a15 15 0 0 0 15 15.7h148.8a15.1 15.1 0 0 0 15-15.7c-.4-8-7.4-14.3-15.5-14.3m0 120.7h-97.8c-8.1 0-15.1 6.2-15.5 14.3a15 15 0 0 0 15 15.7h98.8a15.1 15.1 0 0 0 15-15.7c-.4-8.1-7.4-14.3-15.5-14.3" />
                    </svg> مرتـب سـازی : </span>
                <button class="sort-btn selctsort" data-sort="newest">جدیـدترین</button>
                <button class="sort-btn" data-sort="helpful">مفـیدترین</button>
                <button class="sort-btn" data-sort="buyers">خریـداران</button>
                <div class="commentscountParent"> <?php display_approved_comments_count(); ?></div>
            </div>
            <div id="loading-spinner" style="display: none;"><span class="loader"></span></div>
            <?php
            function display_comments_or_message()
            {
                global $product;

                if (!isset($product) || !is_a($product, 'WC_Product')) {
                    return;
                }

                // شناسه محصول
                $product_id = $product->get_id();

                // دریافت تعداد نظرات تایید شده برای محصول
                $comments = get_comments(array(
                    'post_id' => $product_id,
                    'status' => 'approve',
                ));

                // بررسی وجود نظرات
                if (empty($comments)) {
                    // نمایش پیام پیش‌فرض در صورت نبود نظرات
                    echo '<div class="no-comments-message" style="text-align: center;font-size: 13.5px;font-weight: 500;color: #59B20F;">
                        <span class="lightAnimeSvg"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.252 12.49c-.284 2.365-1.833 3.31-2.502 3.996-.67.688-.55.825-.505 1.834a.916.916 0 0 1-.916.971h-2.658a.92.92 0 0 1-.917-.971c0-.99.092-1.22-.504-1.834-.76-.76-2.548-1.833-2.548-4.784a5.307 5.307 0 1 1 10.55.788" stroke="#ddd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.46 19.236v1.512c0 .413.23.752.513.752h2.053c.285 0 .514-.34.514-.752v-1.512m-2.32-10.54a2.227 2.227 0 0 0-2.226 2.227m10.338.981h1.834m-3.68-6.012 1.301-1.301M18.486 17l1.301 1.3M12 2.377V3.86m-6.76.73 1.292 1.302M4.24 18.3 5.532 17m-.864-5.096H2.835" stroke="#ddd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>        
                    <p style="color: #919191;text-align: center;font-size: 13.5px;font-weight: 500;">هنوز نظری برای این محصول ثبت نشده است.</p>
                            <p style="text-align: center;font-size: 12px;font-weight: 700;color: #59B20F;">اولین نفری باشید که نظر خود را ثبت می‌کند!</p>
                        </div>';
                } else {
                    // نمایش لیست کامنت‌ها (AJAX به صورت خودکار اینجا بارگذاری می‌شود)
                }
            }
            display_comments_or_message();
            ?>


            <div id="comments-list" class="commentsListCustom">

            </div>
            <div id="comments-pagination">
            </div>

        </div>
    </div>

    <!-- Popup add cooment-->
    <div id="comment-popup" class="commentCustomsPopup">

        <span class="closs_custom_comment_Gen"><svg width="26" height="26" viewBox="0 0 24 24" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="m15.958 8.042-7.916 7.916m7.916 0L8.042 8.042M12 21.5a9.5 9.5 0 1 0 0-19 9.5 9.5 0 0 0 0 19"
                    stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg></span>
        <form id="ajax-comment-form">

            <?php if (!$is_logged_in): ?>
                <div class="inputParent">
                    <label>نام و نام خانوادگی</label>
                    <input type="text" name="author" placeholder="امیر حیدری " required>
                </div>
                <div class="inputParent">
                    <label>آدرس ایمیل</label>
                    <input type="email" name="email" placeholder="Email@gmail.com" required>
                </div>
            <?php else: ?>
                <input type="hidden" name="author" value="<?php echo esc_attr($user->display_name); ?>">
                <input type="hidden" name="email" value="<?php echo esc_attr($user->user_email); ?>">
            <?php endif; ?>
            <div class="inputParent">
                <label>متن پیام</label>
                <textarea name="comment" placeholder="دیدگاه خود را وارد کنید ." required></textarea>
            </div>

            <div class="ParentGenReaSub">
                <div class="star-rating">
                    <span class="custom-star" data-value="1"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path
                                d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z"
                                stroke="#F7B661" stroke-width="1.5"></path>

                        </svg></span>
                    <span class="custom-star" data-value="2"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path
                                d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z"
                                stroke="#F7B661" stroke-width="1.5"></path>

                        </svg></span>
                    <span class="custom-star" data-value="3"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path
                                d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z"
                                stroke="#F7B661" stroke-width="1.5"></path>

                        </svg></span>
                    <span class="custom-star" data-value="4"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path
                                d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z"
                                stroke="#F7B661" stroke-width="1.5"></path>

                        </svg></span>
                    <span class="custom-star" data-value="5"><svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg">

                            <path
                                d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z"
                                stroke="#F7B661" stroke-width="1.5"></path>

                        </svg></span>


                </div>
                <div id="tooltip" class="tooltip">امتیــاز شمـا : <span id="tooltip-rating">5</span></div>

                <button type="submit" class="GenralBtns">ارسال</button>

            </div>

            <input type="hidden" name="rating" id="rating-input" required>


            <input type="hidden" name="post_id" value="<?php echo $product->get_id(); ?>">
            <input type="hidden" name="action" value="submit_comment">
        </form>
    </div>

    <script>


        isLoading = false;
        // Loading Ajax For Comment
        function loadComments(sort = 'newest', page = 1) {
            const postId = <?php echo $product->get_id(); ?>;
            const spinner = document.getElementById('loading-spinner');
            isLoading = true;
            spinner.style.display = 'block';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=load_comments&post_id=' + postId + '&sort=' + sort + '&page=' + page)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('comments-list').innerHTML = data;
                    isLoading = false;
                    spinner.style.display = 'none';
                });
        }
    </script>
    <?php
}

function has_purchased_product($user_id, $product_id)
{
    if (!$user_id)
        return false;
    return wc_customer_bought_product('', $user_id, $product_id);
}

// هندلر برای ارسال کامنت
add_action('wp_ajax_submit_comment', 'ajax_submit_comment');
add_action('wp_ajax_nopriv_submit_comment', 'ajax_submit_comment');
function ajax_submit_comment()
{
    $post_id = intval($_POST['post_id']);
    $author = sanitize_text_field($_POST['author']);
    $email = sanitize_email($_POST['email']);
    $comment = sanitize_textarea_field($_POST['comment']);
    $rating = intval($_POST['rating']);
    $user_id = get_current_user_id();

    // بررسی وضعیت خریدار
    $is_buyer = has_purchased_product($user_id, $post_id);

    // ذخیره کامنت
    $comment_id = wp_insert_comment([
        'comment_post_ID' => $post_id,
        'comment_author' => $author,
        'comment_author_email' => $email,
        'comment_content' => $comment,
        'comment_approved' => 0,
        'user_id' => $user_id,
        'comment_type' => 'review',
    ]);

    if ($comment_id) {
        add_comment_meta($comment_id, 'review_comment', true);
    }

    if ($comment_id) {
        add_comment_meta($comment_id, 'rating', $rating);
        add_comment_meta($comment_id, 'verified_buyer', $is_buyer ? '1' : '0');
        wp_send_json_success();
    } else {
        wp_send_json_error('خطا در ذخیره کامنت.');
    }
}


// هندلر برای بارگذاری کامنت‌ها
add_action('wp_ajax_load_comments', 'ajax_load_comments');
add_action('wp_ajax_nopriv_load_comments', 'ajax_load_comments');

function ajax_load_comments()
{
    $post_id = intval($_GET['post_id']);
    $sort = sanitize_text_field($_GET['sort']);
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 30;

    $args = [
        'post_id' => $post_id,
        'status' => 'approve',
        'number' => $per_page,
        'offset' => ($page - 1) * $per_page,
        'parent' => 0,
    ];


    if ($sort == 'helpful') {
        $args['meta_key'] = 'rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
    } elseif ($sort == 'buyers') {
        $args['meta_query'] = [
            [
                'key' => 'verified_buyer',
                'value' => '1',
            ],
        ];
    } else { // newest
        $args['orderby'] = 'comment_date';
        $args['order'] = 'DESC';
    }


    $comments = get_comments($args);

    foreach ($comments as $comment) {
        $is_buyer = get_comment_meta($comment->comment_ID, 'verified_buyer', true) === '1';
        $role = $is_buyer ? 'خریدار' : 'میهمان';
        $rating = get_comment_meta($comment->comment_ID, 'rating', true);

        echo '<div id="comment-' . $comment->comment_ID . '" class="comment">';
        echo '<div class="CommHeading">';

        // نمایش نام و نقش (خریدار/میهمان) با کلاس رنگی
        $role_class = $is_buyer ? 'green' : 'red';
        echo '<p><strong>' . esc_html($comment->comment_author) . '</strong><span class="' . $role_class . '">' . $role . '</span></p>';

        echo '<p class="DataComm">' . human_time_diff(get_comment_date('U', $comment), current_time('timestamp')) . ' پـیش</p>';
        echo '</div>';

        // نمایش ستاره‌های امتیاز
        echo '<div class="CommRating">';
        echo '<div class="stars-container">';
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $rating ? 'star full' : 'star empty';
            echo '<span class="' . $class . '"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"></path></svg></span>';
        }
        echo '</div>';
        echo '</div>';

        echo '<p>' . esc_html($comment->comment_content) . '</p>';

        // دکمه پاسخ
        echo '<button class="reply-btn" data-comment-id="' . $comment->comment_ID . '" data-comment-author="' . esc_attr($comment->comment_author) . '">پاسخ</button>';

        // بخش پاسخ‌ها
        echo '<div class="replies">';

        $replies = get_comments([
            'post_id' => $post_id,
            'status' => 'approve',
            'parent' => $comment->comment_ID,
        ]);

        foreach ($replies as $reply) {
            echo '<div id="reply-' . $reply->comment_ID . '" class="reply">';
            echo '<div class="CommHeading">';

            $reply_is_buyer = get_comment_meta($reply->comment_ID, 'verified_buyer', true) === '1';
            $reply_role = $reply_is_buyer ? 'خریدار' : 'میهمان';
            $reply_role_class = $reply_is_buyer ? 'green' : 'red';

            echo '<p><strong>' . esc_html($reply->comment_author) . '</strong><span class="' . $reply_role_class . '">' . $reply_role . '</span></p>';

            echo '<p class="DataComm">' . human_time_diff(get_comment_date('U', $reply), current_time('timestamp')) . ' پـیش</p>';
            echo '</div>';

            echo '<p>' . esc_html($reply->comment_content) . '</p>';
            echo '</div>';
        }


        echo '</div>'; // پایان بخش پاسخ‌ها
        echo '</div>'; // پایان کامنت اصلی
    }


    // Add Ajax pagination 
    $total_comments = get_comments([
        'post_id' => $post_id,
        'status' => 'approve',
        'count' => true,
    ]);

    $total_pages = ceil($total_comments / $per_page);
    if ($total_pages > 1) {
        echo '<div class="pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            echo '<a href="#" class="pagination-link" data-page="' . $i . '">' . $i . '</a> ';
        }
        echo '</div>';
    }

    wp_die();
}

// هندلر جدید برای ذخیره پاسخ
add_action('wp_ajax_submit_reply', 'ajax_submit_reply');
add_action('wp_ajax_nopriv_submit_reply', 'ajax_submit_reply');

function ajax_submit_reply()
{
    $post_id = intval($_POST['post_id']);
    $parent_id = intval($_POST['parent_id']);
    $author = sanitize_text_field($_POST['author']);
    $email = sanitize_email($_POST['email']);
    $comment = sanitize_textarea_field($_POST['comment']);
    $user_id = get_current_user_id();


    $comment_id = wp_insert_comment([
        'comment_post_ID' => $post_id,
        'comment_author' => $author,
        'comment_author_email' => $email,
        'comment_content' => $comment,
        'comment_parent' => $parent_id,
        'user_id' => $user_id,
        'comment_approved' => 0,
    ]);

    if ($comment_id) {
        wp_send_json_success();
    } else {
        wp_send_json_error('خطا در ذخیره پاسخ.');
    }
}

// Add JS Files
add_action('wp_enqueue_scripts', 'enqueue_comment_scripts');
function enqueue_comment_scripts()
{
    if (is_singular('product')) { // فقط در صفحات سینگل محصول
        wp_enqueue_script('ajax-comments', plugin_dir_url(__FILE__) . 'assets/js/ajax-comments.js', ['jquery'], '1.0', true);
        wp_enqueue_style('ajax-comments-Style', plugin_dir_url(__FILE__) . 'assets/css/products-single.css', array(), _S_VERSION);


        $current_user = wp_get_current_user();
        wp_localize_script('ajax-comments', 'ajaxComments', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'postId' => get_the_ID(),
            'isLoggedIn' => is_user_logged_in(),
            'userName' => is_user_logged_in() ? $current_user->display_name : '',
        ]);
    }
}

// بارگذاری template فقط در صفحات سینگل محصول
function load_reply_popup_template()
{
    if (is_singular('product')) {
        include plugin_dir_path(__FILE__) . 'assets/templates/reply-popup.php';
    }
}
add_action('wp_footer', 'load_reply_popup_template');
function get_product_rating_and_reviews()
{
    global $product;

    if (!isset($product) || !is_a($product, 'WC_Product')) {
        return;
    }

    // شناسه محصول
    $product_id = $product->get_id();

    // دریافت میانگین امتیاز و تعداد نظرات
    $average_rating = get_post_meta($product_id, '_wc_average_rating', true);
    $rating_count = get_comments_number($product_id);

    // بررسی تعداد نظرات
    if ($rating_count == 0) {
        // در صورت نبود نظرات
        return '<span>هنوز امتیازی ثبت نشده است</span>
                <div class="rating-stars-noneZ">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg>
                </div>';
    }

    // فرمت کردن میانگین امتیاز
    $formatted_rating = number_format((float) $average_rating, 1);

    // ساخت ستاره‌ها بر اساس امتیاز
    $full_stars = floor($average_rating); // تعداد ستاره‌های پر
    $half_stars = ($average_rating - $full_stars) >= 0.5 ? 1 : 0; // بررسی ستاره نیمه پر
    $empty_stars = 5 - $full_stars - $half_stars; // تعداد ستاره‌های خالی

    $stars_html = str_repeat('<span class="star full"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill="#f7b661" d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg></span>', $full_stars);
    $stars_html .= str_repeat('<span class="star half"><svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><defs><mask id="a"><path fill="#fff" d="M0 0h12v24H0z"/></mask></defs><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" fill="#f7b661" stroke="#f7b661" stroke-width="1.5" mask="url(#a)"/><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" fill="none" stroke="#f7b661" stroke-width="1.5"/></svg></span>', $half_stars);
    $stars_html .= str_repeat('<span class="star empty"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.153 5.408C10.42 3.136 11.053 2 12 2s1.58 1.136 2.847 3.408l.328.588c.36.646.54.969.82 1.182s.63.292 1.33.45l.636.144c2.46.557 3.689.835 3.982 1.776.292.94-.546 1.921-2.223 3.882l-.434.507c-.476.557-.715.836-.822 1.18-.107.345-.071.717.001 1.46l.066.677c.253 2.617.38 3.925-.386 4.506s-1.918.051-4.22-1.009l-.597-.274c-.654-.302-.981-.452-1.328-.452s-.674.15-1.328.452l-.596.274c-2.303 1.06-3.455 1.59-4.22 1.01-.767-.582-.64-1.89-.387-4.507l.066-.676c.072-.744.108-1.116 0-1.46-.106-.345-.345-.624-.821-1.18l-.434-.508c-1.677-1.96-2.515-2.941-2.223-3.882S3.58 8.328 6.04 7.772l.636-.144c.699-.158 1.048-.237 1.329-.45s.46-.536.82-1.182z" stroke="#f7b661" stroke-width="1.5"/></svg></span>', $empty_stars);

    // ساخت خروجی
    return '<div class="rating-container">
                <div class="rating-summary">
                    <strong>' . $formatted_rating . '</strong> از <span>' . $rating_count . ' نظر</span>
                </div>
                <div class="stars-container">' . $stars_html . '</div>
            </div>';
}