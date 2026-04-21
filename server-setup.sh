#!/bin/bash
# =============================================================================
# REZI — Setup initial serveur Ubuntu 22.04 (Hetzner)
# Usage : bash server-setup.sh
# À lancer UNE SEULE FOIS via la console VNC Hetzner ou SSH root
# =============================================================================

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $1"; }
success() { echo -e "${GREEN}[OK]${NC}    $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

DOMAIN="reziapp.ci"
DEPLOY_USER="deploy"
DEPLOY_PATH="/var/www/reziapp"
PHP_VERSION="8.2"
DB_NAME="rezi_prod"
DB_USER="rezi"
# Générer un mot de passe aléatoire pour MySQL
DB_PASS=$(openssl rand -base64 20 | tr -dc 'A-Za-z0-9' | head -c 24)

echo ""
echo "=============================================================="
echo "        REZI — Setup Serveur Ubuntu 22.04"
echo "        Domaine : $DOMAIN"
echo "=============================================================="
echo ""

# ── 1. Mise à jour système ────────────────────────────────────────────────────
info "Mise à jour du système..."
apt-get update -qq && apt-get upgrade -y -qq
success "Système à jour"

# ── 2. Packages de base ───────────────────────────────────────────────────────
info "Installation des packages de base..."
apt-get install -y -qq \
    curl wget git unzip zip nano htop \
    software-properties-common apt-transport-https ca-certificates \
    gnupg lsb-release supervisor cron
success "Packages de base OK"

# ── 3. PHP 8.2 ───────────────────────────────────────────────────────────────
info "Installation PHP $PHP_VERSION..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-redis \
    php${PHP_VERSION}-imagick
success "PHP $PHP_VERSION OK"

# ── 4. Nginx ──────────────────────────────────────────────────────────────────
info "Installation Nginx..."
apt-get install -y -qq nginx
systemctl enable nginx
success "Nginx OK"

# ── 5. MySQL 8.0 ─────────────────────────────────────────────────────────────
info "Installation MySQL 8.0..."
apt-get install -y -qq mysql-server
systemctl enable mysql

# Sécuriser MySQL + créer la base
info "Configuration MySQL..."
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"
success "MySQL OK — Base: $DB_NAME, User: $DB_USER"

# ── 6. Composer ───────────────────────────────────────────────────────────────
info "Installation Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --quiet
success "Composer $(composer --version --no-ansi | cut -d' ' -f3) OK"

# ── 7. Node.js 20 ─────────────────────────────────────────────────────────────
info "Installation Node.js 20..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash - -qq
apt-get install -y -qq nodejs
success "Node.js $(node --version) OK"

# ── 8. Utilisateur deploy ─────────────────────────────────────────────────────
info "Création utilisateur $DEPLOY_USER..."
id "$DEPLOY_USER" &>/dev/null || useradd -m -s /bin/bash "$DEPLOY_USER"
usermod -aG www-data "$DEPLOY_USER"
mkdir -p /home/$DEPLOY_USER/.ssh
chmod 700 /home/$DEPLOY_USER/.ssh
chown -R $DEPLOY_USER:$DEPLOY_USER /home/$DEPLOY_USER/.ssh
success "Utilisateur $DEPLOY_USER OK"

# ── 9. Répertoire application ─────────────────────────────────────────────────
info "Création répertoire $DEPLOY_PATH..."
mkdir -p "$DEPLOY_PATH"
chown -R $DEPLOY_USER:www-data "$DEPLOY_PATH"
chmod -R 755 "$DEPLOY_PATH"
success "Répertoire OK"

# ── 10. Config Nginx ──────────────────────────────────────────────────────────
info "Configuration Nginx pour $DOMAIN..."
cat > /etc/nginx/sites-available/reziapp << NGINX_CONF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${DEPLOY_PATH}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

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

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Compression Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1024;
}
NGINX_CONF

ln -sf /etc/nginx/sites-available/reziapp /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
success "Nginx configuré pour $DOMAIN"

# ── 11. Certbot SSL (Let's Encrypt) ──────────────────────────────────────────
info "Installation Certbot (SSL)..."
apt-get install -y -qq certbot python3-certbot-nginx
success "Certbot OK — Lancez manuellement: certbot --nginx -d $DOMAIN -d www.$DOMAIN"

# ── 12. PHP-FPM config optimisée ─────────────────────────────────────────────
info "Optimisation PHP-FPM..."
sed -i 's/^pm.max_children = .*/pm.max_children = 20/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm.start_servers = .*/pm.start_servers = 5/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 3/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 10/' /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
systemctl restart php${PHP_VERSION}-fpm
success "PHP-FPM optimisé"

# ── 13. Supervisor (Queue Worker) ────────────────────────────────────────────
info "Configuration Supervisor (Laravel Queue)..."
cat > /etc/supervisor/conf.d/rezi-worker.conf << SUPERVISOR_CONF
[program:rezi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${DEPLOY_PATH}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${DEPLOY_PATH}/storage/logs/worker.log
stopwaitsecs=3600
SUPERVISOR_CONF

supervisorctl reread
supervisorctl update
success "Supervisor Queue Worker OK"

# ── 14. Cron Laravel Scheduler ───────────────────────────────────────────────
info "Configuration Cron Laravel Scheduler..."
(crontab -l 2>/dev/null; echo "* * * * * cd ${DEPLOY_PATH} && php artisan schedule:run >> /dev/null 2>&1") | crontab -
success "Cron OK"

# ── 15. Pare-feu UFW ─────────────────────────────────────────────────────────
info "Configuration pare-feu UFW..."
ufw --force enable
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
success "UFW OK"

# ── Résumé final ──────────────────────────────────────────────────────────────
echo ""
echo "=============================================================="
echo -e "${GREEN}  SETUP TERMINÉ !${NC}"
echo "=============================================================="
echo ""
echo "  ── Informations MySQL ──────────────────────────────────────"
echo "  Base     : $DB_NAME"
echo "  User     : $DB_USER"
echo "  Password : $DB_PASS    ← SAUVEGARDER CETTE VALEUR !"
echo ""
echo "  ── Prochaines étapes ───────────────────────────────────────"
echo "  1. Ajouter la clé SSH GitHub Actions dans :"
echo "     /home/$DEPLOY_USER/.ssh/authorized_keys"
echo "  2. Activer SSH root (ou utiliser l'user $DEPLOY_USER)"
echo "  3. Lancer SSL : certbot --nginx -d $DOMAIN -d www.$DOMAIN"
echo "  4. Créer /var/www/reziapp/.env (voir .env.example)"
echo "  5. Configurer les GitHub Secrets (voir docs/DEPLOY.md)"
echo "=============================================================="
