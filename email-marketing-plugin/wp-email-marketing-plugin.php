<?php
/*
 * Plugin Name: WP Email Marketing Plugin - ایده چی
 * Plugin URI:   https://idechy.ir/
 * Description:  افزونه اختصاصی مدیریت مخاطبین و ارسال گروهی ایمیل - (آپدیت نسخه 1.1.9 تاریخ 1 مرداد 1403 : رفع مشکل تداخل با وردپرس های نسخه زیر 5.3 ، بهینه سازی سورس افزونه )
 * Version:      1.1.9
 * Author:       idechy
 * Author URI:   https://idechy.ir/
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  wpb-tutorial
 * Domain Path:  /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Start output buffering
ob_start();

// Include necessary files.
include_once(plugin_dir_path(__FILE__) . 'includes/admin-menu.php');
include_once(plugin_dir_path(__FILE__) . 'includes/group-functions.php');
include_once(plugin_dir_path(__FILE__) . 'includes/contact-functions.php');
include_once(plugin_dir_path(__FILE__) . 'includes/send-mail-functions.php');

// Enqueue admin styles.
function wp_email_marketing_admin_styles() {
    wp_enqueue_style('wp-email-marketing-style', plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('admin_enqueue_scripts', 'wp_email_marketing_admin_styles');
function wp_email_marketing_init() {

    //  
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['add_group'])) {
            add_group($_POST['group_name']);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'گروه با موفقیت اضافه شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-groups'], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['delete_group'])) {
            delete_group($_POST['group_id']);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'گروه با موفقیت حذف شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-groups'], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['update_group'])) {
            $group_id = intval($_GET['id']);
            update_group($group_id, $_POST['group_name']);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'گروه با موفقیت به روز شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-edit-group', 'id' => $group_id], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['remove_contact'])) {
            $group_id = intval($_GET['id']);
            update_contact($_POST['contact_id'], null, null, 0);  
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'مخاطب با موفقیت از گروه حذف شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-edit-group', 'id' => $group_id], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['add_contact_to_group'])) {
            $group_id = intval($_GET['id']);
            update_contact($_POST['contact_id'], null, null, $group_id);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'مخاطب با موفقیت به گروه اضافه شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-edit-group', 'id' => $group_id], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['update_contact'])) {
            $contact_id = intval($_GET['id']);
            update_contact($contact_id, $_POST['contact_email'], $_POST['contact_name'], $_POST['group_id']);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'مخاطب با موفقیت به روز شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-edit-contact', 'id' => $contact_id], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['add_contact'])) {
            add_contact($_POST['contact_email'], $_POST['contact_name'], $_POST['group_id']);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'مخاطب با موفقیت اضافه شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-contacts'], admin_url('admin.php')));
            exit;
        }
        elseif (isset($_POST['delete_contact'])) {
            delete_contact($_POST['contact_id']);
            add_settings_error('email_marketing_messages', 'email_marketing_message', 'مخاطب با موفقیت حذف شد.', 'updated');
            set_transient('settings_errors', get_settings_errors(), 30);
            wp_redirect(add_query_arg(['page' => 'email-marketing-contacts'], admin_url('admin.php')));
            exit;
        }
        elseif(isset($_POST['send_to_group']) || isset($_POST['send_to_contacts']) || isset($_POST['send_to_emails'])){
            $subject = sanitize_text_field($_POST['subject']);
            $body = wp_kses_post($_POST['body']);
            $template = sanitize_text_field($_POST['template']);
            add_filter('wp_mail_content_type', function() { return 'text/html'; });
            if (isset($_POST['send_to_group'])) {
                $group_id = intval($_POST['group_id']);
                send_email_to_group($group_id, $subject, $body, $template);
                add_settings_error('email_marketing_messages', 'email_marketing_message', 'ایمیل با موفقیت به گروه ارسال شد.', 'updated');
                set_transient('settings_errors', get_settings_errors(), 30);
                wp_redirect(add_query_arg(['page' => 'email-marketing-send'], admin_url('admin.php')));
                exit;
            } elseif (isset($_POST['send_to_contacts'])) {
                $contact_ids = array_map('intval', $_POST['contact_ids']);
                $contacts = array_map('get_contact', $contact_ids);
                $emails = array_column($contacts, 'email');
                send_email_to_contacts($emails, $subject, $body, $template);
                add_settings_error('email_marketing_messages', 'email_marketing_message', 'ایمیل با موفقیت به مخاطبین ارسال شد.', 'updated');
                set_transient('settings_errors', get_settings_errors(), 30);
                wp_redirect(add_query_arg(['page' => 'email-marketing-send'], admin_url('admin.php')));
                exit;
            } elseif (isset($_POST['send_to_emails'])) {
                $emails = array_map('sanitize_email', $_POST['emails']);
                send_email_to_contacts($emails, $subject, $body, $template);
                add_settings_error('email_marketing_messages', 'email_marketing_message', 'ایمیل با موفقیت به ایمیل ها ارسال شد.', 'updated');
                set_transient('settings_errors', get_settings_errors(), 30);
                wp_redirect(add_query_arg(['page' => 'email-marketing-send'], admin_url('admin.php')));
                exit;
            }
            remove_filter('wp_mail_content_type', function() { return 'text/html'; });
        }
        
    }
    // Display messages
    $messages = get_transient('settings_errors');
    if ($messages) {
        foreach ($messages as $message) {
            add_settings_error($message['setting'], $message['code'], $message['message'], $message['type']);
        }
        delete_transient('settings_errors');
    }
}
add_action('admin_init', 'wp_email_marketing_init');

// Activation and deactivation hooks.
register_activation_hook(__FILE__, 'email_marketing_activate');
register_deactivation_hook(__FILE__, 'email_marketing_deactivate');

function email_marketing_activate() {
    create_groups_table();
    create_contacts_table();
    create_email_history_table();
}

function email_marketing_deactivate() {
    // Cleanup or remove options if necessary.
}

function create_email_history_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_history';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        subject text NOT NULL,
        body text NOT NULL,
        recipients text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Register the email history table creation on activation
register_activation_hook(__FILE__, 'create_email_history_table');

// End output buffering and flush
ob_end_flush();
