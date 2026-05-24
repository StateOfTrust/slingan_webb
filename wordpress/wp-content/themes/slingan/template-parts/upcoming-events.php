<?php

declare(strict_types=1);

if (! slingan_events_calendar_active()) {
    return;
}

$events = slingan_get_upcoming_events(4);
$eventsUrl = slingan_events_archive_url();
?>
<section class="slingan-events" aria-labelledby="slingan-events-heading">
    <header class="slingan-events__header">
        <p class="slingan-events__eyebrow"><?php esc_html_e('Kommande', 'slingan'); ?></p>
        <h2 id="slingan-events-heading" class="slingan-events__title"><?php esc_html_e('Spelträffar', 'slingan'); ?></h2>
        <p class="slingan-events__intro"><?php esc_html_e('Nästa gång vi ses vid bordet — från kalendern.', 'slingan'); ?></p>
    </header>

    <?php if ($events === []) : ?>
        <p class="slingan-events__empty">
            <?php
            printf(
                wp_kses_post(__('Inga kommande träffar i kalendern ännu. <a href="%1$s">Lägg till ett evenemang</a> eller öppna <a href="%2$s">kalendern</a>.', 'slingan')),
                esc_url(admin_url('edit.php?post_type=tribe_events')),
                esc_url($eventsUrl)
            );
            ?>
        </p>
    <?php else : ?>
        <div class="slingan-events__grid">
            <?php foreach ($events as $index => $event) :
                $permalink = slingan_event_permalink($event);
                $color = slingan_front_tile_color((int) $index);
                $hasThumb = has_post_thumbnail($event);
                ?>
                <article class="slingan-event-card" style="--tile-color:<?php echo esc_attr($color); ?>">
                    <a class="slingan-event-card__media" href="<?php echo esc_url($permalink); ?>">
                        <?php if ($hasThumb) : ?>
                            <?php
                            echo get_the_post_thumbnail(
                                $event,
                                'medium_large',
                                [
                                    'class' => 'slingan-event-card__img',
                                    'loading' => 'lazy',
                                    'decoding' => 'async',
                                ]
                            );
                            ?>
                        <?php else : ?>
                            <span class="slingan-event-card__media-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                        <time class="slingan-event-card__badge" datetime="<?php echo esc_attr(slingan_event_datetime_attr($event)); ?>">
                            <?php echo esc_html(slingan_event_date_badge($event)); ?>
                        </time>
                        <span class="screen-reader-text"><?php echo esc_html(get_the_title($event)); ?></span>
                    </a>
                    <div class="slingan-event-card__body">
                        <h3 class="slingan-event-card__name">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <?php echo esc_html(get_the_title($event)); ?>
                            </a>
                        </h3>
                        <?php $schedule = slingan_event_schedule_line($event); ?>
                        <?php if ($schedule !== '') : ?>
                            <p class="slingan-event-card__when"><?php echo esc_html($schedule); ?></p>
                        <?php endif; ?>
                        <?php $venue = slingan_event_venue_line($event); ?>
                        <?php if ($venue !== '') : ?>
                            <p class="slingan-event-card__where"><?php echo esc_html($venue); ?></p>
                        <?php endif; ?>
                        <?php if (slingan_event_has_tickets($event)) : ?>
                            <p class="slingan-event-card__tickets"><?php esc_html_e('Anmälan / biljetter', 'slingan'); ?></p>
                        <?php endif; ?>
                        <a class="slingan-btn slingan-btn--tile" href="<?php echo esc_url($permalink); ?>">
                            <?php esc_html_e('Visa träff', 'slingan'); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p class="slingan-events__more">
        <a href="<?php echo esc_url($eventsUrl); ?>"><?php esc_html_e('Hela kalendern', 'slingan'); ?></a>
    </p>
</section>
