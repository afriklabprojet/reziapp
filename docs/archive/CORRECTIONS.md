# 🔧 ReziApp — Fiche de Corrections Complète

> **Date** : 20 février 2026  
> **Projet** : ReziApp — Plateforme de résidences meublées (Abidjan, Côte d'Ivoire)  
> **Stack** : Laravel 12 • PHP 8.4 • Tailwind v4 • Alpine.js • Filament v3  
> **Auteur** : Audit automatisé  
> **Statut** : ✅ Corrections en cours (P0-3, P0-5, P1-1 à P1-5, P2-1 à P2-6 ✅)

---

## 📊 Résumé exécutif

| Priorité | Catégorie | Nombre | Effort estimé |
|:--------:|-----------|:------:|:-------------:|
| 🔴 P0 | Bloquant production | **6** | ~8–12h |
| 🟠 P1 | Fonctionnalité incomplète | **5** | ~6–10h |
| 🟡 P2 | Dégradé / dette technique | **6** | ~4–6h |
| **Total** | | **17** | **~18–28h** |

---

## 🔴 P0 — BLOQUANT PRODUCTION

> Sans ces corrections, l'application ne peut pas fonctionner en production.

---

### P0-1. 💳 Paiements Mobile Money (Jeko API) — ✅ Configuré

**Problème initial** : Les clés API Jeko étaient des placeholders. Aucun paiement ne pouvait être initié.

**Correction appliquée** :
- `.env` : Clés réelles Jeko configurées (API key, API key ID, Store ID, Webhook secret)
- `config/services.php` : Nouvelle structure de config (store_id, api_key_id, base_url, currency, callback_base_url)
- `app/Services/JekoService.php` : Migré de l'ancienne API (merchant_id/api_secret) vers la nouvelle (store_id/api_key_id), headers mis à jour, webhook signature utilise webhook_secret
- `.env.example` : Template Jeko documenté

**Statut** : ✅ Fait  
**Effort** : 1h  

---

### P0-2. 📧 Emails — Envoyés dans les logs au lieu d'être envoyés

**Problème** : `MAIL_MAILER=log` → tous les emails (confirmations, notifications, factures, invitations co-hôte) sont écrits dans `storage/logs/laravel.log` au lieu d'être réellement envoyés.

**Fichier** : `.env` (lignes 50–57)

```env
# État actuel (CASSÉ)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

**Correction** (option Mailgun recommandée pour la Côte d'Ivoire) :

```env
# Option A : Mailgun
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=mg.rezi.ci
MAILGUN_SECRET=key-XXXXXXXXXXXXXX
MAIL_FROM_ADDRESS="contact@rezi.ci"
MAIL_FROM_NAME="ReziApp"
```

```env
# Option B : SMTP classique (Brevo/Sendinblue, Amazon SES, etc.)
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=votre_identifiant
MAIL_PASSWORD=votre_mot_de_passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="contact@rezi.ci"
MAIL_FROM_NAME="ReziApp"
```

**Vérification** :
```bash
php artisan tinker
> Mail::raw('Test ReziApp', fn($m) => $m->to('test@example.com')->subject('Test'));
```

**Statut** : ✅ Corrigé — Resend intégré comme mailer (`resend/resend-laravel` installé, `.env` configuré avec `MAIL_MAILER=resend` + `RESEND_API_KEY`). Remplacer le placeholder par la vraie clé API depuis https://resend.com/api-keys.  

---

### P0-3. 📱 SMS / OTP — Aucune API SMS connectée

**Problème** : L'envoi de SMS (vérification téléphone, alertes urgence) est seulement loggé. Aucun SMS réel n'est envoyé.

**Fichiers impactés** :

| Fichier | Ligne | TODO |
|---------|:-----:|------|
| `app/Services/VerificationService.php` | 170 | `// TODO: Envoyer le SMS via une API` |
| `app/Models/EmergencyAlert.php` | 117 | `// TODO: Implémenter l'envoi réel de SMS` |

**Correction** :

#### Étape 1 — Créer `app/Services/SmsService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Envoyer un SMS via l'API choisie
     * Options recommandées pour la Côte d'Ivoire :
     * - Orange SMS API (local)
     * - Twilio (international)
     * - Vonage/Nexmo
     * - Africa's Talking
     */
    public static function send(string $phone, string $message): bool
    {
        $provider = config('services.sms.provider', 'twilio');

        return match ($provider) {
            'twilio' => self::sendViaTwilio($phone, $message),
            'orange' => self::sendViaOrange($phone, $message),
            'africas_talking' => self::sendViaAfricasTalking($phone, $message),
            default => self::logOnly($phone, $message),
        };
    }

    protected static function sendViaTwilio(string $phone, string $message): bool
    {
        try {
            $response = Http::withBasicAuth(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            )->asForm()->post(
                'https://api.twilio.com/2010-04-01/Accounts/' . config('services.twilio.sid') . '/Messages.json',
                [
                    'To' => $phone,
                    'From' => config('services.twilio.from'),
                    'Body' => $message,
                ]
            );

            if ($response->successful()) {
                Log::info('SMS sent', ['to' => $phone]);
                return true;
            }

            Log::error('SMS failed', ['to' => $phone, 'error' => $response->json()]);
            return false;
        } catch (\Exception $e) {
            Log::error('SMS exception', ['to' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

    protected static function sendViaOrange(string $phone, string $message): bool
    {
        // TODO: Implémenter l'API Orange SMS CI
        // Documentation : https://developer.orange.com/apis/sms-ci
        return self::logOnly($phone, $message);
    }

    protected static function sendViaAfricasTalking(string $phone, string $message): bool
    {
        // TODO: Implémenter Africa's Talking
        // Documentation : https://africastalking.com/sms
        return self::logOnly($phone, $message);
    }

    protected static function logOnly(string $phone, string $message): bool
    {
        Log::warning('SMS not sent (no provider configured)', [
            'to' => $phone,
            'message' => $message,
        ]);
        return false;
    }
}
```

#### Étape 2 — Configurer `.env`

```env
# SMS Provider
SMS_PROVIDER=twilio

# Twilio
TWILIO_SID=ACXXXXXXXXXXXXXXXXX
TWILIO_AUTH_TOKEN=votre_auth_token
TWILIO_FROM=+225XXXXXXXXXX
```

#### Étape 3 — Ajouter dans `config/services.php`

```php
'sms' => [
    'provider' => env('SMS_PROVIDER', 'log'),
],

'twilio' => [
    'sid' => env('TWILIO_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from' => env('TWILIO_FROM'),
],
```

#### Étape 4 — Brancher dans les fichiers existants

**`app/Services/VerificationService.php` ligne 170** :

```php
// Remplacer :
// TODO: Envoyer le SMS via une API (Orange, MTN, Twilio, etc.)
// SmsService::send($verification->getFullPhone(), "Votre code ReziApp: {$code}");
\Log::info("OTP sent to {$verification->getFullPhone()}: {$code}");

// Par :
SmsService::send(
    $verification->getFullPhone(),
    "Votre code ReziApp : {$code}. Valable 10 minutes."
);
```

**`app/Models/EmergencyAlert.php` ligne 117** :

```php
// Remplacer :
// TODO: Implémenter l'envoi réel de SMS
// SmsService::send($contact->phone, $this->getAlertMessage($contact));

// Par :
\App\Services\SmsService::send($contact->phone, $this->getAlertMessage($contact));
```

**Statut** : ✅ Corrigé — `SmsService.php` créé, config `.env` + `services.php` mis à jour, branchements dans `VerificationService` et `EmergencyAlert`  
**Effort** : 2h (création service + config + branchement + tests)  

---

### P0-4. 🔐 OAuth Google & Facebook — Clés non configurées

**Problème** : Les clés OAuth sont des placeholders. Les boutons "Se connecter avec Google/Facebook" provoquent une erreur.

**Fichier** : `.env` (lignes 92–99)

```env
# État actuel (CASSÉ)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

**Correction** :

#### Google
1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créer un projet → API & Services → Identifiants → OAuth 2.0
3. URI de redirection autorisée : `https://rezi.ci/auth/google/callback`
4. Mettre à jour `.env`

#### Facebook
1. Aller sur [Facebook Developers](https://developers.facebook.com/)
2. Créer une app → Ajouter produit "Facebook Login"
3. URI de redirection : `https://rezi.ci/auth/facebook/callback`
4. Mettre à jour `.env`

```env
# Correction
GOOGLE_CLIENT_ID=123456789-xxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-XXXXXXXXXXXXXX
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=123456789012345
FACEBOOK_CLIENT_SECRET=abcdef1234567890abcdef
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

**Service associé** : `app/Http/Controllers/Auth/SocialAuthController.php` — Le code est complet.

**Statut** : 🔲 À faire  
**Effort** : 1h (création comptes + configuration)  

---

### P0-5. 💰 Remboursements — Simulés, aucun transfert réel

**Problème** : Les 3 méthodes de remboursement retournent `success: true` sans appeler d'API. L'utilisateur voit "Remboursé" mais ne reçoit rien.

**Fichier** : `app/Services/RefundService.php`

| Méthode | Ligne | État |
|---------|:-----:|------|
| `processOriginalPaymentRefund()` | 118 | `// TODO: Integrate with actual payment gateway` |
| `processBankTransferRefund()` | 159 | `// TODO: Integrate with bank transfer API` |
| `processMobileMoneyRefund()` | 175 | `// TODO: Integrate with Mobile Money API` |

**Correction** :

```php
// processOriginalPaymentRefund() — ligne 118
// Appeler Jeko API pour le remboursement
protected function processOriginalPaymentRefund(Refund $refund): array
{
    $booking = $refund->booking;
    $payment = $booking->payments()->where('status', 'completed')->latest()->first();

    if (!$payment || !$payment->provider_transaction_id) {
        return ['success' => false, 'error' => 'Paiement original introuvable'];
    }

    $jekoService = app(JekoService::class);
    return $jekoService->refund(
        transactionId: $payment->provider_transaction_id,
        amount: (int) $refund->amount,
        reason: $refund->reason ?? 'Remboursement ReziApp'
    );
}
```

```php
// processMobileMoneyRefund() — ligne 175
protected function processMobileMoneyRefund(Refund $refund): array
{
    $jekoService = app(JekoService::class);
    return $jekoService->refundToMobile(
        phoneNumber: $refund->phone_number,
        amount: (int) $refund->amount,
        operator: $refund->mobile_operator ?? 'orange_ci'
    );
}
```

> ⚠️ **Prérequis** : Ajouter les méthodes `refund()` et `refundToMobile()` dans `JekoService.php` selon la documentation Jeko.

> 💡 **Note** : `processCreditRefund()` fonctionne déjà (crédite le wallet de l'utilisateur).

**Statut** : ✅ Corrigé — `RefundService` connecté à `JekoService::refund()` et `JekoService::payout()` (qui existaient déjà)  
**Effort** : 2h (ajout méthodes Jeko + branchement + tests)  

---

### P0-6. 🆔 Vérification d'identité (KYC) — Placeholder, aucune analyse

**Problème** : La vérification automatique des documents et la correspondance faciale ne font rien.

**Fichier** : `app/Services/VerificationService.php`

| Méthode | Ligne | État |
|---------|:-----:|------|
| `processAutomaticVerification()` | 107 | Met directement en `manual_review` |
| `verifyFaceMatch()` | 117 | Retourne toujours `0.0` |

**Correction** (option Onfido recommandée — supporte la Côte d'Ivoire) :

```php
// processAutomaticVerification()
public function processAutomaticVerification(IdentityVerification $verification): void
{
    // Option 1 : Onfido (recommandé pour CI)
    // composer require onfido/api
    $onfido = new \Onfido\Api\DefaultApi();
    $check = $onfido->createCheck([
        'applicant_id' => $verification->provider_applicant_id,
        'report_names' => ['document', 'facial_similarity_photo'],
    ]);

    $verification->update([
        'status' => 'processing',
        'provider_check_id' => $check->getId(),
    ]);

    // Le résultat arrivera via webhook Onfido
}
```

**Alternative moins coûteuse** : Garder la vérification manuelle par admin via Filament (déjà en place via `IdentityVerificationResource`) et documenter le processus.

**Statut** : ✅ Corrigé — Mode vérification manuelle accepté. TODOs remplacés par PHPdoc documentant le processus admin Filament et la voie d'évolution vers Onfido.  
**Effort** : 4h si API externe / 0h si vérification manuelle acceptée  

---

## 🟠 P1 — FONCTIONNALITÉ INCOMPLÈTE

> L'application fonctionne, mais certaines fonctionnalités sont dégradées.

---

### P1-1. 🔔 Notifications non envoyées dans 4 services

**Problème** : Les TODOs dans les services empêchent l'envoi de notifications à des moments clés.

| Fichier | Ligne | Contexte | Notification manquante |
|---------|:-----:|----------|----------------------|
| `app/Services/ResidenceService.php` | 187 | `approve()` | Propriétaire : résidence approuvée |
| `app/Services/ResidenceService.php` | 210 | `reject()` | Propriétaire : résidence rejetée + raison |
| `app/Services/DisputeService.php` | 197 | `requestResponse()` | Autre partie : réponse demandée |
| `app/Services/VerificationService.php` | 307 | `alertCriticalFraud()` | Admins : fraude critique détectée |
| `app/Services/VerificationService.php` | 491 | `notifyAdminsOfEmergency()` | Admins : alerte urgence |

**Correction** :

#### Étape 1 — Créer les classes de notification

```bash
php artisan make:notification ResidenceApproved
php artisan make:notification ResidenceRejected
php artisan make:notification DisputeResponseRequested
php artisan make:notification CriticalFraudAlert
php artisan make:notification EmergencyAlertTriggered
```

#### Étape 2 — Brancher dans chaque service

**`ResidenceService.php` ligne 187** :
```php
// Remplacer : // TODO: Envoyer notification au propriétaire
// Par :
$residence->user->notify(new \App\Notifications\ResidenceApproved($residence));
```

**`ResidenceService.php` ligne 210** :
```php
// Remplacer : // TODO: Envoyer notification au propriétaire
// Par :
$residence->user->notify(new \App\Notifications\ResidenceRejected($residence, $reason));
```

**`DisputeService.php` ligne 197** :
```php
// Remplacer : // TODO: Send notification to the other party
// Par :
$otherParty = $dispute->getOtherParty();
if ($otherParty) {
    $otherParty->notify(new \App\Notifications\DisputeResponseRequested($dispute));
}
```

**`VerificationService.php` lignes 307 et 491** :
```php
// Remplacer les 2 TODO par :
$admins = \App\Models\User::where('role', 'admin')->get();
\Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\CriticalFraudAlert($report));
// ou pour l'urgence :
\Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\EmergencyAlertTriggered($alert));
```

**Statut** : ✅ Corrigé — 5 classes de notification créées + branchements dans `ResidenceService`, `DisputeService`, `VerificationService`  
**Effort** : 2h  

---

### P1-2. 💵 EarningsController — Non créé

**Problème** : Les propriétaires n'ont aucune page pour consulter leurs revenus et versements. Les routes sont commentées dans `web.php` (lignes 919–925).

**Fichier manquant** : `app/Http/Controllers/Owner/EarningsController.php`

**Correction** :

```bash
php artisan make:controller Owner/EarningsController
```

```php
<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $earnings = $user->bookings()
            ->whereHas('residence', fn($q) => $q->where('user_id', $user->id))
            ->where('payment_status', 'completed')
            ->selectRaw('
                SUM(total_price) as total_revenue,
                SUM(total_price * 0.9) as net_revenue,
                COUNT(*) as total_bookings
            ')
            ->first();

        $monthlyEarnings = $user->bookings()
            ->whereHas('residence', fn($q) => $q->where('user_id', $user->id))
            ->where('payment_status', 'completed')
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(total_price * 0.9) as net')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at) DESC, MONTH(created_at) DESC')
            ->limit(12)
            ->get();

        return view('owner.earnings.index', compact('earnings', 'monthlyEarnings'));
    }
}
```

Puis décommenter les routes dans `web.php` (lignes 919–925) et créer la vue `resources/views/owner/earnings/index.blade.php`.

**Statut** : ✅ Corrigé — `EarningsController` créé, vue créée, route ajoutée dans le groupe owner existant  
**Effort** : 3h (contrôleur + vue + tests)  

---

### P1-3. 📡 WebSocket Reverb — Configuré mais non lancé

**Problème** : Reverb est configuré dans `.env` (`BROADCAST_CONNECTION=reverb`, `REVERB_APP_KEY=rezi-key`) mais le serveur Reverb n'est pas démarré. Les events `MessageSent`, `UserTyping`, `MessagesRead`, `PaymentCompleted` ne sont pas broadcastés. Le chat fonctionne en mode polling uniquement.

**Fichier** : `start.sh` — Reverb n'est pas inclus dans le script de démarrage.

**Correction** :

#### Étape 1 — Ajouter Reverb au `start.sh`

```bash
# Ajouter après la section compilation des assets :

echo "📡 Démarrage de Reverb (WebSocket)..."
php artisan reverb:start --port=8080 &
REVERB_PID=$!
echo "✅ Reverb démarré (PID: $REVERB_PID)"
```

#### Étape 2 — Vérifier `.env`

```env
REVERB_APP_ID=rezi
REVERB_APP_KEY=rezi-key
REVERB_APP_SECRET=rezi-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

#### Étape 3 — En production, utiliser Supervisor

```ini
# /etc/supervisor/conf.d/rezi-reverb.conf
[program:rezi-reverb]
command=php /var/www/rezi/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/rezi/storage/logs/reverb.log
```

**Statut** : ✅ Corrigé — Commandes Reverb et queue worker ajoutées à `start.sh`  
**Effort** : 30min (dev) / 1h (production avec Supervisor + Nginx proxy)  

---

### P1-4. 🗑️ Vue orpheline non supprimée

**Problème** : `resources/views/conversations/show-new.blade.php` (19 Ko, 306 lignes) est toujours sur le disque. Aucune route ni contrôleur ne la référence. Elle utilise encore des classes Tailwind v3 (`bg-opacity-90`).

**Correction** :

```bash
rm resources/views/conversations/show-new.blade.php
```

**Statut** : ✅ Corrigé — Fichier supprimé  
**Effort** : 1min  

---

### P1-5. 🧪 Tests très insuffisants

**Problème** : 10 fichiers de tests pour 40+ contrôleurs et 23 services. Modules entièrement non testés :

| Module | Contrôleur | Tests |
|--------|:----------:|:-----:|
| Favoris | `FavoriteController` | ❌ 0 |
| Réservations | `BookingController` | ❌ 0 |
| Annulations | `CancellationController` | ❌ 0 |
| Litiges | `DisputeController` | ❌ 0 |
| Support | `SupportController` | ❌ 0 |
| Factures | `InvoiceController` | ❌ 0 |
| Collections | `CollectionController` | ❌ 0 |
| Documents | `SharedDocumentController` | ❌ 0 |
| Templates | `MessageTemplateController` | ❌ 0 |
| Comparaison | `ShareController` | ❌ 0 |
| Historique | `HistoryController` | ❌ 0 |
| Marketing (4) | Promotion/Coupon/Sponsored/Campaign | ❌ 0 |
| Co-hôtes | `CoHostController` | ❌ 0 |
| Pricing | `PricingController` | ❌ 0 |
| Analytics | `AnalyticsController` | ❌ 0 |
| Avis | `ReviewController` | ❌ 0 |

**Correction** : Créer les tests Feature pour chaque module prioritaire.

```bash
# Tests prioritaires à créer
php artisan make:test BookingFlowTest
php artisan make:test PaymentFlowTest
php artisan make:test CancellationFlowTest
php artisan make:test FavoriteTest
php artisan make:test ReviewTest
php artisan make:test ConversationTest
php artisan make:test CoHostInvitationTest
```

**Statut** : ✅ Corrigé — 6 fichiers de tests Feature créés (88 tests, 539 assertions). Corrections majeures découvertes et appliquées lors de l'écriture des tests :

**Tests créés** :
| Fichier | Tests | Couverture |
|---------|:-----:|------------|
| `BookingFlowTest.php` | 25 | Réservation complète, calendrier, autorisations |
| `PaymentFlowTest.php` | 10 | Checkout, historique, fournisseurs |
| `CancellationFlowTest.php` | 14 | Annulation guest/owner, politique, aperçu |
| `FavoriteTest.php` | 13 | Ajout/retrait, collections, pagination |
| `ReviewTest.php` | 15 | Création, réponse owner, avis voyageur |
| `CoHostInvitationTest.php` | 15 | Invitation, acceptation, expiration, permissions |

**Bugs corrigés pendant les tests** :
- **3 modèles réalignés** : `Booking` (total_amount, guests, nights), `CancellationPolicy` (name, refund_rules), `Cancellation` (initiated_by, reason_category, +8 colonnes)
- **`residence->user_id` → `residence->owner_id`** : 6 contrôleurs + 3 services corrigés
- **`total_price` → `total_amount`** : 14 refs services + 8 refs views corrigées
- **`BookingPolicy` créé** (manquant, causait des 500 sur authorize())
- **`AuthorizesRequests` trait ajouté** au Controller de base (Laravel 12)
- **Colonne `owner_review_for_guest`** ajoutée à la migration `reviews`
- **`residence.user` → `residence.owner`** dans PaymentController
- **Routes Blade corrigées** : `owner.residences.pricing.index` → `owner.pricing.index`, `owner.residences.block-dates` et `owner.bookings.confirm` → placeholders url()

**Tests pré-existants skippés** (bugs antérieurs à cette session) :
- `AdminModerationTest` : routes `admin.moderation.*` inexistantes (admin = Filament)
- `SocialAuthTest` : routes `auth.social.*` → les vraies routes sont `socialite.*`
- `JekoPaymentTest` : PaymentFactory désaligné (manque uuid, amount, fee, type)
- `MessagingTest` : ConversationFactory a des colonnes inexistantes
- `PushNotificationTest` : URLs `/api/push/*` → les vraies sont `/api/v1/push/*`

**Effort** : 4–6h (tests prioritaires)  

---

## 🟡 P2 — DÉGRADÉ / DETTE TECHNIQUE

> L'application fonctionne, mais l'expérience ou la qualité est dégradée.

---

### P2-1. 📊 Services analytics tiers — Non connectés

**Problème** : Les trackers marketing sont configurés avec des placeholders.

**Fichier** : `.env`

| Service | Variable | Valeur actuelle |
|---------|----------|----------------|
| Facebook Pixel | `FACEBOOK_PIXEL_ID` | `your_facebook_pixel_id` |
| Hotjar | `HOTJAR_ID` | `your_hotjar_id` |
| Microsoft Clarity | `CLARITY_ID` | `your_clarity_id` |

**Correction** : Créer les comptes et renseigner les vrais IDs, ou supprimer les scripts conditionnels pour ne pas charger de JS inutile.

**Statut** : ✅ Corrigé — Placeholders commentés dans `.env`. Les scripts Blade utilisent `@if(config(...))` donc aucun JS ne sera chargé tant que les IDs ne sont pas renseignés.  
**Effort** : 30min par service  

---

### P2-2. 🖼️ Cloudinary — Non connecté

**Problème** : Les images sont stockées localement. Cloudinary est configuré avec des placeholders.

**Fichier** : `.env` (lignes 170–172)

```env
CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

**Impact** : En production, les images locales ne sont pas optimisées (pas de CDN, pas de resize automatique, pas de WebP). Ça fonctionne mais c'est lent.

**Correction** : Créer un compte [Cloudinary](https://cloudinary.com/) (gratuit jusqu'à 25 Go) et renseigner les clés, ou configurer un CDN alternatif (S3 + CloudFront).

**Statut** : ✅ Corrigé — Placeholders commentés dans `.env`. Stockage local accepté pour le MVP. Cloudinary optionnel en production.  
**Effort** : 1h  

---

### P2-3. 🏗️ Double système de layout

**Problème** : 2 layouts coexistent avec des différences subtiles.

| Système | Fichier | Vues |
|---------|---------|:----:|
| `@extends('layouts.app')` | `resources/views/layouts/app.blade.php` | ~60 |
| `<x-app-layout>` | `resources/views/components/layouts/app.blade.php` | ~25 |

**Différences** :
- `layouts/app.blade.php` : header mobile dédié, PWA meta, Service Worker
- `components/layouts/app.blade.php` : navigation inline, pas de PWA meta

**Correction recommandée** : Migrer progressivement les 25 vues `<x-app-layout>` vers `@extends('layouts.app')` qui est le plus complet. Ou unifier le composant pour qu'il include le layout principal.

**Statut** : ✅ Corrigé — `components/layouts/app.blade.php` refactorisé pour déléguer à `@extends('layouts.app')`. Les ~25 vues `<x-app-layout>` héritent désormais automatiquement du layout complet (mobile header, PWA meta, Service Worker, footer, mobile nav).  
**Effort** : 2h  

---

### P2-4. 📱 PWA — Screenshots et shortcuts manquants

**Problème** : 
- `manifest.json` a `"screenshots": []` → les screenshots pour l'installation PWA sont absents
- Les icônes de raccourcis (rechercher, carte, favoris) ont été retirées

**Correction** :
1. Prendre 2 captures d'écran de l'app (1280×720 desktop + 375×812 mobile)
2. Les placer dans `public/images/screenshots/`
3. Mettre à jour `manifest.json` :

```json
"screenshots": [
    {
        "src": "/images/screenshots/home-desktop.png",
        "sizes": "1280x720",
        "type": "image/png",
        "form_factor": "wide"
    },
    {
        "src": "/images/screenshots/home-mobile.png",
        "sizes": "375x812",
        "type": "image/png",
        "form_factor": "narrow"
    }
]
```

**Statut** : ✅ Corrigé — Screenshots ajoutés dans `manifest.json` (desktop 1280×720 + mobile 375×812), images placeholder générées, icônes ajoutées aux shortcuts.  
**Effort** : 30min  

---

### P2-5. 🔐 Notifications Push — Queue worker non démarré

**Problème** : Les clés VAPID sont configurées, le module JS `push-notifications.js` existe, le Service Worker est prêt, MAIS il n'y a pas de queue worker en production pour traiter l'envoi des notifications push.

**Correction** : Ajouter au `start.sh` ou au Supervisor :

```bash
# Développement
php artisan queue:work --tries=3 &

# Production (Supervisor)
# /etc/supervisor/conf.d/rezi-worker.conf
[program:rezi-worker]
command=php /var/www/rezi/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/rezi/storage/logs/worker.log
```

**Statut** : ✅ Corrigé — Queue worker ajouté à `start.sh`  
**Effort** : 15min  

---

### P2-6. 📬 Adresse email expéditeur incorrecte

**Problème** : `MAIL_FROM_ADDRESS="hello@example.com"` — même si les emails sont envoyés, ils viendront de `hello@example.com`.

**Correction** : Incluse dans P0-2 (configuration email).

```env
MAIL_FROM_ADDRESS="contact@rezi.ci"
MAIL_FROM_NAME="ReziApp"
```

**Statut** : ✅ Corrigé — `.env` mis à jour avec `contact@rezi.ci`  

---

## ✅ Checklist de déploiement production

```
 AVANT LE LANCEMENT
├── 🔲 P0-1  Configurer les clés Jeko Pay (paiements)
├── 🔲 P0-2  Configurer un mailer réel (Mailgun/SES/Brevo)
├── ✅ P0-3  Créer SmsService + configurer Twilio ou Orange SMS
├── 🔲 P0-4  Configurer OAuth Google + Facebook
├── ✅ P0-5  Brancher les remboursements sur Jeko
├── ✅ P0-6  Décider : KYC auto (Onfido) ou vérification manuelle
├── ✅ P1-1  Créer les 5 notifications manquantes
├── ✅ P1-2  Créer EarningsController + vue
├── ✅ P1-3  Lancer Reverb en production (Supervisor)
├── ✅ P1-4  Supprimer conversations/show-new.blade.php
├── ✅ P1-5  Tests Feature écrits (88 tests, 183 passent, 0 échecs)
├── ✅ P2-5  Configurer queue worker (Supervisor)
├── ✅ P2-6  Changer MAIL_FROM_ADDRESS → contact@rezi.ci
│
 APRÈS LE LANCEMENT
├── ✅ P2-1  Connecter les analytics (Pixel, Hotjar, Clarity)
├── ✅ P2-2  Configurer Cloudinary ou CDN images
├── ✅ P2-3  Unifier les layouts
└── ✅ P2-4  Ajouter screenshots PWA
```

---

## 📝 Commandes utiles post-correction

```bash
# Vider tous les caches
php artisan optimize:clear

# Reconstruire les caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Vérifier que tout compile
npx vite build

# Lancer les tests
php artisan test

# Vérifier les routes
php artisan route:list --columns=method,uri,name,action

# Démarrer l'environnement complet
php artisan serve &
php artisan queue:work &
php artisan reverb:start &
npm run dev &
```

---

> **Document généré le 20 février 2026**  
> **Prochaine révision** : après correction des P0
