<?php

declare(strict_types=1);

final class Slingan_Bgg_Plays
{
    private const OPTION_USERNAME = 'slingan_bgg_username';

    private const OPTION_TOKEN = 'slingan_bgg_api_token';

    private const OPTION_COUNT = 'slingan_bgg_count';

    private const OPTION_CACHE_TTL = 'slingan_bgg_cache_ttl';

    private const OPTION_LOCATION = 'slingan_bgg_location';

    private const OPTION_LOCATION_MATCH = 'slingan_bgg_location_match';

    private const OPTION_MONTHS = 'slingan_bgg_months';

    private const OPTION_LIST_PAGE_URL = 'slingan_bgg_list_page_url';

    public static function init(): void
    {
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_menu', [self::class, 'register_settings_page']);
        add_shortcode('slingan_bgg_plays', [self::class, 'render_shortcode']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_filter('query_vars', [self::class, 'register_query_vars']);
    }

    /**
     * @param list<string> $vars
     * @return list<string>
     */
    public static function register_query_vars(array $vars): array
    {
        $vars[] = Slingan_Bgg_Plays_List::QUERY_VAR;

        return $vars;
    }

    public static function register_settings(): void
    {
        Slingan_Bgg_Plays_List::register_settings();
        register_setting('slingan_bgg_plays', self::OPTION_USERNAME, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_TOKEN, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_COUNT, [
            'type' => 'integer',
            'sanitize_callback' => static fn ($v): int => max(1, min(12, (int) $v)),
            'default' => 4,
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_CACHE_TTL, [
            'type' => 'integer',
            'sanitize_callback' => static fn ($v): int => max(300, min(86400, (int) $v)),
            'default' => 3600,
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_LOCATION, [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_LOCATION_MATCH, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_location_match'],
            'default' => 'exact',
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_MONTHS, [
            'type' => 'integer',
            'sanitize_callback' => [self::class, 'sanitize_months'],
            'default' => 0,
        ]);
        register_setting('slingan_bgg_plays', self::OPTION_LIST_PAGE_URL, [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ]);
    }

    public static function sanitize_months($value): int
    {
        $months = (int) $value;

        if ($months <= 0) {
            return 0;
        }

        return min(120, $months);
    }

    public static function resolve_months(string $shortcodeValue): int
    {
        if ($shortcodeValue !== '') {
            return self::sanitize_months($shortcodeValue);
        }

        return self::sanitize_months(get_option(self::OPTION_MONTHS, 0));
    }

    public static function sanitize_year($value): int
    {
        $year = (int) $value;
        $current = (int) wp_date('Y');

        if ($year < 1970 || $year > $current) {
            return 0;
        }

        return $year;
    }

    public static function default_year(): int
    {
        return (int) wp_date('Y');
    }

    /**
     * @return array{months: int, year: int}
     */
    public static function resolve_period(string $monthsAttr, string $yearAttr): array
    {
        if ($yearAttr !== '') {
            $year = self::sanitize_year($yearAttr);
            if ($year > 0) {
                return [
                    'months' => 0,
                    'year' => $year,
                ];
            }
        }

        if ($monthsAttr !== '') {
            $months = self::sanitize_months($monthsAttr);

            return [
                'months' => $months,
                'year' => 0,
            ];
        }

        $settingMonths = self::sanitize_months(get_option(self::OPTION_MONTHS, 0));
        if ($settingMonths > 0) {
            return [
                'months' => $settingMonths,
                'year' => 0,
            ];
        }

        return [
            'months' => 0,
            'year' => self::default_year(),
        ];
    }

    public static function period_filter_label(int $months, int $year): string
    {
        if ($year > 0) {
            return sprintf(
                /* translators: %d: calendar year */
                __('År %d', 'slingan-bgg'),
                $year
            );
        }

        return self::months_filter_label($months);
    }

    public static function months_filter_label(int $months): string
    {
        if ($months <= 0) {
            return '';
        }

        return sprintf(
            /* translators: %d: number of months */
            _n('Senaste %d månaden', 'Senaste %d månaderna', $months, 'slingan-bgg'),
            $months
        );
    }

    public static function sanitize_location_match($value): string
    {
        $value = is_string($value) ? strtolower(trim($value)) : 'exact';

        return $value === 'contains' ? 'contains' : 'exact';
    }

    public static function register_settings_page(): void
    {
        add_options_page(
            __('BGG spel', 'slingan-bgg'),
            __('BGG spel', 'slingan-bgg'),
            'manage_options',
            'slingan-bgg-plays',
            [self::class, 'render_settings_page']
        );
    }

    public static function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $tokenFromConfig = defined('SLINGAN_BGG_API_TOKEN') && is_string(SLINGAN_BGG_API_TOKEN) && SLINGAN_BGG_API_TOKEN !== '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('BoardGameGeek — senaste spel', 'slingan-bgg'); ?></h1>
            <p>
                <?php esc_html_e('Visar senaste inloggade brädspel i samma kortlayout som bloggrullen på Slingan.', 'slingan-bgg'); ?>
            </p>
            <p>
                <?php
                printf(
                    wp_kses_post(
                        /* translators: %s: BGG applications URL */
                        __('Registrera en app och skapa en token på <a href="%s">boardgamegeek.com/applications</a>. Lägg token i wp-config som <code>SLINGAN_BGG_API_TOKEN</code> om du vill hålla den utanför databasen.', 'slingan-bgg')
                    ),
                    'https://boardgamegeek.com/applications'
                );
                ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields('slingan_bgg_plays'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="slingan_bgg_username"><?php esc_html_e('BGG-användarnamn', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_USERNAME); ?>" id="slingan_bgg_username" type="text" class="regular-text" value="<?php echo esc_attr((string) get_option(self::OPTION_USERNAME, '')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_api_token"><?php esc_html_e('API-token', 'slingan-bgg'); ?></label></th>
                        <td>
                            <?php if ($tokenFromConfig) : ?>
                                <p><?php esc_html_e('Token sätts via SLINGAN_BGG_API_TOKEN i wp-config.php.', 'slingan-bgg'); ?></p>
                            <?php else : ?>
                                <input name="<?php echo esc_attr(self::OPTION_TOKEN); ?>" id="slingan_bgg_api_token" type="password" class="regular-text" value="<?php echo esc_attr((string) get_option(self::OPTION_TOKEN, '')); ?>" autocomplete="off" />
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_count"><?php esc_html_e('Antal kort', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_COUNT); ?>" id="slingan_bgg_count" type="number" min="1" max="12" value="<?php echo esc_attr((string) get_option(self::OPTION_COUNT, 4)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_cache_ttl"><?php esc_html_e('Cache (sekunder)', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_CACHE_TTL); ?>" id="slingan_bgg_cache_ttl" type="number" min="300" max="86400" value="<?php echo esc_attr((string) get_option(self::OPTION_CACHE_TTL, 3600)); ?>" />
                            <p class="description"><?php esc_html_e('BGG rekommenderar server-side cache. Standard: 3600 (1 timme).', 'slingan-bgg'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_location"><?php esc_html_e('Platsfilter (standard)', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_LOCATION); ?>" id="slingan_bgg_location" type="text" class="regular-text" value="<?php echo esc_attr((string) get_option(self::OPTION_LOCATION, '')); ?>" placeholder="<?php esc_attr_e('t.ex. Slingan eller Hos Martina', 'slingan-bgg'); ?>" />
                            <p class="description"><?php esc_html_e('Lämna tomt för alla platser. Flera platser: separera med | (pipe). Tom plats i BGG: skriv (tom).', 'slingan-bgg'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_location_match"><?php esc_html_e('Platsmatchning', 'slingan-bgg'); ?></label></th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPTION_LOCATION_MATCH); ?>" id="slingan_bgg_location_match">
                                <option value="exact" <?php selected(get_option(self::OPTION_LOCATION_MATCH, 'exact'), 'exact'); ?>><?php esc_html_e('Exakt', 'slingan-bgg'); ?></option>
                                <option value="contains" <?php selected(get_option(self::OPTION_LOCATION_MATCH, 'exact'), 'contains'); ?>><?php esc_html_e('Innehåller', 'slingan-bgg'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_list_per_page"><?php esc_html_e('Rader per sida (lista)', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="slingan_bgg_list_per_page" id="slingan_bgg_list_per_page" type="number" min="5" max="100" value="<?php echo esc_attr((string) get_option('slingan_bgg_list_per_page', 25)); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_list_page_url"><?php esc_html_e('Lista-sida (URL)', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_LIST_PAGE_URL); ?>" id="slingan_bgg_list_page_url" type="url" class="regular-text" value="<?php echo esc_attr((string) get_option(self::OPTION_LIST_PAGE_URL, '')); ?>" placeholder="<?php echo esc_attr(home_url('/')); ?>" />
                            <p class="description"><?php esc_html_e('Mål för länken «Visa som lista» under kortvyn. Lämna tomt för att söka efter en sida med shortcoden [slingan_bgg_plays_list].', 'slingan-bgg'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slingan_bgg_months"><?php esc_html_e('Senaste månader (standard)', 'slingan-bgg'); ?></label></th>
                        <td>
                            <input name="<?php echo esc_attr(self::OPTION_MONTHS); ?>" id="slingan_bgg_months" type="number" min="0" max="120" value="<?php echo esc_attr((string) get_option(self::OPTION_MONTHS, 0)); ?>" />
                            <p class="description"><?php esc_html_e('0 = inget månadsfilter (standard blir innevarande år). T.ex. 6 = senaste 6 månaderna.', 'slingan-bgg'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2><?php esc_html_e('Shortcode', 'slingan-bgg'); ?></h2>
            <p><code>[slingan_bgg_plays]</code> — <?php esc_html_e('valfria attribut: count, username, months, year, location, location_match, list_url, eyebrow, title, intro', 'slingan-bgg'); ?></p>
            <p><code>[slingan_bgg_plays_list]</code> — <?php esc_html_e('tabell med alla spel; paginering via ?bgg_plays_page=2. Attribut: per_page, username, months, year, location, location_match, title, intro', 'slingan-bgg'); ?></p>
            <p><code>[slingan_bgg_top_plays year="2024"]</code> — <?php esc_html_e('3×3 rutnät med bara omslagsbilder (flest spelningar). Period: months eller year (year vinner). Övrigt: location, username, count (max 9)', 'slingan-bgg'); ?></p>
            <?php
            $preview = self::render_section([
                'count' => (int) get_option(self::OPTION_COUNT, 4),
                'username' => (string) get_option(self::OPTION_USERNAME, ''),
                'location' => (string) get_option(self::OPTION_LOCATION, ''),
                'location_match' => (string) get_option(self::OPTION_LOCATION_MATCH, 'exact'),
                'months' => self::sanitize_months(get_option(self::OPTION_MONTHS, 0)),
            ]);
            if ($preview !== '') {
                echo '<hr /><h2>' . esc_html__('Förhandsvisning', 'slingan-bgg') . '</h2>';
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo $preview;
            }
            ?>
        </div>
        <?php
    }

    /**
     * @param array<string, string> $atts
     */
    public static function render_shortcode($atts): string
    {
        $atts = shortcode_atts(
            [
                'count' => '',
                'username' => '',
                'eyebrow' => '',
                'title' => '',
                'intro' => '',
                'list_url' => '',
                'location' => '',
                'location_match' => '',
                'months' => '',
                'year' => '',
            ],
            $atts,
            'slingan_bgg_plays'
        );

        $count = $atts['count'] !== '' ? (int) $atts['count'] : (int) get_option(self::OPTION_COUNT, 4);
        $username = $atts['username'] !== ''
            ? sanitize_text_field($atts['username'])
            : self::default_username();

        $location = $atts['location'] !== ''
            ? sanitize_text_field($atts['location'])
            : (string) get_option(self::OPTION_LOCATION, '');

        $locationMatch = $atts['location_match'] !== ''
            ? self::sanitize_location_match($atts['location_match'])
            : self::sanitize_location_match(get_option(self::OPTION_LOCATION_MATCH, 'exact'));

        $period = self::resolve_period($atts['months'], $atts['year']);

        $listUrl = self::resolve_list_url($atts['list_url'], $location, $period['months'], $period['year']);

        return self::render_section([
            'count' => $count,
            'username' => $username,
            'eyebrow' => sanitize_text_field($atts['eyebrow']),
            'title' => sanitize_text_field($atts['title']),
            'intro' => sanitize_text_field($atts['intro']),
            'list_url' => $listUrl,
            'location' => $location,
            'location_match' => $locationMatch,
            'months' => $period['months'],
            'year' => $period['year'],
        ]);
    }

    public static function resolve_list_url(
        string $shortcodeUrl = '',
        string $location = '',
        int $months = 0,
        int $year = 0
    ): string
    {
        $url = $shortcodeUrl !== '' ? esc_url_raw($shortcodeUrl) : esc_url_raw((string) get_option(self::OPTION_LIST_PAGE_URL, ''));
        if (! is_string($url) || $url === '') {
            $url = self::discover_list_page_url();
        }

        if ($url === '') {
            return '';
        }

        $args = [];
        if ($location !== '') {
            $args['bgg_location'] = $location;
        }
        if ($year > 0) {
            $args['bgg_year'] = (string) $year;
        } elseif ($months > 0) {
            $args['bgg_months'] = (string) $months;
        }

        return $args !== [] ? (string) add_query_arg($args, $url) : $url;
    }

    public static function discover_list_page_url(): string
    {
        $cacheKey = 'slingan_bgg_list_page_permalink';
        $cached = get_transient($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $query = new WP_Query([
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $found = '';
        foreach ($query->posts as $post) {
            if ($post instanceof WP_Post && has_shortcode((string) $post->post_content, 'slingan_bgg_plays_list')) {
                $link = get_permalink($post);
                if (is_string($link) && $link !== '') {
                    $found = $link;
                    break;
                }
            }
        }

        if ($found !== '') {
            set_transient($cacheKey, $found, DAY_IN_SECONDS);
        }

        return $found;
    }

    public static function enqueue_assets(): void
    {
        if (! is_singular() && ! is_front_page()) {
            return;
        }

        global $post;
        if (! $post instanceof WP_Post) {
            return;
        }

        $content = (string) $post->post_content;
        if (
            ! has_shortcode($content, 'slingan_bgg_plays')
            && ! has_shortcode($content, 'slingan_bgg_plays_list')
            && ! has_shortcode($content, 'slingan_bgg_top_plays')
        ) {
            return;
        }

        self::enqueue_styles();
    }

    public static function enqueue_styles(): void
    {
        wp_enqueue_style(
            'slingan-bgg-plays',
            content_url('mu-plugins/slingan-bgg-plays/assets/bgg-plays.css'),
            [],
            '1.6.1'
        );
    }

    public static function default_username_public(): string
    {
        return self::default_username();
    }

    public static function has_api_token(): bool
    {
        return self::api_token() !== '';
    }

    public static function cache_ttl_public(): int
    {
        return self::cache_ttl();
    }

    /**
     * @param array{
     *   count?: int,
     *   username?: string,
     *   eyebrow?: string,
     *   title?: string,
     *   intro?: string,
     *   list_url?: string,
     *   location?: string,
     *   location_match?: string,
     *   months?: int,
     *   year?: int
     * } $args
     */
    public static function render_section(array $args): string
    {
        $username = isset($args['username']) && $args['username'] !== ''
            ? sanitize_text_field((string) $args['username'])
            : self::default_username();

        if ($username === '') {
            return self::render_message(__('Ange BGG-användarnamn under Inställningar → BGG spel.', 'slingan-bgg'));
        }

        if (self::api_token() === '') {
            return self::render_message(__('BGG API-token saknas. Registrera en app på BoardGameGeek och lägg in token under Inställningar → BGG spel.', 'slingan-bgg'));
        }

        $count = isset($args['count']) ? max(1, min(12, (int) $args['count'])) : 4;
        $location = isset($args['location']) ? trim((string) $args['location']) : '';
        $locationMatch = isset($args['location_match'])
            ? self::sanitize_location_match($args['location_match'])
            : 'exact';

        $months = isset($args['months']) ? self::sanitize_months((int) $args['months']) : 0;
        $year = isset($args['year']) ? self::sanitize_year((int) $args['year']) : 0;
        if ($year > 0) {
            $months = 0;
        }

        $plays = self::get_cached_plays($username, $count, $location, $locationMatch, $months, $year);

        if ($plays === null) {
            return self::render_message(__('Kunde inte hämta spel från BoardGameGeek just nu. Försök igen om en stund.', 'slingan-bgg'));
        }

        if ($plays === []) {
            if ($location !== '') {
                return self::render_message(
                    sprintf(
                        /* translators: %s: location filter label */
                        __('Inga inloggade spel hittades på platsen «%s».', 'slingan-bgg'),
                        self::location_filter_label($location)
                    )
                );
            }

            if ($months > 0 || $year > 0) {
                return self::render_message(
                    sprintf(
                        /* translators: %s: period label */
                        __('Inga inloggade spel hittades (%s).', 'slingan-bgg'),
                        self::period_filter_label($months, $year)
                    )
                );
            }

            return self::render_message(__('Inga inloggade spel hittades för den här profilen.', 'slingan-bgg'));
        }

        $gameIds = array_map(static fn (array $p): int => (int) $p['game_id'], $plays);
        $images = self::get_cached_images($gameIds);

        $eyebrow = isset($args['eyebrow']) ? (string) $args['eyebrow'] : '';
        $title = isset($args['title']) ? (string) $args['title'] : '';
        $intro = isset($args['intro']) ? (string) $args['intro'] : '';
        $listUrl = isset($args['list_url']) ? (string) $args['list_url'] : '';
        $locationFilterLabel = '';
        $monthsFilterLabel = '';
        $collectionRatings = self::collection_ratings_map($username);

        ob_start();
        include SLINGAN_BGG_PLAYS_DIR . '/templates/plays-section.php';

        return (string) ob_get_clean();
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private static function get_cached_plays(
        string $username,
        int $count,
        string $location = '',
        string $locationMatch = 'exact',
        int $months = 0,
        int $year = 0
    ): ?array {
        $cacheKey = 'slingan_bgg_plays_v6_' . md5(
            strtolower($username)
            . '|' . $count
            . '|' . strtolower($location)
            . '|' . $locationMatch
            . '|' . Slingan_Bgg_Api::period_cache_segment($months, $year)
        );
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $plays = Slingan_Bgg_Api::fetch_recent_plays($username, $count, $location, $locationMatch, $months, $year);
        if ($plays === null) {
            return null;
        }

        set_transient($cacheKey, $plays, self::cache_ttl());

        return $plays;
    }

    public static function location_filter_label(string $locationFilter): string
    {
        $parts = array_values(array_filter(array_map('trim', explode('|', $locationFilter))));
        $labels = [];
        foreach ($parts as $part) {
            if ($part === '(tom)' || $part === '__empty__') {
                $labels[] = __('ingen plats angiven', 'slingan-bgg');
            } else {
                $labels[] = $part;
            }
        }

        if ($labels === []) {
            return $locationFilter;
        }

        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels) . ' ' . __('eller', 'slingan-bgg') . ' ' . $last;
    }

    /**
     * @param int[] $gameIds
     * @return array<int, array{thumbnail: string, image: string}>
     */
    private static function get_cached_images(array $gameIds): array
    {
        sort($gameIds);
        $cacheKey = 'slingan_bgg_images_' . md5(implode(',', $gameIds));
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $images = Slingan_Bgg_Api::fetch_game_images($gameIds);
        set_transient($cacheKey, $images, self::cache_ttl());

        return $images;
    }

    /**
     * @param int[] $gameIds
     * @return array<int, array{thumbnail: string, image: string}>
     */
    public static function get_cached_images_public(array $gameIds): array
    {
        return self::get_cached_images($gameIds);
    }

    private static function default_username(): string
    {
        if (defined('SLINGAN_BGG_USERNAME') && is_string(SLINGAN_BGG_USERNAME)) {
            $u = trim(SLINGAN_BGG_USERNAME);
            if ($u !== '') {
                return $u;
            }
        }

        return trim((string) get_option(self::OPTION_USERNAME, ''));
    }

    private static function api_token(): string
    {
        if (defined('SLINGAN_BGG_API_TOKEN') && is_string(SLINGAN_BGG_API_TOKEN)) {
            return trim(SLINGAN_BGG_API_TOKEN);
        }

        return trim((string) get_option(self::OPTION_TOKEN, ''));
    }

    private static function cache_ttl(): int
    {
        return max(300, min(86400, (int) get_option(self::OPTION_CACHE_TTL, 3600)));
    }

    private static function render_message(string $message): string
    {
        return '<section class="slingan-tiles slingan-bgg-plays"><p class="slingan-tiles__empty">' . esc_html($message) . '</p></section>';
    }

    public static function tile_color(int $index): string
    {
        if (function_exists('slingan_front_tile_color')) {
            return slingan_front_tile_color($index);
        }

        $colors = ['#d24749', '#1e1e1e', '#787878', '#a63a3c'];

        return $colors[max(0, $index) % count($colors)];
    }

    /**
     * @return array{datetime: string, label: string}
     */
    public static function play_date_parts(string $isoDate): array
    {
        $ts = strtotime($isoDate);

        if ($ts === false) {
            return ['datetime' => '', 'label' => ''];
        }

        return [
            'datetime' => gmdate('Y-m-d', $ts),
            'label' => date_i18n('j M', $ts),
        ];
    }

    /**
     * Compact winner + score label for the tile image badge.
     *
     * @param array{players?: list<array{name: string, username: string, score: string, win: bool, score_int: int|null}>} $play
     * @return array{label: string, variant: string}
     */
    public static function play_result_badge(array $play, string $profileUsername): array
    {
        $outcome = self::resolve_play_outcome($play, $profileUsername);
        if ($outcome === null) {
            return ['label' => '', 'variant' => 'none'];
        }

        $winners = $outcome['winners'];
        $profileWon = $outcome['profile_won'];
        $singleWinner = count($winners) === 1;

        if ($outcome['is_tie']) {
            return [
                'label' => __('Oavgjort', 'slingan-bgg') . ' · ' . self::join_player_scores($winners),
                'variant' => 'tie',
            ];
        }

        if ($singleWinner) {
            return [
                'label' => self::player_score_compact($winners[0]),
                'variant' => $profileWon ? 'self' : 'other',
            ];
        }

        if ($profileWon) {
            return [
                'label' => __('Delad seger', 'slingan-bgg') . ' · ' . self::join_player_scores($winners),
                'variant' => 'self',
            ];
        }

        return [
            'label' => self::join_player_scores($winners),
            'variant' => 'tie',
        ];
    }

    /**
     * @param array{players?: list<array{name: string, username: string, score: string, win: bool, score_int: int|null}>} $play
     * @return array{
     *   winners: list<array{name: string, username: string, score: string, win: bool, score_int: int|null}>,
     *   profile_won: bool,
     *   is_tie: bool
     * }|null
     */
    private static function resolve_play_outcome(array $play, string $profileUsername): ?array
    {
        $players = $play['players'] ?? [];
        if ($players === []) {
            return null;
        }

        $profileUsername = strtolower(trim($profileUsername));
        $winners = array_values(array_filter($players, static fn (array $p): bool => $p['win']));
        $isTie = false;

        if ($winners === []) {
            $withScores = array_values(array_filter(
                $players,
                static fn (array $p): bool => $p['score_int'] !== null
            ));

            if ($withScores === []) {
                return null;
            }

            usort(
                $withScores,
                static fn (array $a, array $b): int => (int) $b['score_int'] <=> (int) $a['score_int']
            );
            $topScore = $withScores[0]['score_int'];
            $winners = array_values(array_filter(
                $withScores,
                static fn (array $p): bool => $p['score_int'] === $topScore
            ));

            if (count($winners) > 1) {
                $isTie = true;
            }
        }

        $profileWon = false;
        if ($profileUsername !== '') {
            foreach ($winners as $winner) {
                if (strtolower($winner['username']) === $profileUsername) {
                    $profileWon = true;
                    break;
                }
            }
        }

        return [
            'winners' => $winners,
            'profile_won' => $profileWon,
            'is_tie' => $isTie,
        ];
    }

    /**
     * @param array{name: string, score: string} $player
     */
    private static function player_score_compact(array $player): string
    {
        if ($player['score'] !== '') {
            return $player['name'] . ' · ' . $player['score'] . ' p';
        }

        return $player['name'];
    }

    /**
     * @param list<array{name: string, score: string}> $players
     */
    private static function join_player_scores(array $players): string
    {
        $parts = array_map(
            static fn (array $player): string => self::player_score_compact($player),
            $players
        );

        return implode(' · ', array_values(array_filter($parts)));
    }

    /**
     * Personal BGG rating (1–10) logged by the profile user on this play, if any.
     *
     * @param array{players?: list<array{username: string, rating: float|null}>} $play
     */
    public static function play_profile_rating(array $play, string $profileUsername): ?float
    {
        $profileUsername = strtolower(trim($profileUsername));
        if ($profileUsername === '') {
            return null;
        }

        foreach ($play['players'] ?? [] as $player) {
            if (strtolower($player['username']) === $profileUsername) {
                $rating = $player['rating'] ?? null;

                return is_float($rating) && $rating > 0 ? $rating : null;
            }
        }

        return null;
    }

    /**
     * @return array<int, float>
     */
    public static function collection_ratings_map(string $username): array
    {
        $map = Slingan_Bgg_Api::fetch_collection_ratings_map($username);

        return is_array($map) ? $map : [];
    }

    public static function collection_rating_label(int $gameId, array $ratingsMap): string
    {
        if ($gameId <= 0 || ! isset($ratingsMap[$gameId])) {
            return '';
        }

        return self::format_rating((float) $ratingsMap[$gameId]);
    }

    public static function format_rating(?float $rating): string
    {
        if ($rating === null || $rating <= 0) {
            return '';
        }

        $formatted = number_format($rating, 1, ',', '');
        if (str_ends_with($formatted, ',0')) {
            $formatted = substr($formatted, 0, -2);
        }

        return $formatted;
    }

    /**
     * @param array{length: int, player_count: int, quantity: int, location: string, comments: string, players?: list<array{username: string, rating: float|null}>} $play
     */
    public static function play_meta_line(array $play, string $profileUsername = ''): string
    {
        $parts = [];

        if ($play['player_count'] > 0) {
            $parts[] = sprintf(
                /* translators: %d: player count */
                _n('%d spelare', '%d spelare', $play['player_count'], 'slingan-bgg'),
                $play['player_count']
            );
        }

        if ($play['length'] > 0) {
            $parts[] = sprintf(
                /* translators: %d: minutes */
                __('%d min', 'slingan-bgg'),
                $play['length']
            );
        }

        if ($play['quantity'] > 1) {
            $parts[] = sprintf(
                /* translators: %d: play count same day */
                __('×%d', 'slingan-bgg'),
                $play['quantity']
            );
        }

        if ($play['location'] !== '') {
            $parts[] = $play['location'];
        }

        return implode(' · ', $parts);
    }

    /**
     * @param array{comments: string} $play
     */
    public static function play_excerpt(array $play): string
    {
        $comments = trim($play['comments']);
        if ($comments === '') {
            return '';
        }

        return wp_trim_words($comments, 18, ' …');
    }

    public static function game_url(int $gameId, string $gameName): string
    {
        $slug = sanitize_title($gameName);

        return 'https://boardgamegeek.com/boardgame/' . $gameId . ($slug !== '' ? '/' . $slug : '');
    }

    public static function play_url(int $playId): string
    {
        return 'https://boardgamegeek.com/play/' . $playId;
    }
}
