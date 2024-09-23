<?php
    if (!isset($_GET['id'])) {
        wp_die('شناسه ایمیل نامعتبر است');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'email_history';
    $email_id = intval($_GET['id']);
    $email = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $email_id));

    if (!$email) {
        wp_die('ایمیل پیدا نشد');
    }

    $recipients = explode(', ', $email->recipients);
    ?>
    <div class="wrap email-details">
        <h1 class="wp-heading-inline">جزئیات ایمیل</h1>
        <p><strong>موضوع:</strong> <?php echo esc_html($email->subject); ?></p>
        <p><strong>زمان:</strong> <?php echo $email->time; ?></p>
        <p><strong>دریافت کنندگان:</strong></p>
        <ul>
            <?php foreach ($recipients as $recipient): ?>
                <li><?php echo esc_html($recipient); ?></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>متن ایمیل:</strong></p>
        <div><?php echo wp_kses_post($email->body); ?></div>
        <a href="<?php echo admin_url('admin.php?page=email-marketing-history'); ?>" class="button">بازگشت به تاریخچه ایمیل</a>
    </div>
    <?php

