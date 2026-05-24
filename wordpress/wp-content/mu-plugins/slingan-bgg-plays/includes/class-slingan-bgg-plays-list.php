<?php

declare(strict_types=1);

final class Slingan_Bgg_Plays_List
{
    public const QUERY_VAR = 'bgg_plays_page';

    private const OPTION_PER_PAGE = 'slingan_bgg_list_per_page';

    public static function init(): void
    {
        add_shortcode('slingan_bgg_plays_list', [self::class, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function register_settings(): void
    {
        register_setting('slingan_bgg_plays', self::OPTION_PER_PAGE, [
            'type' => 'integer',
            'sanitize_callback' => static fn ($v): int => max(5, min(100, (int) $v)),
            'default' => 25,
        ]);
    }

    /**
     * @param array<string, string> $atts
     */
    public static function render_shortcode($atts): string
    {
        $atts = shortcode_atts(
            [
                'per_page' => '',
                'username' => '',
                'location' => '',
                'location_match' => '',
                'title' => '',
                'intro' => '',
                'months' => '',
                'year' => '',
            ],
            $atts,
            'slingan_bgg_plays_list'
        );

        $username = $atts['username'] !== ''
            ? sanitize_text_field($atts['username'])
            : Slingan_Bgg_Plays::default_username_public();

        if ($username === '') {
            return self::render_message(__('Ange BGG-användarnamn under Inställningar → BGG spel.', 'slingan-bgg'));
        }

        if (! Slingan_Bgg_Plays::has_api_token()) {
            return self::render_message(__('BGG API-token saknas.', 'slingan-bgg'));
        }

        $perPage = $atts['per_page'] !== ''
            ? max(5, min(100, (int) $atts['per_page']))
            : max(5, min(100, (int) get_option(self::OPTION_PER_PAGE, 25)));

        if (isset($_GET['bgg_location'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $location = sanitize_text_field(wp_unslash((string) $_GET['bgg_location']));
        } else {
            $location = $atts['location'] !== ''
                ? sanitize_text_field($atts['location'])
                : (string) get_option('slingan_bgg_location', '');
        }

        $locationMatch = $atts['location_match'] !== ''
            ? Slingan_Bgg_Plays::sanitize_location_match($atts['location_match'])
            : Slingan_Bgg_Plays::sanitize_location_match(get_option('slingan_bgg_location_match', 'exact'));

        if (isset($_GET['bgg_year'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $period = [
                'months' => 0,
                'year' => Slingan_Bgg_Plays::sanitize_year(wp_unslash((string) $_GET['bgg_year'])),
            ];
        } elseif (isset($_GET['bgg_months'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $period = [
                'months' => Slingan_Bgg_Plays::sanitize_months(wp_unslash((string) $_GET['bgg_months'])),
                'year' => 0,
            ];
        } else {
            $period = Slingan_Bgg_Plays::resolve_period($atts['months'], $atts['year']);
        }

        $months = $period['months'];
        $year = $period['year'];

        $listPage = 1;
        if (isset($_GET[self::QUERY_VAR])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $listPage = max(1, (int) wp_unslash($_GET[self::QUERY_VAR]));
        } else {
            $qv = (int) get_query_var(self::QUERY_VAR, 1);
            if ($qv > 0) {
                $listPage = $qv;
            }
        }

        $cacheKey = 'slingan_bgg_list_v4_' . md5(
            strtolower($username)
            . '|' . $perPage
            . '|' . $listPage
            . '|' . strtolower($location)
            . '|' . $locationMatch
            . '|' . Slingan_Bgg_Api::period_cache_segment($months, $year)
        );
        $cached = get_transient($cacheKey);
        if (is_array($cached) && isset($cached['items'], $cached['total'], $cached['total_pages'])) {
            $data = $cached;
        } else {
            $data = Slingan_Bgg_Api::fetch_list_slice($username, $listPage, $perPage, $location, $locationMatch, $months, $year);
            if ($data === null) {
                return self::render_message(__('Kunde inte hämta spel från BoardGameGeek just nu.', 'slingan-bgg'));
            }
            set_transient($cacheKey, $data, Slingan_Bgg_Plays::cache_ttl_public());
        }

        if ($data['total'] === 0) {
            if ($location !== '') {
                return self::render_message(
                    sprintf(
                        __('Inga inloggade spel på platsen «%s».', 'slingan-bgg'),
                        Slingan_Bgg_Plays::location_filter_label($location)
                    )
                );
            }

            if ($months > 0 || $year > 0) {
                return self::render_message(
                    sprintf(
                        __('Inga inloggade spel hittades (%s).', 'slingan-bgg'),
                        Slingan_Bgg_Plays::period_filter_label($months, $year)
                    )
                );
            }

            return self::render_message(__('Inga inloggade spel hittades.', 'slingan-bgg'));
        }

        $title = sanitize_text_field($atts['title']);
        $intro = sanitize_text_field($atts['intro']);
        $locationFilterLabel = '';
        $monthsFilterLabel = '';
        $showMeta = false;
        $pagination = self::render_pagination($listPage, (int) $data['total_pages']);
        $collectionRatings = Slingan_Bgg_Plays::collection_ratings_map($username);

        ob_start();
        include SLINGAN_BGG_PLAYS_DIR . '/templates/plays-list.php';

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

        if (! has_shortcode((string) $post->post_content, 'slingan_bgg_plays_list')) {
            return;
        }

        Slingan_Bgg_Plays::enqueue_styles();
    }

    private static function render_pagination(int $current, int $totalPages): string
    {
        if ($totalPages <= 1) {
            return '';
        }

        $permalink = get_permalink();
        if (! is_string($permalink) || $permalink === '') {
            $permalink = home_url('/');
        }

        $paginationArgs = [self::QUERY_VAR => '%#%'];
        if (isset($_GET['bgg_location'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $paginationArgs['bgg_location'] = sanitize_text_field(wp_unslash((string) $_GET['bgg_location']));
        }
        if (isset($_GET['bgg_year'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $paginationArgs['bgg_year'] = (string) Slingan_Bgg_Plays::sanitize_year(wp_unslash((string) $_GET['bgg_year']));
        } elseif (isset($_GET['bgg_months'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $paginationArgs['bgg_months'] = (string) Slingan_Bgg_Plays::sanitize_months(wp_unslash((string) $_GET['bgg_months']));
        }

        $links = paginate_links([
            'base' => esc_url_raw(add_query_arg($paginationArgs, $permalink)),
            'format' => '',
            'current' => $current,
            'total' => $totalPages,
            'prev_text' => '« ' . __('Föregående', 'slingan-bgg'),
            'next_text' => __('Nästa', 'slingan-bgg') . ' »',
            'type' => 'list',
        ]);

        if (! is_string($links) || $links === '') {
            return '';
        }

        return '<nav class="slingan-bgg-plays__pagination" aria-label="' . esc_attr__('Sidnavigering', 'slingan-bgg') . '">' . $links . '</nav>';
    }

    private static function render_message(string $message): string
    {
        return '<section class="slingan-bgg-plays-list"><p class="slingan-bgg-plays-list__empty">' . esc_html($message) . '</p></section>';
    }

    /**
     * @return array{label: string, datetime: string}
     */
    public static function list_date_cell(string $isoDate): array
    {
        $parts = Slingan_Bgg_Plays::play_date_parts($isoDate);
        $ts = strtotime($isoDate);

        if ($ts !== false) {
            $parts['label'] = date_i18n('j M Y', $ts);
        }

        return $parts;
    }
}
