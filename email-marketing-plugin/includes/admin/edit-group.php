<?php
if (!isset($_GET['id'])) {
    wp_die('Invalid Group ID');
}

$group_id = intval($_GET['id']);
$group = get_group($group_id);
$contacts = get_contacts();

if (!$group) {
    wp_die('Group not found');
}


$group_contacts = array_filter($contacts, function ($contact) use ($group_id) {
    return $contact->group_id == $group_id;
});


?>
<div class="wrap">
    <?php settings_errors('email_marketing_messages'); ?>
    <h1 class="wp-heading-inline"> ویرایش گروه </h1>
    <form method="POST" class="form-wrap">
        <input type="text" name="group_name" value="<?php echo esc_attr($group->name); ?>" required class="regular-text">
        <button type="submit" name="update_group" class="button button-primary">بروزرسانی گروه </button>
    </form>
    <h2>مخاطبین در گروه</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>ایمیل </th>
                <th>نام</th>
                <th>عملکرد</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($group_contacts as $contact): ?>
            <tr>
                <td><?php echo $contact->id; ?></td>
                <td><?php echo $contact->email; ?></td>
                <td><?php echo $contact->name; ?></td>
                <td>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="contact_id" value="<?php echo $contact->id; ?>">
                        <button type="submit" name="remove_contact" class="button button-secondary">حذف از گروه</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h2>افزودن مخاطبین به گروه</h2>
    <form method="POST" class="form-wrap">
        <select name="contact_id" class="regular-text">
            <?php foreach ($contacts as $contact): ?>
                <?php if ($contact->group_id != $group_id): ?>
                    <option value="<?php echo $contact->id; ?>"><?php echo $contact->email . ' (' . $contact->name . ')'; ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_contact_to_group" class="button button-primary">افزودن به گروه</button>
    </form>
    <a href="<?php echo admin_url('admin.php?page=email-marketing-groups'); ?>" class="button">بازگشت به گروه ها</a>
</div>
<style>
.form-wrap {
    margin-bottom: 20px;
}
</style>
