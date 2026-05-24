<?php

declare(strict_types=1);

/**
 * Four-tile blog roll carousel: 1–4, then 2–5, 3–6, …
 *
 * @var WP_Post[]|null $posts
 */

$posts = isset($posts) && is_array($posts) ? $posts : slingan_get_blog_roll_posts();

if ($posts === []) {
    return;
}

$postCount = count($posts);
$hasMore = $postCount > 4;
$padCount = 0;
if ($hasMore) {
    $remainder = $postCount % 4;
    $padCount = $remainder === 0 ? 0 : 4 - $remainder;
}
?>
<div class="slingan-blog-roll" data-slingan-blog-roll>
    <button
        type="button"
        class="slingan-blog-roll__nav slingan-blog-roll__nav--prev"
        aria-label="<?php esc_attr_e('Föregående inlägg', 'slingan'); ?>"
        hidden
    >
        <span aria-hidden="true">‹</span>
    </button>

    <div
        class="slingan-blog-roll__viewport"
        role="region"
        aria-label="<?php esc_attr_e('Senaste inlägg', 'slingan'); ?>"
    >
        <div class="slingan-blog-roll__track">
            <?php foreach ($posts as $index => $post) : ?>
                <?php
                get_template_part('template-parts/post-card', null, [
                    'post' => $post,
                    'index' => $index,
                ]);
                ?>
            <?php endforeach; ?>
            <?php for ($pad = 0; $pad < $padCount; $pad++) : ?>
                <article class="slingan-tile slingan-tile--placeholder" aria-hidden="true">
                    <div class="slingan-tile__media">
                        <span class="slingan-tile__media-placeholder" aria-hidden="true"></span>
                    </div>
                    <div class="slingan-tile__body">
                        <h3 class="slingan-tile__title"><span aria-hidden="true">&nbsp;</span></h3>
                    </div>
                </article>
            <?php endfor; ?>
        </div>
    </div>

    <button
        type="button"
        class="slingan-blog-roll__nav slingan-blog-roll__nav--next"
        aria-label="<?php esc_attr_e('Nästa inlägg', 'slingan'); ?>"
        <?php echo $hasMore ? '' : ' hidden'; ?>
    >
        <span aria-hidden="true">›</span>
    </button>
</div>
