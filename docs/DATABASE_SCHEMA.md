# REZI - Architecture Base de Données

## 📊 Diagramme Logique (ERD)

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                            REZI DATABASE SCHEMA                                          │
│                     Optimisé pour recherche géolocalisée ≤ 2km                         │
└─────────────────────────────────────────────────────────────────────────────────────────┘

                                    ┌──────────────────┐
                                    │      USERS       │
                                    ├──────────────────┤
                                    │ id (PK)          │
                                    │ name             │
                                    │ email (UNIQUE)   │
                                    │ role (ENUM)      │◄─────── user | owner | admin
                                    │ phone            │
                                    │ profile_photo    │
                                    │ password         │
                                    │ email_verified_at│
                                    │ created_at       │
                                    │ updated_at       │
                                    └────────┬─────────┘
                                             │
                    ┌────────────────────────┼────────────────────────┐
                    │ (owner_id)             │ (user_id)              │ (owner_id)
                    ▼                        ▼                        ▼
        ┌───────────────────────┐   ┌────────────────┐    ┌─────────────────────┐
        │     RESIDENCES        │   │   CONTACTS     │    │    (CONTACTS)       │
        ├───────────────────────┤   ├────────────────┤    └─────────────────────┘
        │ id (PK)               │   │ id (PK)        │
        │ owner_id (FK)         │   │ user_id (FK)   │
        │ name                  │   │ residence_id   │
        │ description           │   │ owner_id (FK)  │
        │ address               │   │ phone          │
        │ commune               │   │ message        │
        │ quartier              │   │ status (ENUM)  │◄─── pending|viewed|responded|closed
        │ ═══════════════════   │   │ viewed_at      │
        │ latitude  (DECIMAL)   │◄──┤ responded_at   │
        │ longitude (DECIMAL)   │   │ user_latitude  │◄─── Position au moment du contact
        │ ═══════════════════   │   │ user_longitude │
        │ price_per_day         │   │ created_at     │
        │ price_per_week        │   │ updated_at     │
        │ price_per_month       │   └────────────────┘
        │ status (ENUM)         │◄─────── pending | approved | rejected
        │ is_available          │
        │ views_count           │
        │ contacts_count        │
        │ created_at            │
        │ updated_at            │
        │ deleted_at            │◄─────── Soft Delete
        │                       │
        │ INDEX(lat, lng)       │◄─────── Index composite géographique
        └───────────┬───────────┘
                    │
    ┌───────────────┼───────────────┬───────────────────────┐
    │               │               │                       │
    ▼               ▼               ▼                       ▼
┌─────────┐  ┌─────────────┐  ┌────────────────┐   ┌────────────────┐
│ PHOTOS  │  │ STATISTICS  │  │ RESIDENCE_     │   │   AMENITIES    │
├─────────┤  ├─────────────┤  │   AMENITY      │   ├────────────────┤
│ id (PK) │  │ id (PK)     │  ├────────────────┤   │ id (PK)        │
│ resid.  │  │ residence_id│  │ id (PK)        │   │ name           │
│ path    │  │ stat_date   │  │ residence_id   │   │ icon           │
│ order   │  │ views       │  │ amenity_id     │   │ created_at     │
│is_primary│ │ contacts    │  │ created_at     │   │ updated_at     │
│created_at│ │ shares      │  │ updated_at     │   └────────────────┘
│updated_at│ │ favorites   │  │                │            ▲
└─────────┘  │ geo_searches│  │ UNIQUE(res,am) │            │
             │ map_views   │  └────────────────┘────────────┘
             │ mobile_views│
             │ desktop_view│
             │ created_at  │
             │ updated_at  │
             │             │
             │ UNIQUE(res, │
             │   date)     │
             └─────────────┘
