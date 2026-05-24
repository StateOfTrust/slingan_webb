<?php

declare(strict_types=1);

/**
 * BoardGameGeek XML API2 client (plays + thing metadata).
 */
final class Slingan_Bgg_Api
{
    private const API_BASE = 'https://boardgamegeek.com/xmlapi2/';

    private const MAX_RETRIES = 6;

    private const RETRY_SLEEP_SECONDS = 2;

    private const PLAYS_PAGE_SIZE = 100;

    /** Max API pages scanned when filtering by location (100 plays per page). */
    private const MAX_LOCATION_SCAN_PAGES = 10;

    /** Max API pages scanned when building a filtered play list. */
    private const MAX_LIST_SCAN_PAGES = 30;

    /**
     * @return array<int, array{
     *   play_id: int,
     *   date: string,
     *   game_id: int,
     *   game_name: string,
     *   quantity: int,
     *   length: int,
     *   location: string,
     *   comments: string,
     *   player_count: int,
     *   players: list<array{name: string, username: string, score: string, win: bool, score_int: int|null, rating: float|null}>
     * }>|null null on failure
     */
    public static function fetch_recent_plays(
        string $username,
        int $limit,
        string $locationFilter = '',
        string $locationMatch = 'exact',
        int $months = 0,
        int $year = 0
    ): ?array {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $limit = max(1, min(20, $limit));
        $locationFilter = trim($locationFilter);
        $scanPages = $locationFilter === '' ? 1 : self::MAX_LOCATION_SCAN_PAGES;

        $plays = [];
        for ($page = 1; $page <= $scanPages; $page++) {
            $bundle = self::parse_plays_page($username, $page, $months, $year);
            if ($bundle === null) {
                return $plays === [] ? null : $plays;
            }

            foreach ($bundle['plays'] as $play) {
                if ($locationFilter !== '' && ! self::play_matches_location($play['location'], $locationFilter, $locationMatch)) {
                    continue;
                }

                $plays[] = $play;
                if (count($plays) >= $limit) {
                    return $plays;
                }
            }

            if (count($bundle['plays']) < self::PLAYS_PAGE_SIZE) {
                break;
            }
        }

        return $plays;
    }

    /**
     * Paginated list slice (all plays or location-filtered).
     *
     * @return array{items: list<array<string, mixed>>, total: int, total_pages: int}|null
     */
    public static function fetch_list_slice(
        string $username,
        int $listPage,
        int $perPage,
        string $locationFilter = '',
        string $locationMatch = 'exact',
        int $months = 0,
        int $year = 0
    ): ?array {
        $username = trim($username);
        if ($username === '' || $perPage < 1) {
            return null;
        }

        $listPage = max(1, $listPage);
        $perPage = min(100, $perPage);
        $offset = ($listPage - 1) * $perPage;
        $locationFilter = trim($locationFilter);

        if ($locationFilter === '') {
            return self::fetch_list_slice_unfiltered($username, $offset, $perPage, $months, $year);
        }

        return self::fetch_list_slice_filtered($username, $offset, $perPage, $locationFilter, $locationMatch, $months, $year);
    }

