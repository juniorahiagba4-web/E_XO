#!/usr/bin/env bash
# Transitional redirect for the bare domain.
#
# The bare domain and "www" are meant to be served by Vercel (the frontend),
# but their DNS records still point at this VPS. Until they are switched,
# this vhost redirects the visitors to the Vercel deployment instead of
# showing a 404.
#
#   ssh root@54.39.230.49 'bash -s' < deploy/04-redirect-root-domain.sh
#
# Once the DNS points to Vercel, this vhost is simply never reached.

set -euo pipefail

APP_DIR="/var/www/laperledor"
DOMAIN="api.laperledor-togo.com"
ROOT_DOMAIN="laperledor-togo.com"
TARGET="https://frontend-three-tau-99.vercel.app"

cat > "/etc/nginx/sites-available/${ROOT_DOMAIN}" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${ROOT_DOMAIN} www.${ROOT_DOMAIN};

    # The frontend lives on Vercel; the API stays on this server.
    return 301 ${TARGET}\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;
    server_name ${ROOT_DOMAIN} www.${ROOT_DOMAIN};

    ssl_certificate     /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;

    return 301 ${TARGET}\$request_uri;
}
EOF

ln -sf "/etc/nginx/sites-available/${ROOT_DOMAIN}" "/etc/nginx/sites-enabled/${ROOT_DOMAIN}"
nginx -t
systemctl reload nginx

echo "==> ${ROOT_DOMAIN} and www.${ROOT_DOMAIN} now redirect to ${TARGET}"
echo "    (remove the DNS A record for them and point it to Vercel to finish the migration)"
