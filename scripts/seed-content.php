<?php

declare(strict_types=1);

/**
 * Seed WordPress-owned content for Slingan environments.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$args = getopt('', [
    'wp-path:',
    'site-url::',
    'blogname::',
    'tagline::',
    'public::',
]);

$wpPath = rtrim((string) ($args['wp-path'] ?? getenv('WP_PATH') ?: getcwd()), '/');
$wpLoad = $wpPath . '/wp-load.php';

if (!is_file($wpLoad)) {
    fwrite(STDERR, "Could not find wp-load.php at {$wpLoad}\n");
    exit(1);
}

require $wpLoad;

$siteUrl = isset($args['site-url']) ? trim((string) $args['site-url']) : '';
$blogName = (string) ($args['blogname'] ?? 'Slingan');
$tagline = (string) ($args['tagline'] ?? 'Boardgame studio, community, and archive of play.');
$isPublic = (string) ($args['public'] ?? '0');

update_option('blogname', $blogName);
update_option('blogdescription', $tagline);
update_option('blog_public', $isPublic === '1' ? '1' : '0');

if ($siteUrl !== '') {
    update_option('home', $siteUrl);
    update_option('siteurl', $siteUrl);
}

update_option('permalink_structure', '/%postname%/');

if (wp_get_theme('slingan')->exists()) {
    switch_theme('slingan');
}

$frontPageId = slingan_upsert_page([
    'post_title' => 'Slingan',
    'post_name' => 'home',
    'post_content' => implode("\n\n", [
        'Slingan is a boardgame studio, community, and archive of play.',
        'Sessions, playlists, conventions, reviews, and practical tabletop culture.',
        'We do not stop playing because we grow old; we grow old because we stop playing.',
    ]),
    'post_status' => 'publish',
]);

slingan_upsert_page([
    'post_title' => 'Spelträffar',
    'post_name' => 'speltraffar',
    'post_content' => 'Upcoming and recurring boardgame sessions from Slingan.',
    'post_status' => 'publish',
]);

slingan_upsert_page([
    'post_title' => 'Spelade spel',
    'post_name' => 'spelade-spel',
    'post_content' => 'A record of games played, sessions remembered, and tables revisited.',
    'post_status' => 'publish',
]);

slingan_upsert_page([
    'post_title' => 'Playlists',
    'post_name' => 'playlists',
    'post_content' => 'Music for boardgame nights, campaigns, conventions, and strange creative focus.',
    'post_status' => 'publish',
]);

slingan_upsert_page([
    'post_title' => 'Vad är Slingan?',
    'post_name' => 'vad-ar-slingan',
    'post_content' => 'Slingan is a boardgame studio, community, and archive of play: reviews, gatherings, playlists, events, and tabletop culture.',
    'post_status' => 'publish',
]);

slingan_upsert_page([
    'post_title' => 'Betygssystemet',
    'post_name' => 'betygssystemet',
    'post_content' => 'How Slingan thinks about ratings, recommendations, and game impressions.',
    'post_status' => 'publish',
]);

slingan_upsert_page([
    'post_title' => 'Instagram',
    'post_name' => 'instagram',
    'post_content' => 'Photos and visual notes from the table, the shelf, conventions, and ongoing play.',
    'post_status' => 'publish',
]);

update_option('show_on_front', 'page');
update_option('page_on_front', $frontPageId);
flush_rewrite_rules(false);

echo "Seeded WordPress content for {$blogName}.\n";
echo "Front page ID: {$frontPageId}\n";

/**
 * @param array{post_title:string,post_name:string,post_content:string,post_status:string} $page
 */
function slingan_upsert_page(array $page): int
{
    $existing = get_page_by_path($page['post_name'], OBJECT, 'page');

    $postData = [
        'post_type' => 'page',
        'post_title' => $page['post_title'],
        'post_name' => $page['post_name'],
        'post_content' => $page['post_content'],
        'post_status' => $page['post_status'],
    ];

    if ($existing instanceof WP_Post) {
        update_post_meta($existing->ID, '_wp_page_template', 'default');
        $postData['ID'] = $existing->ID;
        $updated = wp_update_post($postData, true);

        if (is_wp_error($updated)) {
            fwrite(STDERR, $updated->get_error_message() . "\n");
            exit(1);
        }

        return (int) $updated;
    }

    $created = wp_insert_post($postData, true);

    if (is_wp_error($created)) {
        fwrite(STDERR, $created->get_error_message() . "\n");
        exit(1);
    }

    return (int) $created;
}
