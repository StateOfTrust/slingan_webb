# Slingan — release pipeline

Develop locally, review on NAS staging, deploy to production when ready.

## Flow

```text
Local WP (slingan.local) -> git push main -> GitHub Actions -> NAS staging -> production (manual)
```

1. Develop in Local WP.
2. Commit and **push to `main`** — staging deploys automatically (see `docs/github-actions.md`).
3. Review staging (default `http://100.72.42.84:8082`).
4. Back up production database and uploads.
5. `./scripts/deploy-production.sh`
6. `./scripts/seed-production-content.sh` when `seed-content.php` changed.
7. Verify the live URL.

First-time NAS + GitHub secrets: **`docs/nas-staging.md`**, **`docs/github-actions.md`**.

## What Git owns

- `wordpress/wp-content/mu-plugins/`
- `wordpress/wp-content/themes/slingan`
- `docker/staging/`
- `.github/workflows/staging.yml`
- `scripts/`
- `docs/`

## Staging scripts (manual / emergency)

| Script | Purpose |
|--------|---------|
| `deploy-staging.sh` | Same steps as the GitHub Action |
| `seed-staging-content.sh` | Seed (`--yes` or `CI=true` skips prompt) |
| `backup-staging.sh` | Snapshot DB + `wp-content` on NAS |
| `rollback-staging.sh` | Restore a backup folder |

## Production (Loopia)

Same account as **Mörk Quest** (`LOOPIA_USER=vfbi97`, `LOOPIA_HOST=ssh.loopia.se`). Copy `.env.production.example` to `.env.production` and confirm `LOOPIA_WP_PATH` matches the folder name on Loopia (default `slingan.se/public_html`).

```bash
./scripts/deploy-production.sh      # mu-plugins + fallback theme
./scripts/seed-production-content.sh
```

Board Games is installed on Loopia manually (not in Git).
