<?php

declare(strict_types=1);

const SLINGAN_EVENT_CATEGORY_SLUG = 'speltraff';
const SLINGAN_LINKED_EVENT_META = '_slingan_linked_event_id';

/**
 * Ensure "Spelträff" exists for event-related blog posts.
 */
function slingan_register_event_post_category(): void
{
    if (term_exists(SLINGAN_EVENT_CATEGORY_SLUG, 'category')) {
        return;
    }

    wp_insert_term(
        __('Spelträff', 'slingan'),
        'category',
        [
            'slug' => SLINGAN_EVENT_CATEGORY_SLUG,
            'description' => __('Blogginlägg om en spelträff (visas i blogglistan med spelträff-stil).', 'slingan'),
        ]
    );
}
add_action('after_setup_theme', 'slingan_register_event_post_category');

function slingan_post_event_meta_box(): void
{
    add_meta_box(
        'slingan_post_event',
        __('Spelträff-inlägg', 'slingan'),
        'slingan_render_post_event_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'slingan_post_event_meta_box');

function slingan_render_post_event_meta_box(WP_Post $post): void
{
    wp_nonce_field('slingan_save_post_event', 'slingan_post_event_nonce');

    $isEvent = has_category(SLINGAN_EVENT_CATEGORY_SLUG, $post);
    $linkedId = (int) get_post_meta($post->ID, SLINGAN_LINKED_EVENT_META, true);
    ?>
    <p>
        <label>
            <input type="checkbox" name="slingan_is_event_post" value="1" <?php checked($isEvent); ?>>
            <?php esc_html_e('Det här inlägget handlar om en spelträff', 'slingan'); ?>
        </label>
    </p>
    <p class="description">
        <?php esc_html_e('Visas i blogglistan på startsidan med etiketten Spelträff.', 'slingan'); ?>
    </p>
    <?php if (slingan_events_calendar_active()) : ?>
        <p>
            <label for="slingan_linked_event_id"><strong><?php esc_html_e('Koppla till evenemang', 'slingan'); ?></strong></label>
            <select name="slingan_linked_event_id" id="slingan_linked_event_id" class="widefat">
                <option value="0"><?php esc_html_e('— Inget —', 'slingan'); ?></option>
                <?php foreach (slingan_get_linkable_events() as $event) : ?>
                    <option value="<?php echo (int) $event->ID; ?>" <?php selected($linkedId, (int) $event->ID); ?>>
                        <?php
                        echo esc_html(
                            sprintf(
                                '%s (%s)',
                                get_the_title($event),
                                slingan_event_date_badge($event)
                            )
                        );
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="description">
            <?php esc_html_e('Valfritt: hämtar datum och bild från kalendern om inlägget saknar egen utvald bild.', 'slingan'); ?>
        </p>
    <?php endif; ?>
    <?php
}

/**
 * @return WP_Post[]
 */
function slingan_get_linkable_events(): array
{
    if (! slingan_events_calendar_active()) {
        return [];
    }

    $events = tribe_get_events([
        'posts_per_page' => 50,
        'start_date' => 'today - 30 days',
        'orderby' => 'event_date',
        'order' => 'DESC',
    ]);

    return is_array($events) ? $events : [];
}

function slingan_save_post_event_meta(int $postId): void
{
    if (! isset($_POST['slingan_post_event_nonce'])
        || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['slingan_post_event_nonce'])), 'slingan_save_post_event')
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (! current_user_can('edit_post', $postId)) {
        return;
    }

    $isEvent = isset($_POST['slingan_is_event_post']) && $_POST['slingan_is_event_post'] === '1';
    $linkedId = isset($_POST['slingan_linked_event_id']) ? (int) $_POST['slingan_linked_event_id'] : 0;

    if ($isEvent) {
        wp_set_post_terms($postId, [SLINGAN_EVENT_CATEGORY_SLUG], 'category', false);
    } else {
        wp_remove_object_terms($postId, SLINGAN_EVENT_CATEGORY_SLUG, 'category');
        $linkedId = 0;
    }

    if ($linkedId > 0 && get_post_type($linkedId) === 'tribe_events') {
        update_post_meta($postId, SLINGAN_LINKED_EVENT_META, $linkedId);
    } else {
        delete_post_meta($postId, SLINGAN_LINKED_EVENT_META);
    }
}
add_action('save_post_post', 'slingan_save_post_event_meta');

function slingan_tile_is_calendar_event(WP_Post $post): bool
{
    return $post->post_type === 'tribe_events';
}

function slingan_post_is_event_article(WP_Post $post): bool
{
    return slingan_tile_is_calendar_event($post)
        || has_category(SLINGAN_EVENT_CATEGORY_SLUG, $post)
        || slingan_post_linked_event($post) !== null;
}

function slingan_post_linked_event(WP_Post $post): ?WP_Post
{
    $eventId = (int) get_post_meta($post->ID, SLINGAN_LINKED_EVENT_META, true);
    if ($eventId <= 0) {
        return null;
    }

    $event = get_post($eventId);

    if (! $event instanceof WP_Post || $event->post_type !== 'tribe_events') {
        return null;
    }

    return $event;
}

function slingan_post_tile_has_thumbnail(WP_Post $post): bool
{
    if (has_post_thumbnail($post)) {
        return true;
    }

    $event = slingan_post_linked_event($post);

    return $event !== null && has_post_thumbnail($event);
}

function slingan_post_tile_thumbnail(WP_Post $post, string $size = 'medium_large'): string
{
    if (slingan_tile_is_calendar_event($post) && has_post_thumbnail($post)) {
        return (string) get_the_post_thumbnail($post, $size, [
            'class' => 'slingan-tile__img',
            'loading' => 'lazy',
            'decoding' => 'async',
        ]);
    }

    if (has_post_thumbnail($post)) {
        return (string) get_the_post_thumbnail($post, $size, [
            'class' => 'slingan-tile__img',
            'loading' => 'lazy',
            'decoding' => 'async',
        ]);
    }

    $event = slingan_post_linked_event($post);
    if ($event !== null && has_post_thumbnail($event)) {
        return (string) get_the_post_thumbnail($event, $size, [
            'class' => 'slingan-tile__img',
            'loading' => 'lazy',
            'decoding' => 'async',
        ]);
    }

    return '';
}

/**
 * @return array{datetime: string, label: string}
 */
function slingan_post_tile_date(WP_Post $post): array
{
    if (slingan_tile_is_calendar_event($post)) {
        return [
            'datetime' => slingan_event_datetime_attr($post),
            'label' => slingan_event_date_badge($post),
        ];
    }

    $event = slingan_post_linked_event($post);
    if ($event !== null) {
        return [
            'datetime' => slingan_event_datetime_attr($event),
            'label' => slingan_event_date_badge($event),
        ];
    }

    return [
        'datetime' => get_the_date('c', $post),
        'label' => get_the_date('j M', $post),
    ];
}

function slingan_post_tile_schedule_line(WP_Post $post): string
{
    if (slingan_tile_is_calendar_event($post)) {
        return slingan_event_schedule_line($post);
    }

    if (! slingan_post_is_event_article($post)) {
        return '';
    }

    $event = slingan_post_linked_event($post);
    if ($event !== null) {
        return slingan_event_schedule_line($event);
    }

    return '';
}

function slingan_post_event_calendar_url(WP_Post $post): string
{
    $event = slingan_post_linked_event($post);

    return $event !== null ? slingan_event_permalink($event) : '';
}
