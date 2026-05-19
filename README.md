# Slingan

Git-backed WordPress project for the Slingan boardgame community site — separate from [State of Trust](https://github.com/StateOfTrust/StateOfTrust).

Repo: https://github.com/StateOfTrust/slingan_webb

## Pipeline

```text
Local Mac -> GitHub -> production
```

No NAS staging.

## Local

- Site: `http://slingan.local`
- Admin: `ola` / `othello`

```bash
./scripts/sync-local-theme.sh
./scripts/seed-local-content.sh
./scripts/reset-local-wp.sh --yes
```

If the browser opens the wrong site or domain, run `./scripts/seed-local-content.sh` from **this** repository (or `./scripts/reset-local-wp.sh --yes`). Scripts pick the MySQL socket that belongs to `Local Sites/slingan`, not another Local site.

## Production

```bash
cp .env.production.example .env.production
./scripts/deploy-production.sh
./scripts/seed-production-content.sh
```

See `docs/pipeline.md`.
