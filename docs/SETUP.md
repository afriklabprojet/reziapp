# 📋 ReziApp - Résumé de l'Installation & Configuration

## ✅ Ce qui a été créé

### 1. Structure Laravel Complète
- ✅ Laravel 12 installé avec toutes les dépendances
- ✅ Tailwind CSS + Alpine.js configurés
- ✅ PostCSS configuré pour Tailwind

### 2. Base de Données
#### Migrations créées :
- ✅ `add_role_to_users_table` - Ajoute role (user/owner/admin), phone, profile_photo
- ✅ `create_residences_table` - Table principale avec géolocalisation (lat/lng)
- ✅ `create_photos_table` - Photos des résidences
- ✅ `create_amenities_table` - Équipements (WiFi, Clim, etc.)
- ✅ `create_residence_amenity_table` - Table pivot

### 3. Modèles Eloquent
- ✅ `User` - Avec méthodes isAdmin(), isOwner(), relation residences()
- ✅ `Residence` - Avec géolocalisation Haversine, scopes, relations
- ✅ `Photo` - Avec auto-suppression fichiers
- ✅ `Amenity` - Relations many-to-many

### 4. Controllers
- ✅ `HomeController` - Page d'accueil + recherche géolocalisée + API map
- ✅ `ResidenceController` - Vues publiques (show, index)
- ✅ `Owner/ResidenceController` - CRUD propriétaires
- ✅ `Admin/DashboardController` - Dashboard admin
- ✅ `Admin/ResidenceController` - Modération annonces

### 5. Configuration
- ✅ `.env.example` mis à jour avec MySQL et Google Maps
- ✅ `tailwind.config.js` avec chemins Blade
- ✅ `postcss.config.js` 
- ✅ `resources/css/app.css` avec composants personnalisés

### 6. Documentation
- ✅ `README.md` complet avec instructions
- ✅ `.github/copilot-instructions.md` pour le projet

---

## 🚀 Prochaines Étapes

### 1. Configurer la base de données

```bash
# Créer la base de données MySQL
mysql -u root -p
CREATE DATABASE rezi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Copier .env et configurer
cp .env.example .env
php artisan key:generate

# Mettre à jour .env avec vos identifiants MySQL
```

### 2. Exécuter les migrations

```bash
php artisan migrate
```

### 3. Obtenir une clé API Maps

#### Google Maps

1. Aller sur https://console.cloud.google.com
2. Créer un projet
3. Activer "Maps JavaScript API", "Places API", "Geocoding API", "Directions API" et "Street View Static API"
4. Créer une clé API
5. Ajouter dans `.env`: `GOOGLE_MAPS_API_KEY=votre_cle`

### 4. Installer Laravel Breeze (Authentification)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
```

### 5. Compiler les assets

```bash
npm run dev
# OU pour production
npm run build
```

### 6. Créer le storage link

```bash
php artisan storage:link
```

### 7. (Optionnel) Créer un seeder pour les équipements

```bash
php artisan make:seeder AmenitySeeder
```

Dans `database/seeders/AmenitySeeder.php`:

```php
use App\Models\Amenity;

Amenity::create(['name' => 'WiFi', 'icon' => 'wifi']);
Amenity::create(['name' => 'Climatisation', 'icon' => 'ac']);
Amenity::create(['name' => 'TV', 'icon' => 'tv']);
Amenity::create(['name' => 'Cuisine équipée', 'icon' => 'kitchen']);
Amenity::create(['name' => 'Parking', 'icon' => 'parking']);
Amenity::create(['name' => 'Piscine', 'icon' => 'pool']);
Amenity::create(['name' => 'Sécurité', 'icon' => 'security']);
Amenity::create(['name' => 'Générateur', 'icon' => 'generator']);
```

Puis exécuter:

```bash
php artisan db:seed --class=AmenitySeeder
```

### 8. Créer un utilisateur admin

```bash
php artisan tinker
```

```php
$user = new App\Models\User();
$user->name = 'Admin ReziApp';
$user->email = 'admin@rezi.ci';
$user->password = bcrypt('password');
$user->role = 'admin';
$user->email_verified_at = now();
$user->save();
exit;
```

### 9. Démarrer le serveur

```bash
php artisan serve
```

Visiter: http://localhost:8000

---

## 📝 Routes à Créer

Dans `routes/web.php`, ajouter:

```php
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResidenceController;
use App\Http\Controllers\Owner\ResidenceController as OwnerResidenceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResidenceController as AdminResidenceController;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/api/map-data', [HomeController::class, 'mapData'])->name('api.map-data');
Route::resource('residences', ResidenceController::class)->only(['index', 'show']);

