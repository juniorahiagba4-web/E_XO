#!/usr/bin/env bash
# Application setup — run as the deploy user (not root), after 01-server-setup.sh.
#   ssh laperledor@<server-ip> 'bash -s' < 02-app-setup.sh
#
# Clones the repo, installs dependencies, configures the .env, runs
# migrations, sets up Nginx + free SSL (Let's Encrypt), a supervised
# Horizon queue worker, the Laravel scheduler cron, and a disk-space
# alert. Safe to re-run (idempotent for the parts that matter).

set -euo pipefail

# --- Edit these before running -------------------------------------------
REPO_URL="https://github.com/juniorahiagba4-web/E_XO.git"
BRANCH="Wilfried"
# The backend lives on its own subdomain — the bare domain and "www" point
# to Vercel (the frontend) instead, configured separately in Vercel + DNS.
DOMAIN="api.laperledor-togo.com"
APP_DIR="/var/www/laperledor"
DB_PASSWORD="__PASTE_THE_PASSWORD_FROM_STEP_01_HERE__"
# Same DeepL key already used locally (see backend/.env on the dev machine) —
# without it, admin forms just leave the English fields blank, nothing breaks.
DEEPL_API_KEY=""
# Optional: a free ntfy.sh topic name (no account needed) to receive a push
# notification when disk space runs low. Pick any unguessable topic name,
# e.g. "laperledor-alerts-x7f2", and subscribe to it in the ntfy app.
NTFY_TOPIC=""
# ---------------------------------------------------------------------------

echo "==> Cloning the repository"
sudo mkdir -p "$APP_DIR"
sudo chown "$USER:www-data" "$APP_DIR"
git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR" 2>/dev/null || (cd "$APP_DIR" && git fetch && git checkout "$BRANCH")

cd "$APP_DIR/backend"

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Writing production .env"
if [ ! -f .env ]; then
    cp .env.example .env
fi
php -r '
$path = ".env";
$env = file_get_contents($path);
$set = function (string $key, string $value) use (&$env) {
    $escaped = preg_quote($key, "/");
    if (preg_match("/^{$escaped}=.*/m", $env)) {
        $env = preg_replace("/^{$escaped}=.*/m", "{$key}={$value}", $env);
    } else {
        $env .= "\n{$key}={$value}\n";
    }
};
$set("APP_ENV", "production");
$set("APP_DEBUG", "false");
$set("APP_URL", "https://'"$DOMAIN"'");
$set("DB_CONNECTION", "mysql");
$set("DB_HOST", "127.0.0.1");
$set("DB_PORT", "3306");
$set("DB_DATABASE", "laperledor");
$set("DB_USERNAME", "laperledor");
$set("DB_PASSWORD", "'"$DB_PASSWORD"'");
$set("REDIS_HOST", "127.0.0.1");
$set("REDIS_CLIENT", "phpredis");
$set("CACHE_STORE", "redis");
$set("QUEUE_CONNECTION", "redis");
$set("SESSION_DRIVER", "redis");
$set("FILESYSTEM_DISK", "public");
$set("CORS_ALLOWED_ORIGINS", "https://laperledor-togo.com,https://www.laperledor-togo.com");
if ("'"$DEEPL_API_KEY"'" !== "") {
    $set("DEEPL_API_KEY", "'"$DEEPL_API_KEY"'");
}
file_put_contents($path, $env);
'

php artisan key:generate --force
php artisan storage:link
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Setting file permissions"
sudo chown -R "$USER:www-data" "$APP_DIR"
sudo find "$APP_DIR/backend/storage" "$APP_DIR/backend/bootstrap/cache" -type d -exec chmod 775 {} \;
sudo find "$APP_DIR/backend/storage" "$APP_DIR/backend/bootstrap/cache" -type f -exec chmod 664 {} \;

echo "==> Configuring PHP-FPM pool (run as www-data, matches Nginx)"
sudo tee /etc/php/8.4/fpm/pool.d/laperledor.conf >/dev/null <<EOF
[laperledor]
user = www-data
group = www-data
listen = /run/php/laperledor.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 4
EOF
sudo systemctl restart php8.4-fpm

echo "==> Configuring Nginx"
sudo tee "/etc/nginx/sites-available/$DOMAIN" >/dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $APP_DIR/backend/public;
    index index.php;

    client_max_body_size 20m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/laperledor.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
sudo ln -sf "/etc/nginx/sites-available/$DOMAIN" "/etc/nginx/sites-enabled/$DOMAIN"
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

echo "==> Requesting a free SSL certificate (Let's Encrypt, auto-renews)"
sudo certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "admin@laperledor-togo.com" --redirect

echo "==> Setting up the Horizon queue worker as a supervised systemd service"
sudo tee /etc/systemd/system/laperledor-horizon.service >/dev/null <<EOF
[Unit]
Description=La Perle d'Or queue worker (Laravel Horizon)
After=network.target redis-server.service mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php8.4 $APP_DIR/backend/artisan horizon
ExecStopSec=30
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
EOF
sudo systemctl daemon-reload
sudo systemctl enable --now laperledor-horizon

echo "==> Setting up the Laravel scheduler (runs ExpireStaleQuotes hourly)"
(crontab -l 2>/dev/null | grep -v "artisan schedule:run"; echo "* * * * * cd $APP_DIR/backend && php8.4 artisan schedule:run >> /dev/null 2>&1") | crontab -

if [ -n "$NTFY_TOPIC" ]; then
    echo "==> Setting up a disk-space alert (checks daily, notifies via ntfy.sh if >80% full)"
    sudo tee /usr/local/bin/disk-alert.sh >/dev/null <<EOF
#!/usr/bin/env bash
USAGE=\$(df --output=pcent / | tail -1 | tr -dc '0-9')
if [ "\$USAGE" -ge 80 ]; then
    curl -s -d "Serveur La Perle d'Or : disque plein à \${USAGE}%, intervention nécessaire." "https://ntfy.sh/$NTFY_TOPIC" >/dev/null
fi
EOF
    sudo chmod +x /usr/local/bin/disk-alert.sh
    (crontab -l 2>/dev/null | grep -v "disk-alert.sh"; echo "0 8 * * * /usr/local/bin/disk-alert.sh") | crontab -
fi

cat <<EOF

============================================================
Application setup complete.

Site should now be live at: https://$DOMAIN
Queue worker status:  sudo systemctl status laperledor-horizon
Scheduler (cron):      crontab -l
============================================================
EOF
