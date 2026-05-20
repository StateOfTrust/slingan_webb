# Slingan

Git-backed WordPress project for the Slingan boardgame community site — separate from [State of Trust](https://github.com/StateOfTrust/StateOfTrust).

Repo: https://github.com/StateOfTrust/slingan_webb

## Pipeline

```text
Local Mac -> GitHub -> NAS staging -> production
```

| Environment | Default URL |
|-------------|-------------|
| Local | `http://slingan.local` |
| NAS staging | `http://100.72.42.84:8083` |
| Production | set in `.env.production` |

## Board Games theme

Install the commercial **Board Games** theme in WordPress on each environment (not in Git). The **startsida** uses the theme **banner** and **four promo tiles**; the **`slingan-frontpage`** MU plugin skips the WooCommerce product strip.

See **`docs/board-games-for-slingan.md`** and **`docs/nas-staging.md`**.

## Local

- Admin: `ola` / `othello`

```bash
./scripts/sync-local-mu-plugins.sh
./scripts/sync-local-theme.sh        # optional: fallback slingan theme
./scripts/seed-local-content.sh
./scripts/open-local-site.sh
```

## NAS staging (automatic)

**Push to `main`** → GitHub Actions runs `.github/workflows/staging.yml` → NAS staging is updated over SSH.

One-time setup: NAS `.env`, WordPress install, Board Games theme, and GitHub secret **`NAS_SSH_PRIVATE_KEY`**. See **`docs/github-actions.md`** and **`docs/nas-staging.md`**.

Default staging URL: `http://100.72.42.84:8083`

Manual fallback:

```bash
./scripts/deploy-staging.sh
./scripts/seed-staging-content.sh --yes
```

## Production (Loopia, same as Mörk Quest)

Account **`vfbi97`** on **`ssh.loopia.se`**. Default path **`slingan.se/public_html`** — confirm in Loopia if the folder name differs.

```bash
cp .env.production.example .env.production
./scripts/deploy-production.sh
./scripts/seed-production-content.sh
```

See `docs/pipeline.md`.
