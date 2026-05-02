# REZI - Plateforme de Localisation de Résidences Meublées<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>



🏠 **Trouvez une résidence meublée à moins de 2 km, instantanément.**<p align="center">

<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>

REZI est une plateforme SaaS permettant de trouver rapidement des résidences meublées géolocalisées dans le Grand Abidjan.<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>

<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>

---<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>

</p>

## 🎯 Fonctionnalités Principales

## About Laravel

### Pour les Utilisateurs

- ✅ Géolocalisation automatiqueLaravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- ✅ Recherche par rayon (2km, 5km, 10km)

- ✅ Carte interactive avec pins géolocalisés- [Simple, fast routing engine](https://laravel.com/docs/routing).

- ✅ Fiches détaillées des résidences- [Powerful dependency injection container](https://laravel.com/docs/container).

- ✅ Contact direct (appel, WhatsApp, itinéraire)- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.

- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).

### Pour les Propriétaires- Database agnostic [schema migrations](https://laravel.com/docs/migrations).

- ✅ Tableau de bord de gestion- [Robust background job processing](https://laravel.com/docs/queues).

- ✅ Ajout/modification de résidences- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

- ✅ Upload photos

- ✅ Gestion prix & disponibilitéLaravel is accessible, powerful, and provides tools required for large, robust applications.

- ✅ Statistiques (vues, contacts)

## Learning Laravel

### Pour les Administrateurs

- ✅ Validation des annoncesLaravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

- ✅ Modération des contenus

- ✅ Gestion des utilisateursIf you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

- ✅ Statistiques globales

## Laravel Sponsors

---

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

## 🛠️ Stack Technique

### Premium Partners

- **Backend**: Laravel 12

- **Frontend**: Blade + Tailwind CSS + Alpine.js- **[Vehikl](https://vehikl.com)**

- **Base de données**: MySQL avec indexation géospatiale- **[Tighten Co.](https://tighten.co)**

- **Cartes**: Google Maps / Mapbox- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**

- **Authentification**: Laravel Breeze/Sanctum- **[64 Robots](https://64robots.com)**

- **[Curotec](https://www.curotec.com/services/technologies/laravel)**

---- **[DevSquad](https://devsquad.com/hire-laravel-developers)**

- **[Redberry](https://redberry.international/laravel-development)**

## 📋 Prérequis- **[Active Logic](https://activelogic.com)**



- PHP >= 8.2## Contributing

- Composer

- Node.js & NPMThank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

- MySQL >= 8.0

- Git## Code of Conduct



---In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).



## 🚀 Installation## Security Vulnerabilities



### 1. Cloner le projetIf you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.



```bash## License

git clone <repo-url>

cd reziThe Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

```

### 2. Installer les dépendances

```bash
# Dépendances PHP
composer install

# Dépendances JavaScript
npm install
```

### 3. Configuration de l'environnement

```bash
# Copier le fichier .env
cp .env.example .env

# Générer la clé d'application
php artisan key:generate
```

### 4. Configuration de la base de données

Modifier `.env` avec vos paramètres MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rezi
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Configuration de l'API Maps

Ajouter votre clé API dans `.env`:

```env
GOOGLE_MAPS_API_KEY=your_api_key_here
# OU
MAPBOX_API_KEY=your_api_key_here
```

### 6. Exécuter les migrations

```bash
php artisan migrate
```

### 7. (Optionnel) Seed la base de données

```bash
php artisan db:seed
```

### 8. Créer le lien symbolique pour le stockage

```bash
php artisan storage:link
```

### 9. Compiler les assets

```bash
# Mode développement
npm run dev

# Mode production
npm run build
```

### 10. Démarrer le serveur

```bash
php artisan serve
```

L'application sera accessible sur: `http://localhost:8000`

---

## 📁 Structure du Projet

```
rezi/
├── app/
│   ├── Http/Controllers/
│   │   ├── HomeController.php              # Page d'accueil & recherche
│   │   ├── ResidenceController.php         # Vues publiques des résidences
│   │   ├── Owner/
│   │   │   └── ResidenceController.php     # Gestion propriétaire
│   │   └── Admin/
│   │       ├── DashboardController.php     # Dashboard admin
│   │       └── ResidenceController.php     # Modération annonces
│   └── Models/
│       ├── User.php                         # Utilisateurs (user/owner/admin)
│       ├── Residence.php                    # Résidences + géolocalisation
│       ├── Photo.php                        # Photos des résidences
│       └── Amenity.php                      # Équipements
├── database/
│   └── migrations/
│       ├── add_role_to_users_table.php
│       ├── create_residences_table.php      # Table avec lat/lng
│       ├── create_photos_table.php
│       ├── create_amenities_table.php
│       └── create_residence_amenity_table.php
├── resources/
│   ├── views/                               # Templates Blade
│   ├── css/
│   │   └── app.css                          # Tailwind CSS
│   └── js/
│       └── app.js                           # Alpine.js + Maps
└── routes/
    └── web.php                              # Routes de l'application
```

---

## 🗺️ Fonctionnalités Géospatiales

Le projet utilise la **formule de Haversine** pour calculer les distances précises entre deux coordonnées GPS.

### Recherche par Rayon

```php
$residences = Residence::approved()
    ->available()
    ->withinRadius($latitude, $longitude, $radius)
    ->get();
```

### Calcul de Distance

```php
$distance = $residence->distanceFrom($userLat, $userLng);
// Retourne la distance en mètres
```

---

## 🔐 Rôles & Permissions

### user (Utilisateur)
- Rechercher des résidences
- Voir les détails
- Contacter les propriétaires

### owner (Propriétaire)
- Toutes les permissions de `user`
- Créer/modifier ses résidences
- Uploader des photos
- Voir ses statistiques

### admin (Administrateur)
- Toutes les permissions
- Valider/rejeter les annonces
- Gérer tous les utilisateurs
- Accès aux statistiques globales

---

## 🎨 Composants Tailwind Personnalisés

Le projet inclut des classes utilitaires:

```css
.btn-primary      /* Bouton primaire bleu */
.btn-secondary    /* Bouton secondaire gris */
.card             /* Carte avec ombre */
.input-field      /* Champ de saisie stylisé */
```

---

## 📊 Base de Données

### Tables Principales

- **users**: Utilisateurs avec rôle (user/owner/admin)
- **residences**: Résidences avec coordonnées GPS
- **photos**: Photos des résidences
- **amenities**: Équipements (WiFi, Clim, etc.)
- **residence_amenity**: Pivot table

### Indexation Géospatiale

Un index composite sur `(latitude, longitude)` optimise les requêtes de distance.

---

## 🌐 API Endpoints

### Public

- `GET /` - Page d'accueil avec recherche
- `GET /residences/{id}` - Détails d'une résidence
- `POST /api/map-data` - Données pour la carte

### Propriétaire (Auth Required)

- `GET /owner/dashboard` - Dashboard propriétaire
- `GET /owner/residences` - Liste des résidences
- `POST /owner/residences` - Créer une résidence
- `PUT /owner/residences/{id}` - Modifier
- `DELETE /owner/residences/{id}` - Supprimer

### Admin (Admin Role Required)

- `GET /admin/dashboard` - Dashboard admin
- `GET /admin/residences` - Toutes les résidences
- `POST /admin/residences/{id}/approve` - Approuver
- `POST /admin/residences/{id}/reject` - Rejeter

---

## 🧪 Tests

```bash
# Lancer les tests
php artisan test

# Avec couverture
php artisan test --coverage
```

---

## 📦 Déploiement

### Production Checklist

1. ✅ Configurer `.env` en production
2. ✅ `APP_DEBUG=false`
3. ✅ `APP_ENV=production`
4. ✅ Configurer la base de données
5. ✅ Compiler les assets: `npm run build`
6. ✅ Optimiser l'autoload: `composer install --optimize-autoloader --no-dev`
7. ✅ Cacher les configs: `php artisan config:cache`
8. ✅ Cacher les routes: `php artisan route:cache`
9. ✅ Cacher les vues: `php artisan view:cache`
10. ✅ Configurer HTTPS
11. ✅ Configurer les sauvegardes

---

## 🔄 Roadmap

### Phase 1 - MVP (Actuel)
- ✅ Recherche géolocalisée
- ✅ Carte interactive
- ✅ Gestion propriétaires
- ✅ Admin panel

### Phase 2 - Améliorations
- ⏳ Réservation en ligne
- ⏳ Paiement Mobile Money
- ⏳ Notation & avis
- ⏳ Application mobile (React Native)
- ⏳ Mode hors-ligne (PWA)
- ⏳ Notifications push

---

## 📞 Support

Pour toute question ou problème:

- 📧 Email: support@rezi.ci
- 💬 WhatsApp: +225 XX XX XX XX XX
- 🌐 Site: https://rezi.ci

---

## 📄 Licence

Propriétaire - © 2026 REZI

---

## 👥 Équipe

Développé avec ❤️ par l'équipe REZI à Abidjan, Côte d'Ivoire.
