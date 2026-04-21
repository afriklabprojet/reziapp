# 🚀 REZI — Guide de Déploiement Production

> **Domaine** : https://reziapp.ci  
> **Serveur** : Hetzner Ubuntu 22.04 — `178.104.190.169`  
> **CI/CD** : GitHub Actions (auto-deploy sur push `main`)

---

## Étape 1 — Ouvrir SSH sur Hetzner

1. Aller sur [console.hetzner.com](https://console.hetzner.com)
2. Sélectionner votre serveur → **Networking** → **Firewalls**
3. Ajouter une règle entrante : **TCP port 22** (ou vérifier qu'elle existe)
4. Ou via la **console VNC** Hetzner, vérifier :
   ```bash
   systemctl status sshd
   # Si absent : apt install openssh-server && systemctl enable --now sshd
   ```

---

## Étape 2 — Setup initial du serveur (une seule fois)

Via la **console VNC** Hetzner (ou SSH une fois le port ouvert) :

```bash
# Télécharger et lancer le script de setup
curl -O https://raw.githubusercontent.com/VOTRE_REPO/rezi/main/server-setup.sh
bash server-setup.sh
```

Ce script installe : PHP 8.2, Nginx, MySQL 8, Composer, Node 20, Supervisor, UFW.

---

## Étape 3 — Clé SSH pour GitHub Actions

Sur votre machine locale, générer une clé dédiée au déploiement :

```bash
ssh-keygen -t ed25519 -C "github-actions-rezi-deploy" -f ~/.ssh/rezi_deploy -N ""
cat ~/.ssh/rezi_deploy.pub   # ← copier cette clé
```

Sur le serveur Hetzner (via VNC console) :
```bash
mkdir -p /root/.ssh
echo "COLLER_LA_CLE_PUBLIQUE_ICI" >> /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys
```

Obtenir le known_host du serveur :
```bash
ssh-keyscan -H 178.104.190.169
# ← copier la ligne qui commence par |1|...
```

---

## Étape 4 — Secrets GitHub

Aller sur : **GitHub repo → Settings → Secrets and variables → Actions**

Ajouter ces 4 secrets :

| Secret | Valeur |
|--------|--------|
| `SERVER_HOST` | `178.104.190.169` |
| `SERVER_USER` | `root` |
| `SERVER_SSH_KEY` | Contenu de `~/.ssh/rezi_deploy` (clé **privée**) |
| `SERVER_SSH_KNOWN_HOST` | Ligne obtenue avec `ssh-keyscan` ci-dessus |

---

## Étape 5 — Fichier .env production sur le serveur

```bash
# Sur le serveur Hetzner
cp /var/www/reziapp/.env.example /var/www/reziapp/.env
nano /var/www/reziapp/.env
```

Valeurs à mettre à jour :
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://reziapp.ci

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rezi_prod
DB_USERNAME=rezi
DB_PASSWORD=MOT_DE_PASSE_GENERE_PAR_SETUP

# Générer avec : php artisan key:generate --show
APP_KEY=base64:...

GOOGLE_MAPS_API_KEY=...
MAIL_MAILER=smtp
# ... autres variables
```

Puis :
```bash
cd /var/www/reziapp
php artisan key:generate
```

---

## Étape 6 — SSL Let's Encrypt

```bash
# Sur le serveur (après que le domaine pointe vers 178.104.190.169)
certbot --nginx -d reziapp.ci -d www.reziapp.ci --non-interactive --agree-tos -m admin@reziapp.ci
```

---

## Étape 7 — Premier déploiement

```bash
# Sur le serveur, cloner le repo (premier déploiement uniquement)
cd /var/www
git clone https://github.com/VOTRE_USER/VOTRE_REPO.git reziapp
cd reziapp
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
```

Ensuite, chaque `git push main` déclenche le deploy automatiquement via GitHub Actions.

---

## Commandes utiles (sur le serveur)

```bash
# Logs application
tail -f /var/www/reziapp/storage/logs/laravel.log

# Logs Nginx
tail -f /var/log/nginx/error.log

# Status Queue Worker
supervisorctl status rezi-worker:*

# Redémarrer Queue Worker
supervisorctl restart rezi-worker:*

# Status PHP-FPM
systemctl status php8.2-fpm

# Vérifier cron
crontab -l
```

---

## Architecture

```
reziapp.ci
    │
    ├── Nginx (reverse proxy + SSL)
    │       └── PHP 8.2-FPM (Laravel)
    │
    ├── MySQL 8.0 (base: rezi_prod)
    │
    ├── Supervisor
    │       └── Queue Worker x2 (jobs async)
    │
    └── Cron
            └── php artisan schedule:run (toutes les minutes)
```
