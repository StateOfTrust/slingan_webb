<?php

declare(strict_types=1);

/**
 * One blog roll card (front-page tiles + /blog/).
 *
 * @var WP_Post $post
 * @var int     $index
 */

if (! isset($post) || ! $post instanceof WP_Post) {
    return;
}

$permalink = get_permalink($post);
$color = slingan_front_tile_color((int) ($index ?? 0));
$isEventPost = slingan_post_is_event_article($post);
$categoryLabel = slingan_post_tile_category_label($post);
$thumbHtml = slingan_post_tile_thumbnail($post);
$date = slingan_post_tile_date($post);
$schedule = slingan_post_tile_schedule_line($post);
$eventUrl = slingan_tile_is_calendar_event($post) ? '' : slingan_post_event_calendar_url($post);
$isCalendarEvent = slingan_tile_is_calendar_event($post);
$linkedEvent = slingan_post_linked_event($post);
$openRsvpModal = slingan_post_opens_rsvp_modal($post);
?>
<article class="slingan-tile<?php echo $isEventPost ? ' slingan-tile--event-post' : ''; ?>" style="--tile-color:<?php echo esc_attr($color); ?>">
    <a class="slingan-tile__media" href="<?php echo esc_url($permalink); ?>">
        <?php if ($thumbHtml !== '') : ?>
            <?php echo $thumbHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php else : ?>
            <span class="slingan-tile__media-placeholder" aria-hidden="true"></span>
        <?php endif; ?>
        <?php if ($categoryLabel !== '') : ?>
            <span class="slingan-tile__type"><?php echo esc_html($categoryLabel); ?></span>
        <?php endif; ?>
        <time class="slingan-tile__badge" datetime="<?php echo esc_attr($date['datetime']); ?>">
            <?php echo esc_html($date['label']); ?>
        </time>
        <span class="screen-reader-text"><?php echo esc_html(get_the_title($post)); ?></span>
    </a>
    <div class="slingan-tile__body">
        <h3 class="slingan-tile__title">
            <a href="<?php echo esc_url($permalink); ?>">
                <?php echo esc_html(get_the_title($post)); ?>
            </a>
        </h3>
        <?php if ($schedule !== '') : ?>
            <p class="slingan-tile__when"><?php echo esc_html($schedule); ?></p>
        <?php endif; ?>
        <?php
        $teaser = slingan_post_tile_teaser($post);
        if ($teaser !== '') :
            ?>
            <p class="slingan-tile__excerpt"><?php echo esc_html($teaser); ?></p>
        <?php endif; ?>
        <div class="slingan-tile__actions">
            <?php if ($openRsvpModal && $linkedEvent instanceof WP_Post) : ?>
                <button
                    type="button"
                    class="slingan-btn slingan-btn--tile slingan-open-rsvp-modal"
                    data-event-id="<?php echo (int) $linkedEvent->ID; ?>"
                    data-post-url="<?php echo esc_url($permalink); ?>"
                    data-post-title="<?php echo esc_attr(get_the_title($post)); ?>"
                >
                    <?php esc_html_e('Läs inlägg', 'slingan'); ?>
                </button>
            <?php else : ?>
                <a class="slingan-btn slingan-btn--tile" href="<?php echo esc_url($permalink); ?>">
                    <?php
                    echo esc_html(
                        $isCalendarEvent
                            ? __('Se spelträff', 'slingan')
                            : __('Läs inlägg', 'slingan')
                    );
                    ?>
                </a>
            <?php endif; ?>
            <?php if ($eventUrl !== '') : ?>
                <a class="slingan-tile__calendar-link" href="<?php echo esc_url($eventUrl); ?>">
                    <?php esc_html_e('I kalendern', 'slingan'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</article>
