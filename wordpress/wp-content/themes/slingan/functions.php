<?php

declare(strict_types=1);

function slingan_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'slingan_setup');
