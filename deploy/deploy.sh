#!/usr/bin/env bash
# Redeploy script — run this on the server after every `git push`:
#
#   ssh laperledor@<server-ip> 'bash /var/www/laperledor/deploy/deploy.sh'                  # production
#   ssh laperledor@<server-ip> 'bash /var/www/laperledor-staging/deploy/deploy.sh staging'  # pre-production
#
# Production follows the "Wilfried" branch, pre-production the "staging" one.

set -euo pipefail

TARGET="${1:-production}"

case "$TARGET" in
    production)
        APP_DIR="/var/www/laperledor"
        BRANCH="Wilfried"
        HORIZON_SERVICE="laperledor-horizon"
        ;;
    staging)
        APP_DIR="/var/www/laperledor-staging"
        BRANCH="staging"
        HORIZON_SERVICE="laperledor-staging-horizon"
        ;;
    *)
        echo "Usage: deploy.sh [production|staging]" >&2
        exit 1
        ;;
esac

echo "==> Deploying $TARGET ($BRANCH) in $APP_DIR"
cd "$APP_DIR"

# A git command once run as root leaves root-owned files in .git, and the
# next fetch then fails with "insufficient permission". Hand them back first.
sudo chown -R "$USER:www-data" "$APP_DIR/.git"

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

sudo systemctl restart "$HORIZON_SERVICE"
sudo systemctl reload php8.4-fpm

echo "Deploy complete ($TARGET)."
