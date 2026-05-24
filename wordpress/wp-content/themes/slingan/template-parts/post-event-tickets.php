<?php

declare(strict_types=1);

/**
 * RSVP / tickets for the calendar event linked to this blog post.
 *
 * @var array<string, mixed> $args
 * @var WP_Post              $event
 */

if (isset($args) && is_array($args) && isset($args['event']) && $args['event'] instanceof WP_Post) {
    $event = $args['event'];
}

if (! isset($event) || ! $event instanceof WP_Post) {
    return;
}

$ticketsHtml = slingan_render_event_tickets_html($event);

$eventUrl = slingan_event_permalink($event);
$schedule = slingan_event_schedule_line($event);
$venue = slingan_event_venue_line($event);
$rsvpLabel = function_exists('tribe_get_rsvp_label_singular')
    ? tribe_get_rsvp_label_singular('slingan_post_rsvp_heading')
    : __('RSVP', 'slingan');
?>
<section class="slingan-post-event-tickets" aria-labelledby="slingan-post-event-tickets-heading">
    <header class="slingan-post-event-tickets__header">
        <h2 id="slingan-post-event-tickets-heading" class="slingan-post-event-tickets__title">
            <?php echo esc_html($rsvpLabel); ?>
        </h2>
        <?php if ($schedule !== '') : ?>
            <p class="slingan-post-event-tickets__when"><?php echo esc_html($schedule); ?></p>
        <?php endif; ?>
        <?php if ($venue !== '') : ?>
            <p class="slingan-post-event-tickets__where"><?php echo esc_html($venue); ?></p>
        <?php endif; ?>
        <?php if ($eventUrl !== '') : ?>
            <p class="slingan-post-event-tickets__calendar">
                <a href="<?php echo esc_url($eventUrl); ?>">
                    <?php esc_html_e('Se evenemanget i kalendern', 'slingan'); ?>
                </a>
            </p>
        <?php endif; ?>
    </header>
    <?php if ($ticketsHtml !== '') : ?>
        <div class="slingan-post-event-tickets__form">
            <?php echo $ticketsHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Event Tickets markup ?>
        </div>
    <?php elseif (current_user_can('edit_post', get_the_ID())) : ?>
        <p class="slingan-post-event-tickets__notice">
            <?php
            esc_html_e(
                'Det kopplade evenemanget har ingen RSVP ännu. Lägg till RSVP under evenemanget i admin (Tickets / RSVP), eller öppna evenemanget i kalendern.',
                'slingan'
            );
            ?>
        </p>
    <?php elseif ($eventUrl !== '') : ?>
        <p class="slingan-post-event-tickets__notice">
            <a class="slingan-btn slingan-btn--primary" href="<?php echo esc_url($eventUrl); ?>">
                <?php esc_html_e('Anmäl dig via evenemanget', 'slingan'); ?>
            </a>
        </p>
    <?php endif; ?>
</section>
