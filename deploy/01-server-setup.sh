#!/usr/bin/env bash
# One-time server bootstrap for a fresh Ubuntu 24.04 VPS (Hetzner CX23 or similar).
# Run as root, once, right after the server is created:
#   ssh root@<server-ip> 'bash -s' < 01-server-setup.sh
#
# Installs Nginx, PHP 8.4, MySQL, Redis, Composer, Certbot; creates a
# low-privilege deploy user; enables the firewall, fail2ban and automatic
# security updates. Does NOT touch the application itself — see
# 02-app-setup.sh for that, run afterwards as the deploy user.

set -euo pipefail

# --- Edit these before running -------------------------------------------
DEPLOY_USER="laperledor"
DOMAIN="laperledor-togo.com"
# ---------------------------------------------------------------------------

echo "==> Updating system packages"
apt-get update -y
apt-get upgrade -y

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

echo "==> Installing PHP 8.4 and required extensions (via ondrej/php PPA)"
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y php8.4 php8.4-fpm php8.4-mysql php8.4-redis php8.4-mbstring \
    php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl

echo "==> Installing Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Creating deploy user ($DEPLOY_USER)"
if ! id "$DEPLOY_USER" &>/dev/null; then
    adduser --disabled-password --gecos "" "$DEPLOY_USER"
    usermod -aG www-data "$DEPLOY_USER"
    mkdir -p "/home/$DEPLOY_USER/.ssh"
    cp /root/.ssh/authorized_keys "/home/$DEPLOY_USER/.ssh/authorized_keys" 2>/dev/null || true
    chown -R "$DEPLOY_USER:$DEPLOY_USER" "/home/$DEPLOY_USER/.ssh"
    chmod 700 "/home/$DEPLOY_USER/.ssh"
    chmod 600 "/home/$DEPLOY_USER/.ssh/authorized_keys" 2>/dev/null || true
fi

echo "==> Securing MySQL and creating the application database"
DB_PASSWORD=$(openssl rand -base64 24)
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
