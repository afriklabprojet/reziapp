#!/bin/bash
# sync.sh — Déploiement REZI depuis Mac vers prod
# Usage : bash sync.sh

set -euo pipefail

SERVER="root@178.104.190.169"
REMOTE="/var/www/reziapp"

echo "🚀 Rsync vers $SERVER..."
rsync -az --delete \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='.env' \
  --exclude='storage/app/public/residences' \
  --exclude='storage/logs' \
  --exclude='post-deploy.sh' \
  ./ "$SERVER:$REMOTE/"

echo "✅ Rsync OK — lancement post-deploy..."
ssh "$SERVER" "bash $REMOTE/post-deploy.sh"
