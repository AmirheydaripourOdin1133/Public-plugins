<?php

function create_contacts_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_contacts';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(100) NOT NULL,
        name varchar(100) NOT NULL,
        group_id mediumint(9),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_contacts_table');

function add_contact($email, $name, $group_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_contacts';
    $wpdb->insert($table_name, ['email' => $email, 'name' => $name, 'group_id' => $group_id]);
}

function get_contacts() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_contacts';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

function get_contact($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_contacts';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

function delete_contact($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_contacts';
    $wpdb->delete($table_name, ['id' => $id]);
}

function update_contact($id, $email = null, $name = null, $group_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_contacts';
    $data = [];
    if ($email !== null) $data['email'] = $email;
    if ($name !== null) $data['name'] = $name;
    if ($group_id !== null) $data['group_id'] = $group_id;
    $wpdb->update($table_name, $data, ['id' => $id]);
}
?>
