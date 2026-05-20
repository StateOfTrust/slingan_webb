# Automatic staging (GitHub Actions)

Pushing to **`main`** deploys to NAS staging — no manual `./scripts/deploy-staging.sh` for routine work.

Same idea as the other WordPress projects on your NAS: **commit → push → staging updates**.

## What runs automatically

| Trigger | Deploy code + Compose | Seed content |
|---------|----------------------|--------------|
| Push to `main` | Always | Only if `scripts/seed-content.php` or `wordpress/wp-content/mu-plugins/**` changed |
| **Actions → Staging (NAS) → Run workflow** | Yes | Yes (always) |

Workflow file: `.github/workflows/staging.yml`

## One-time: GitHub repository secrets

Same NAS as **Mörk Quest** (`morkwebb`). You can reuse the **same secrets** in this repo (or set org-level secrets once for all WordPress projects).

In **GitHub → StateOfTrust/slingan_webb → Settings → Secrets and variables → Actions**:

| Secret | Required | Value (same as Mörk Quest) |
|--------|----------|----------------------------|
| `NAS_SSH_PRIVATE_KEY` | **Yes** | `~/.ssh/id_ed25519_nas_bot` private key |
| `NAS_HOST` | No | `100.72.42.84` |
| `NAS_PORT` | No | `9250` |
| `NAS_USER` | No | `bot` |
| `NAS_PROJECT` | No | `/volume1/docker/slingan-staging` (not `morkwebb-staging`) |
| `STAGING_URL` | No | `http://100.72.42.84:8083` (Mörk **8080**, SoT **8082**) |
| `NAS_DOCKER` | No | `sudo -n /usr/local/bin/docker` |

Only **`NAS_SSH_PRIVATE_KEY`** is mandatory if defaults match your NAS.

## One-time: NAS (still manual)

GitHub cannot create the NAS `.env` or install Board Games for you:

1. Create `/volume1/docker/slingan-staging/.env` from `.env.example` (passwords, `WP_HOME`, `WP_SITEURL`).
2. `docker compose up -d` on the NAS.
3. Finish WordPress install in the browser.
4. Install **Board Games** theme (zip).
5. Run **Actions → Staging (NAS) → Run workflow** once (or push a commit that touches the seed paths) so `seed-staging-content.sh` runs.

After that, **every push to `main`** keeps code in sync on staging.

## Network note

Deploy uses **SSH to the NAS** (Tailscale). GitHub’s hosted runners must be able to reach `NAS_HOST:NAS_PORT`. If deploy fails with SSH timeout:

- Use a **self-hosted runner** on your Mac/LAN, or
- Connect the job to Tailscale (e.g. [Tailscale GitHub Action](https://github.com/tailscale/github-action)), or
- Run deploy from your machine when off-network (scripts still work).

The workflow skips the HTTP “curl staging URL” check when `CI=true` (cloud runners often cannot open the Tailscale URL anyway).

## Production (Loopia)

Same host and SSH user as Mörk Quest: **`ssh.loopia.se`**, user **`vfbi97`**. Paths differ — see `.env.production.example` (`slingan.se/public_html`).

Production is **not** auto-deployed on push (same as Mörk Quest). When staging looks good:

```bash
cp .env.production.example .env.production   # adjust path if Loopia folder name differs
./scripts/deploy-production.sh
./scripts/seed-production-content.sh
```

## Local scripts

Still useful for emergencies or before secrets exist:

```bash
./scripts/deploy-staging.sh
./scripts/seed-staging-content.sh          # prompts SEED
./scripts/seed-staging-content.sh --yes    # non-interactive
```
