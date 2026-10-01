#!/usr/bin/env bash
# Redeploy script — run this on the server after every `git push` to pick up
# new backend code:
#   ssh laperledor@<server-ip> 'bash /var/www/laperledor/deploy/deploy.sh'

set -euo pipefail

APP_DIR="/var/www/laperledor"
BRANCH="Wilfried"

cd "$APP_DIR"
git fetch origin
git checkout "$BRANCH"
git reset --hard "origin/$BRANCH"

cd "$APP_DIR/backend"
composer install --no-dev --optimize-autoloader --no-interaction

php artisan down --render="errors::503" || true

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R "$USER:www-data" "$APP_DIR/backend/storage" "$APP_DIR/backend/bootstrap/cache"

php artisan up

sudo systemctl restart laperledor-horizon
sudo systemctl reload php8.4-fpm

echo "Deploy complete."