// Owner routes (authentification requise)
Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->name('owner.')->group(function () {
    Route::resource('residences', OwnerResidenceController::class);
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/residences', [AdminResidenceController::class, 'index'])->name('residences.index');
    Route::post('/residences/{residence}/approve', [AdminResidenceController::class, 'approve'])->name('residences.approve');
    Route::post('/residences/{residence}/reject', [AdminResidenceController::class, 'reject'])->name('residences.reject');
});
```

---

## 🎨 Views à Créer

### Structure de base:
```
resources/views/
├── layouts/
│   ├── app.blade.php          # Layout principal
│   ├── navigation.blade.php   # Barre de navigation
├── home.blade.php             # Page d'accueil avec recherche
├── residences/
│   ├── index.blade.php        # Liste des résidences
│   ├── show.blade.php         # Détails résidence
├── owner/
│   ├── dashboard.blade.php    # Dashboard propriétaire
│   └── residences/
│       ├── index.blade.php
│       ├── create.blade.php
│       ├── edit.blade.php
└── admin/
    ├── dashboard.blade.php    # Dashboard admin
    └── residences/
        └── index.blade.php    # Modération
```

---

## 🔧 Middleware à Créer

Créer un middleware pour vérifier le rôle:

```bash
php artisan make:middleware EnsureUserHasRole
```

Dans `app/Http/Middleware/EnsureUserHasRole.php`:

```php
public function handle($request, Closure $next, ...$roles)
{
    if (!$request->user() || !in_array($request->user()->role, $roles)) {
        abort(403, 'Accès refusé');
    }
    return $next($request);
}
```

Enregistrer dans `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

---

## 📊 Fonctionnalités Clés Implémentées

### Géolocalisation Haversine
Le modèle `Residence` inclut:
- ✅ Méthode `distanceFrom()` pour calculer la distance
- ✅ Scope `withinRadius()` pour recherche par rayon
- ✅ Index sur latitude/longitude pour performance

### Exemple d'utilisation:
```php
// Trouver toutes les résidences dans un rayon de 2km
$residences = Residence::approved()
    ->available()
    ->withinRadius($latitude, $longitude, 500)
    ->get();

// Chaque résidence aura un attribut 'distance' en mètres
foreach ($residences as $residence) {
    echo "Distance: " . round($residence->distance) . " m";
}
```

---

## 🎯 Checklist Complète

- [x] Laravel installé
- [x] Tailwind CSS configuré
- [x] Alpine.js installé
- [x] Migrations créées
- [x] Modèles avec relations
- [x] Controllers de base
- [x] Géolocalisation Haversine
- [x] README.md complet
- [x] .env.example configuré
- [ ] Laravel Breeze installé (à faire)
- [ ] Routes définies (à faire)
- [ ] Views Blade créées (à faire)
- [ ] Middleware de rôle (à faire)
- [ ] Seeder amenities (à faire)
- [ ] Tests unitaires (optionnel)

---

## 📞 Aide & Support

Si vous rencontrez des problèmes:

1. Vérifier les logs: `storage/logs/laravel.log`
2. Vider le cache: `php artisan cache:clear`
3. Recompiler les assets: `npm run dev`
4. Vérifier les permissions: `chmod -R 775 storage bootstrap/cache`

---

Bon développement ! 🚀
