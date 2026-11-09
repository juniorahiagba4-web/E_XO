#!/usr/bin/env bash
# Pre-production setup — run once, as the deploy user, after the DNS record
# "api-test" points at the server:
#
#   ssh laperledor@54.39.230.49 'bash -s' < deploy/05-staging-setup.sh
#
# Installs a second, fully separate copy of the backend next to production:
#   * code:      /var/www/laperledor-staging, branch "staging"
#   * address:   https://api-test.laperledor-togo.com (admin: /admin)
#   * database:  laperledor_staging (its own MySQL user)
#   * Redis:     databases 2 and 3 (production uses 0 and 1), its own prefixes
#   * worker:    laperledor-staging-horizon service, its own scheduler cron
# It copies the catalogue (categories, items, promotions, settings, images)
# from production, but no customer, order, quote PDF or account. Safe to
# re-run: existing data and passwords are kept.

set -euo pipefail

DOMAIN="api-test.laperledor-togo.com"
FRONTEND_URL="https://test.laperledor-togo.com"
SERVER_IP="54.39.230.49"
APP_DIR="/var/www/laperledor-staging"
PROD_DIR="/var/www/laperledor"
BRANCH="staging"
PHP_VERSION="8.4"
DB_NAME="laperledor_staging"
DB_USER="laperledor_staging"
ADMIN_EMAIL="admin@eventloc.tg"

echo "==> Checking that $DOMAIN points at this server"
if ! getent ahosts "$DOMAIN" | grep -q "$SERVER_IP"; then
    echo "!! $DOMAIN does not resolve to $SERVER_IP yet." >&2
    echo "!! Add the DNS record in Hostinger (type A, name api-test, value $SERVER_IP), wait a few minutes and re-run." >&2
    exit 1
fi

echo "==> Code (branch $BRANCH)"
sudo mkdir -p "$APP_DIR"
sudo chown "$USER:www-data" "$APP_DIR"
if [ ! -d "$APP_DIR/.git" ]; then
    git clone --branch "$BRANCH" "$(git -C "$PROD_DIR" remote get-url origin)" "$APP_DIR"
else
    git -C "$APP_DIR" fetch origin
    git -C "$APP_DIR" checkout "$BRANCH"
    git -C "$APP_DIR" reset --hard "origin/$BRANCH"
fi
cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Configuration (.env)"
FIRST_INSTALL="no"
if [ ! -f .env ]; then
    FIRST_INSTALL="yes"
    # Start from production's settings (mail, translation key...) and change
    # everything that must be separate.
    cp "$PROD_DIR/backend/.env" .env
    sed -i 's/^APP_KEY=.*/APP_KEY=/' .env
    DB_PASSWORD="$(openssl rand -hex 20)"
else
    DB_PASSWORD="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2-)"
fi
DOMAIN="$DOMAIN" FRONTEND_URL="$FRONTEND_URL" DB_NAME="$DB_NAME" DB_USER="$DB_USER" DB_PASSWORD="$DB_PASSWORD" php -r '
$path = ".env";
$env = file_get_contents($path);
$set = function (string $key, string $value) use (&$env) {
    $line = $key."=".$value;
    $pattern = "/^".preg_quote($key, "/")."=.*/m";
    $env = preg_match($pattern, $env) ? preg_replace($pattern, addcslashes($line, "\\\\$"), $env) : $env."\n".$line."\n";
};
$set("APP_NAME", "\"La Perle d'"'"'Or TEST\"");
$set("APP_ENV", "staging");
$set("APP_DEBUG", "false");
$set("APP_URL", "https://".getenv("DOMAIN"));
$set("FRONTEND_URL", getenv("FRONTEND_URL"));
$set("DB_DATABASE", getenv("DB_NAME"));
$set("DB_USERNAME", getenv("DB_USER"));
$set("DB_PASSWORD", getenv("DB_PASSWORD"));
$set("REDIS_DB", "2");
$set("REDIS_CACHE_DB", "3");
$set("REDIS_PREFIX", "laperledor_staging_database_");
$set("CACHE_PREFIX", "laperledor_staging_cache_");
$set("HORIZON_PREFIX", "laperledor_staging_horizon:");
$set("MAIL_MAILER", "log");
$set("CORS_ALLOWED_ORIGINS", getenv("FRONTEND_URL"));
file_put_contents($path, $env);
'

echo "==> Database $DB_NAME"
sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

if ! grep -qE '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force
fi
php artisan config:clear
php artisan migrate --force
php artisan db:seed --class=RolesAndAdminUserSeeder --force

