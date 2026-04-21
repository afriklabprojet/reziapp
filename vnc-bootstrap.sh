#!/bin/bash
# =============================================================================
# REZI — Bootstrap VNC Hetzner (à coller dans la console VNC)
# Active SSH + installe l'environnement complet
# =============================================================================
# ÉTAPES :
# 1. Aller sur https://console.hetzner.com
# 2. Sélectionner le serveur → Cliquer "Console" (icône terminal)
# 3. Se connecter en root
# 4. Copier-coller la commande ci-dessous EN UN BLOC
# =============================================================================

set -euo pipefail

DEPLOY_KEY="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFAXlXe1lHM9vHbOKZJGlrEsvuXdsdcx/uae8nsS94mw github-actions-rezi-deploy"
DOMAIN="reziapp.ci"
PHP_VERSION="8.2"
DEPLOY_PATH="/var/www/reziapp"
DB_NAME="rezi_prod"
DB_USER="rezi"
DB_PASS=$(openssl rand -base64 20 | tr -dc 'A-Za-z0-9' | head -c 24)

echo "===== REZI Bootstrap — Serveur Hetzner ====="

# ── SSH ──────────────────────────────────────────────────────────────────────
apt-get install -y openssh-server 2>/dev/null || true
mkdir -p /root/.ssh && chmod 700 /root/.ssh
grep -qF "$DEPLOY_KEY" /root/.ssh/authorized_keys 2>/dev/null || echo "$DEPLOY_KEY" >> /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys

# Autoriser authentification par clé + root login
sed -i 's/^#\?PubkeyAuthentication.*/PubkeyAuthentication yes/' /etc/ssh/sshd_config
sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config
sed -i 's/^#\?Port 22/Port 22/' /etc/ssh/sshd_config
systemctl enable ssh && systemctl restart ssh
echo "[OK] SSH activé sur le port 22"

# ── Packages système ─────────────────────────────────────────────────────────
apt-get update -qq
apt-get install -y -qq curl wget git unzip zip supervisor cron software-properties-common

# ── PHP 8.2 ──────────────────────────────────────────────────────────────────
add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd php${PHP_VERSION}-zip php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath php${PHP_VERSION}-imagick
echo "[OK] PHP $PHP_VERSION installé"

# ── Nginx ────────────────────────────────────────────────────────────────────
apt-get install -y -qq nginx
systemctl enable nginx
echo "[OK] Nginx installé"

# ── MySQL 8 ──────────────────────────────────────────────────────────────────
apt-get install -y -qq mysql-server
systemctl enable mysql
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
echo "[OK] MySQL — Base: $DB_NAME | User: $DB_USER | Pass: $DB_PASS"

# ── Composer ─────────────────────────────────────────────────────────────────
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet
echo "[OK] Composer installé"

# ── Node.js 20 ───────────────────────────────────────────────────────────────
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - 2>/dev/null
apt-get install -y -qq nodejs
echo "[OK] Node.js $(node --version) installé"

# ── Certbot SSL ──────────────────────────────────────────────────────────────
apt-get install -y -qq certbot python3-certbot-nginx
echo "[OK] Certbot installé"

# ── Répertoire déploiement ───────────────────────────────────────────────────
mkdir -p "$DEPLOY_PATH"
chown -R www-data:www-data "$DEPLOY_PATH"
chmod -R 755 "$DEPLOY_PATH"

# ── Config Nginx ─────────────────────────────────────────────────────────────
cat > /etc/nginx/sites-available/reziapp << NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${DEPLOY_PATH}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    index index.php;
    charset utf-8;
    client_max_body_size 50M;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    location ~ /\.(?!well-known).* { deny all; }
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
}
NGINX

ln -sf /etc/nginx/sites-available/reziapp /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
echo "[OK] Nginx configuré pour $DOMAIN"

# ── Supervisor (Queue Worker) ────────────────────────────────────────────────
cat > /etc/supervisor/conf.d/rezi-worker.conf << SUPERVISOR
[program:rezi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${DEPLOY_PATH}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR

# ── PHP-FPM permissions ──────────────────────────────────────────────────────
sed -i 's/^user = .*/user = www-data/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^group = .*/group = www-data/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
systemctl restart php${PHP_VERSION}-fpm

# ── UFW Pare-feu ─────────────────────────────────────────────────────────────
ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
echo "[OK] Pare-feu UFW configuré"

# ── Cron Laravel Scheduler ───────────────────────────────────────────────────
(crontab -l 2>/dev/null; echo "* * * * * cd ${DEPLOY_PATH} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

echo ""
echo "========================================================"
echo "  BOOTSTRAP TERMINÉ !"
echo "  MySQL Password : $DB_PASS  ← NOTER CETTE VALEUR !"
echo ""
echo "  Vérifier SSH : ssh root@178.104.190.169"
echo "========================================================"
