# 🏗️ REZI - Architecture Technique & Conventions
## Document Lead Tech - MVP Scalable

**Version**: 1.0  
**Date**: 29 Janvier 2026  
**Auteur**: Lead Tech Laravel  
**Statut**: Production Ready

---

## 📋 Table des Matières

1. [Vision Architecture](#1-vision-architecture)
2. [Architecture Globale](#2-architecture-globale)
3. [Modules Principaux](#3-modules-principaux)
4. [Conventions de Nommage](#4-conventions-de-nommage)
5. [Standards de Code](#5-standards-de-code)
6. [Organisation des Dossiers](#6-organisation-des-dossiers)
7. [Stack Technique & Dépendances](#7-stack-technique--dépendances)
8. [Patterns & Best Practices](#8-patterns--best-practices)
9. [Sécurité](#9-sécurité)
10. [Performance & Scalabilité](#10-performance--scalabilité)
11. [Testing Strategy](#11-testing-strategy)
12. [Deployment](#12-deployment)

---

## 1. Vision Architecture

### 1.1 Objectifs
- ✅ **MVP Scalable**: Code prêt à supporter 10k utilisateurs/jour
- ✅ **Maintenabilité**: Architecture modulaire et découplée
- ✅ **Performance**: < 2s temps de réponse
- ✅ **Sécurité**: Conformité OWASP Top 10
- ✅ **Évolutivité**: Prêt pour mobile app + API REST

### 1.2 Principes Directeurs
- **DRY** (Don't Repeat Yourself)
- **SOLID** principles
- **Repository Pattern** pour la couche data
- **Service Layer** pour la logique métier complexe
- **Single Responsibility** par classe/méthode
- **Separation of Concerns** (Controller ≠ Business Logic)

---

## 2. Architecture Globale

### 2.1 Architecture en Couches

```
┌─────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   Public    │  │   Owner     │  │    Admin    │    │
│  │   Views     │  │  Dashboard  │  │   Panel     │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                     │
│  ┌──────────────────────────────────────────────────┐  │
│  │           Controllers (Thin Layer)               │  │
│  │  HomeController | ResidenceController | etc.    │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↓                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Services (Business Logic)           │  │
│  │  ResidenceService | GeolocationService | etc.   │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                      DOMAIN LAYER                        │
│  ┌──────────────────────────────────────────────────┐  │
│  │        Repositories (Data Access)                │  │
│  │  ResidenceRepository | UserRepository           │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↓                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │           Models (Eloquent ORM)                  │  │
│  │  User | Residence | Photo | Amenity             │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────┐
│                   INFRASTRUCTURE LAYER                   │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐    │
│  │   MySQL     │  │   Storage   │  │  Cache      │    │
│  │  (Spatial)  │  │   (S3)      │  │  (Redis)    │    │
│  └─────────────┘  └─────────────┘  └─────────────┘    │
└─────────────────────────────────────────────────────────┘
```

### 2.2 Flux de Données Typique

**Exemple: Recherche de résidences**

```
User Request
    ↓
Route (web.php)
    ↓
Middleware (auth, role)
    ↓
Controller (HomeController@search)
    ↓
Service (ResidenceService)
    ↓
Repository (ResidenceRepository)
    ↓
Model (Residence with Scopes)
    ↓
Database (MySQL with Spatial Index)
    ↓
Response (JSON or Blade View)
    ↓
User
```

---

## 3. Modules Principaux

### 3.1 Module Authentication
**Responsabilité**: Gestion utilisateurs & rôles

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Auth/
│   │       ├── LoginController.php
│   │       ├── RegisterController.php
│   │       └── PasswordResetController.php
│   └── Middleware/
│       ├── Authenticate.php
│       └── CheckRole.php
├── Models/
│   └── User.php
└── Services/
    └── AuthService.php
```

**Features**:
- ✅ Login/Register avec Laravel Breeze
- ✅ Gestion des rôles (user, owner, admin)
- ✅ Email verification
- ✅ Password reset
- ✅ Remember me

---

### 3.2 Module Geolocation
**Responsabilité**: Recherche spatiale & calcul distances

```
app/
├── Services/
│   ├── GeolocationService.php
│   └── DistanceCalculator.php
├── Models/
│   └── Residence.php (with spatial scopes)
└── Http/
    └── Controllers/
        └── Api/
            └── GeolocationController.php
```

**Features**:
- ✅ Calcul Haversine
- ✅ Recherche par rayon (100m, 300m, 500m)
- ✅ Tri par distance
- ✅ Cache des requêtes fréquentes
- ✅ API endpoints pour carte interactive

**Exemple Service**:
```php
class GeolocationService
{
    public function findNearby(float $lat, float $lng, int $radius): Collection
    {
        return Cache::remember(
            "residences_{$lat}_{$lng}_{$radius}",
            3600,
            fn() => Residence::withinRadius($lat, $lng, $radius)
                ->approved()
                ->available()
                ->with(['photos', 'amenities'])
                ->get()
        );
    }
}
```

---

### 3.3 Module Residence Management
**Responsabilité**: CRUD résidences, photos, équipements

```
app/
├── Http/
│   └── Controllers/
│       ├── ResidenceController.php         # Public
│       ├── Owner/
│       │   └── ResidenceController.php     # Owner CRUD
│       └── Admin/
│           └── ResidenceController.php     # Moderation
├── Services/
│   ├── ResidenceService.php
│   └── PhotoUploadService.php
├── Repositories/
│   └── ResidenceRepository.php
├── Models/
│   ├── Residence.php
│   ├── Photo.php
│   └── Amenity.php
└── Http/
    ├── Requests/
    │   ├── StoreResidenceRequest.php
    │   └── UpdateResidenceRequest.php
    └── Resources/
        └── ResidenceResource.php
```

**Features**:
- ✅ CRUD complet avec validation
- ✅ Upload multiple photos
- ✅ Gestion équipements (many-to-many)
- ✅ Géolocalisation sur carte
- ✅ Soft deletes
- ✅ Statistiques (vues, contacts)

---

### 3.4 Module Administration
**Responsabilité**: Modération, statistiques, gestion

```
app/
├── Http/
│   └── Controllers/
│       └── Admin/
│           ├── DashboardController.php
│           ├── ResidenceController.php
│           ├── UserController.php
│           └── StatisticsController.php
├── Services/
│   ├── ModerationService.php
│   └── StatisticsService.php
└── Models/
    └── ActivityLog.php
```

**Features**:
- ✅ Dashboard avec KPIs
- ✅ Validation/Rejet annonces
- ✅ Gestion utilisateurs
- ✅ Logs d'activité
- ✅ Export CSV
- ✅ Statistiques géographiques

---

### 3.5 Module Notification (Phase 2)
**Responsabilité**: Emails, SMS, Push

```
app/
├── Notifications/
│   ├── ResidenceApprovedNotification.php
│   ├── NewContactNotification.php
│   └── WeeklyStatsNotification.php
└── Services/
    └── NotificationService.php
```

---

## 4. Conventions de Nommage

### 4.1 Classes

| Type | Convention | Exemple |
|------|------------|---------|
| **Model** | Singular, PascalCase | `Residence`, `Photo` |
| **Controller** | PascalCase + "Controller" | `ResidenceController` |
| **Service** | PascalCase + "Service" | `GeolocationService` |
| **Repository** | PascalCase + "Repository" | `ResidenceRepository` |
| **Request** | PascalCase + "Request" | `StoreResidenceRequest` |
| **Resource** | PascalCase + "Resource" | `ResidenceResource` |
| **Middleware** | PascalCase | `CheckRole` |
| **Job** | PascalCase + "Job" | `ProcessImageJob` |
| **Event** | PascalCase (Past Tense) | `ResidenceCreated` |
| **Listener** | PascalCase | `SendApprovalNotification` |

### 4.2 Méthodes

| Type | Convention | Exemple |
|------|------------|---------|
| **Controller** | Verbe + Nom | `index()`, `store()`, `show()`, `update()`, `destroy()` |
| **Service** | Verbe descriptif | `createResidence()`, `calculateDistance()` |
| **Repository** | `find*`, `get*`, `create*` | `findById()`, `getApproved()` |
| **Scope** | `scope` + PascalCase | `scopeWithinRadius()`, `scopeApproved()` |
| **Accessor** | `get*Attribute` | `getDistanceAttribute()` |
| **Mutator** | `set*Attribute` | `setPriceAttribute()` |

### 4.3 Variables

```php
// ✅ Bon
$residence = Residence::find($id);
$approvedResidences = Residence::approved()->get();
$userLocation = ['lat' => $latitude, 'lng' => $longitude];

// ❌ Mauvais
$r = Residence::find($id);
$data = Residence::approved()->get();
$loc = ['lat' => $latitude, 'lng' => $longitude];
```

### 4.4 Routes

| Type | Convention | Exemple |
|------|------------|---------|
| **Public** | `/resource` | `/residences`, `/residences/{id}` |
| **Owner** | `/owner/resource` | `/owner/residences`, `/owner/dashboard` |
| **Admin** | `/admin/resource` | `/admin/residences`, `/admin/dashboard` |
| **API** | `/api/v1/resource` | `/api/v1/residences/nearby` |

### 4.5 Base de Données

```php
// Tables: plural, snake_case
residences, photos, amenities, residence_amenity

// Colonnes: snake_case
first_name, price_per_month, is_available, created_at

// Foreign keys: singular_table_id
owner_id, residence_id, amenity_id

// Pivot tables: alphabetical order
amenity_residence ❌  // Wrong
residence_amenity ✅  // Correct
```

### 4.6 Fichiers & Dossiers

```
// Dossiers: PascalCase
app/Services/
app/Repositories/

// Fichiers: PascalCase.php
ResidenceService.php
GeolocationService.php

// Views: kebab-case.blade.php
residence-card.blade.php
search-form.blade.php
```

---

## 5. Standards de Code

### 5.1 PSR-12 Compliance

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Residence;
use Illuminate\Support\Collection;

class ResidenceService
{
    public function __construct(
        private ResidenceRepository $repository,
        private GeolocationService $geolocation
    ) {}
    
    public function findNearby(
        float $latitude,
        float $longitude,
        int $radius = 500
    ): Collection {
        return $this->repository->findWithinRadius(
            $latitude,
            $longitude,
            $radius
        );
    }
}
```

### 5.2 Docblocks Obligatoires

```php
/**
 * Find residences within specified radius
 *
 * @param float $latitude User latitude
 * @param float $longitude User longitude
 * @param int $radius Search radius in meters
 * @return Collection<Residence>
 * @throws \InvalidArgumentException if radius > 5000m
 */
public function findNearby(float $latitude, float $longitude, int $radius): Collection
{
    if ($radius > 5000) {
        throw new \InvalidArgumentException('Radius cannot exceed 5000m');
    }
    
    return $this->repository->findWithinRadius($latitude, $longitude, $radius);
}
```

### 5.3 Type Hints Strictes

```php
// ✅ Bon - Types stricts
public function calculateDistance(float $lat1, float $lng1): float
{
    return $this->haversine($lat1, $lng1);
}

// ❌ Mauvais - Pas de types
public function calculateDistance($lat1, $lng1)
{
    return $this->haversine($lat1, $lng1);
}
```

### 5.4 Validation des Requests

```php
// Toujours utiliser Form Requests
class StoreResidenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:50'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'price_per_month' => ['required', 'numeric', 'min:0'],
            'photos.*' => ['required', 'image', 'max:5120'], // 5MB
            'amenities' => ['array'],
            'amenities.*' => ['exists:amenities,id'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la résidence est obligatoire.',
            'description.min' => 'La description doit contenir au moins 50 caractères.',
        ];
    }
}
```

### 5.5 Controllers Minces

```php
// ✅ Bon - Controller délègue à Service
class ResidenceController extends Controller
{
    public function store(StoreResidenceRequest $request, ResidenceService $service)
    {
        $residence = $service->create($request->validated());
        
        return redirect()
            ->route('owner.residences.show', $residence)
            ->with('success', 'Résidence créée avec succès');
    }
}

// ❌ Mauvais - Logique métier dans Controller
class ResidenceController extends Controller
{
    public function store(Request $request)
    {
        $residence = new Residence();
        $residence->name = $request->name;
        $residence->save();
        
        foreach ($request->photos as $photo) {
            $path = $photo->store('photos');
            // ... logique complexe
        }
        // ... beaucoup de code
    }
}
```

---

## 6. Organisation des Dossiers

### 6.1 Structure Complète

```
rezi/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       └── GenerateSitemapCommand.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php
│   │   │   ├── HomeController.php
│   │   │   ├── ResidenceController.php
│   │   │   ├── Auth/
│   │   │   │   └── LoginController.php
│   │   │   ├── Owner/
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── ResidenceController.php
│   │   │   ├── Admin/
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ResidenceController.php
│   │   │   │   └── UserController.php
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           └── GeolocationController.php
│   │   ├── Middleware/
│   │   │   ├── CheckRole.php
│   │   │   └── LogActivity.php
│   │   ├── Requests/
│   │   │   ├── StoreResidenceRequest.php
│   │   │   └── UpdateResidenceRequest.php
│   │   └── Resources/
│   │       ├── ResidenceResource.php
│   │       └── ResidenceCollection.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Residence.php
│   │   ├── Photo.php
│   │   └── Amenity.php
│   ├── Repositories/
│   │   ├── ResidenceRepository.php
│   │   └── UserRepository.php
│   ├── Services/
│   │   ├── ResidenceService.php
│   │   ├── GeolocationService.php
│   │   ├── PhotoUploadService.php
│   │   └── StatisticsService.php
│   ├── Traits/
│   │   └── HasGeolocation.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── RepositoryServiceProvider.php
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   ├── services.php (Google Maps key)
│   └── rezi.php (Custom config)
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   └── ResidenceFactory.php
│   ├── migrations/
│   │   ├── 2026_01_29_000000_create_users_table.php
│   │   ├── 2026_01_29_000001_add_role_to_users_table.php
│   │   └── 2026_01_29_000002_create_residences_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── AmenitySeeder.php
│       └── AdminUserSeeder.php
├── public/
│   └── storage/ (symlink)
├── resources/
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.js
│   │   └── components/
│   │       ├── map.js
│   │       └── search-filters.js
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php
│       │   ├── navigation.blade.php
│       │   └── footer.blade.php
│       ├── components/
│       │   ├── residence-card.blade.php
│       │   └── search-form.blade.php
│       ├── home.blade.php
│       ├── residences/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── owner/
│       │   ├── dashboard.blade.php
│       │   └── residences/
│       │       ├── index.blade.php
│       │       ├── create.blade.php
│       │       └── edit.blade.php
│       └── admin/
│           ├── dashboard.blade.php
│           └── residences/
│               └── index.blade.php
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── storage/
│   ├── app/
│   │   ├── public/
│   │   │   └── photos/
│   │   └── private/
│   └── logs/
├── tests/
│   ├── Feature/
│   │   ├── Auth/
│   │   │   └── LoginTest.php
│   │   ├── Residence/
│   │   │   ├── CreateResidenceTest.php
│   │   │   └── SearchResidenceTest.php
│   │   └── Admin/
│   │       └── ModerationTest.php
│   └── Unit/
│       ├── Models/
│       │   └── ResidenceTest.php
│       └── Services/
│           └── GeolocationServiceTest.php
├── .env.example
├── .gitignore
├── composer.json
├── package.json
├── phpunit.xml
└── README.md
```

### 6.2 Dossiers Customs

#### app/Repositories/
Couche d'abstraction pour les requêtes complexes

```php
interface ResidenceRepositoryInterface
{
    public function findWithinRadius(float $lat, float $lng, int $radius): Collection;
    public function findApproved(): Collection;
}

class ResidenceRepository implements ResidenceRepositoryInterface
{
    public function findWithinRadius(float $lat, float $lng, int $radius): Collection
    {
        return Residence::withinRadius($lat, $lng, $radius)->get();
    }
}
```

#### app/Services/
Logique métier complexe

```php
class ResidenceService
{
    public function create(array $data): Residence
    {
        DB::beginTransaction();
        
        try {
            $residence = Residence::create($data);
            
            $this->photoService->uploadMultiple($residence, $data['photos']);
            $residence->amenities()->sync($data['amenities']);
            
            DB::commit();
            return $residence->fresh(['photos', 'amenities']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

#### app/Traits/
Réutilisabilité du code

```php
trait HasGeolocation
{
    public function scopeWithinRadius($query, float $lat, float $lng, int $radius)
    {
        // Implementation Haversine
    }
    
    public function distanceFrom(float $lat, float $lng): float
    {
        // Implementation calcul distance
    }
}
```

---

## 7. Stack Technique & Dépendances

### 7.1 Core Dependencies

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "laravel/breeze": "^2.0",
    "laravel/sanctum": "^4.0",
    "intervention/image": "^3.0",
    "spatie/laravel-permission": "^6.0",
    "barryvdh/laravel-debugbar": "^3.9"
  },
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "laravel/pint": "^1.0",
    "nunomaduro/collision": "^8.0",
    "phpunit/phpunit": "^11.0"
  }
}
```

### 7.2 Frontend Dependencies

```json
{
  "devDependencies": {
    "@tailwindcss/postcss": "^4.0",
    "alpinejs": "^3.13",
    "autoprefixer": "^10.4",
    "axios": "^1.6",
    "laravel-vite-plugin": "^2.0",
    "postcss": "^8.4",
    "tailwindcss": "^4.0",
    "vite": "^7.0"
  }
}
```

### 7.3 Packages Recommandés (Phase 2)

```bash
# API Documentation
composer require darkaonline/l5-swagger

# Excel Export
composer require maatwebsite/excel

# Activity Logs
composer require spatie/laravel-activitylog

# Rate Limiting
composer require spatie/laravel-rate-limiter

# Backup
composer require spatie/laravel-backup

# Media Library
composer require spatie/laravel-medialibrary
```

---

## 8. Patterns & Best Practices

### 8.1 Repository Pattern

```php
// Interface
interface ResidenceRepositoryInterface
{
    public function find(int $id): ?Residence;
    public function findApproved(): Collection;
    public function create(array $data): Residence;
}

// Implementation
class EloquentResidenceRepository implements ResidenceRepositoryInterface
{
    public function find(int $id): ?Residence
    {
        return Residence::find($id);
    }
    
    public function findApproved(): Collection
    {
        return Residence::approved()->get();
    }
}

// Service Provider
class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ResidenceRepositoryInterface::class,
            EloquentResidenceRepository::class
        );
    }
}
```

### 8.2 Service Layer Pattern

```php
class ResidenceService
{
    public function __construct(
        private ResidenceRepository $repository,
        private PhotoUploadService $photoService,
        private NotificationService $notificationService
    ) {}
    
    public function create(array $data): Residence
    {
        DB::beginTransaction();
        
        try {
            $residence = $this->repository->create($data);
            
            if (isset($data['photos'])) {
                $this->photoService->uploadForResidence($residence, $data['photos']);
            }
            
            if (isset($data['amenities'])) {
                $residence->amenities()->sync($data['amenities']);
            }
            
            $this->notificationService->notifyAdmins($residence);
            
            DB::commit();
            
            return $residence->fresh(['photos', 'amenities']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Residence creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
```

### 8.3 Query Scopes

```php
class Residence extends Model
{
    /**
     * Scope: Only approved residences
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    /**
     * Scope: Only available residences
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
    
    /**
     * Scope: Within geographic radius
     */
    public function scopeWithinRadius($query, float $lat, float $lng, int $radius)
    {
        $earthRadius = 6371000; // meters
        
        return $query->selectRaw(
            "*, (
                {$earthRadius} * acos(
                    cos(radians(?)) * cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )
            ) AS distance",
            [$lat, $lng, $lat]
        )
        ->having('distance', '<=', $radius)
        ->orderBy('distance');
    }
}

// Usage
$residences = Residence::approved()
    ->available()
    ->withinRadius($lat, $lng, 500)
    ->get();
```

### 8.4 Resource Transformers

```php
class ResidenceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'location' => [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'address' => $this->address,
                'commune' => $this->commune,
                'quartier' => $this->quartier,
            ],
            'pricing' => [
                'per_day' => $this->price_per_day,
                'per_week' => $this->price_per_week,
                'per_month' => $this->price_per_month,
            ],
            'photos' => PhotoResource::collection($this->whenLoaded('photos')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'owner' => new UserResource($this->whenLoaded('owner')),
            'stats' => $this->when($request->user()?->isAdmin(), [
                'views' => $this->views_count,
                'contacts' => $this->contacts_count,
            ]),
            'distance' => $this->when(isset($this->distance), fn() => round($this->distance)),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

### 8.5 Events & Listeners

```php
// Event
class ResidenceCreated
{
    public function __construct(public Residence $residence) {}
}

// Listener
class SendModerationNotification
{
    public function handle(ResidenceCreated $event): void
    {
        $admins = User::where('role', 'admin')->get();
        
        Notification::send($admins, new NewResidenceNotification($event->residence));
    }
}

// Controller
public function store(StoreResidenceRequest $request, ResidenceService $service)
{
    $residence = $service->create($request->validated());
    
    event(new ResidenceCreated($residence));
    
    return redirect()->route('owner.residences.show', $residence);
}
```

---

## 9. Sécurité

### 9.1 Checklist Sécurité

- ✅ **CSRF Protection**: Tous les formulaires incluent `@csrf`
- ✅ **XSS Prevention**: Utiliser `{{ }}` au lieu de `{!! !!}`
- ✅ **SQL Injection**: Toujours utiliser Eloquent ou Query Builder avec bindings
- ✅ **Mass Assignment**: Définir `$fillable` ou `$guarded` sur tous les modèles
- ✅ **Authorization**: Utiliser Policies pour les permissions
- ✅ **Rate Limiting**: Limiter les requêtes API et login
- ✅ **File Upload**: Valider type, taille, extensions
- ✅ **HTTPS**: Obligatoire en production
- ✅ **Environment Variables**: Jamais commit `.env`
- ✅ **Dependencies**: Régulièrement mettre à jour les packages

### 9.2 Middleware Stack

```php
// routes/web.php
Route::middleware(['web'])->group(function () {
    Route::get('/', [HomeController::class, 'index']);
});

Route::middleware(['auth'])->group(function () {
    // Routes authentifiées
});

Route::middleware(['auth', 'role:owner,admin'])->prefix('owner')->group(function () {
    // Routes propriétaires
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Routes admin
});
```

### 9.3 Policy Authorization

```php
class ResidencePolicy
{
    public function update(User $user, Residence $residence): bool
    {
        return $user->id === $residence->owner_id || $user->isAdmin();
    }
    
    public function delete(User $user, Residence $residence): bool
    {
        return $user->id === $residence->owner_id || $user->isAdmin();
    }
    
    public function approve(User $user, Residence $residence): bool
    {
        return $user->isAdmin();
    }
}

// Controller
public function update(UpdateResidenceRequest $request, Residence $residence)
{
    $this->authorize('update', $residence);
    
    // Update logic
}
```

### 9.4 Rate Limiting

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
];

// routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/residences/search', [GeolocationController::class, 'search']);
});

// routes/web.php
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});
```

---

## 10. Performance & Scalabilité

### 10.1 Database Optimization

```php
// Indexes
Schema::table('residences', function (Blueprint $table) {
    $table->index(['latitude', 'longitude']); // Geospatial queries
    $table->index('status'); // Filtering
    $table->index('is_available'); // Filtering
    $table->index('created_at'); // Sorting
});

// Eager Loading
$residences = Residence::with(['photos', 'amenities', 'owner'])
    ->approved()
    ->available()
    ->get();

// Lazy Eager Loading
$residences = Residence::all();
$residences->load('photos');

// Chunking for large datasets
Residence::chunk(100, function ($residences) {
    foreach ($residences as $residence) {
        // Process
    }
});
```

### 10.2 Caching Strategy

```php
// Cache geolocation queries (1 hour)
$residences = Cache::remember(
    "residences_{$lat}_{$lng}_{$radius}",
    3600,
    fn() => Residence::withinRadius($lat, $lng, $radius)->get()
);

// Cache homepage data (5 minutes)
$featuredResidences = Cache::remember(
    'featured_residences',
    300,
    fn() => Residence::approved()->inRandomOrder()->limit(6)->get()
);

// Cache user-specific data with tags
Cache::tags(['user', "user_{$userId}"])->put("residences_{$userId}", $residences, 3600);

// Clear cache on update
Cache::forget("residences_{$lat}_{$lng}_{$radius}");
Cache::tags(['residences'])->flush();
```

### 10.3 Queue Jobs

```php
// Photo processing in background
class ProcessResidencePhotos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public Residence $residence,
        public array $photos
    ) {}
    
    public function handle(PhotoUploadService $service): void
    {
        foreach ($this->photos as $photo) {
            $service->optimizeAndStore($this->residence, $photo);
        }
    }
}

// Dispatch job
ProcessResidencePhotos::dispatch($residence, $photos);
```

### 10.4 CDN & Asset Optimization

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
    ],
],

// Store photos on S3/CDN
Storage::disk('s3')->put($path, $file);
```

---

## 11. Testing Strategy

### 11.1 Test Structure

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   └── RegisterTest.php
│   ├── Residence/
│   │   ├── CreateResidenceTest.php
│   │   ├── SearchResidenceTest.php
│   │   └── UpdateResidenceTest.php
│   └── Admin/
│       └── ModerationTest.php
└── Unit/
    ├── Models/
    │   └── ResidenceTest.php
    └── Services/
        ├── GeolocationServiceTest.php
        └── ResidenceServiceTest.php
```

### 11.2 Feature Test Example

```php
class CreateResidenceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_owner_can_create_residence(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        
        $response = $this->actingAs($owner)->post('/owner/residences', [
            'name' => 'Villa Cocody',
            'description' => 'Belle villa avec piscine dans le quartier résidentiel de Cocody',
            'address' => '123 Rue des Jardins',
            'commune' => 'Cocody',
            'quartier' => 'II Plateaux',
            'latitude' => 5.3599517,
            'longitude' => -4.0082563,
            'price_per_month' => 500000,
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('residences', ['name' => 'Villa Cocody']);
    }
    
    public function test_guest_cannot_create_residence(): void
    {
        $response = $this->post('/owner/residences', []);
        
        $response->assertRedirect('/login');
    }
}
```

### 11.3 Unit Test Example

```php
class GeolocationServiceTest extends TestCase
{
    protected GeolocationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeolocationService();
    }
    
    public function test_calculates_distance_correctly(): void
    {
        // Abidjan to Cocody (approx 5km)
        $distance = $this->service->calculateDistance(
            5.316667, -4.033333, // Abidjan
            5.3599517, -4.0082563  // Cocody
        );
        
        $this->assertGreaterThan(4000, $distance);
        $this->assertLessThan(6000, $distance);
    }
}
```

### 11.4 Coverage Targets

- **Controllers**: 80%+
- **Services**: 90%+
- **Models**: 75%+
- **Repositories**: 85%+

```bash
# Run tests with coverage
php artisan test --coverage --min=80
```

---

## 12. Deployment

### 12.1 Environments

| Environment | URL | Branch | Auto-Deploy |
|-------------|-----|--------|-------------|
| **Local** | localhost:8000 | - | - |
| **Staging** | staging.rezi.ci | develop | ✅ |
| **Production** | rezi.ci | main | ❌ Manual |

### 12.2 Deployment Checklist

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database
php artisan migrate --force

# 5. Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Storage
php artisan storage:link

# 7. Permissions
chmod -R 775 storage bootstrap/cache

# 8. Queue & Cron
php artisan queue:restart
```

### 12.3 CI/CD Pipeline (GitHub Actions)

```yaml
name: Laravel CI

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install Dependencies
      run: composer install
      
    - name: Run Tests
      run: php artisan test
      
    - name: Run Pint
      run: ./vendor/bin/pint --test
```

### 12.4 Monitoring

```php
// Log important actions
Log::info('Residence created', [
    'residence_id' => $residence->id,
    'owner_id' => $residence->owner_id
]);

Log::warning('High search load', [
    'requests_per_minute' => $rpm
]);

Log::error('Failed to process photo', [
    'photo_id' => $photo->id,
    'error' => $e->getMessage()
]);
```

---

## 13. Documentation Obligatoire

### 13.1 README.md
- ✅ Installation steps
- ✅ Environment setup
- ✅ API documentation
- ✅ Contributing guidelines

### 13.2 API Documentation
- ✅ Use L5 Swagger
- ✅ Document all endpoints
- ✅ Include examples

### 13.3 Inline Comments
- ✅ Complex algorithms
- ✅ Business rules
- ✅ Workarounds

---

## Conclusion

Cette architecture est **production-ready** et **scalable**. Elle respecte les standards Laravel et les best practices de l'industrie.

**Next Steps**:
1. Implémenter les routes manquantes
2. Créer les views Blade
3. Ajouter les tests
4. Setup CI/CD
5. Deploy staging environment

---

**Document maintenu par**: Lead Tech Team  
**Dernière mise à jour**: 29 Janvier 2026  
**Version**: 1.0
