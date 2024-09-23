<?php

function create_groups_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_groups';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_groups_table');

function add_group($name) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_groups';
    $wpdb->insert($table_name, ['name' => $name]);
}

function get_groups() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_groups';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

function get_group($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_groups';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

function delete_group($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_groups';
    $wpdb->delete($table_name, ['id' => $id]);
}

function update_group($id, $name) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'email_groups';
    $wpdb->update($table_name, ['name' => $name], ['id' => $id]);
}
?>
