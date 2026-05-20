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
$tagline = (string) ($args['tagline'] ?? 'Brädspel, gemenskap och söndagsspel i Malmö.');
$isPublic = (string) ($args['public'] ?? '0');

update_option('blogname', $blogName);
update_option('blogdescription', $tagline);
update_option('blog_public', $isPublic === '1' ? '1' : '0');

if ($siteUrl !== '') {
    update_option('home', $siteUrl);
    update_option('siteurl', $siteUrl);
}

update_option('permalink_structure', '/%postname%/');

$useBoardGames = wp_get_theme('board-games')->exists();
$homeTemplate = $useBoardGames ? 'frontpage.php' : 'default';

$frontPageId = slingan_upsert_page([
    'post_title' => 'Slingan',
    'post_name' => 'home',
    'post_content' => '',
    'post_status' => 'publish',
    'page_template' => $homeTemplate,
]);

$aboutContent = <<<'HTML'
<p>Slingan är inte en butik och inte ett företag — det är bordet där vi faktiskt träffas. Vi spelar för att umgås, skratta, tänka till och dela saker vi gillar. Här får du vara ny, vara nyfiken och få tid att lära reglerna utan stress.</p>
<p>Vi träffas regelbundet i Malmö. Stämningen är avslappnad: alla åldrar och erfarenhetsnivåer är välkomna. Ta med ett eget spel eller hoppa in i något någon annan bär fram — det viktiga är att vi spelar tillsammans.</p>
<p><strong>Varför lägga en söndag här?</strong> För att det känns som en klubbstuga: riktiga människor, riktiga tärningar, och samtal som fortsätter efter sista poängräkningen.</p>
HTML;

