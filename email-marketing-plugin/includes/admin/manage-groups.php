<?php
$success_message = '';
$error_message = '';


$search_query = '';
if (isset($_GET['s'])) {
    $search_query = sanitize_text_field($_GET['s']);
}

$groups = get_groups();

if ($search_query) {
    $groups = array_filter($groups, function ($group) use ($search_query) {
        return stripos($group->name, $search_query) !== false;
    });
}


?>
<div class="wrap">
    <?php settings_errors('email_marketing_messages'); ?>
    <h1 class="wp-heading-inline"> مدیریت گروه ها</h1>
    <form method="GET" class="search-form">
        <input type="hidden" name="page" value="email-marketing-groups">
        <input type="search" name="s" value="<?php echo esc_attr($search_query); ?>" class="regular-text" placeholder="جستجو گروه ها  ">
        <button type="submit" class="button">جستجو</button>
    </form>
    <form method="POST" class="form-wrap">
        <input type="text" name="group_name" placeholder="نام گروه" required class="regular-text">
        <button type="submit" name="add_group" class="button button-primary">افزودن گروه </button>
    </form>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>نام</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($groups as $group): ?>
            <tr>
                <td><?php echo $group->id; ?></td>
                <td><?php echo $group->name; ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=email-marketing-edit-group&id=' . $group->id); ?>" class="button">ویرایش</a>
                    <form method="POST" style="display:inline-block;">
                        <input type="hidden" name="group_id" value="<?php echo $group->id; ?>">
                        <button type="submit" name="delete_group" class="button button-secondary">حذف</button>
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