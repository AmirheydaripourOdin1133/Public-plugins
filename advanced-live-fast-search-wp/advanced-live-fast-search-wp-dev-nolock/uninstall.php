<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

$mfs_options = array('mfs_settings', 'mfs_cache_info', 'mfs_cache_name', 'mfs_rewrite_flushed');

foreach ($mfs_options as $mfs_option) {
	delete_option($mfs_option);
}

delete_transient('mfs_build_lock');
delete_transient('mfs_just_activated');
delete_transient('mfs_cache_info');

wp_clear_scheduled_hook('mfs_refresh_event');

$mfs_uploads = wp_upload_dir();
$mfs_dir     = trailingslashit($mfs_uploads['basedir']) . 'advanced-live-fast-search';

if (is_dir($mfs_dir)) {
	foreach (glob(trailingslashit($mfs_dir) . '*') as $mfs_file) {
		if (is_file($mfs_file)) {
			@unlink($mfs_file);
		}
	}
	@rmdir($mfs_dir);
}
