# REZI API Documentation

## Overview

L'API REZI permet d'intégrer les fonctionnalités de recherche et réservation de résidences meublées à Abidjan.

**Base URL**: `https://rezi.ci/api`

**Version**: v1

**Authentication**: Bearer Token (Laravel Sanctum)

---

## Authentication

### Obtenir un token

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "your-password"
}
```

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "user"
        },
        "token": "1|abc123xyz...",
        "token_type": "Bearer"
    }
}
```

### Utiliser le token

```http
Authorization: Bearer 1|abc123xyz...
```

---

## Endpoints

### Résidences

#### Liste des résidences

```http
GET /api/residences
```

**Query Parameters**:
| Param | Type | Description |
|-------|------|-------------|
| `q` | string | Recherche textuelle |
| `commune` | string | Filtrer par commune |
| `min_price` | integer | Prix minimum (FCFA) |
| `max_price` | integer | Prix maximum (FCFA) |
| `type` | string | Type: `studio`, `apartment`, `villa`, `house` |
| `bedrooms` | integer | Nombre minimum de chambres |
| `amenities[]` | array | Équipements requis |
| `lat` | float | Latitude pour recherche géo |
| `lng` | float | Longitude pour recherche géo |
| `radius` | integer | Rayon en km (défaut: 5) |
| `sort` | string | `price_asc`, `price_desc`, `recent`, `distance` |
| `page` | integer | Page (pagination) |
| `per_page` | integer | Résultats par page (max: 50) |

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "residences": [
            {
                "id": 1,
                "title": "Studio moderne Cocody",
                "slug": "studio-moderne-cocody",
                "description": "...",
                "type": "studio",
                "price_per_month": 150000,
                "price_per_day": 15000,
                "commune": "Cocody",
                "quartier": "Riviera 2",
                "latitude": 5.3478,
                "longitude": -3.9892,
                "bedrooms": 1,
                "bathrooms": 1,
                "surface": 35,
                "photos": [
                    {
                        "id": 1,
                        "url": "https://rezi.ci/storage/residences/1/photo1.jpg",
                        "is_primary": true
                    }
                ],
                "amenities": ["wifi", "climatisation", "parking"],
                "average_rating": 4.5,
                "reviews_count": 12,
                "is_instant_booking": true,
                "is_verified": true
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 150,
            "total_pages": 8
        }
    }
}
```

#### Détails d'une résidence

```http
GET /api/residences/{id}
```

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "residence": {
            "id": 1,
            "title": "Studio moderne Cocody",
            "description": "Description complète...",
            "type": "studio",
            "price_per_month": 150000,
            "price_per_day": 15000,
            "commune": "Cocody",
            "quartier": "Riviera 2",
            "address": "Rue des Jardins",
            "latitude": 5.3478,
            "longitude": -3.9892,
            "bedrooms": 1,
            "bathrooms": 1,
            "surface": 35,
            "floor": 2,
            "photos": [...],
            "photos_360": [...],
            "amenities": [...],
            "house_rules": ["Non-fumeur", "Pas d'animaux"],
            "check_in_time": "14:00",
            "check_out_time": "11:00",
            "cancellation_policy": "flexible",
            "owner": {
                "id": 5,
                "name": "Marie K.",
                "avatar": "...",
                "is_verified": true,
                "response_rate": 98,
                "response_time": "1 heure"
            },
            "reviews": [...],
            "average_rating": 4.5,
            "reviews_count": 12,
            "availability": {
                "is_available": true,
                "next_available_date": "2025-02-01"
            },
            "points_of_interest": [
                {
                    "name": "Centre Commercial",
                    "type": "shopping",
                    "distance": 500
                }
            ]
        }
    }
}
```

#### Vérifier disponibilité

```http
POST /api/residences/{id}/availability
Content-Type: application/json

{
    "check_in": "2025-02-01",
    "check_out": "2025-02-05"
}
```

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "is_available": true,
        "price_breakdown": {
            "nights": 4,
            "price_per_night": 15000,
            "subtotal": 60000,
            "cleaning_fee": 5000,
            "service_fee": 6500,
            "total": 71500
        }
    }
}
```

---

### Réservations

#### Créer une réservation

```http
POST /api/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
    "residence_id": 1,
    "check_in": "2025-02-01",
    "check_out": "2025-02-05",
    "guests": 2,
    "message": "Message optionnel au propriétaire"
}
```

**Response** `201 Created`:
```json
{
    "success": true,
    "data": {
        "booking": {
            "id": 123,
            "reference": "REZ-2025-00123",
            "status": "pending",
            "residence_id": 1,
            "check_in": "2025-02-01",
            "check_out": "2025-02-05",
            "guests": 2,
            "total_price": 71500,
            "payment_status": "pending",
            "created_at": "2025-01-15T10:30:00Z"
        },
        "payment_url": "https://rezi.ci/payment/123"
    }
}
```

#### Mes réservations

```http
GET /api/bookings
Authorization: Bearer {token}
```

**Query Parameters**:
| Param | Type | Description |
|-------|------|-------------|
| `status` | string | `pending`, `confirmed`, `completed`, `cancelled` |
| `upcoming` | boolean | Seulement les futures réservations |

---

### Favoris

#### Liste des favoris

```http
GET /api/favorites
Authorization: Bearer {token}
```

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "favorites": [
            {
                "id": 1,
                "title": "Studio Cocody",
                "image": "...",
                "price": 150000,
                "location": "Cocody, Riviera 2",
                "rating": 4.5,
                "added_at": "2025-01-10T15:00:00Z"
            }
        ]
    }
}
```

