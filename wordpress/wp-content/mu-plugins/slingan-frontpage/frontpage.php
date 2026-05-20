<?php

/**
 * Front Page Template — banner only (no WooCommerce / product block).
 *
 * @package Board Games
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

get_header();

if (function_exists('board_games_main_slider')) {
    echo board_games_main_slider();
}

get_footer();
