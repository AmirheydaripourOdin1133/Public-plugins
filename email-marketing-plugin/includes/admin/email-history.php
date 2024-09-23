<?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_history';

    if (isset($_POST['delete_email'])) {
        $email_id = intval($_POST['delete_email_id']);
        $wpdb->delete($table_name, ['id' => $email_id], ['%d']);
        echo '<div class="updated"><p>ایمیل با موفقیت حذف شد.</p></div>';
    }

    $search_query = '';
    if (isset($_GET['s'])) {
        $search_query = sanitize_text_field($_GET['s']);
    }

    $per_page = 2;
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($paged - 1) * $per_page;

    $where = '';
    if ($search_query) {
        $where = $wpdb->prepare("WHERE subject LIKE %s OR recipients LIKE %s", '%' . $wpdb->esc_like($search_query) . '%', '%' . $wpdb->esc_like($search_query) . '%');
    }

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where ORDER BY time DESC LIMIT %d OFFSET %d", $per_page, $offset));

    $total_pages = ceil($total_items / $per_page);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">تاریخچه ایمیل‌ها</h1>
        <form method="GET" class="search-form">
            <input type="hidden" name="page" value="email-marketing-history">
            <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" class="regular-text" placeholder="جستجوی تاریخچه ایمیل‌ها">
            <button type="submit" class="button">جستجو</button>
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>زمان</th>
                    <th>موضوع</th>
                    <th>دریافت کنندگان</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo $row->id; ?></td>
                        <td><?php echo $row->time; ?></td>
                        <td><?php echo esc_html($row->subject); ?></td>
                        <td><?php echo count(explode(', ', $row->recipients)); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=email-marketing-view-details&id=' . $row->id); ?>" class="button">نمایش جزئیات</a>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="delete_email_id" value="<?php echo $row->id; ?>">
                                <button type="submit" name="delete_email" class="button button-secondary">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $paged,
                ]);
                ?>
            </div>
        </div>
    </div>
    <?php

