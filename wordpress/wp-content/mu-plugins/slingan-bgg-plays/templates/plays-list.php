<?php

declare(strict_types=1);

/**
 * @var array{items: list<array<string, mixed>>, total: int, total_pages: int} $data
 * @var string $username
 * @var string $title
 * @var string $intro
 * @var string $locationFilterLabel
 * @var string $monthsFilterLabel
 * @var string $pagination
 * @var int $listPage
 * @var int $perPage
 * @var bool $showMeta
 * @var array<int, float> $collectionRatings
 */

if (! defined('ABSPATH')) {
    exit;
}

$hasHeader = $title !== ''
    || $intro !== ''
    || $monthsFilterLabel !== ''
    || $locationFilterLabel !== ''
    || $showMeta;
?>
<section
    class="slingan-bgg-plays-list"
    <?php echo $title !== '' ? 'aria-labelledby="slingan-bgg-plays-list-heading"' : 'aria-label="' . esc_attr__('Spellista från BoardGameGeek', 'slingan-bgg') . '"'; ?>
>
    <?php if ($hasHeader) : ?>
        <header class="slingan-bgg-plays-list__header">
            <?php if ($title !== '') : ?>
                <h2 id="slingan-bgg-plays-list-heading" class="slingan-bgg-plays-list__title"><?php echo esc_html($title); ?></h2>
            <?php endif; ?>
            <?php if ($intro !== '') : ?>
                <p class="slingan-bgg-plays-list__intro"><?php echo esc_html($intro); ?></p>
            <?php endif; ?>
            <?php if ($monthsFilterLabel !== '') : ?>
                <p class="slingan-bgg-plays__filter"><?php echo esc_html($monthsFilterLabel); ?></p>
            <?php endif; ?>
            <?php if ($locationFilterLabel !== '') : ?>
                <p class="slingan-bgg-plays__filter">
                    <?php
                    printf(
                        esc_html__('Filtrerat på plats: %s', 'slingan-bgg'),
                        esc_html($locationFilterLabel)
                    );
                    ?>
                </p>
            <?php endif; ?>
            <?php if ($showMeta) : ?>
                <p class="slingan-bgg-plays-list__meta">
                    <?php
                    printf(
                        /* translators: 1: total plays, 2: current page, 3: total pages */
                        esc_html__('Visar %1$d spel totalt · sida %2$d av %3$d', 'slingan-bgg'),
                        (int) $data['total'],
                        (int) $listPage,
                        max(1, (int) $data['total_pages'])
                    );
                    ?>
                </p>
            <?php endif; ?>
        </header>
    <?php endif; ?>

    <div class="slingan-bgg-plays-list__table-wrap">
        <table class="slingan-bgg-plays-list__table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('Datum', 'slingan-bgg'); ?></th>
                    <th scope="col"><?php esc_html_e('Spel', 'slingan-bgg'); ?></th>
                    <th scope="col"><?php esc_html_e('Vinnare', 'slingan-bgg'); ?></th>
                    <th scope="col"><?php esc_html_e('Betyg', 'slingan-bgg'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['items'] as $play) : ?>
                    <?php
                    $gameId = (int) $play['game_id'];
                    $gameName = (string) $play['game_name'];
                    $playId = (int) $play['play_id'];
                    $gameUrl = Slingan_Bgg_Plays::game_url($gameId, $gameName);
                    $playUrl = Slingan_Bgg_Plays::play_url($playId);
                    $date = Slingan_Bgg_Plays_List::list_date_cell((string) $play['date']);
                    $result = Slingan_Bgg_Plays::play_result_badge($play, $username);
                    $ratingLabel = Slingan_Bgg_Plays::collection_rating_label((int) $play['game_id'], $collectionRatings);
                    ?>
                    <tr>
                        <td>
                            <?php if ($date['datetime'] !== '') : ?>
                                <time datetime="<?php echo esc_attr($date['datetime']); ?>"><?php echo esc_html($date['label']); ?></time>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($gameUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($gameName); ?></a>
                        </td>
                        <td class="slingan-bgg-plays-list__winner slingan-bgg-plays-list__winner--<?php echo esc_attr($result['variant']); ?>">
                            <?php if ($result['label'] !== '') : ?>
                                <?php echo esc_html($result['label']); ?>
                            <?php else : ?>
                                <span class="slingan-bgg-plays-list__muted"><?php esc_html_e('—', 'slingan-bgg'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ratingLabel !== '') : ?>
                                <?php echo esc_html($ratingLabel); ?>
                            <?php else : ?>
                                <span class="slingan-bgg-plays-list__muted"><?php esc_html_e('—', 'slingan-bgg'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $pagination;
    ?>

    <p class="slingan-bgg-plays__credit">
        <a href="https://boardgamegeek.com/" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('Powered by BoardGameGeek', 'slingan-bgg'); ?>
        </a>
    </p>
</section>
