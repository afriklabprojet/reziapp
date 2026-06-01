# REZI

REZI est une plateforme SaaS de recherche de residences meublees axee sur la localisation. L'experience principale permet a un utilisateur de se geolocaliser, de voir les logements autour de lui sur une carte, puis d'elargir automatiquement le rayon si aucune residence n'est trouvee dans la zone immediate.

## Fonctionnalites

- Recherche geolocalisee par rayons configures : 2 km, 5 km, 10 km, 25 km et 50 km.
- Carte interactive Mapbox avec marqueurs de residences, position utilisateur et rayon actif.
- Liste de residences a proximite synchronisee avec les resultats reels du rayon selectionne.
- Parcours proprietaire : gestion des annonces, photos, disponibilites et documents.
- Parcours admin Filament : moderation, utilisateurs, statistiques et parametres plateforme.
- Authentification Laravel, Sanctum, double facteur et protections de securite applicative.

## Stack

- Backend : Laravel 12, PHP 8.2+
- Frontend : Blade, Tailwind CSS, Alpine.js, Vite
- Base de donnees : MySQL
- Cartographie : Mapbox GL JS
- Admin : Filament
- Tests : PHPUnit/Laravel testing, Playwright pour les parcours E2E

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

Demarrage local :

```bash
php artisan serve
npm run dev
```

Application locale : `http://127.0.0.1:8000`

## Configuration

Variables importantes dans `.env` :

```env
APP_NAME=REZI
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rezi
DB_USERNAME=root
DB_PASSWORD=

MAPBOX_ACCESS_TOKEN=your_mapbox_token
```

Les rayons de recherche sont centralises dans `config/rezi.php` :

```php
'search' => [
    'allowed_radii' => [2000, 5000, 10000, 25000, 50000],
    'default_radius' => 2000,
],
```

## API Geolocalisation

Comptage des residences par rayon :

```http
GET /api/v1/geo/radius-counts?latitude=5.3477&longitude=-3.9892
```

Residences proches dans un rayon donne :

```http
GET /api/v1/geo/nearby?latitude=5.3477&longitude=-3.9892&radius=5000&limit=15
```

Recherche geolocalisee avec filtres :

```http
POST /api/v1/geo/search
Content-Type: application/json

{
  "latitude": 5.3477,
  "longitude": -3.9892,
  "radius": 5000,
  "sort": "distance"
}
```

## Commandes Utiles

```bash
# Build frontend
npm run build

# Serveur Laravel
php artisan serve

# Cache Blade
php artisan view:clear && php artisan view:cache

# Format PHP
./vendor/bin/pint

# Tests Laravel, si les dependances dev sont installees
php artisan test
```

## Structure Projet

```text
app/
├── Http/Controllers/       # Pages, API, owner, payment
├── Http/Requests/          # Validation des entrees
├── Http/Resources/         # Reponses API
├── Models/                 # Eloquent models
├── Services/               # Logique metier, geolocalisation, paiements
└── Filament/               # Back-office admin

resources/
├── views/                  # Blade templates
├── css/                    # Tailwind CSS
└── js/                     # Alpine/Mapbox modules

database/
├── migrations/
├── factories/
└── seeders/
```

## Securite

- Garder `APP_DEBUG=false` en production.
- Utiliser HTTPS et cookies securises en production.
- Valider toutes les entrees via Form Requests ou validateurs explicites.
- Stocker les secrets uniquement dans l'environnement, jamais dans le depot.
- Controler les uploads de fichiers et les acces proprietaire/admin via policies/middleware.

## Deploiement

Checklist minimale :

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Verifier aussi les taches planifiees, la queue, le stockage public et la configuration Sentry avant mise en production.
