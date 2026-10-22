#!/usr/bin/env bash
# One-time server bootstrap for a fresh Ubuntu VPS (OVHcloud / Hetzner / etc.).
# Run as root, once, right after the server is created:
#   ssh root@<server-ip> 'bash -s' < 01-server-setup.sh
#
# Installs Nginx, PHP 8.4, MySQL, Redis, Composer, Certbot; creates a
# low-privilege deploy user; enables the firewall, fail2ban and automatic
# security updates. Does NOT touch the application itself — see
# 02-app-setup.sh for that, run afterwards as the deploy user.
#
# Tested on Ubuntu 24.04 (noble) and 26.04 (resolute). PHP 8.4 comes from
# sury.org on 26.04 (the ondrej/php PPA does not cover that release yet).

set -euo pipefail

# --- Edit these before running -------------------------------------------
DEPLOY_USER="laperledor"
DOMAIN="api.laperledor-togo.com"
PHP_VERSION="8.4"
# Optional: public SSH key of the deploy user. When empty, the root user's
# authorized_keys is copied over, which is what we want when the same key is
# used for both.
DEPLOY_SSH_PUBKEY=""
# ---------------------------------------------------------------------------

echo "==> Updating system packages"
apt-get update -y
apt-get upgrade -y
export DEBIAN_FRONTEND=noninteractive

echo "==> Installing unattended security upgrades (applied automatically every night)"
apt-get install -y unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades
cat >/etc/apt/apt.conf.d/52unattended-upgrades-local <<'EOF'
Unattended-Upgrade::Automatic-Reboot "true";
Unattended-Upgrade::Automatic-Reboot-Time "03:30";
EOF

echo "==> Installing fail2ban (blocks repeated SSH login attempts)"
apt-get install -y fail2ban
systemctl enable --now fail2ban

echo "==> Configuring firewall (SSH, HTTP, HTTPS only)"
apt-get install -y ufw
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "==> Installing Nginx, Redis, MySQL, Certbot"
apt-get install -y nginx redis-server mysql-server certbot python3-certbot-nginx

echo "==> Installing PHP $PHP_VERSION and required extensions"
apt-get install -y apt-transport-https ca-certificates lsb-release gnupg curl
curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/trusted.gpg.d/sury-php.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" >/etc/apt/sources.list.d/sury-php.list
apt-get update -y
apt-get install -y \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-redis" \
    "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" "php${PHP_VERSION}-opcache"

echo "==> Installing Composer"
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --quiet
rm -f /tmp/composer-setup.php

echo "==> Starting and enabling the services"
systemctl enable --now nginx mysql redis-server
systemctl enable --now "php${PHP_VERSION}-fpm"

echo "==> Creating deploy user ($DEPLOY_USER)"
if ! id "$DEPLOY_USER" &>/dev/null; then
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
    usermod -aG www-data "$DEPLOY_USER"
    usermod -aG sudo "$DEPLOY_USER"
    mkdir -p "/home/$DEPLOY_USER/.ssh"
    chmod 700 "/home/$DEPLOY_USER/.ssh"
fi
# The deploy user has no password (key-based login only), so it cannot answer
# a sudo prompt: give it passwordless sudo instead.
echo "$DEPLOY_USER ALL=(ALL) NOPASSWD:ALL" >"/etc/sudoers.d/$DEPLOY_USER"
chmod 440 "/etc/sudoers.d/$DEPLOY_USER"
visudo -c -f "/etc/sudoers.d/$DEPLOY_USER"
if [ -n "$DEPLOY_SSH_PUBKEY" ]; then
    echo "$DEPLOY_SSH_PUBKEY" >"/home/$DEPLOY_USER/.ssh/authorized_keys"
else
    cp /root/.ssh/authorized_keys "/home/$DEPLOY_USER/.ssh/authorized_keys" 2>/dev/null || true
fi
chown -R "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
chmod 700 "/home/$DEPLOY_USER/.ssh"
chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys" 2>/dev/null || true

echo "==> Securing MySQL and creating the application database"
# Hex only: no quotes, no backslashes, no trailing newline (a base64 pipe
# truncated by `head` used to leave a "\n" inside the password, which then
# no longer matched the value copied into the .env file).
DB_PASSWORD=$(openssl rand -hex 16)
mysql <<SQL
CREATE DATABASE IF NOT EXISTS laperledor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'laperledor'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON laperledor.* TO 'laperledor'@'localhost';
FLUSH PRIVILEGES;
SQL

echo "==> Disabling SSH password authentication (key-based login only)"
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
systemctl restart ssh

mkdir -p /var/www
chown "$DEPLOY_USER:www-data" /var/www

cat <<EOF

============================================================
Server base setup complete.

Database password (save this now, it is not stored anywhere else):
  ${DB_PASSWORD}

Next step: log in as $DEPLOY_USER and run 02-app-setup.sh
  ssh $DEPLOY_USER@<server-ip>
============================================================
EOF
