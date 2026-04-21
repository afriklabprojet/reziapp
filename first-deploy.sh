#!/bin/bash
# =============================================================================
# REZI — Premier déploiement (à lancer UNE SEULE FOIS sur le serveur)
# Pré-requis : server-setup.sh déjà exécuté
# Usage : bash first-deploy.sh
# =============================================================================

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
info()    { echo -e "${BLUE}[INFO]${NC}  $1"; }
success() { echo -e "${GREEN}[OK]${NC}    $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

REPO_URL="git@github.com:VOTRE_USER/VOTRE_REPO.git"  # ← À MODIFIER
DEPLOY_PATH="/var/www/reziapp"

# ── Cloner le repo ────────────────────────────────────────────────────────────
if [ -d "$DEPLOY_PATH/.git" ]; then
    warn "Repo déjà cloné dans $DEPLOY_PATH"
else
    info "Clonage du dépôt..."
    git clone "$REPO_URL" "$DEPLOY_PATH"
    success "Repo cloné"
fi

cd "$DEPLOY_PATH"

# ── Vérifier .env ─────────────────────────────────────────────────────────────
if [ ! -f .env ]; then
    error ".env manquant. Créez-le avec : cp .env.example .env && nano .env"
fi
grep -q "APP_KEY=base64:" .env || error "APP_KEY non généré. Lancez : php artisan key:generate"
grep -q "APP_ENV=production" .env || warn "APP_ENV n'est pas production dans .env"

# ── Dépendances ───────────────────────────────────────────────────────────────
info "Installation Composer (prod)..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
success "Composer OK"

# ── Migrations ────────────────────────────────────────────────────────────────
info "Migrations..."
php artisan migrate --force
success "Migrations OK"

# ── Storage link ──────────────────────────────────────────────────────────────
info "Lien storage..."
php artisan storage:link 2>/dev/null || true

# ── Caches prod ───────────────────────────────────────────────────────────────
info "Génération caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# ── Permissions ───────────────────────────────────────────────────────────────
info "Permissions..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ── Ajouter la clé SSH GitHub Actions (authorized_keys) ───────────────────────
info "Ajout clé SSH GitHub Actions..."
DEPLOY_PUB_KEY="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIFAXlXe1lHM9vHbOKZJGlrEsvuXdsdcx/uae8nsS94mw github-actions-rezi-deploy"
mkdir -p /root/.ssh
grep -qF "$DEPLOY_PUB_KEY" /root/.ssh/authorized_keys 2>/dev/null || echo "$DEPLOY_PUB_KEY" >> /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys
success "Clé SSH GitHub Actions autorisée"

# ── Supervisor démarrage ──────────────────────────────────────────────────────
info "Démarrage Supervisor..."
supervisorctl reread
supervisorctl update
supervisorctl start rezi-worker:* 2>/dev/null || true

echo ""
echo "=============================================================="
echo -e "${GREEN}  PREMIER DÉPLOIEMENT TERMINÉ !${NC}"
echo ""
echo "  Étapes suivantes :"
echo "  1. SSL : certbot --nginx -d reziapp.ci -d www.reziapp.ci --non-interactive --agree-tos -m admin@reziapp.ci"
echo "  2. Vérifier : curl -I https://reziapp.ci"
echo "  3. Configurer les GitHub Secrets → les prochains deploys seront automatiques"
echo "=============================================================="
