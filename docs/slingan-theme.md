# Slingan theme (in-repo)

The **Slingan** theme lives in Git at `wordpress/wp-content/themes/slingan/`. It is the recommended theme for this project.

## What you get

- **Front page:** slim red intro line + **four tiles** in one block (visible without scrolling on desktop). No separate `/blog/` archive.
- **No** shop strip, Freemius, or plugin conflicts
- **Colors:** State of Trust profile (red `#d24749`, charcoal `#1e1e1e`, off-white `#f5f5f0`, gray `#787878`) — same as `StateOfTrust` theme
- **Header:** bundled `assets/slingan-logo.png` (stroke logo); optional override via **Utseende → Anpassa → Webbplatsens identitet → Logotyp**
- **Customizer:** Utseende → Anpassa → **Slingan startsida** (banner, colours) and **Sidhuvud** — tiles are not manual; they follow the blog
- **Pages & blog:** simple templates for inner pages and the blog index

## Local

```bash
./scripts/seed-local-content.sh
```

Seed activates **slingan** by default and fills Swedish copy.

To use Board Games instead (not recommended):

```bash
SLINGAN_THEME=board-games-pro ./scripts/seed-local-content.sh
```

## Production

Three ways to install the theme (same files in Git):

| Method | When to use |
|--------|-------------|
| **`./scripts/deploy-production.sh`** | Normal for this project — rsyncs `themes/slingan/` to Loopia over SSH |
| **`./scripts/package-theme.sh`** | Builds `dist/slingan-X.Y.Z.zip` for wp-admin upload or manual unzip |
| **GitHub → NAS staging** | Automatic on push to `main` (see `docs/github-actions.md`) |

After the theme is on the server:

1. wp-admin → **Utseende → Teman → Slingan** → activate.
2. Run `./scripts/seed-production-content.sh` (or seed with `SLINGAN_THEME=slingan` on the server).

### Zip package (Loopia file manager / upload)

```bash
./scripts/package-theme.sh
```

Creates `dist/slingan-<version>.zip` (folder inside zip is `slingan/`, as WordPress expects). Upload under **Utseende → Teman → Lägg till → Ladda upp tema**, or extract to `wp-content/themes/slingan/`.

Zips are gitignored; rebuild before each release.

You can remove Board Games Pro, compat MU plugins, and Superb Blocks from the critical path once this theme is live.

## Files

| File | Role |
|------|------|
| `front-page.php` | Static front page layout |
| `template-parts/hero.php` | Banner |
| `template-parts/tiles.php` | Four latest posts |
| `inc/front-post-tiles.php` | Query + tile colours |
| `inc/customizer.php` | All editable strings and colours |
