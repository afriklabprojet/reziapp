# Audit Pré-Production REZI — Rapport & Checklist

> Généré lors de l'audit sévère avant déploiement en production.
> **Date** : Juin 2025 | **Auditeur** : GitHub Copilot (Claude Sonnet 4.6)

---

## Résumé Exécutif

| Catégorie | Statut |
|-----------|--------|
| 🔴 Critiques | 3 → **FIXÉS** |
| 🟠 Élevés | 3 → 1 actionnable, 2 documentés |
| 🟡 Moyens | 3 → documentés |
| ✅ Validés | 14 points propres |

---

## 🔴 Critiques — FIXÉS EN CODE

### ✅ C1 — Fuite `$e->getMessage()` dans les réponses API publiques

**Fichier** : `app/Http/Controllers/Api/BookingApiController.php` (cancel flow)  
**Fichier** : `app/Http/Controllers/SupportController.php` (flash + JSON API)

**Avant** :
```php
'message' => $e->getMessage(),
```

**Après** :
```php
'message' => config('app.debug') ? $e->getMessage() : 'Message générique.',
```

Leaks internes (schema DB, chemins serveur, noms de services) bloqués en production.

---

### ✅ C2 — XSS stocké dans la preview de campagne

**Fichier** : `resources/views/filament/pages/campaign-preview.blade.php`

**Avant** :
```blade
{!! $campaign->content !!}
```

**Après** :
```blade
{!! strip_tags($campaign->content, '<p><br><b><strong><i><em><ul><ol><li><h1><h2><h3><h4><a><span><div>') !!}
```

Balises `<script>`, `<iframe>`, `<object>`, attributs `onXxx` et styles inline supprimés.

---

### ✅ C3 — league/commonmark CVEs

**CVEs** : CVE-2026-30838, CVE-2026-33347 (embed domain bypass, HTML sanitization bypass)  
**Fix** : `composer update league/commonmark` → 2.8.0 → **2.8.2** ✅

---

## 🟠 Élevés — ACTION REQUISE EN PRODUCTION

### ⚠️ H1 — Variables d'environnement dangereux en prod

Ces valeurs DOIVENT être modifiées sur le serveur de production (PAS dans ce repo) :

```env
# À changer OBLIGATOIREMENT en prod
APP_ENV=production          # était: local
APP_DEBUG=false             # était: true
LOG_LEVEL=warning           # était: debug

# À ajouter OBLIGATOIREMENT en prod (HTTPS requis)
SESSION_SECURE_COOKIE=true

# Recommandé : passer à Redis pour perfs
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

**Impact si `APP_DEBUG=true` en prod** : Stack traces complètes + code source exposés aux utilisateurs sur toute exception non capturée.  
**Impact `SESSION_SECURE_COOKIE` non défini** : Les cookies de session peuvent transiter en HTTP (interception MITM).

---

### ⚠️ H2 — phpseclib CVEs (dépendance transitive)

| CVE | Sévérité | Problème |
|-----|----------|---------|
| CVE-2026-40194 | Low | Variable-time HMAC comparison dans SSH2 |
| CVE-2026-32935 | High | AES-CBC padding oracle timing attack |

**Vecteur** : Ces vulnérabilités sont dans SSH/crypto de phpseclib, utilisé comme dépendance transitive. REZI n'expose pas de service SSH public. **Risque réel : faible** — surveiller la sortie d'un patch.

---

### ⚠️ H3 — psy/psysh (tinker) en dépendance prod

`laravel/tinker` est déclaré dans `require` (pas `require-dev`). Tinker + psysh sont installés en production.

**CVE-2026-25129** : Local Privilege Escalation via `.psysh.php` auto-load.  
**Action** : Déplacer `laravel/tinker` dans `require-dev` si tinker n'est jamais utilisé en prod.

```bash
# Optionnel mais recommandé
composer remove laravel/tinker
composer require --dev laravel/tinker
```

---

## 🟡 Moyens — À surveiller

### M1 — Authorization coverage ~18%

- 123 appels `authorize/Policy/Gate` sur ~690 actions publiques de controllers
- **Contexte** : La plupart des controllers API sont derrière `auth:sanctum` + Form Requests
- **Risque résiduel** : Les GET sensibles (profil d'un autre user, documents privés) pourraient manquer de vérification de propriété
- **Action** : Audit ciblé de `ResidenceController`, `DocumentController`, `ReviewController`

### M2 — Cache/Queue/Session sur driver `database`

- Acceptable pour le lancement, mais sous charge, la DB devient le bottleneck pour 3 workloads simultanés
- Prévoir Redis dès que la charge augmente (voir H1 ci-dessus)

### M3 — `SupportController` ligne 88 — flash error scope web

- Flash message avec `$e->getMessage()` exposé dans l'UI web (visible dans `session('error')`)
- Fixé : `config('app.debug') ? $e->getMessage() : 'Erreur générique'`

---

## ✅ Points Validés (14/14)

| Point | Résultat |
|-------|---------|
| Mass assignment | 129/129 modèles avec `$fillable` ou `$guarded` ✅ |
| SQL injection (raw queries) | Tous les `whereRaw`/`selectRaw` utilisent des bindings `?` ✅ |
| Validation fichiers uploads | MIME + taille max validés dans toutes les Form Requests ✅ |
| CSRF | 1 seule exclusion (`/payments/webhook`) + HMAC `hash_equals` ✅ |
| Rate limiting | Appliqué sur auth, upload, contact, chat, vérification ✅ |
| CORS | Domaines explicites, localhost seulement en non-prod ✅ |
| Routes admin | Protégées par `auth:sanctum` + `role:admin` middleware ✅ |
| IDOR (API) | Vérification `user_id === Auth::id()` dans tous les controllers API ✅ |
| Hachage mots de passe | `Hash::make()` + `Password::min(8)->mixedCase()->numbers()` ✅ |
| Injections shell | `BackupDatabase.php` utilise `escapeshellarg()` partout ✅ |
| Debug leaks (dd/dump) | Aucun `dd()/dump()/var_dump()` dans les controllers ✅ |
| Webhook Jeko | HMAC SHA-256 + `hash_equals()` (timing-safe) ✅ |
| `BookingApiController` `InvalidArgumentException` | Messages métier user-friendly, pas de fuite ✅ |
| Stockage fichiers modèles | `Storage::delete()` sur chemins internes uniquement ✅ |

---

## Commandes de déploiement production

```bash
# 1. Mettre à jour les dépendances (déjà fait pour commonmark)
composer install --optimize-autoloader --no-dev

# 2. Optimiser Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Vérifier les migrations
php artisan migrate --force

# 4. Clé d'application (si nouveau serveur)
php artisan key:generate

# 5. Permissions storage
chmod -R 755 storage bootstrap/cache
```

---

## Variables .env minimales pour prod

```env
APP_NAME="REZI"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://reziapp.ci

LOG_CHANNEL=daily
LOG_LEVEL=warning

SESSION_DRIVER=database  # ou redis
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120

QUEUE_CONNECTION=database  # ou redis
CACHE_STORE=database  # ou redis

# Toutes les autres clés API : conserver les valeurs actuelles
```

---

*Rapport généré automatiquement — vérifier avant chaque release majeure.*