ITEM_COUNT="$(sudo mysql -N -e "SELECT COUNT(*) FROM \`$DB_NAME\`.items")"
if [ "$ITEM_COUNT" = "0" ]; then
    echo "==> Copying the catalogue from production (no customers, orders or accounts)"
    sudo mysqldump --no-create-info --skip-triggers --complete-insert --insert-ignore laperledor \
        categories items promotions delivery_slot_templates settings | sudo mysql "$DB_NAME"
    # Only the reviews that are not tied to a real customer's order.
    sudo mysqldump --no-create-info --skip-triggers --complete-insert --insert-ignore \
        --where="reservation_id IS NULL AND customer_id IS NULL" laperledor item_reviews | sudo mysql "$DB_NAME"
fi

echo "==> Catalogue images (quote PDFs of real customers are left out)"
mkdir -p storage/app/public
sudo rsync -a --exclude quotes --exclude livewire-tmp "$PROD_DIR/backend/storage/app/public/" storage/app/public/

ADMIN_PASSWORD=""
if [ "$FIRST_INSTALL" = "yes" ]; then
    ADMIN_PASSWORD="$(openssl rand -base64 18 | tr -d '/+=' | cut -c1-16)"
    ADMIN_EMAIL="$ADMIN_EMAIL" ADMIN_PASSWORD="$ADMIN_PASSWORD" php artisan tinker --execute='
        $u = App\Models\User::where("email", getenv("ADMIN_EMAIL"))->first();
        $u->forceFill(["password" => Illuminate\Support\Facades\Hash::make(getenv("ADMIN_PASSWORD")), "is_active" => true])->save();
    ' >/dev/null
fi

php artisan storage:link 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> File permissions"
sudo chown -R "$USER:www-data" "$APP_DIR"
sudo find "$APP_DIR/backend/storage" "$APP_DIR/backend/bootstrap/cache" -type d -exec chmod 775 {} \;
sudo find "$APP_DIR/backend/storage" "$APP_DIR/backend/bootstrap/cache" -type f -exec chmod 664 {} \;

echo "==> PHP-FPM pool (small: it only serves tests)"
sudo tee "/etc/php/${PHP_VERSION}/fpm/pool.d/laperledor-staging.conf" >/dev/null <<EOF
[laperledor-staging]
user = www-data
group = www-data
listen = /run/php/laperledor-staging.sock
listen.owner = www-data
listen.group = www-data
pm = ondemand
pm.max_children = 4
pm.process_idle_timeout = 30s
EOF
sudo systemctl restart "php${PHP_VERSION}-fpm"

echo "==> Nginx"
sudo tee "/etc/nginx/sites-available/$DOMAIN" >/dev/null <<EOF
server {
    listen 80;
    server_name $DOMAIN;
    root $APP_DIR/backend/public;
    index index.php;

    client_max_body_size 20m;

    # Keep the test copy out of search engines.
    add_header X-Robots-Tag "noindex, nofollow" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/laperledor-staging.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME \$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
sudo ln -sf "/etc/nginx/sites-available/$DOMAIN" "/etc/nginx/sites-enabled/$DOMAIN"
sudo nginx -t
sudo systemctl reload nginx

echo "==> HTTPS certificate (Let's Encrypt)"
sudo certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "admin@laperledor-togo.com" --redirect

echo "==> Queue worker and scheduler"
sudo tee /etc/systemd/system/laperledor-staging-horizon.service >/dev/null <<EOF
[Unit]
Description=La Perle d'Or TEST queue worker (Laravel Horizon)
After=network.target redis-server.service mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php${PHP_VERSION} $APP_DIR/backend/artisan horizon
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
EOF
sudo systemctl daemon-reload
sudo systemctl enable --now laperledor-staging-horizon
sudo systemctl restart laperledor-staging-horizon
(crontab -l 2>/dev/null | grep -v "$APP_DIR/backend"; echo "* * * * * cd $APP_DIR/backend && php${PHP_VERSION} artisan schedule:run >> /dev/null 2>&1") | crontab -

cat <<EOF

============================================================
Pre-production is ready.

  API:    https://$DOMAIN/api/v1/categories
  Admin:  https://$DOMAIN/admin
EOF
if [ -n "$ADMIN_PASSWORD" ]; then
    cat <<EOF
  Login:  $ADMIN_EMAIL
  Password (shown once, keep it): $ADMIN_PASSWORD
  The admin asks to set up the authenticator app on first login.
EOF
fi
cat <<EOF

Next updates of the test copy:
  ssh laperledor@$SERVER_IP 'bash $APP_DIR/deploy/deploy.sh staging'
============================================================
EOF
