# ReziApp — Architecture Technique

**Derniere mise a jour** : 2026-06-01
**Environnement** : Production — <https://reziapp.ci>

---

## Stack reelle

| Couche | Technologie |
| ------ | ----------- |
| Backend | Laravel 12, PHP 8.2 |
| Admin | Filament 3.2 |
| Auth | Laravel Sanctum 4.3 + Socialite 5.24 + Google2FA (pragmarx/google2fa 9.0) |
| Paiements | Jeko (Mobile Money XOF) — JekoPaymentService + JekoService |
| Email | Resend (resend/resend-laravel 1.2) |
| Temps reel | Pusher + Laravel Echo + pusher-js |
| Monitoring | Sentry (sentry/sentry-laravel 4.25) |
| PDF | barryvdh/laravel-dompdf 3.1 |
| Base de donnees | MySQL 8.0 |
| Assets | Vite 7 + Tailwind CSS 4 + Alpine.js 3 |
| Cartographie | Leaflet 1.9 (frontend) |
| Tests | PHPUnit 11.5 |

### Dependances absentes (mentionnees dans l'ancienne doc, NON installees)

- `spatie/laravel-permission` — absent, les roles sont geres par un champ `role` sur User
- `intervention/image` — absent, upload via PhotoUploadService (local/Cloudinary)
- `barryvdh/laravel-debugbar` — absent en prod
- `darkaonline/l5-swagger` — absent
- `maatwebsite/excel` — absent
- `spatie/laravel-activitylog` — absent (AdminActivityLog maison)
- AWS S3 — absent, le stockage est local + Cloudinary optionnel

---

## Metriques reelles (2026-06-01)

| Element | Compte |
| ------- | ------ |
| Models (app/Models/) | 131 |
| Services (app/Services/) | 83 |
| Controllers (app/Http/Controllers/) | 103 |
| Migrations (database/migrations/) | 146 |
| Vues Blade (resources/views/) | 331 |
| Jobs (app/Jobs/) | 13 |
| Filament Resources/Pages/Widgets | 315 fichiers |
| Livewire Components | 2 |

---

## Structure des modules

### Auth

- Sanctum : API tokens pour les appels SPA/mobile
- Socialite : OAuth Google/Facebook (`Auth/SocialiteController`)
- Google2FA : double authentification TOTP (`TwoFactorController`)
- KYC : `IdentityVerificationService`, modele `IdentityVerification`, flow manuel + `AutoKycService`
- Middleware : `EnsureIdentityVerified`, `CheckSubscription`, roles via `User::role` (owner/client/admin)

### Paiements

- Passerelle unique : **Jeko** (Mobile Money XOF — Orange Money, MTN, Wave)
- Services : `JekoPaymentService` (requetes API), `JekoService` (logique metier)
- Webhook entrant : `Payment/JekoCallbackController` — verification HMAC sur signature header
- Modeles : `Payment`, `PaymentTransaction`, `Payout`, `Refund`, `OwnerBalance`
- Abonnements proprietaires : `Subscription`, `SubscriptionPlan`, `SubscriptionPayment`
- Assurance reservation : `InsurancePlan`, `InsuranceSubscription`, `BookingInsurance`
- Depot de garantie : `SecurityDeposit`
- Montants en XOF entiers (pas de multiplicateur x100)

### Recherche geo

- Moteur actuel : MySQL Haversine avec bounding box pre-filter (double WHERE sur lat/lng)
- Implementation : `GeoSearchController` + scope `withinRadius` sur `Residence`
- Migration en cours : vers `ST_Distance_Sphere` + `SPATIAL INDEX` (non encore deploye)
- Cache : `ResidenceCacheService` — TTL configurable par type de requete

### Tarification

- `PricingService` : prix de base (`price_per_day/week/month` de Residence) + `SpecialPrice` (tarifs journaliers ponctuels)
- `DynamicPricingService` + `YieldManagementService` : ajustements automatiques
- `SeasonalPrice` (table `seasonal_prices`) : prix absolus FCFA, gere via `PricingController` proprietaire
- `SeasonalPricing` (table `seasonal_pricing`) : multiplicateurs + templates CI (Noel, Paques, ete), utilise par `AvailabilityCalendar` et l'API dispo
- `LongStayDiscount` : reduction sejours longs (>= N nuits)
- `PromoCode` / `Coupon` : codes de reduction

### Queue

- Driver : Laravel Queue (database ou Redis selon env)
- 13 Jobs : envoi d'emails, notifications, nettoyage, renouvellement abonnements, etc.
- Commandes planifiees : `SendRentReminders`, `Console/Commands/` (scheduler Laravel)
- `php artisan queue:restart` declenche a chaque deploy

### Admin (Filament 3)

- Panel Filament : 315 fichiers (Resources, Pages, Widgets)
- Acces : role `admin` uniquement, protege par policies Filament
- Pages cles : `StatisticsPage`, `PlatformSettings`
- Pas de spatie/laravel-permission : autorisation via `canAccess()` sur chaque Resource

### Notifications et messagerie

- Email : Resend (driver `resend`)
- SMS : `SmsService`
- WhatsApp : `WhatsAppService` + webhook `WhatsAppWebhookController` + `AutoReply`
- Push : `PushSubscription` (Web Push), `NotificationService`
- Sequences automatiques : `MessageSequence`, `MessageSequenceStep`, `MessageSequenceService`

### Calendrier et disponibilite

- `AvailabilityCalendar` : grille de prix et dispo par jour
- `BlockedDate` / `IcalBlockedDate` : dates bloquees proprietaire
- `IcalFeed` / `IcalService` : synchronisation iCal (Airbnb, Booking)
- `ChannelListing` / `ChannelManagerService` : gestion multi-canal

---

## Organisation des dossiers cles

```text
app/
  Console/Commands/     # Commandes planifiees (cron)
  Filament/             # Admin panel (Resources, Pages, Widgets)
  Http/
    Controllers/
      Api/              # Endpoints JSON (disponibilite, geo, webhooks)
      Auth/             # Login, register, socialite, 2FA
      Owner/            # Dashboard proprietaire
      Payment/          # Callbacks paiement
      Webhook/          # Webhooks entrants
  Jobs/                 # 13 jobs asynchrones
  Livewire/             # 2 composants (AvailabilityManager, OwnerCalendar)
  Models/               # 131 modeles Eloquent
  Services/             # 83 services metier
```

---

## Deploiement

- Serveur : Hetzner VPS, deploy via rsync + SSH
- Trigger : `workflow_dispatch` manuel uniquement (approbation requise via GitHub Environment `production`)
- Backup DB avant migration : `/var/backups/rezi/` (10 derniers conserves)
- Maintenance : `php artisan down/up` encadre le deploy
- Health check : HTTP 200/302 sur <https://reziapp.ci> post-deploy
- CI : tests PHPUnit + build Vite obligatoires avant approbation
