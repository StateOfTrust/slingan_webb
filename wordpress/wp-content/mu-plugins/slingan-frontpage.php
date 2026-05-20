<?php

/**
 * Plugin Name: Slingan front page
 * Description: Board Games front page without the WooCommerce / product strip (“Från gemenskapen”).
 * Version: 1.0.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('SLINGAN_FRONTPAGE_DIR', __DIR__ . '/slingan-frontpage');

add_filter('template_include', 'slingan_frontpage_template_include', 99);

function slingan_frontpage_queried_page_id(): int
{
    if (! is_front_page()) {
        return 0;
    }
    $obj = get_queried_object();

    return ($obj instanceof WP_Post && $obj->post_type === 'page') ? (int) $obj->ID : 0;
}

function slingan_frontpage_template_include(string $template): string
{
    if (get_option('stylesheet') !== 'board-games') {
        return $template;
    }

    $page_id = slingan_frontpage_queried_page_id();
    if ($page_id === 0 || get_page_template_slug($page_id) !== 'frontpage.php') {
        return $template;
    }

    $custom = SLINGAN_FRONTPAGE_DIR . '/frontpage.php';

    return is_readable($custom) ? $custom : $template;
}
