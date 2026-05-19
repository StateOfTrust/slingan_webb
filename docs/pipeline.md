# Slingan — release pipeline

Personal site: no NAS staging. Develop locally, commit to Git, deploy to production when ready.

## Flow

```text
Local WP (slingan.local) -> GitHub -> production host
```

1. Develop in Local WP.
2. `./scripts/sync-local-theme.sh`
3. Commit and push to GitHub.
4. Back up production database and uploads.
5. `./scripts/deploy-production.sh`
6. `./scripts/seed-production-content.sh` when `seed-content.php` changed.
7. Verify the live URL.

## What Git owns

- `wordpress/wp-content/themes/slingan`
- `scripts/`
- `docs/`

## Production

Copy `.env.production.example` to `.env.production`. Set host, SSH user, WordPress paths, and `PRODUCTION_URL` for wherever Slingan is hosted.
