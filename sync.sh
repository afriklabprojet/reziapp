#!/bin/bash
# sync.sh — Déploiement REZI depuis Mac vers prod
# Usage : bash sync.sh

set -euo pipefail

SERVER_HOST="${SERVER_HOST:-}"
SERVER_USER="${SERVER_USER:-root}"

if [ -z "$SERVER_HOST" ]; then
  echo "SERVER_HOST manquant"
  exit 1
fi

SERVER="${SERVER_USER}@${SERVER_HOST}"
REMOTE="/var/www/reziapp"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/rezi_deploy}"
SSH_OPTS="-i $SSH_KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=no"

echo "🧱 Build assets front..."
npm run build

echo "🚀 Rsync vers $SERVER..."
rsync -az --delete -e "ssh $SSH_OPTS" \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.env' \
  --exclude='public/storage' \
  --exclude='storage/app/public' \
  --exclude='storage/app/private' \
  --exclude='storage/logs' \
  --exclude='post-deploy.sh' \
  ./ "$SERVER:$REMOTE/"

echo "✅ Rsync OK — lancement post-deploy..."
ssh $SSH_OPTS "$SERVER" "bash $REMOTE/post-deploy.sh"
