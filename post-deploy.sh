#!/bin/bash
# =============================================================================
# REZI — Post-deploy (exécuté sur le serveur après rsync)
# =============================================================================
set -euo pipefail

DEPLOY_PATH="/var/www/reziapp"
PHP_VERSION="8.2"

cd "$DEPLOY_PATH"

echo "[1/7] Composer (prod)..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet

echo "[2/7] Migrations..."
php artisan migrate --force

echo "[3/7] Caches de production..."
# Load .env vars so config:cache picks up APP_KEY (www-data owns .env, root runs this script)
set -a; source .env; set +a
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "[4/7] Permissions storage..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "[5/7] Lien storage..."
php artisan storage:link --force 2>/dev/null || true

echo "[6/7] Redémarrage PHP-FPM..."
systemctl restart php${PHP_VERSION}-fpm

echo "[7/7] Redémarrage workers queue..."
php artisan queue:restart

echo ""
echo "========================================================"
echo "  POST-DEPLOY TERMINÉ — $(date '+%d/%m/%Y %H:%M:%S')"
echo "========================================================"
