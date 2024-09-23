<?php
if (!isset($_GET['id'])) {
    wp_die('Invalid Contact ID');
}

$contact_id = intval($_GET['id']);
$contact = get_contact($contact_id);
$groups = get_groups();

if (!$contact) {
    wp_die('Contact not found');
}

// Display messages
$messages = get_transient('settings_errors');
if ($messages) {
    foreach ($messages as $message) {
        add_settings_error($message['setting'], $message['code'], $message['message'], $message['type']);
    }
    delete_transient('settings_errors');
}
?>
<div class="wrap">
    <?php settings_errors('email_marketing_messages'); ?>
    <h1 class="wp-heading-inline">ویرایش مخاطب</h1>
    <form method="POST" class="form-wrap">
        <input type="email" name="contact_email" value="<?php echo esc_attr($contact->email); ?>" required class="regular-text">
        <input type="text" name="contact_name" value="<?php echo esc_attr($contact->name); ?>" required class="regular-text">
        <select name="group_id" class="regular-text">
            <option value="">بدون گروه</option>
            <?php foreach ($groups as $group): ?>
                <option value="<?php echo $group->id; ?>" <?php if ($group->id == $contact->group_id) echo 'selected'; ?>><?php echo $group->name; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="update_contact" class="button button-primary"> بروز رسانی مخاطب</button>
    </form>
    <a href="<?php echo admin_url('admin.php?page=email-marketing-contacts'); ?>" class="button">بازگشت</a>
</div>
<style>
.form-wrap {
    margin-bottom: 20px;
}
</style>
