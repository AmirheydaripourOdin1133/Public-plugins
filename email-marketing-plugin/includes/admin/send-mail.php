<?php

$groups = get_groups();
$contacts = get_contacts();

// Get email templates
$template_dir = plugin_dir_path(__FILE__) . '../../email-templates/';
if (file_exists($template_dir)) {
    $templates = array_diff(scandir($template_dir), array('..', '.'));
    $template_options = array_map(function($template) {
        return pathinfo($template, PATHINFO_FILENAME);
    }, $templates);
} else {
    $template_options = [];
    error_log("Template directory not found: " . $template_dir);
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
    <h1 class="wp-heading-inline">ارسال ایمیل</h1>
    <form method="POST" class="form-wrap">

        <h2>قالب ایمیل </h2>
        <select name="template" class="regular-text">
            <?php if (!empty($template_options)): ?>
                <?php foreach ($template_options as $template): ?>
                    <option value="<?php echo $template; ?>"><?php echo $template; ?></option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="">هیچ قالبی یافت نشد ! </option>
            <?php endif; ?>
        </select>
        <h2>موضوع ایمیل : </h2>
        <input type="text" name="subject" required class="regular-text">
        <h2>محتوای ایمیل : </h2>
        <?php
        wp_editor('', 'body');
        ?>
        
        <h2>حالت ارسال را انتخاب کنید</h2>
        <select id="send_mode" class="regular-text">
            <option value="group">ارسال گروهی </option>
            <option value="contacts">ارسال از طریق مخاطبین</option>
            <option value="emails">ارسال به ایمیل </option>
        </select>
        <div id="group_mode" class="send-mode">
            <h2>ارسال گروهی </h2>
            <select name="group_id" class="regular-text">
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group->id; ?>"><?php echo $group->name; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="send_to_group" class="button button-primary">ارسال گروهی </button>
        </div>
        <div id="contacts_mode" class="send-mode" style="display:none;">
            <h2>ارسال از طریق مخاطبین</h2>
            <select name="contact_ids[]" class="regular-text" multiple>
                <?php foreach ($contacts as $contact): ?>
                    <option value="<?php echo $contact->id; ?>"><?php echo $contact->email . ' (' . $contact->name . ')'; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="send_to_contacts" class="button button-primary">ارسال از طریق مخاطبین</button>
        </div>
        <div id="emails_mode" class="send-mode" style="display:none;">
            <h2>ارسال به ایمیل </h2>
            <div id="emails_container">
                <input type="email" name="emails[]" placeholder="Email" class="regular-text">
            </div>
            <button type="button" id="add_email_field" class="button">افزودن فیلد ایمیل</button>
            <button type="submit" name="send_to_emails" class="button button-primary">ارسال به ایمیل </button>
        </div>
    </form>
</div>
<script>
document.getElementById('send_mode').addEventListener('change', function() {
    var selectedMode = this.value;
    document.querySelectorAll('.send-mode').forEach(function(el) {
        el.style.display = 'none';
    });
    document.getElementById(selectedMode + '_mode').style.display = 'block';
});

document.getElementById('add_email_field').addEventListener('click', function() {
    var container = document.getElementById('emails_container');
    var input = document.createElement('input');
    input.type = 'email';
    input.name = 'emails[]';
    input.placeholder = 'Email';
    input.className = 'regular-text';
    container.appendChild(input);
});
</script>
