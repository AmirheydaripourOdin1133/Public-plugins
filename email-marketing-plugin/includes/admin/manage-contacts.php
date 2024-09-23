<?php
$success_message = '';
$error_message = '';

$search_query = '';
if (isset($_GET['s'])) {
    $search_query = sanitize_text_field($_GET['s']);
}

$contacts = get_contacts();
$groups = get_groups();

if ($search_query) {
    $contacts = array_filter($contacts, function ($contact) use ($search_query) {
        return stripos($contact->email, $search_query) !== false || stripos($contact->name, $search_query) !== false;
    });
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
    <h1 class="wp-heading-inline">مدیریت مخاطبین</h1>
    <form method="GET" class="search-form">
        <input type="hidden" name="page" value="email-marketing-contacts">
        <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" class="regular-text" placeholder="جستجو مخاطب . . . ">
        <button type="submit" class="button">جستجو </button>
    </form>
    <form method="POST" class="form-wrap">
        <input type="email" name="contact_email" placeholder="ایمیل" required class="regular-text">
        <input type="text" name="contact_name" placeholder="نام " required class="regular-text">
        <select name="group_id" class="regular-text">
            <option value="">بدون گروه </option>
            <?php foreach ($groups as $group): ?>
                <option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="add_contact" class="button button-primary">افزودن مخاطب </button>
    </form>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>ایمیل</th>
                <th>نام</th>
                <th>گروه</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($contacts as $contact): ?>
            <tr>
                <td><?php echo $contact->id; ?></td>
                <td><?php echo $contact->email; ?></td>
                <td><?php echo $contact->name; ?></td>
                <td>
                    <?php
                        foreach ($groups as $group) {
                            if ($group->id == $contact->group_id) {
                                echo $group->name;
                            }
                        }
                    ?>
                </td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=email-marketing-edit-contact&id=' . $contact->id); ?>" class="button">ویرایش</a>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="contact_id" value="<?php echo $contact->id; ?>">
                        <button type="submit" name="delete_contact" class="button button-secondary">حذف</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<style>
.form-wrap {
    margin-bottom: 20px;
}
</style>
