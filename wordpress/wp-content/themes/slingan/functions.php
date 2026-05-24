<?php

declare(strict_types=1);

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/front-post-tiles.php';
require_once get_template_directory() . '/inc/blog-roll.php';
require_once get_template_directory() . '/inc/events-calendar.php';
require_once get_template_directory() . '/inc/post-event-link.php';
require_once get_template_directory() . '/inc/rsvp-modal.php';

function slingan_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height' => 120,
        'width' => 320,
        'flex-height' => true,
        'flex-width' => true,
    ]);
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    register_nav_menus([
        'primary' => __('Primary menu', 'slingan'),
    ]);
}
add_action('after_setup_theme', 'slingan_setup');

function slingan_enqueue_assets(): void
{
    $themeVersion = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'slingan-site',
        get_stylesheet_directory_uri() . '/assets/site.css',
        [],
        $themeVersion
    );

    if (is_front_page()) {
        wp_enqueue_script(
            'slingan-blog-roll-carousel',
            get_stylesheet_directory_uri() . '/assets/blog-roll-carousel.js',
            [],
            $themeVersion,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'slingan_enqueue_assets');

/**
 * Header logo: theme-bundled asset (avoids stale custom_logo from mixed Local DBs).
 * Utseende → Anpassa → Webbplatsens identitet can still replace via custom_logo when set deliberately.
 */
function slingan_header_logo_url(): string
{
    $bundled = get_stylesheet_directory_uri() . '/assets/slingan-logo.png';

    if (! has_custom_logo()) {
        return $bundled;
    }

    $logoId = (int) get_theme_mod('custom_logo');
    if ($logoId <= 0) {
        return $bundled;
    }

    $src = wp_get_attachment_image_url($logoId, 'full');

    return is_string($src) && $src !== '' ? $src : $bundled;
}

/**
 * Hero band colour (always State of Trust red unless overridden in Customizer).
 */
function slingan_hero_background_color(): string
{
    $color = get_theme_mod('slingan_hero_bg_color', '#d24749');
    if (! is_string($color) || ! preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
        return '#d24749';
    }

    return $color;
}

/**
 * Resolve theme mod URLs that may be stored as paths.
 */
function slingan_theme_mod_url(string $mod, string $default = ''): string
{
    $val = get_theme_mod($mod, $default);
    if (! is_string($val) || $val === '') {
        return '';
    }
    if (str_starts_with($val, 'http') || str_starts_with($val, '#')) {
        return $val;
    }

    return home_url($val);
}
