# Slingan — NAS staging

WordPress in Docker on the Synology NAS — **same NAS, SSH user, and bot key as Mörk Quest** (`morkwebb`), different project folder and port.

## Defaults

| Item | Value |
|------|--------|
| URL | `http://100.72.42.84:8083` (Tailscale IP + port **8083**) |
| NAS path | `/volume1/docker/slingan-staging` |
| SSH | `bot@100.72.42.84` port **9250** |
| SSH key (if present) | `~/.ssh/id_ed25519_nas_bot` |

Other sites on the NAS: **8080** Mörk Quest, **8081** UtopiaZtudioz, **8082** State of Trust staging; **Slingan uses 8083**.

Override any of this by copying `.env.staging.example` to `.env.staging` in the repo root (gitignored).

## One-time setup on the NAS

1. **Create the project folder** (SSH as `bot` or use File Station):

   ```text
   /volume1/docker/slingan-staging
   ```

2. **First deploy from your Mac** (uploads Compose + code, but stack needs `.env`):

   ```bash
   cd ~/Documents/slingan_webb
   ./scripts/deploy-staging.sh
   ```

   If `.env` is missing, the script stops after uploading files.

3. **Create `.env` on the NAS** — copy the uploaded `.env.example`, set strong passwords, and match `WP_HOME` / `WP_SITEURL` to how you reach the site (Tailscale IP and port 8083, or a hostname if you add one):

   ```bash
   ssh -p 9250 -i ~/.ssh/id_ed25519_nas_bot bot@100.72.42.84
   cd /volume1/docker/slingan-staging
   cp .env.example .env
   nano .env   # edit passwords and URLs
   sudo -n /usr/local/bin/docker compose up -d
   ```

   Alternatively: in **Container Manager**, import the project folder and start the stack (same `docker-compose.yml`).

4. **Open staging in the browser** and finish the **WordPress install wizard** (language, admin user, etc.).

5. **Install Board Games** — commercial theme, not in Git. In **Utseende → Teman**, upload the same theme zip you use on Local, then activate it.

6. **Seed Slingan content** — push to `main` (with seed/MU-plugin changes) or run **GitHub Actions → Staging (NAS) → Run workflow** once. Or locally: `./scripts/seed-staging-content.sh --yes`.

## Routine workflow

```text
Local → git push main → GitHub Actions → review staging → production (manual)
```

After one-time setup, you **do not** run deploy scripts for normal changes — **push to `main`** is enough. See **`docs/github-actions.md`**.

Optional: `./scripts/backup-staging.sh` before risky DB/content experiments.

## What deploy uploads

- `docker/staging/docker-compose.yml` → `docker-compose.yml` on NAS
- `docker/staging/.env.example` → reference for `.env` (not overwritten if you already have `.env`)
- `wordpress/wp-content/mu-plugins/` (e.g. `slingan-frontpage`)
- `wordpress/wp-content/themes/slingan/` (fallback theme)

**Not uploaded:** Board Games theme (install manually on staging), database, uploads.

## Rollback

```bash
BACKUP_ID=20260520-143000 ./scripts/rollback-staging.sh
```

Use the folder name under `/volume1/docker/slingan-staging/backups/` from `backup-staging.sh`.

## Troubleshooting

- **Deploy says no `.env`** — create `.env` on the NAS from `.env.example`, then re-run deploy.
- **Seed fails** — complete WP install + activate Board Games first.
- **Blank banner** — run seed; check **Utseende → Anpassa → Frontpage settings → Enable Banner**.
- **Port conflict** — change `WORDPRESS_PORT` in NAS `.env` and `STAGING_URL` in `.env.staging`.
