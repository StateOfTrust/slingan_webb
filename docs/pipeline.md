# Slingan WordPress pipeline

Slingan is deployed separately from [State of Trust](https://github.com/StateOfTrust/StateOfTrust).

## Staging (NAS)

| Setting | Default |
|---------|---------|
| URL | `http://100.72.42.84:8083/` |
| NAS path | `/volume1/docker/slingan-staging` |
| SSH | `bot@100.72.42.84:9250` (Tailscale) |

1. On the NAS, create the project folder and copy `docker/staging/.env.example` to `.env` with real passwords.
2. Start the stack in Container Manager (or `docker compose up -d` over SSH).
3. From your Mac: `./scripts/deploy-staging.sh` then `./scripts/seed-staging-content.sh`.

## Production

Copy `.env.production.example` to `.env.production` when Loopia (or other host) paths are known. Theme deploy is code-only; take DB and uploads backups before releases.

## Git owns

- `wordpress/wp-content/themes/slingan`
- `docker/staging`
- `scripts/`
- `docs/`

Git does not own database, uploads, or credentials.
