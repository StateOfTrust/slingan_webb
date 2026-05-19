<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$args = getopt('', [
    'wp-path:',
    'site-url:',
    'admin-user:',
    'admin-password:',
    'admin-email:',
]);

$wpPath = rtrim((string) ($args['wp-path'] ?? ''), '/');
$siteUrl = (string) ($args['site-url'] ?? 'http://slingan.local');
$adminUser = (string) ($args['admin-user'] ?? 'ola');
$adminPassword = (string) ($args['admin-password'] ?? 'othello');
$adminEmail = (string) ($args['admin-email'] ?? 'ola@slingan.local');

if ($wpPath === '') {
    fwrite(STDERR, "Missing --wp-path\n");
    exit(1);
}

$wpLoad = $wpPath . '/wp-load.php';
if (!is_file($wpLoad)) {
    fwrite(STDERR, "Could not find wp-load.php at {$wpLoad}\n");
    exit(1);
}

define('WP_INSTALLING', true);

require $wpLoad;
require ABSPATH . 'wp-admin/includes/upgrade.php';

global $wpdb;

$wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
$tables = $wpdb->get_col('SHOW TABLES');
if (is_array($tables)) {
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }
}
$wpdb->query('SET FOREIGN_KEY_CHECKS = 1');

wp_cache_flush();
wp_install('Slingan', $adminUser, $adminEmail, false, '', $adminPassword, 'en_US');
update_option('home', $siteUrl);
update_option('siteurl', $siteUrl);

if (wp_get_theme('slingan')->exists()) {
    switch_theme('slingan');
}

echo "Reinstalled WordPress at {$siteUrl}\n";
echo "Admin user: {$adminUser}\n";