```

---

## 📋 Schéma Complet des Tables

### 1. **USERS** (Utilisateurs, Propriétaires, Admins)
```sql
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    role            ENUM('user', 'owner', 'admin') DEFAULT 'user',
    phone           VARCHAR(255) NULL,
    profile_photo   VARCHAR(255) NULL,
    email_verified_at TIMESTAMP NULL,
    password        VARCHAR(255) NOT NULL,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    INDEX idx_role (role)
);
```

### 2. **RESIDENCES** (Logements meublés)
```sql
CREATE TABLE residences (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id        BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    description     TEXT NOT NULL,
    address         VARCHAR(255) NOT NULL,
    commune         VARCHAR(255) NOT NULL,
    quartier        VARCHAR(255) NOT NULL,
    
    -- Géolocalisation (Abidjan: ~5.3° lat, ~-4.0° lng)
    latitude        DECIMAL(10, 8) NOT NULL,  -- Précision: 1.1mm
    longitude       DECIMAL(11, 8) NOT NULL,  -- Précision: 1.1mm
    
    -- Tarification
    price_per_day   DECIMAL(10, 2) NULL,
    price_per_week  DECIMAL(10, 2) NULL,
    price_per_month DECIMAL(10, 2) NOT NULL,
    
    -- Statut
    status          ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_available    BOOLEAN DEFAULT TRUE,
    
    -- Statistiques agrégées
    views_count     INT UNSIGNED DEFAULT 0,
    contacts_count  INT UNSIGNED DEFAULT 0,
    
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    
    -- Index composite pour recherche géographique optimisée
    INDEX idx_geo (latitude, longitude),
    INDEX idx_status_available (status, is_available),
    INDEX idx_commune (commune),
    INDEX idx_owner (owner_id)
);
```

### 3. **PHOTOS** (Images des résidences)
```sql
CREATE TABLE photos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    residence_id    BIGINT UNSIGNED NOT NULL,
    path            VARCHAR(255) NOT NULL,
    order           INT DEFAULT 0,
    is_primary      BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    FOREIGN KEY (residence_id) REFERENCES residences(id) ON DELETE CASCADE,
    INDEX idx_residence_order (residence_id, order)
);
```

### 4. **AMENITIES** (Équipements)
```sql
CREATE TABLE amenities (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    icon            VARCHAR(255) NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL
);
```

### 5. **RESIDENCE_AMENITY** (Pivot)
```sql
CREATE TABLE residence_amenity (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    residence_id    BIGINT UNSIGNED NOT NULL,
    amenity_id      BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    FOREIGN KEY (residence_id) REFERENCES residences(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_residence_amenity (residence_id, amenity_id)
);
```

### 6. **CONTACTS** (Demandes de contact)
```sql
CREATE TABLE contacts (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    residence_id    BIGINT UNSIGNED NOT NULL,
    owner_id        BIGINT UNSIGNED NOT NULL,
    phone           VARCHAR(255) NULL,
    message         TEXT NULL,
    status          ENUM('pending', 'viewed', 'responded', 'closed') DEFAULT 'pending',
    viewed_at       TIMESTAMP NULL,
    responded_at    TIMESTAMP NULL,
    
    -- Position utilisateur au moment du contact
    user_latitude   DECIMAL(10, 8) NULL,
    user_longitude  DECIMAL(11, 8) NULL,
    
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES residences(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_owner_status (owner_id, status),
    INDEX idx_residence_date (residence_id, created_at),
    INDEX idx_user_date (user_id, created_at)
);
```

### 7. **STATISTICS** (Statistiques journalières)
```sql
CREATE TABLE statistics (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    residence_id    BIGINT UNSIGNED NOT NULL,
    stat_date       DATE NOT NULL,
    
    -- Compteurs journaliers
    views           INT UNSIGNED DEFAULT 0,
    contacts        INT UNSIGNED DEFAULT 0,
    shares          INT UNSIGNED DEFAULT 0,
    favorites       INT UNSIGNED DEFAULT 0,
    
    -- Métriques géolocalisées
    geo_searches    INT UNSIGNED DEFAULT 0,
    map_views       INT UNSIGNED DEFAULT 0,
    
    -- Origine des visites
    mobile_views    INT UNSIGNED DEFAULT 0,
    desktop_views   INT UNSIGNED DEFAULT 0,
    
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    
    FOREIGN KEY (residence_id) REFERENCES residences(id) ON DELETE CASCADE,
    UNIQUE KEY unique_residence_date (residence_id, stat_date),
    INDEX idx_date (stat_date)
);
```

---

## 🗺️ Index Géographiques et Optimisation

### Stratégie d'indexation pour rayon ≤ 2km

```php
// Index composite dans la migration
$table->index(['latitude', 'longitude']);
```

### Requête Haversine optimisée (dans Residence.php)
```php
public function scopeWithinRadius($query, float $lat, float $lng, int $radius)
{
    $earthRadius = 6371000; // mètres
    
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
```

### Pré-filtrage avec Bounding Box (optimisation)
```php
// Calcul de la bounding box pour pré-filtrer avant Haversine
$latDelta = $radius / 111320; // ~111.32 km par degré de latitude
$lngDelta = $radius / (111320 * cos(deg2rad($lat)));

$query->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
      ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
```

---

## 🔗 Relations Eloquent

### User.php
```php
// Propriétaire → Résidences
public function residences()
{
    return $this->hasMany(Residence::class, 'owner_id');
}

// Propriétaire → Contacts reçus
public function receivedContacts()
{
    return $this->hasMany(Contact::class, 'owner_id');
}

// Utilisateur → Contacts envoyés
public function sentContacts()
{
    return $this->hasMany(Contact::class, 'user_id');
}
```

### Residence.php
```php
// Résidence → Propriétaire
public function owner()
{
    return $this->belongsTo(User::class, 'owner_id');
}

// Résidence → Photos
public function photos()
{
    return $this->hasMany(Photo::class)->orderBy('order');
}

// Résidence → Photo principale
public function primaryPhoto()
{
    return $this->hasOne(Photo::class)->where('is_primary', true);
}

// Résidence → Équipements (Many-to-Many)
public function amenities()
{
    return $this->belongsToMany(Amenity::class, 'residence_amenity');
}

// Résidence → Contacts
public function contacts()
{
    return $this->hasMany(Contact::class);
}

// Résidence → Statistiques
public function statistics()
{
    return $this->hasMany(Statistic::class);
}
```

### Photo.php
```php
public function residence()
{
    return $this->belongsTo(Residence::class);
}
```

### Amenity.php
```php
public function residences()
{
    return $this->belongsToMany(Residence::class, 'residence_amenity');
}
```

### Contact.php
```php
public function user()
{
    return $this->belongsTo(User::class);
}

public function residence()
{
    return $this->belongsTo(Residence::class);
}

public function owner()
{
    return $this->belongsTo(User::class, 'owner_id');
}
```

### Statistic.php
```php
public function residence()
{
    return $this->belongsTo(Residence::class);
}
```

---

## 📈 Performances

### Cache des requêtes géolocalisées
```php
// GeolocationService.php
public function findNearby(float $lat, float $lng, int $radius = 500): Collection
{
    $cacheKey = "geo:{$lat}:{$lng}:{$radius}";
    
    return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $radius) {
        return $this->repository->findWithinRadius($lat, $lng, $radius);
    });
}
```

### Temps de réponse cible: < 2 secondes
- Bounding box pré-filtrage: réduit les calculs Haversine de 90%
- Index composite (lat, lng): accès O(log n)
- Cache des résultats: réponse instantanée pour requêtes répétées

---

## 📊 Résumé des Tables

| Table | Lignes estimées | Index clés |
|-------|-----------------|------------|
| users | ~10,000 | email, role |
| residences | ~5,000 | (lat,lng), status, commune |
| photos | ~25,000 | residence_id |
| amenities | ~50 | - |
| residence_amenity | ~15,000 | (residence_id, amenity_id) |
| contacts | ~20,000 | (owner_id, status), (residence_id, date) |
| statistics | ~150,000 | (residence_id, stat_date), stat_date |
