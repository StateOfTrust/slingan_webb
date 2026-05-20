<?php

declare(strict_types=1);

function slingan_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
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
    wp_enqueue_style(
        'slingan-site',
        get_stylesheet_directory_uri() . '/assets/site.css',
        [],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'slingan_enqueue_assets');