#### Toggle favori

```http
POST /api/favorites/{residence_id}/toggle
Authorization: Bearer {token}
```

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "isFavorite": true,
        "message": "Ajouté aux favoris"
    }
}
```

#### Prévisualiser résidences (pour visiteurs)

```http
POST /api/residences/preview
Content-Type: application/json

{
    "ids": [1, 2, 3, 5]
}
```

---

### Messagerie

#### Conversations

```http
GET /api/conversations
Authorization: Bearer {token}
```

#### Envoyer un message

```http
POST /api/conversations/{id}/messages
Authorization: Bearer {token}
Content-Type: application/json

{
    "content": "Bonjour, la résidence est-elle disponible ?",
    "attachments": []
}
```

---

### Paiements (Jeko Mobile Money)

#### Initier un paiement

```http
POST /api/payments/jeko/initiate
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_id": 123,
    "phone": "+22507XXXXXXXX",
    "operator": "orange"
}
```

**Operators**: `orange`, `mtn`, `moov`, `wave`

**Response** `200 OK`:
```json
{
    "success": true,
    "data": {
        "transaction_id": "JK-123456",
        "status": "pending",
        "otp_required": true,
        "message": "Entrez le code OTP reçu par SMS"
    }
}
```

#### Vérifier OTP

```http
POST /api/payments/jeko/verify-otp
Authorization: Bearer {token}
Content-Type: application/json

{
    "transaction_id": "JK-123456",
    "otp": "123456"
}
```

#### Statut paiement

```http
GET /api/payments/{transaction_id}/status
Authorization: Bearer {token}
```

---

### Notifications Push

#### S'abonner aux notifications

```http
POST /api/push/subscribe
Authorization: Bearer {token}
Content-Type: application/json

{
    "endpoint": "https://fcm.googleapis.com/...",
    "keys": {
        "p256dh": "...",
        "auth": "..."
    }
}
```

#### Préférences

```http
PUT /api/push/preferences
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_updates": true,
    "messages": true,
    "promotions": false,
    "quiet_hours": {
        "enabled": true,
        "start": "22:00",
        "end": "07:00"
    }
}
```

---

## Webhooks

### Configuration

Les webhooks permettent de recevoir des notifications en temps réel.

**URL**: Configurez dans votre dashboard propriétaire

**Events disponibles**:
- `booking.created`
- `booking.confirmed`
- `booking.cancelled`
- `payment.completed`
- `payment.failed`
- `message.received`

### Payload exemple

```json
{
    "event": "booking.created",
    "timestamp": "2025-01-15T10:30:00Z",
    "data": {
        "booking_id": 123,
        "reference": "REZ-2025-00123",
        "residence_id": 1,
        "guest_name": "John Doe",
        "check_in": "2025-02-01",
        "check_out": "2025-02-05",
        "total_price": 71500
    },
    "signature": "sha256=..."
}
```

### Vérification signature

```php
$signature = hash_hmac('sha256', $payload, $webhookSecret);
$valid = hash_equals($signature, $receivedSignature);
```

---

## Codes d'erreur

| Code | Description |
|------|-------------|
| 400 | Bad Request - Paramètres invalides |
| 401 | Unauthorized - Token manquant ou invalide |
| 403 | Forbidden - Action non autorisée |
| 404 | Not Found - Ressource inexistante |
| 422 | Unprocessable Entity - Validation échouée |
| 429 | Too Many Requests - Rate limit dépassé |
| 500 | Internal Server Error |

### Format erreur

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Les données fournies sont invalides",
        "details": {
            "email": ["Le champ email est obligatoire"]
        }
    }
}
```

---

## Rate Limiting

- **Authentifié**: 60 requêtes/minute
- **Non authentifié**: 30 requêtes/minute

Headers de réponse:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1642234567
```

---

## SDKs

### JavaScript

```javascript
import { ReziClient } from '@rezi/sdk';

const rezi = new ReziClient({
    apiKey: 'your-api-key'
});

// Rechercher des résidences
const residences = await rezi.residences.search({
    commune: 'Cocody',
    minPrice: 100000
});

// Créer une réservation
const booking = await rezi.bookings.create({
    residenceId: 1,
    checkIn: '2025-02-01',
    checkOut: '2025-02-05'
});
```

### PHP

```php
use Rezi\Client;

$rezi = new Client('your-api-key');

// Rechercher
$residences = $rezi->residences()->search([
    'commune' => 'Cocody'
]);

// Réserver
$booking = $rezi->bookings()->create([
    'residence_id' => 1,
    'check_in' => '2025-02-01',
    'check_out' => '2025-02-05'
]);
```

---

## Support

- **Email**: api@rezi.ci
- **Documentation**: https://developers.rezi.ci
- **Status**: https://status.rezi.ci
