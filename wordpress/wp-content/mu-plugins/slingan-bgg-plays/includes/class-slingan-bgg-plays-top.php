<?php

declare(strict_types=1);

final class Slingan_Bgg_Plays_Top
{
    public static function init(): void
    {
        add_shortcode('slingan_bgg_top_plays', [self::class, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * @param array<string, string> $atts
     */
    public static function render_shortcode($atts): string
    {
        $atts = shortcode_atts(
            [
                'count' => '9',
                'username' => '',
                'months' => '',
                'year' => '',
                'location' => '',
                'location_match' => '',
            ],
            $atts,
            'slingan_bgg_top_plays'
        );

        $username = $atts['username'] !== ''
            ? sanitize_text_field($atts['username'])
            : Slingan_Bgg_Plays::default_username_public();

        if ($username === '') {
            return self::message(__('Ange BGG-användarnamn under Inställningar → BGG spel.', 'slingan-bgg'));
        }

        if (! Slingan_Bgg_Plays::has_api_token()) {
            return self::message(__('BGG API-token saknas.', 'slingan-bgg'));
        }

        $count = max(1, min(9, (int) $atts['count']));

        $location = $atts['location'] !== ''
            ? sanitize_text_field($atts['location'])
            : (string) get_option('slingan_bgg_location', '');

        $locationMatch = $atts['location_match'] !== ''
            ? Slingan_Bgg_Plays::sanitize_location_match($atts['location_match'])
            : Slingan_Bgg_Plays::sanitize_location_match(get_option('slingan_bgg_location_match', 'exact'));

        $period = Slingan_Bgg_Plays::resolve_period($atts['months'], $atts['year']);

        $topGames = Slingan_Bgg_Api::fetch_top_played_games(
            $username,
            $count,
            $location,
            $locationMatch,
            $period['months'],
            $period['year']
        );
        if ($topGames === null) {
            return self::message(__('Kunde inte hämta spel från BoardGameGeek just nu.', 'slingan-bgg'));
        }

        if ($topGames === []) {
            return self::message(__('Inga spel hittades för den valda perioden.', 'slingan-bgg'));
        }

        $gameIds = array_map(static fn (array $row): int => (int) $row['game_id'], $topGames);
        $images = Slingan_Bgg_Plays::get_cached_images_public($gameIds);

        ob_start();
        include SLINGAN_BGG_PLAYS_DIR . '/templates/plays-top-grid.php';

        return (string) ob_get_clean();
    }

    public static function enqueue_assets(): void
    {
        if (! is_singular()) {
            return;
        }

        global $post;
        if (! $post instanceof WP_Post) {
            return;
        }

        if (! has_shortcode((string) $post->post_content, 'slingan_bgg_top_plays')) {
            return;
        }

        Slingan_Bgg_Plays::enqueue_styles();
    }

    private static function message(string $text): string
    {
        return '<section class="slingan-bgg-top"><p class="slingan-bgg-top__intro">' . esc_html($text) . '</p></section>';
    }
}
