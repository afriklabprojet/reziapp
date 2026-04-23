#!/bin/bash

# =============================================================================
# REZI — Script de déploiement production
# Serveur : SSH/cPanel Linux (Apache ou Nginx + PHP 8.2+)
# Usage   : bash deploy.sh
# =============================================================================

set -euo pipefail

# ── Couleurs ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC}  $1"; }
success() { echo -e "${GREEN}[OK]${NC}    $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ── Vérifications préalables ──────────────────────────────────────────────────
command -v php  >/dev/null 2>&1 || error "PHP non trouvé"
command -v composer >/dev/null 2>&1 || error "Composer non trouvé"
command -v npm  >/dev/null 2>&1 || error "npm non trouvé"

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
info "PHP version : $PHP_VERSION"
[[ "$PHP_VERSION" < "8.2" ]] && error "PHP 8.2+ requis (trouvé : $PHP_VERSION)"

echo ""
echo "=============================================================="
echo "         REZI — Déploiement Production"
echo "=============================================================="
echo ""

# ── 1. Mode maintenance ───────────────────────────────────────────────────────
info "Passage en mode maintenance..."
php artisan down --render="errors.503" --retry=60 || warn "Mode maintenance déjà actif ou erreur ignorée"

# ── 2. Dépendances Composer (production, sans dev) ────────────────────────────
info "Installation des dépendances Composer (prod)..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
success "Composer OK"

# ── 3. Build assets frontend ──────────────────────────────────────────────────
info "Build des assets frontend (Vite)..."
npm ci --silent
npm run build
success "Assets buildés"

# ── 4. Variables d'environnement ──────────────────────────────────────────────
info "Vérification du fichier .env..."
[[ ! -f .env ]] && error ".env manquant. Copiez .env.example et configurez-le."

# Forcer production
grep -q "APP_ENV=production" .env || {
    warn "APP_ENV n'est pas 'production' dans .env"
    warn "Assurez-vous que ces valeurs sont correctes dans .env :"
    warn "  APP_ENV=production"
    warn "  APP_DEBUG=false"
    warn "  APP_URL=https://votre-domaine.com"
}

# ── 5. Migrations ─────────────────────────────────────────────────────────────
info "Exécution des migrations..."
php artisan migrate --force
success "Migrations OK"

# ── 6. Caches de config/routes/vues (optimisation prod) ───────────────────────
info "Génération des caches de production..."
# Charger les variables .env dans l'environnement avant de construire le cache
# Évite que des variables système vides n'écrasent les valeurs du .env
if [[ -f .env ]]; then
    while IFS='=' read -r key value; do
        [[ "$key" =~ ^[[:space:]]*# ]] && continue
        [[ -z "$key" ]] && continue
        value="${value%%#*}"
        value="${value%"${value##*[![:space:]]}"}"
        export "$key=$value"
    done < <(grep -v '^#' .env | grep '=')
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
success "Caches générés"

# ── 7. Lien de stockage ───────────────────────────────────────────────────────
info "Création du lien storage..."
php artisan storage:link 2>/dev/null || warn "Lien storage déjà existant"

# ── 8. Permissions fichiers ───────────────────────────────────────────────────
info "Application des permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs
# Propriétaire www-data requis pour que Nginx/Apache puisse écrire
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
success "Permissions OK"

# ── 9. Queue worker — redémarrage ─────────────────────────────────────────────
info "Redémarrage du queue worker..."
php artisan queue:restart
success "Queue restart signal envoyé"

# ── 10. Health check de l'application ─────────────────────────────────────────
info "Vérification santé de l'application..."
php artisan about --only=environment 2>/dev/null | head -20 || true

# ── 11. Sortie du mode maintenance ────────────────────────────────────────────
info "Sortie du mode maintenance..."
php artisan up
success "Application EN LIGNE ✓"

echo ""
echo "=============================================================="
echo -e "${GREEN}  DÉPLOIEMENT TERMINÉ AVEC SUCCÈS${NC}"
echo "=============================================================="
echo ""
echo "  ── Si déploiement via rsync depuis Mac ─────────────────────"
echo "  Après le rsync, exécuter sur le serveur :"
echo "  ssh root@178.104.190.169 'bash /var/www/reziapp/post-deploy.sh'"
echo ""
echo "  ── Cron (ajouter via crontab -e) ──────────────────────────"
echo "  * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "  ── Queue Worker (supervisor recommandé) ────────────────────"
echo "  php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
echo "=============================================================="
