<?php

if (!function_exists('send_email_with_template')) {
    function send_email_with_template($recipients, $subject, $body, $template = 'default-template') {
        $template_path = plugin_dir_path(__FILE__) . '../email-templates/' . $template . '.php';

        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $email_content = ob_get_clean();

            add_filter('wp_mail_from_name', 'custom_wp_mail_from_name');
            add_filter('wp_mail_from', 'custom_wp_mail_from');

            foreach ($recipients as $email) {
                wp_mail($email, $subject, $email_content);
            }

            remove_filter('wp_mail_from_name', 'custom_wp_mail_from_name');
            remove_filter('wp_mail_from', 'custom_wp_mail_from');

            email_marketing_save_email_history($subject, $email_content, implode(', ', $recipients));
        } else {
            error_log("Template file not found: " . $template_path);
        }
    }
}

if (!function_exists('send_email_to_group')) {
    function send_email_to_group($group_id, $subject, $body, $template = 'default-template') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'email_contacts';
        $contacts = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE group_id = %d", $group_id));

        $emails = [];
        foreach ($contacts as $contact) {
            $emails[] = $contact->email;
        }

        send_email_with_template($emails, $subject, $body, $template);
    }
}

if (!function_exists('send_email_to_contacts')) {
    function send_email_to_contacts($emails, $subject, $body, $template = 'default-template') {
        send_email_with_template($emails, $subject, $body, $template);
    }
}

if (!function_exists('email_marketing_save_email_history')) {
    function email_marketing_save_email_history($subject, $body, $recipients) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'email_history';
        $wpdb->insert($table_name, [
            'time' => current_time('mysql'),
            'subject' => $subject,
            'body' => $body,
            'recipients' => $recipients,
        ]);
    }
}

if (!function_exists('custom_wp_mail_from_name')) {
    function custom_wp_mail_from_name($original_email_from) {
        return get_bloginfo('name');
    }
}

if (!function_exists('custom_wp_mail_from')) {
    function custom_wp_mail_from($original_email_address) {
        return get_bloginfo('admin_email');
    }
}
?>
