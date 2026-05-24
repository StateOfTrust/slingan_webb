<?php

declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $plays
 * @var array<int, array{thumbnail: string, image: string}> $images
 * @var string $eyebrow
 * @var string $title
 * @var string $intro
 * @var string $listUrl
 * @var string $username
 * @var string $locationFilterLabel
 * @var string $monthsFilterLabel
 * @var array<int, float> $collectionRatings
 */

if (! defined('ABSPATH')) {
    exit;
}

$hasHeader = $eyebrow !== ''
    || $title !== ''
    || $intro !== ''
    || $monthsFilterLabel !== ''
    || $locationFilterLabel !== '';
?>
<section
    class="slingan-tiles slingan-bgg-plays"
    <?php echo $hasHeader && $title !== '' ? 'aria-labelledby="slingan-bgg-plays-heading"' : 'aria-label="' . esc_attr__('Spel från BoardGameGeek', 'slingan-bgg') . '"'; ?>
>
    <?php if ($hasHeader) : ?>
        <header class="slingan-tiles__header">
            <?php if ($eyebrow !== '') : ?>
                <p class="slingan-tiles__eyebrow"><?php echo esc_html($eyebrow); ?></p>
            <?php endif; ?>
            <?php if ($title !== '') : ?>
                <h2 id="slingan-bgg-plays-heading" class="slingan-tiles__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($intro !== '') : ?>
                <p class="slingan-tiles__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
            <?php if ($monthsFilterLabel !== '') : ?>
                <p class="slingan-bgg-plays__filter"><?php echo esc_html($monthsFilterLabel); ?></p>
            <?php endif; ?>
            <?php if ($locationFilterLabel !== '') : ?>
                <p class="slingan-bgg-plays__filter">
                    <?php
                    printf(
                        /* translators: %s: location name(s) */
                        esc_html__('Filtrerat på plats: %s', 'slingan-bgg'),
                        esc_html($locationFilterLabel)
                    );
                    ?>
                </p>
            <?php endif; ?>
        </header>
    <?php endif; ?>

    <div class="slingan-tiles__grid">
        <?php foreach ($plays as $index => $play) : ?>
            <?php
            $gameId = (int) $play['game_id'];
            $gameName = (string) $play['game_name'];
            $playId = (int) $play['play_id'];
            $gameUrl = Slingan_Bgg_Plays::game_url($gameId, $gameName);
            $playUrl = Slingan_Bgg_Plays::play_url($playId);
            $color = Slingan_Bgg_Plays::tile_color((int) $index);
            $date = Slingan_Bgg_Plays::play_date_parts((string) $play['date']);
            $result = Slingan_Bgg_Plays::play_result_badge($play, $username);
            $meta = Slingan_Bgg_Plays::play_meta_line($play, $username);
            $excerpt = Slingan_Bgg_Plays::play_excerpt($play);
            $thumb = $images[$gameId]['thumbnail'] ?? ($images[$gameId]['image'] ?? '');
            $ratingLabel = Slingan_Bgg_Plays::collection_rating_label($gameId, $collectionRatings);
            ?>
            <article class="slingan-tile slingan-bgg-plays__tile" style="--tile-color:<?php echo esc_attr($color); ?>">
                <a class="slingan-tile__media" href="<?php echo esc_url($gameUrl); ?>" target="_blank" rel="noopener noreferrer">
                    <?php if ($thumb !== '') : ?>
                        <img
                            class="slingan-tile__img"
                            src="<?php echo esc_url($thumb); ?>"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        />
                    <?php else : ?>
                        <span class="slingan-tile__media-placeholder" aria-hidden="true"></span>
                    <?php endif; ?>
                    <?php if ($result['label'] !== '') : ?>
                        <span class="slingan-tile__type slingan-bgg-plays__result slingan-bgg-plays__result--<?php echo esc_attr($result['variant']); ?>">
                            <?php echo esc_html($result['label']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($date['label'] !== '') : ?>
                        <time class="slingan-tile__badge" datetime="<?php echo esc_attr($date['datetime']); ?>">
                            <?php echo esc_html($date['label']); ?>
                        </time>
                    <?php endif; ?>
                    <?php if ($ratingLabel !== '') : ?>
                        <span
                            class="slingan-bgg-plays__rating"
                            aria-label="<?php echo esc_attr(sprintf(
                                /* translators: %1$s: game name, %2$s: rating */
                                __('Ditt betyg på %1$s: %2$s', 'slingan-bgg'),
                                $gameName,
                                $ratingLabel
                            )); ?>"
                        >
                            <?php echo esc_html($ratingLabel); ?>
                        </span>
                    <?php endif; ?>
                    <span class="screen-reader-text"><?php echo esc_html($gameName); ?></span>
                </a>
                <div class="slingan-tile__body">
                    <h3 class="slingan-tile__title">
                        <a href="<?php echo esc_url($gameUrl); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($gameName); ?>
                        </a>
                    </h3>
                    <?php if ($meta !== '') : ?>
                        <p class="slingan-tile__when"><?php echo esc_html($meta); ?></p>
                    <?php endif; ?>
                    <?php if ($excerpt !== '') : ?>
                        <p class="slingan-tile__excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <div class="slingan-tile__actions">
                        <a class="slingan-btn slingan-btn--tile" href="<?php echo esc_url($playUrl); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Spelomgång', 'slingan-bgg'); ?>
                        </a>
                        <a class="slingan-tile__calendar-link" href="<?php echo esc_url($gameUrl); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('På BGG', 'slingan-bgg'); ?>
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($listUrl !== '') : ?>
        <p class="slingan-tiles__more">
            <a href="<?php echo esc_url($listUrl); ?>">
                <?php esc_html_e('Visa som lista', 'slingan-bgg'); ?>
            </a>
        </p>
    <?php endif; ?>

    <p class="slingan-bgg-plays__credit">
        <a href="https://boardgamegeek.com/" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Powered by BoardGameGeek', 'slingan-bgg'); ?>
        </a>
    </p>
</section>
