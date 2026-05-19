# Slingan

Git-backed WordPress project for [Slingan](https://github.com/StateOfTrust/slingan_webb) — boardgame community and studio site, separate from State of Trust.

## Pipeline

```text
Local Mac -> GitHub -> NAS staging (:8083) -> production (when configured)
```

## Local setup

1. Create a Local WP site named **slingan** (or set `LOCAL_WP_PATH` to your install).
2. `./scripts/sync-local-theme.sh`
3. `./scripts/reset-local-wp.sh --yes` for a clean database (destructive).
4. Or `./scripts/seed-local-content.sh` on an existing install.

Default local URL: `http://slingan.local`  
Default admin: `ola` / `othello`

## Scripts

| Script | Purpose |
|--------|---------|
| `scripts/reset-local-wp.sh --yes` | Wipe local DB and reinstall WordPress |
| `scripts/sync-local-theme.sh` | Sync `slingan` theme to Local WP |
| `scripts/seed-local-content.sh` | Apply Slingan pages and options locally |
| `scripts/deploy-staging.sh` | Deploy theme + Compose to NAS |
| `scripts/seed-staging-content.sh` | Seed staging after deploy |

See `docs/pipeline.md` for NAS paths and staging URL.
