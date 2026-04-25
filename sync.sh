#!/bin/bash
# sync.sh — Déploiement REZI depuis Mac vers prod
# Usage : bash sync.sh

set -euo pipefail

SERVER="root@178.104.190.169"
REMOTE="/var/www/reziapp"
SSH_KEY="${SSH_KEY:-$HOME/.ssh/rezi_deploy}"
SSH_OPTS="-i $SSH_KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=no"

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
