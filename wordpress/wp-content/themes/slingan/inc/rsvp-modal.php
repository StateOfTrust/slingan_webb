<?php

declare(strict_types=1);

/**
 * RSVP modal on the front page for blog posts linked to calendar events.
 */

function slingan_front_has_linked_rsvp_tiles(): bool
{
    if (! is_front_page()) {
        return false;
    }

    foreach (slingan_get_blog_roll_posts() as $post) {
        $event = slingan_post_linked_event($post);
        if ($event instanceof WP_Post && slingan_event_has_rsvp_tickets($event)) {
            return true;
        }
    }

    return false;
}

function slingan_post_opens_rsvp_modal(WP_Post $post): bool
{
    if (slingan_tile_is_calendar_event($post)) {
        return false;
    }

    $event = slingan_post_linked_event($post);

    return $event instanceof WP_Post && slingan_event_has_rsvp_tickets($event);
}

function slingan_enqueue_rsvp_ticket_assets(): void
{
    if (! function_exists('tribe_asset_enqueue_group')) {
        return;
    }

    if (function_exists('tribe_tickets_rsvp_new_views_is_enabled') && tribe_tickets_rsvp_new_views_is_enabled()) {
        tribe_asset_enqueue_group('tribe-tickets-rsvp');
        tribe_asset_enqueue('tribe-tickets-rsvp-style');
        tribe_asset_enqueue('tribe-tickets-forms-style');
        tribe_asset_enqueue('tribe-common-responsive');
    } elseif (function_exists('tribe_asset_enqueue')) {
        tribe_asset_enqueue('tribe-tickets-rsvp');
        tribe_asset_enqueue('tribe-tickets-rsvp-js');
    }

    if (function_exists('tribe_tickets_new_views_is_enabled') && tribe_tickets_new_views_is_enabled()) {
        tribe_asset_enqueue_group('tribe-tickets-block-assets');
    }
}

function slingan_enqueue_rsvp_modal_assets(): void
{
    $load = slingan_front_has_linked_rsvp_tiles();

    if (! $load && is_singular('post')) {
        $post = get_queried_object();
        $load = $post instanceof WP_Post && slingan_post_opens_rsvp_modal($post);
    }

    if (! $load) {
        return;
    }

    slingan_enqueue_rsvp_ticket_assets();

    wp_enqueue_script(
        'slingan-rsvp-modal',
        get_stylesheet_directory_uri() . '/assets/rsvp-modal.js',
        [],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script('slingan-rsvp-modal', 'slinganRsvpModal', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('slingan_rsvp_modal'),
        'loading' => __('Laddar anmälan…', 'slingan'),
        'error' => __('Kunde inte ladda anmälan. Försök igen eller öppna inlägget.', 'slingan'),
    ]);
}
add_action('wp_enqueue_scripts', 'slingan_enqueue_rsvp_modal_assets', 35);

function slingan_ajax_event_rsvp_modal(): void
{
    check_ajax_referer('slingan_rsvp_modal', 'nonce');

    $eventId = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
    $event = get_post($eventId);

    if (! $event instanceof WP_Post || $event->post_type !== 'tribe_events') {
        wp_send_json_error(['message' => __('Ogiltigt evenemang.', 'slingan')], 400);
    }

    if (! slingan_event_has_rsvp_tickets($event)) {
        wp_send_json_error(['message' => __('Ingen RSVP för det här evenemanget.', 'slingan')], 404);
    }

    $html = slingan_render_event_tickets_html($event);
    if ($html === '') {
        wp_send_json_error(['message' => __('RSVP kunde inte visas.', 'slingan')], 500);
    }

    wp_send_json_success([
        'html' => $html,
        'title' => get_the_title($event),
        'schedule' => slingan_event_schedule_line($event),
    ]);
}
add_action('wp_ajax_slingan_event_rsvp_modal', 'slingan_ajax_event_rsvp_modal');
add_action('wp_ajax_nopriv_slingan_event_rsvp_modal', 'slingan_ajax_event_rsvp_modal');

function slingan_render_rsvp_modal_shell(): void
{
    if (! slingan_front_has_linked_rsvp_tiles()) {
        return;
    }

    get_template_part('template-parts/rsvp-modal');
}
add_action('wp_footer', 'slingan_render_rsvp_modal_shell', 5);
