# Slingan BGG plays (WordPress MU plugin)

Shows the latest board games logged on a [BoardGameGeek](https://boardgamegeek.com/) profile, using the same tile/card layout as the Slingan blog roll (`slingan-tile`, `slingan-tiles__grid`).

## Setup

1. Register a **non-commercial** application at [boardgamegeek.com/applications](https://boardgamegeek.com/applications) and create an API **Bearer token**.
2. Store the token (pick one):

   - **Local / manual production:** copy `slingan-bgg-plays/slingan-bgg-secrets.example.php` to `slingan-bgg-secrets.php` in `wp-content/mu-plugins/` (gitignored; not deployed by Git). The example must stay in the subfolder — WordPress auto-loads every `.php` file directly in `mu-plugins/`.
   - **Production wp-config.php:** `define('SLINGAN_BGG_API_TOKEN', '…');` above “stop editing”.
   - **WordPress admin:** Settings → BGG spel (stored in DB).

   Optional: `define('SLINGAN_BGG_USERNAME', 'your-bgg-username');` in the same secrets file or wp-config.

3. Set **BGG-användarnamn** under **Settings → BGG spel** if not in wp-config.
4. Deploy MU plugins: `./scripts/deploy-production.sh` (syncs `wordpress/wp-content/mu-plugins/`).

## Usage

### Tile grid (latest plays)

Shortcode on any page or post:

```
[slingan_bgg_plays]
```

### Full list with pagination

```
[slingan_bgg_plays_list per_page="25"]
```

Page 2: add `?bgg_plays_page=2` to the page URL (e.g. `/mina-spel/?bgg_plays_page=2`).

Table columns: **Datum**, **Spel**, **Vinnare** (name + points), **Betyg** (collection rating).

### Top played grid (3×3)

Shows the games with the most logged plays in a time period as a **3×3 grid of cover images only** (no titles, scores, or captions):

```
[slingan_bgg_top_plays year="2024"]
```

| Attribute | Default |
|-----------|---------|
| `count` | 9 (max 9) |
| `months` | From settings — rolling last N months |
| `year` | Calendar year, e.g. `2024` (overrides `months` when set) |
| Period | Defaults to **current year**; override with `year="2023"` or `months="12"` |
| `location` / `location_match` | Same as tiles |
| `username` | From settings |

Images link to the game on BGG. `aria-label` on each cell is the only non-visual label (accessibility).

Optional attributes:

| Attribute | Default |
|-----------|---------|
| `count` | 4 (max 12) |
| `username` | From settings / `SLINGAN_BGG_USERNAME` |
| `location` | _(empty)_ — BGG location; multiple: `Slingan\|Hos Martina`; no location: `(tom)` |
| `location_match` | `exact` — or `contains` |
| `months` | Settings default; `0` in shortcode = no month filter. If neither `year` nor `months` applies, **current year** is used |
| `year` | Calendar year, e.g. `2024` (overrides `months` when set on the shortcode) |
| `eyebrow` | Från BGG |
| `title` | Senast spelat |
| `intro` | Short description |
| `profile_url` | `https://boardgamegeek.com/user/{username}` |

List shortcode `[slingan_bgg_plays_list]`:

| Attribute | Default |
|-----------|---------|
| `per_page` | 25 (Settings, max 100) |
| `username` | From settings |
| `location` / `location_match` | Same as tiles |
| `months` | From settings; `0` = all time |
| `year` | Calendar year; overrides `months` when set. List links from tiles use `?bgg_year=2024` |
| `title` | Alla inloggade spel |
| `intro` | _(empty)_ |

Example for a dedicated “Mina spel” page:

```
[slingan_bgg_plays count="4" username="theFocusshift" title="Senast spelat" intro="Det här har jag loggat på BGG."]

[slingan_bgg_plays location="Slingan" location_match="contains" title="Spel på Slingan"]

[slingan_bgg_plays_list per_page="20" location="Slingan" title="Alla spel på Slingan"]

[slingan_bgg_plays months="6" title="Senast spelat"]
[slingan_bgg_plays_list months="12" per_page="30"]

[slingan_bgg_top_plays year="2024" location="Slingan"]
[slingan_bgg_top_plays months="12"]
```

Set default months under **Settings → BGG spel** (`0` = use current year; set e.g. `6` for rolling 6 months instead).

Location filtering scans up to 10×100 recent plays on BGG (no API location parameter). Set a default under **Settings → BGG spel**.

## Caching

Responses are cached with WordPress transients (default 1 hour). BGG requires server-side requests with an `Authorization: Bearer` header — the token must never be exposed in the browser.

## Theme

Works best with the **slingan** child theme (tile CSS in `assets/site.css`). Other themes still render, but may need matching styles.

## BGG terms

Public-facing use must include a “Powered by BGG” credit (included in the shortcode output). See [Using the XML API](https://boardgamegeek.com/using_the_xml_api).
