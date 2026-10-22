# Production deployment

La Perle d'Or is split in two:

| Part | Where | URL |
| --- | --- | --- |
| Backend (Laravel 13 + Filament 4) | OVHcloud VPS `54.39.230.49` | `https://api.laperledor-togo.com` |
| Frontend (Next.js 16) | Vercel | `https://laperledor-togo.com` (once the DNS is switched) |

The frontend talks to the backend through `NEXT_PUBLIC_API_URL`, which points
at `https://api.laperledor-togo.com/api/v1` in every Vercel environment
(production, preview and development). The backend allows the Vercel origins
through `CORS_ALLOWED_ORIGINS` in `backend/.env`.

## Scripts

Run them in this order, from the repository root.

### 1. `01-server-setup.sh` — server bootstrap (once)

```sh
ssh root@54.39.230.49 'bash -s' < deploy/01-server-setup.sh
```

Installs Nginx, MySQL, Redis, PHP 8.4, Composer, Certbot, fail2ban and
unattended-upgrades, creates the `laperledor` deploy user (passwordless sudo)
and the `laperledor` database. It prints the generated database password —
**copy it, it is needed by step 2**. Secrets are never stored in the repo.

### 2. `02-app-setup.sh` — application setup (once)

```sh
ssh laperledor@54.39.230.49 'bash -s' < deploy/02-app-setup.sh
```

Clone the repository on `Wilfried`, install the Composer dependencies, write
the production `.env`, run the migrations, configure the Nginx vhost, request
the Let's Encrypt certificate, start Horizon as a systemd service and install
the scheduler cron.

Edit the variables at the top of the script before running it:
`DOMAIN`, `DB_PASSWORD` (from step 1), `DEEPL_API_KEY` (same key as in
`backend/.env` locally) and `SEED_DATABASE="yes"` for the demo data.

### 3. `03-upload-media.sh` — catalogue images

```sh
SERVER=root@54.39.230.49 bash deploy/03-upload-media.sh
```

The images live in `backend/storage/app/public`, which is git-ignored, so they
cannot travel with a `git pull` and have to be copied over separately.

### 4. `export-catalogue.php` / `import-catalogue.php` — catalogue data

```sh
php deploy/export-catalogue.php /tmp/catalogue.json
scp /tmp/catalogue.json root@54.39.230.49:/tmp/catalogue.json
ssh root@54.39.230.49 'cd /var/www/laperledor/backend && php ../deploy/import-catalogue.php /tmp/catalogue.json'
```

Copies the categories, items, reviews, promotions and delivery slots of the
local development database into the production MySQL database, keeping the
row ids so the foreign keys stay valid. Users, roles and reservations are not
touched.

## Deploying a new version

```sh
# on the development machine
git push origin Wilfried

# on the server
ssh laperledor@54.39.230.49 'bash /var/www/laperledor/deploy/deploy.sh'
```

`deploy.sh` pulls the branch, updates the Composer dependencies, runs the
migrations, refreshes the caches and restarts Horizon and PHP-FPM. The
frontend is deployed automatically by Vercel (production branch: `Wilfried`).

## Operations

```sh
sudo systemctl status laperledor-horizon     # queue worker
sudo systemctl restart php8.4-fpm            # PHP
sudo nginx -t && sudo systemctl reload nginx # Nginx
sudo certbot renew --dry-run                 # SSL renewal
crontab -l                                   # scheduler
```

## Access

* Admin panel: `https://api.laperledor-togo.com/admin`
* Admin account: `admin@eventloc.tg` (the password is set by the seeder and
  must be changed on the first login)
* A second factor (authenticator app) is **required** by the Filament panel.

## Remaining manual step

The bare domain and `www` still point to the VPS in the Hostinger DNS zone.
They have to be switched to the Vercel nameservers/records, then
`laperledor-togo.com` must be added to the Vercel project as a custom domain.
`api.laperledor-togo.com` must stay on the VPS.
