<?php

declare(strict_types=1);

/**
 * Tile accent colours (shared by blog cards and event cards).
 */
function slingan_front_tile_colors(): array
{
    return ['#d24749', '#1e1e1e', '#787878', '#a63a3c'];
}

function slingan_front_tile_color(int $index): string
{
    $colors = slingan_front_tile_colors();
    $i = max(0, $index) % count($colors);

    return $colors[$i];
}
