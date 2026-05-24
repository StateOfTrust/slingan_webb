<?php

declare(strict_types=1);

/**
 * The Events Calendar + Event Tickets integration.
 */
function slingan_events_calendar_active(): bool
{
    return class_exists('Tribe__Events__Main') && function_exists('tribe_get_events');
}

function slingan_events_archive_url(): string
{
    if (! slingan_events_calendar_active()) {
        return home_url('/speltraffar/');
    }

    $link = tribe_get_events_link();

    return is_string($link) && $link !== '' ? $link : home_url('/events/');
}

/**
 * @return WP_Post[]
 */
function slingan_get_upcoming_events(int $count = 4): array
{
    if (! slingan_events_calendar_active()) {
        return [];
    }

    $events = tribe_get_events([
        'posts_per_page' => $count,
        'start_date' => 'now',
        'orderby' => 'event_date',
        'order' => 'ASC',
        'hide_subsequent_recurrences' => true,
    ]);

    return is_array($events) ? $events : [];
}

function slingan_event_permalink(WP_Post $event): string
{
    $url = get_permalink($event);

    return is_string($url) ? $url : '';
}

function slingan_event_date_badge(WP_Post $event): string
{
    if (function_exists('tribe_get_start_date')) {
        return (string) tribe_get_start_date($event, false, 'j M');
    }

    return get_the_date('j M', $event);
}

function slingan_event_datetime_attr(WP_Post $event): string
{
    if (function_exists('tribe_get_start_date')) {
        return (string) tribe_get_start_date($event, false, 'c');
    }

    return get_the_date('c', $event);
}

function slingan_event_schedule_line(WP_Post $event): string
{
    if (! function_exists('tribe_get_start_date')) {
        return get_the_date('', $event);
    }

    $start = tribe_get_start_date($event, false, 'l j F · H:i');
    $end = tribe_get_end_date($event, false, 'H:i');

    if ($start === '' && $end === '') {
        return '';
    }

    if ($end !== '' && $end !== $start) {
        return $start . '–' . $end;
    }

    return $start;
}

function slingan_event_venue_line(WP_Post $event): string
{
    if (! function_exists('tribe_get_venue')) {
        return '';
    }

    $venue = tribe_get_venue($event);

    return is_string($venue) ? trim($venue) : '';
}

function slingan_event_has_tickets(WP_Post $event): bool
{
    if (slingan_event_has_rsvp_tickets($event)) {
        return true;
    }

    if (! class_exists('Tribe__Tickets__Main')) {
        return false;
    }

    if (function_exists('tribe_events_has_tickets') && tribe_events_has_tickets($event->ID)) {
        return true;
    }

    return function_exists('tribe_tickets_has_tickets_on_sale')
        && tribe_tickets_has_tickets_on_sale($event->ID);
}

function slingan_event_has_rsvp_tickets(WP_Post $event): bool
{
    if (! function_exists('tribe') || ! class_exists('Tribe__Tickets__RSVP')) {
        return false;
    }

    $rsvp = tribe('tickets.rsvp');
    $tickets = $rsvp->get_tickets($event->ID);

    return is_array($tickets) && $tickets !== [];
}

/**
 * Enqueue Event Tickets JS/CSS on single posts linked to an event with tickets/RSVP.
 */
function slingan_enqueue_linked_event_ticket_assets(): void
{
    if (! is_singular('post')) {
        return;
    }

    $post = get_queried_object();
    if (! $post instanceof WP_Post) {
        return;
    }

    $event = slingan_post_linked_event($post);
    if (! $event instanceof WP_Post || ! slingan_event_has_tickets($event)) {
        return;
    }

    slingan_enqueue_rsvp_ticket_assets();
}
add_action('wp_enqueue_scripts', 'slingan_enqueue_linked_event_ticket_assets', 30);

/**
 * Render Event Tickets RSVP and/or ticket forms for an event (same blocks as on the event page).
 */
function slingan_render_event_tickets_html(WP_Post $event): string
{
    if (! slingan_event_has_tickets($event)) {
        return '';
    }

    global $post;
    $previousPost = $post instanceof WP_Post ? $post : null;
    $post = $event;
    setup_postdata($event);

    remove_all_filters('tribe_tickets_order_link_template_already_rendered');

    ob_start();

    if (slingan_event_has_rsvp_tickets($event) && function_exists('tribe')) {
        $rsvp = tribe('tickets.rsvp');
        if (method_exists($rsvp, 'front_end_tickets_form')) {
            $rsvp->front_end_tickets_form('');
        } elseif (class_exists('Tribe__Tickets__Tickets_View')) {
            Tribe__Tickets__Tickets_View::instance()->get_rsvp_block($event, true);
        }
    }

    if (class_exists('Tribe__Tickets__Tickets_View')) {
        Tribe__Tickets__Tickets_View::instance()->get_tickets_block($event, true);
    }

    $html = (string) ob_get_clean();

    if ($previousPost instanceof WP_Post) {
        $post = $previousPost;
        setup_postdata($previousPost);
    } else {
        wp_reset_postdata();
    }

    return trim($html);
}

function slingan_enqueue_events_styles(): void
{
    if (! slingan_events_calendar_active()) {
        return;
    }

    $linkedEvent = null;
    if (is_singular('post')) {
        $queried = get_queried_object();
        if ($queried instanceof WP_Post) {
            $linkedEvent = slingan_post_linked_event($queried);
        }
    }

    $load = is_front_page()
        || is_singular('tribe_events')
        || is_post_type_archive('tribe_events')
        || (function_exists('tribe_is_event') && tribe_is_event())
        || (function_exists('tribe_is_event_query') && tribe_is_event_query())
        || (
            $linkedEvent instanceof WP_Post
            && slingan_event_has_tickets($linkedEvent)
        );

    if (! $load) {
        return;
    }

    wp_enqueue_style(
        'slingan-events-calendar',
        get_stylesheet_directory_uri() . '/assets/events-calendar.css',
        ['slingan-site'],
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'slingan_enqueue_events_styles', 25);
