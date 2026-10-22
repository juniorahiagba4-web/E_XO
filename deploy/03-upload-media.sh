#!/usr/bin/env bash
# Upload the catalogue media (backend/storage/app/public) to the server.
#
# The media folder is git-ignored (Laravel's default), so it cannot be
# deployed with a `git pull` — it has to be copied over separately.
#
# Run it from the repository root:
#   SERVER=root@54.39.230.49 APP_DIR=/var/www/laperledor bash deploy/03-upload-media.sh
#
# The script is idempotent: existing files with the same name are replaced.

set -euo pipefail

SERVER="${SERVER:-root@54.39.230.49}"
APP_DIR="${APP_DIR:-/var/www/laperledor}"
DEPLOY_USER="${DEPLOY_USER:-laperledor}"
MEDIA_DIR="backend/storage/app/public"

if [ ! -d "$MEDIA_DIR" ]; then
    echo "No media directory found at $MEDIA_DIR" >&2
    exit 1
fi

echo "==> Packing $MEDIA_DIR"
TARBALL="$(mktemp /tmp/laperledor-media-XXXXXX.tar.gz)"
tar -czf "$TARBALL" -C "$MEDIA_DIR" --exclude=".gitignore" .
COUNT=$(tar -tzf "$TARBALL" | grep -vc '/$' || true)
echo "    $COUNT files packed"

echo "==> Uploading to $SERVER"
scp "$TARBALL" "$SERVER:/tmp/laperledor-media.tar.gz"

echo "==> Extracting on the server"
ssh "$SERVER" "set -e
    rm -rf '${APP_DIR}/backend/storage/app/public'
    mkdir -p '${APP_DIR}/backend/storage/app/public'
    tar -xzf /tmp/laperledor-media.tar.gz -C '${APP_DIR}/backend/storage/app/public'
    rm -f /tmp/laperledor-media.tar.gz
    chown -R '${DEPLOY_USER}:www-data' '${APP_DIR}/backend/storage/app/public'
    find '${APP_DIR}/backend/storage/app/public' -type d -exec chmod 775 {} +
    find '${APP_DIR}/backend/storage/app/public' -type f -exec chmod 664 {} +
    echo \"    \$(find '${APP_DIR}/backend/storage/app/public' -type f | wc -l) files installed\"
"

rm -f "$TARBALL"
echo "==> Media uploaded"
