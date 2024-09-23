<?php

function email_marketing_admin_menu() {
    add_menu_page('Email Marketing', 'ایمیل مارکتینگ', 'manage_options', 'email-marketing', 'email_marketing_dashboard', 'dashicons-email-alt' , 10);
    add_submenu_page('email-marketing', 'Manage Groups', ' مدیریت گروه ها ', 'manage_options', 'email-marketing-groups', 'email_marketing_manage_groups');
    add_submenu_page('email-marketing', 'Manage Contacts', 'مدیریت مخاطبین ', 'manage_options', 'email-marketing-contacts', 'email_marketing_manage_contacts');
    add_submenu_page(null, 'Edit Group', 'Edit Group', 'manage_options', 'email-marketing-edit-group', 'email_marketing_edit_group');
    add_submenu_page(null, 'Edit Contact', 'Edit Contact', 'manage_options', 'email-marketing-edit-contact', 'email_marketing_edit_contact');
    add_submenu_page('email-marketing', 'Send Mail', 'ارسال ایمیل ', 'manage_options', 'email-marketing-send', 'email_marketing_send_mail');
    add_submenu_page('email-marketing', 'Email History', ' تاریخچه ایمیل ', 'manage_options', 'email-marketing-history', 'email_marketing_email_history');
    add_submenu_page(null, 'View Email Details', 'View Email Details', 'manage_options', 'email-marketing-view-details', 'email_marketing_view_details');
}
add_action('admin_menu', 'email_marketing_admin_menu');


function email_marketing_dashboard() {
    echo '<h1>افزونه اختصاصی مدیریت مخاطبین و ارسال گروهی ایمیل </h1>';
}

function email_marketing_manage_groups() {
    include_once(plugin_dir_path(__FILE__) . 'admin/manage-groups.php');
}

function email_marketing_manage_contacts() {
    include_once(plugin_dir_path(__FILE__) . 'admin/manage-contacts.php');
}

function email_marketing_edit_group() {
    include_once(plugin_dir_path(__FILE__) . 'admin/edit-group.php');
}

function email_marketing_edit_contact() {
    include_once(plugin_dir_path(__FILE__) . 'admin/edit-contact.php');
}

function email_marketing_send_mail() {
    include_once(plugin_dir_path(__FILE__) . 'admin/send-mail.php');
}

function email_marketing_email_history() {
    include_once(plugin_dir_path(__FILE__) . 'admin/email-history.php');
}

function email_marketing_view_details() {
    include_once(plugin_dir_path(__FILE__) . 'admin/view-email-details.php');
}


?>