    /**
     * @return array{plays: list<array<string, mixed>>, total: int}|null
     */
    public static function parse_plays_page(string $username, int $page, int $months = 0, int $year = 0): ?array
    {
        $bounds = self::play_date_bounds($months, $year);
        $xml = self::fetch_plays_page($username, $page, $bounds['mindate'], $bounds['maxdate']);
        if ($xml === null) {
            return null;
        }

        $plays = [];
        foreach ($xml->play as $playNode) {
            $play = self::parse_play_node($playNode);
            if ($play === null) {
                continue;
            }

            if (! self::play_date_in_bounds($play['date'], $bounds['mindate'], $bounds['maxdate'])) {
                continue;
            }

            $plays[] = $play;
        }

        return [
            'plays' => $plays,
            'total' => max(0, (int) ($xml['total'] ?? 0)),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, total_pages: int}|null
     */
    private static function fetch_list_slice_unfiltered(
        string $username,
        int $offset,
        int $perPage,
        int $months = 0,
        int $year = 0
    ): ?array {
        $collected = [];
        $total = 0;
        $remaining = $perPage;
        $bggPage = (int) floor($offset / self::PLAYS_PAGE_SIZE) + 1;
        $skip = $offset % self::PLAYS_PAGE_SIZE;

        while ($remaining > 0 && $bggPage <= 500) {
            $bundle = self::parse_plays_page($username, $bggPage, $months, $year);
            if ($bundle === null) {
                return $collected === [] ? null : [
                    'items' => $collected,
                    'total' => $total,
                    'total_pages' => max(1, (int) ceil($total / $perPage)),
                ];
            }

            if ($total === 0) {
                $total = $bundle['total'];
            }

            $slice = array_slice($bundle['plays'], $skip);
            $take = array_slice($slice, 0, $remaining);
            $collected = array_merge($collected, $take);
            $remaining -= count($take);
            $skip = 0;
            $bggPage++;

            if (count($bundle['plays']) < self::PLAYS_PAGE_SIZE) {
                $total = min($total, $offset + count($collected));
                break;
            }
        }

        if ($collected === [] && $total === 0) {
            return ['items' => [], 'total' => 0, 'total_pages' => 0];
        }

        return [
            'items' => $collected,
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, total_pages: int}|null
     */
    private static function fetch_list_slice_filtered(
        string $username,
        int $offset,
        int $perPage,
        string $locationFilter,
        string $locationMatch,
        int $months = 0,
        int $year = 0
    ): ?array {
        $all = self::get_filtered_plays_catalog($username, $locationFilter, $locationMatch, $months, $year);
        if ($all === null) {
            return null;
        }

        $total = count($all);
        if ($total === 0) {
            return ['items' => [], 'total' => 0, 'total_pages' => 0];
        }

        return [
            'items' => array_slice($all, $offset, $perPage),
            'total' => $total,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * All plays in a period (and optional location filter), for stats / top-played grids.
     *
     * @return list<array<string, mixed>>|null
     */
    public static function fetch_all_matching_plays(
        string $username,
        string $locationFilter = '',
        string $locationMatch = 'exact',
        int $months = 0,
        int $year = 0
    ): ?array {
        return self::get_filtered_plays_catalog($username, $locationFilter, $locationMatch, $months, $year);
    }

    /**
     * @return list<array{
     *   game_id: int,
     *   game_name: string,
     *   play_count: int,
     *   latest_play: array<string, mixed>
     * }>|null
     */
    public static function fetch_top_played_games(
        string $username,
        int $limit = 9,
        string $locationFilter = '',
        string $locationMatch = 'exact',
        int $months = 0,
        int $year = 0
    ): ?array {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $limit = max(1, min(12, $limit));
        $cacheKey = 'slingan_bgg_top_v2_' . md5(
            strtolower($username)
            . '|' . $limit
            . '|' . strtolower(trim($locationFilter))
            . '|' . $locationMatch
            . '|' . self::period_cache_segment($months, $year)
        );
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $plays = self::fetch_all_matching_plays($username, $locationFilter, $locationMatch, $months, $year);
        if ($plays === null) {
            return null;
        }

        $byGame = [];
        foreach ($plays as $play) {
            $gameId = (int) $play['game_id'];
            if ($gameId <= 0) {
                continue;
            }

            if (! isset($byGame[$gameId])) {
                $byGame[$gameId] = [
                    'game_id' => $gameId,
                    'game_name' => (string) $play['game_name'],
                    'play_count' => 0,
                    'latest_play' => $play,
                ];
            }

            $byGame[$gameId]['play_count'] += max(1, (int) $play['quantity']);
            if ((string) $play['date'] >= (string) $byGame[$gameId]['latest_play']['date']) {
                $byGame[$gameId]['latest_play'] = $play;
            }
        }

        $rows = array_values($byGame);
        usort(
            $rows,
            static fn (array $a, array $b): int => (int) $b['play_count'] <=> (int) $a['play_count']
        );

        $top = array_slice($rows, 0, $limit);
        set_transient($cacheKey, $top, self::catalog_cache_ttl());

        return $top;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private static function get_filtered_plays_catalog(
        string $username,
        string $locationFilter,
        string $locationMatch,
        int $months = 0,
        int $year = 0
    ): ?array {
        $cacheKey = 'slingan_bgg_catalog_v4_' . md5(
            strtolower($username)
            . '|' . strtolower($locationFilter)
            . '|' . $locationMatch
            . '|' . self::period_cache_segment($months, $year)
        );
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $all = [];
        for ($page = 1; $page <= self::MAX_LIST_SCAN_PAGES; $page++) {
            $bundle = self::parse_plays_page($username, $page, $months, $year);
            if ($bundle === null) {
                return $all === [] ? null : $all;
            }

            foreach ($bundle['plays'] as $play) {
                if (self::play_matches_location($play['location'], $locationFilter, $locationMatch)) {
                    $all[] = $play;
                }
            }

            if (count($bundle['plays']) < self::PLAYS_PAGE_SIZE) {
                break;
            }
        }

        set_transient($cacheKey, $all, self::catalog_cache_ttl());

        return $all;
    }

    private static function catalog_cache_ttl(): int
    {
        return max(300, min(86400, (int) get_option('slingan_bgg_cache_ttl', 3600)));
    }

    private static function fetch_plays_page(
        string $username,
        int $page,
        string $mindate = '',
        string $maxdate = ''
    ): ?SimpleXMLElement {
        $query = [
            'username' => $username,
            'subtype' => 'boardgame',
            'page' => max(1, $page),
        ];

        if ($mindate !== '') {
            $query['mindate'] = $mindate;
        }

        if ($maxdate !== '') {
            $query['maxdate'] = $maxdate;
        }

        $url = add_query_arg($query, self::API_BASE . 'plays');

        return self::get_xml($url);
    }

    /**
     * @return array{mindate: string, maxdate: string}
     */
    public static function play_date_bounds(int $months, int $year): array
    {
        if ($year > 0) {
            return [
                'mindate' => sprintf('%04d-01-01', $year),
                'maxdate' => sprintf('%04d-12-31', $year),
            ];
        }

        return [
            'mindate' => self::mindate_from_months($months),
            'maxdate' => '',
        ];
    }

    public static function play_date_in_bounds(string $date, string $mindate, string $maxdate): bool
    {
        if ($date === '') {
            return true;
        }

        if ($mindate !== '' && $date < $mindate) {
            return false;
        }

        if ($maxdate !== '' && $date > $maxdate) {
            return false;
        }

        return true;
    }

    public static function period_cache_segment(int $months, int $year): string
    {
        if ($year > 0) {
            return 'y' . $year;
        }

        if ($months > 0) {
            return 'm' . $months;
        }

        return 'all';
    }

    public static function mindate_from_months(int $months): string
    {
        if ($months <= 0) {
            return '';
        }

        $timestamp = strtotime('-' . $months . ' months', (int) current_time('timestamp'));

        return $timestamp !== false ? wp_date('Y-m-d', $timestamp) : '';
    }

    /**
     * @return array{
     *   play_id: int,
     *   date: string,
     *   game_id: int,
     *   game_name: string,
     *   quantity: int,
     *   length: int,
     *   location: string,
     *   comments: string,
     *   player_count: int,
     *   players: list<array{name: string, username: string, score: string, win: bool, score_int: int|null, rating: float|null}>
     * }|null
     */
    private static function parse_play_node(SimpleXMLElement $playNode): ?array
    {
        $item = $playNode->item ?? null;
        if ($item === null) {
            return null;
        }

        $gameId = (int) ($item['objectid'] ?? 0);
        $gameName = trim((string) ($item['name'] ?? ''));
        if ($gameId <= 0 || $gameName === '') {
            return null;
        }

        $parsedPlayers = self::parse_players($playNode->players ?? null);

        return [
            'play_id' => (int) ($playNode['id'] ?? 0),
            'date' => trim((string) ($playNode['date'] ?? '')),
            'game_id' => $gameId,
            'game_name' => $gameName,
            'quantity' => max(1, (int) ($playNode['quantity'] ?? 1)),
            'length' => max(0, (int) ($playNode['length'] ?? 0)),
            'location' => trim((string) ($playNode['location'] ?? '')),
            'comments' => trim((string) ($playNode['comments'] ?? '')),
            'player_count' => count($parsedPlayers),
            'players' => $parsedPlayers,
        ];
    }

    private static function play_matches_location(string $playLocation, string $filter, string $match): bool
    {
        $filter = trim($filter);
        if ($filter === '') {
            return true;
        }

        $match = strtolower(trim($match));
        if ($match !== 'contains') {
            $match = 'exact';
        }

        $filters = array_values(array_filter(array_map('trim', explode('|', $filter))));
        if ($filters === []) {
            return true;
        }

        $playNorm = self::normalize_location($playLocation);

        foreach ($filters as $needle) {
            if ($needle === '(tom)' || $needle === '__empty__') {
                if ($playNorm === '') {
                    return true;
                }
                continue;
            }

            $needleNorm = self::normalize_location($needle);
            if ($match === 'contains') {
                if ($needleNorm !== '' && str_contains($playNorm, $needleNorm)) {
                    return true;
                }
            } elseif ($playNorm === $needleNorm) {
                return true;
            }
        }

        return false;
    }

    private static function normalize_location(string $location): string
    {
        $location = trim($location);

        return function_exists('mb_strtolower')
            ? mb_strtolower($location, 'UTF-8')
            : strtolower($location);
    }

    /**
     * User's personal game ratings from their BGG collection (objectid => 1–10).
     *
     * @return array<int, float>|null null on API failure
     */
    public static function fetch_collection_ratings_map(string $username): ?array
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $cacheKey = 'slingan_bgg_coll_ratings_v1_' . md5(strtolower($username));
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'username' => $username,
                'subtype' => 'boardgame',
                'stats' => 1,
            ],
            self::API_BASE . 'collection'
        );

        $xml = self::get_xml($url);
        if ($xml === null) {
            return null;
        }

        $ratings = [];
        foreach ($xml->item as $item) {
            $id = (int) ($item['objectid'] ?? 0);
            if ($id <= 0 || ! isset($item->stats->rating)) {
                continue;
            }

            $raw = trim((string) ($item->stats->rating['value'] ?? ''));
            if ($raw === '' || strtoupper($raw) === 'N/A' || ! is_numeric($raw)) {
                continue;
            }

            $value = (float) $raw;
            if ($value > 0) {
                $ratings[$id] = $value;
            }
        }

        set_transient($cacheKey, $ratings, self::catalog_cache_ttl());

        return $ratings;
    }

    /**
     * @param int[] $gameIds
     * @return array<int, array{thumbnail: string, image: string}>
     */
    public static function fetch_game_images(array $gameIds): array
    {
        $gameIds = array_values(array_unique(array_filter(array_map('intval', $gameIds))));
        if ($gameIds === []) {
            return [];
        }

        $url = add_query_arg(
            ['id' => implode(',', $gameIds)],
            self::API_BASE . 'thing'
        );

        $xml = self::get_xml($url);
        if ($xml === null) {
            return [];
        }

        $out = [];
        foreach ($xml->item as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $thumb = trim((string) ($item->thumbnail ?? ''));
            $image = trim((string) ($item->image ?? ''));
            $out[$id] = [
                'thumbnail' => $thumb,
                'image' => $image !== '' ? $image : $thumb,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{name: string, username: string, score: string, win: bool, score_int: int|null, rating: float|null}>
     */
    private static function parse_players(?SimpleXMLElement $playersNode): array
    {
        if ($playersNode === null) {
            return [];
        }

        $players = [];
        foreach ($playersNode->player as $playerNode) {
            $name = trim((string) ($playerNode['name'] ?? ''));
            $username = trim((string) ($playerNode['username'] ?? ''));
            if ($name === '' && $username !== '') {
                $name = $username;
            }
            if ($name === '') {
                continue;
            }

            $score = trim((string) ($playerNode['score'] ?? ''));
            $scoreInt = is_numeric($score) ? (int) $score : null;
            $ratingRaw = trim((string) ($playerNode['rating'] ?? ''));
            $rating = is_numeric($ratingRaw) && (float) $ratingRaw > 0 ? (float) $ratingRaw : null;

            $players[] = [
                'name' => $name,
                'username' => $username,
                'score' => $score,
                'win' => (string) ($playerNode['win'] ?? '') === '1',
                'score_int' => $scoreInt,
                'rating' => $rating,
            ];
        }

        return $players;
    }

    private static function api_token(): string
    {
        if (defined('SLINGAN_BGG_API_TOKEN') && is_string(SLINGAN_BGG_API_TOKEN)) {
            return trim(SLINGAN_BGG_API_TOKEN);
        }

        $stored = get_option('slingan_bgg_api_token', '');

        return is_string($stored) ? trim($stored) : '';
    }

    private static function get_xml(string $url): ?SimpleXMLElement
    {
        $token = self::api_token();
        if ($token === '') {
            return null;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/xml',
        ];

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            $response = wp_remote_get($url, [
                'timeout' => 20,
                'headers' => $headers,
            ]);

            if (is_wp_error($response)) {
                return null;
            }

            $code = (int) wp_remote_retrieve_response_code($response);
            $body = (string) wp_remote_retrieve_body($response);

            if ($code === 202) {
                sleep(self::RETRY_SLEEP_SECONDS);
                continue;
            }

            if ($code < 200 || $code >= 300 || $body === '') {
                return null;
            }

            $previous = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $xml instanceof SimpleXMLElement ? $xml : null;
        }

        return null;
    }
}
