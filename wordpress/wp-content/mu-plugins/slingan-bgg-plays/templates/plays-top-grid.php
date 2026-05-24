<?php

declare(strict_types=1);

/**
 * @var list<array{game_id: int, game_name: string, play_count: int, latest_play: array<string, mixed>}> $topGames
 * @var array<int, array{thumbnail: string, image: string}> $images
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="slingan-bgg-top" aria-label="<?php esc_attr_e('Mest spelade spel', 'slingan-bgg'); ?>">
    <div class="slingan-bgg-top__grid">
        <?php foreach ($topGames as $row) : ?>
            <?php
            $gameId = (int) $row['game_id'];
            $gameName = (string) $row['game_name'];
            $gameUrl = Slingan_Bgg_Plays::game_url($gameId, $gameName);
            $thumb = $images[$gameId]['thumbnail'] ?? ($images[$gameId]['image'] ?? '');
            ?>
            <a
                class="slingan-bgg-top__cell"
                href="<?php echo esc_url($gameUrl); ?>"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="<?php echo esc_attr($gameName); ?>"
            >
                <?php if ($thumb !== '') : ?>
                    <img
                        class="slingan-bgg-top__img"
                        src="<?php echo esc_url($thumb); ?>"
                        alt=""
                        loading="lazy"
                        decoding="async"
                    />
                <?php else : ?>
                    <span class="slingan-bgg-top__img-placeholder" aria-hidden="true"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
