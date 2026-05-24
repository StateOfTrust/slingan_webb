<?php

declare(strict_types=1);

/**
 * Whether The Events Calendar "Include events in main blog loop" is on.
 */
function slingan_blog_roll_includes_events(): bool
{
    return slingan_events_calendar_active()
        && (bool) tribe_get_option('showEventsInMainLoop', false);
}

/**
 * Post types for the front tiles / blog roll.
 */
function slingan_blog_roll_post_types(): array
{
    $types = ['post'];

    if (slingan_blog_roll_includes_events()) {
        $types[] = 'tribe_events';
    }

    return $types;
}

/**
 * Shared blog roll query (posts only by default; + tribe_events when TEC setting is on).
 */
function slingan_blog_roll_query_args(array $overrides = []): array
{
    $args = [
        'post_type' => slingan_blog_roll_post_types(),
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'ignore_sticky_posts' => true,
    ];

    return array_merge($args, $overrides);
}

/**
 * Posts loaded for the carousel (more than four so arrows can slide 1–4, 2–5, …).
 */
function slingan_blog_roll_carousel_limit(): int
{
    $limit = (int) apply_filters('slingan_blog_roll_posts_per_page', 20);

    return max(4, min(50, $limit));
}

/**
 * @return WP_Post[]
 */
function slingan_get_blog_roll_posts(): array
{
    $query = new WP_Query(slingan_blog_roll_query_args([
        'posts_per_page' => slingan_blog_roll_carousel_limit(),
        'no_found_rows' => true,
    ]));

    return $query->posts;
}

/**
 * @return WP_Post[]
 */
function slingan_get_front_tile_posts(): array
{
    return slingan_get_blog_roll_posts();
}

/**
 * Keep the posts page (/blog/) in sync with front-page tiles.
 * When TEC "Include events in main blog loop" is on, leave the main query to TEC.
 */
function slingan_blog_roll_main_query(WP_Query $query): void
{
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    if (! $query->is_home() || $query->is_front_page()) {
        return;
    }

    if (slingan_blog_roll_includes_events()) {
        return;
    }

    foreach (slingan_blog_roll_query_args() as $key => $value) {
        $query->set($key, $value);
    }
}
add_action('pre_get_posts', 'slingan_blog_roll_main_query', 20);

/**
 * Short teaser for tile / blog-roll cards (manual excerpt or trimmed post body).
 */
function slingan_post_tile_teaser(WP_Post $post, int $wordLimit = 20): string
{
    $raw = trim($post->post_excerpt);
    if ($raw === '') {
        $raw = strip_shortcodes((string) $post->post_content);
        $raw = wp_strip_all_tags($raw);
        $raw = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');
    } else {
        $raw = wp_strip_all_tags($raw);
    }

    if ($raw === '') {
        return '';
    }

    return wp_trim_words($raw, $wordLimit, ' …');
}

/**
 * Category label for the red tile tag (blog category or event category).
 */
function slingan_post_tile_category_label(WP_Post $post): string
{
    if ($post->post_type === 'tribe_events') {
        $terms = get_the_terms($post, 'tribe_events_cat');
        if (is_array($terms) && $terms !== []) {
            return (string) $terms[0]->name;
        }

        return '';
    }

    $categories = get_the_category($post->ID);
    if (! is_array($categories) || $categories === []) {
        return '';
    }

    foreach ($categories as $category) {
        if ($category->slug !== 'uncategorized') {
            return (string) $category->name;
        }
    }

    return (string) $categories[0]->name;
}