$eventsPageId = slingan_upsert_page([
    'post_title' => 'Spelträffar',
    'post_name' => 'speltraffar',
    'post_content' => '<p>Här samlar vi kommande och återkommande spelträffar. Uppdatera sidan när ni bokat lokal eller satt tema för kvällen.</p>',
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

$aboutPageId = slingan_upsert_page([
    'post_title' => 'Vad är Slingan?',
    'post_name' => 'vad-ar-slingan',
    'post_content' => $aboutContent,
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

$joinPageId = slingan_upsert_page([
    'post_title' => 'Gå med',
    'post_name' => 'join-us',
    'post_content' => <<<'HTML'
<p>Vill du hänga med? Bästa sättet är att komma förbi på en spelträff eller skriva en rad i Discord — vi har inga formella medlemskapskrav.</p>
<p>Om du är helt ny: säg till när du kommer så hjälper vi dig hitta ett bord och ett spel som passar.</p>
HTML,
    'post_status' => 'publish',
]);

$blogPageId = slingan_upsert_page([
    'post_title' => 'Blogg',
    'post_name' => 'blog',
    'post_content' => '<p>Nyheter, rapporter från bordet och längre texter från gemenskapen.</p>',
    'post_status' => 'publish',
]);

if ($useBoardGames) {
    switch_theme('board-games');
    remove_theme_mod('custom_logo');
    slingan_remove_legacy_slingan_mu_theme_mods();
    slingan_seed_board_games_visual_mods();
    slingan_seed_board_games_frontpage_banner();
    slingan_replace_helsingborg_in_all_theme_mods();
} elseif (wp_get_theme('slingan')->exists()) {
    switch_theme('slingan');
    remove_theme_mod('custom_logo');
}

if ($useBoardGames) {
    slingan_seed_primary_menu($frontPageId, $aboutPageId, $eventsPageId, $blogPageId, $joinPageId);
}

update_option('show_on_front', 'page');
update_option('page_on_front', $frontPageId);
update_option('page_for_posts', $blogPageId);

flush_rewrite_rules(false);

echo "Seeded WordPress content for {$blogName}.\n";
echo "Front page ID: {$frontPageId}\n";
if ($useBoardGames) {
    echo "Active theme: board-games (front page: banner only; product strip disabled via slingan-frontpage MU plugin).\n";
    echo "Tune copy and images under Utseende → Anpassa → Frontpage settings (Banner) and Color settings.\n";
} else {
    echo "Active theme: slingan (install Board Games in Local to use the full commercial theme layout).\n";
}

/**
 * Warm accents; no corporate purple. Header CTA points at Join page.
 */
function slingan_seed_board_games_visual_mods(): void
{
    if (get_option('stylesheet') !== 'board-games') {
        return;
    }
    set_theme_mod('board_games_global_color', '#b85c38');
    set_theme_mod('board_games_secondary_color', '#3d6b55');
    set_theme_mod('board_games_background_color', '#faf6f0');
    set_theme_mod('board_games_header_section_button_text', 'Gå med');
    set_theme_mod('board_games_header_section_button_url', home_url('/join-us/'));
    set_theme_mod('board_games_theme_loader', 0);
}

/**
 * Remove theme mods left from the old Slingan clubhouse MU plugin (Customizer clutter).
 */
function slingan_remove_legacy_slingan_mu_theme_mods(): void
{
    if (get_option('stylesheet') !== 'board-games') {
        return;
    }
    $mods = get_theme_mods();
    if (! is_array($mods)) {
        return;
    }
    foreach (array_keys($mods) as $key) {
        if (is_string($key) && str_starts_with($key, 'slingan_')) {
            remove_theme_mod($key);
        }
    }
    delete_option('slingan_clubhouse_defaults_applied');
}

/**
 * Use the Board Games theme front page as designed: banner + four promo tiles + product strip.
 * Copy is Swedish; URLs point at seeded pages. Re-run seed to reset (same as other seed options).
 */
function slingan_seed_board_games_frontpage_banner(): void
{
    if (get_option('stylesheet') !== 'board-games') {
        return;
    }

    $turi = get_template_directory_uri();
    $discord = 'https://discord.gg/REPLACE_ME';

    set_theme_mod('board_games_header_slider', 1);

    set_theme_mod('board_games_banner_section_heading_1', 'Brädspel och gemenskap');
    set_theme_mod('board_games_banner_section_heading_2', 'Slingan — Malmö');
    set_theme_mod(
        'board_games_banner_section_content',
        'En liten spelgemenskap: kom förbi på en spelträff, chatta i Discord, eller läs mer om oss.'
    );
    set_theme_mod('board_games_banner_section_button_text', 'Kommande spelträffar');
    set_theme_mod('board_games_banner_section_button_url', home_url('/speltraffar/'));

    set_theme_mod('board_games_banner_section_post_1_heading', 'När vi ses');
    set_theme_mod('board_games_banner_section_post_1_button_text', 'Spelträffar');
    set_theme_mod('board_games_banner_section_post_1_button_url', home_url('/speltraffar/'));
    set_theme_mod('board_games_banner_section_post_1_image', $turi . '/assets/images/post1.png');

    set_theme_mod('board_games_banner_section_post_2_heading', 'Vad är Slingan?');
    set_theme_mod('board_games_banner_section_post_2_button_text', 'Läs mer');
    set_theme_mod('board_games_banner_section_post_2_button_url', home_url('/vad-ar-slingan/'));
    set_theme_mod('board_games_banner_section_post_2_image', $turi . '/assets/images/post2.png');

    set_theme_mod('board_games_banner_section_post_3_heading', 'Gå med');
    set_theme_mod('board_games_banner_section_post_3_button_text', 'Välkommen');
    set_theme_mod('board_games_banner_section_post_3_button_url', home_url('/join-us/'));
    set_theme_mod('board_games_banner_section_post_3_image', $turi . '/assets/images/post3.png');

    set_theme_mod('board_games_banner_section_post_4_heading', 'Discord');
    set_theme_mod('board_games_banner_section_post_4_button_text', 'Öppna Discord');
    set_theme_mod('board_games_banner_section_post_4_button_url', $discord);
    set_theme_mod('board_games_banner_section_post_4_image', $turi . '/assets/images/post4.png');

    // No WooCommerce on this site — hide product strip headings if theme still reads them elsewhere.
    set_theme_mod('board_games_product_section_short_title', '');
    set_theme_mod('board_games_product_section_heading', '');
    set_theme_mod('board_games_featured_product_category', '');
}

/**
 * Replace “Helsingborg” in any theme mod string (idempotent).
 */
function slingan_replace_helsingborg_in_all_theme_mods(): void
{
    if (get_option('stylesheet') !== 'board-games') {
        return;
    }
    $mods = get_theme_mods();
    if (! is_array($mods)) {
        return;
    }
    foreach ($mods as $key => $val) {
        if (! is_string($key) || ! is_string($val) || $val === '' || stripos($val, 'Helsingborg') === false) {
            continue;
        }
        set_theme_mod($key, str_ireplace('Helsingborg', 'Malmö', $val));
    }
}

/**
 * Primary navigation for Board Games theme location `board-games-primary-menu`.
 */
function slingan_seed_primary_menu(int $homeId, int $aboutId, int $eventsId, int $blogId, int $joinId): void
{
    if (get_option('stylesheet') !== 'board-games') {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/nav-menu.php';

    $name = 'Slingan';
    $existing = wp_get_nav_menu_object($name);
    if ($existing) {
        wp_delete_nav_menu($existing->term_id);
    }

    $menuId = wp_create_nav_menu($name);
    if (is_wp_error($menuId)) {
        fwrite(STDERR, $menuId->get_error_message() . "\n");

        return;
    }
    $menuId = (int) $menuId;

    $pos = 0;
    $add = static function (array $args) use ($menuId, &$pos): void {
        ++$pos;
        $args['menu-item-position'] = $pos;
        $args['menu-item-status'] = 'publish';
        wp_update_nav_menu_item($menuId, 0, $args);
    };

    $add([
        'menu-item-title' => 'Hem',
        'menu-item-object-id' => $homeId,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type',
    ]);
    $add([
        'menu-item-title' => 'Om Slingan',
        'menu-item-object-id' => $aboutId,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type',
    ]);
    $add([
        'menu-item-title' => 'Spelträffar',
        'menu-item-object-id' => $eventsId,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type',
    ]);
    $add([
        'menu-item-title' => 'Blogg',
        'menu-item-object-id' => $blogId,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type',
    ]);
    $add([
        'menu-item-title' => 'Gå med',
        'menu-item-object-id' => $joinId,
        'menu-item-object' => 'page',
        'menu-item-type' => 'post_type',
    ]);
    $add([
        'menu-item-title' => 'Discord',
        'menu-item-url' => 'https://discord.gg/REPLACE_ME',
        'menu-item-type' => 'custom',
    ]);

    $locations = get_theme_mod('nav_menu_locations', []);
    if (! is_array($locations)) {
        $locations = [];
    }
    $locations['board-games-primary-menu'] = $menuId;
    set_theme_mod('nav_menu_locations', $locations);
}

/**
 * @param array{post_title:string,post_name:string,post_content:string,post_status:string,page_template?:string} $page
 */
function slingan_upsert_page(array $page): int
{
    $existing = get_page_by_path($page['post_name'], OBJECT, 'page');
    $template = $page['page_template'] ?? 'default';

    $postData = [
        'post_type' => 'page',
        'post_title' => $page['post_title'],
        'post_name' => $page['post_name'],
        'post_content' => $page['post_content'],
        'post_status' => $page['post_status'],
    ];

    if ($existing instanceof WP_Post) {
        update_post_meta($existing->ID, '_wp_page_template', $template);
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

    $created = (int) $created;
    update_post_meta($created, '_wp_page_template', $template);

    return $created;
}
