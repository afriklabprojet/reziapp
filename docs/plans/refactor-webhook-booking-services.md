# Plan de refactorisation : JekoWebhookController & BookingService

**Date** : 5 juin 2026  
**Objectif** : Éliminer la duplication dans JekoWebhookController et décomposer BookingService en services à responsabilité unique, sans casser les contrôleurs ni les tests existants.

---

## 1. Résumé exécutif

### Problèmes identifiés
1. **JekoWebhookController** : Dispatch par préfixe (`if/elseif`) + squelettes de handlers dupliqués → difficulté d'ajout de nouveaux types de paiement
2. **BookingService** : ~800 lignes, 11 méthodes publiques mélangeant création, état, statistiques, notifications → violation du principe de responsabilité unique

### Solution proposée
1. **Registry/Strategy Pattern** pour les webhooks Jeko avec handlers découplés
2. **Décomposition de BookingService** en 4 services spécialisés avec façade de compatibilité

### Impact
- ✅ **Aucune régression** : Les contrôleurs existants continuent à fonctionner
- ✅ **Tests préservés** : Les tests BookingServiceTest restent valides
- ✅ **Migration progressive** : Nouveau code utilise les nouveaux services, ancien code reste fonctionnel
- ✅ **Extensibilité** : Ajout de nouveaux types de webhooks en 1 fichier, pas 3 modifications

---

## 2. Architecture : JekoWebhookController → Registry Pattern

### 2.1 Structure cible

```
app/Services/Webhook/
├── Handlers/
│   ├── JekoWebhookHandlerInterface.php    # Interface commune
│   ├── BookingPaymentHandler.php          # REZI-BK-*
│   ├── SponsoredListingHandler.php        # REZI-SP-*
│   ├── SubscriptionPaymentHandler.php     # REZI-SUB-*
│   ├── InsurancePaymentHandler.php        # REZI-INS-*
│   ├── GenericPaymentHandler.php          # Fallback
│   └── TransferHandler.php                # transfer.completed / failed
└── JekoWebhookRegistry.php                # Registre central
```

### 2.2 Contrat d'interface

```php
<?php

namespace App\Services\Webhook\Handlers;

interface JekoWebhookHandlerInterface
{
    /**
     * Détermine si ce handler peut traiter l'événement donné.
     */
    public function canHandle(string $event, array $data): bool;

    /**
     * Traite l'événement webhook.
     * @throws \Exception si le traitement échoue
     */
    public function handle(string $event, array $data): void;

    /**
     * Priorité du handler (plus petit = plus prioritaire).
     * Permet de contrôler l'ordre de matching.
     */
    public function priority(): int;
}
```

### 2.3 Exemple de handler : BookingPaymentHandler

```php
<?php

namespace App\Services\Webhook\Handlers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingPaymentHandler implements JekoWebhookHandlerInterface
{
    public function __construct(
        protected PaymentService $paymentService,
    ) {
    }

    public function canHandle(string $event, array $data): bool
    {
        if ($event !== 'transaction.completed') {
            return false;
        }

        $reference = $data['transactionDetails']['reference'] ?? null;
        
        return $reference && str_starts_with($reference, 'REZI-BK-');
    }

    public function handle(string $event, array $data): void
    {
        $reference = $data['transactionDetails']['reference'];
        $transactionId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $paymentMethod = $data['paymentMethod'] ?? null;
        $executedAt = $data['executedAt'] ?? null;

        $payment = Payment::where('reference', $reference)
            ->where('type', Payment::TYPE_BOOKING)
            ->first();

        if (!$payment) {
            Log::warning('Jeko webhook: No booking payment found', [
                'reference' => $reference,
            ]);
            return;
        }

        if ($payment->isCompleted()) {
            Log::info('Jeko webhook: Booking payment already completed', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        if ($status === 'success') {
            DB::transaction(function () use ($payment, $transactionId, $paymentMethod, $executedAt) {
                $payment->markAsCompleted([
                    'jeko_transaction_id' => $transactionId,
                    'payment_method' => $paymentMethod,
                    'executed_at' => $executedAt,
                ]);

                $this->paymentService->onPaymentSuccess($payment);
            });

            Log::info('Jeko webhook: Booking payment confirmed', [
                'payment_id' => $payment->id,
                'booking_id' => $payment->booking_id,
            ]);
        } else {
            $payment->markAsFailed('Paiement échoué via Jeko');
        }
    }

    public function priority(): int
    {
        return 10; // Standard priority
    }
}
```

### 2.4 Registre central : JekoWebhookRegistry

```php
<?php

namespace App\Services\Webhook;

use App\Services\Webhook\Handlers\JekoWebhookHandlerInterface;
use Illuminate\Support\Facades\Log;

class JekoWebhookRegistry
{
    /** @var JekoWebhookHandlerInterface[] */
    protected array $handlers = [];

    /**
     * Enregistre un handler dans le registre.
     */
    public function register(JekoWebhookHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
        
        // Trier par priorité (plus petit = plus prioritaire)
        usort($this->handlers, fn($a, $b) => $a->priority() <=> $b->priority());
    }

    /**
     * Trouve et exécute le premier handler capable de traiter l'événement.
     * 
     * @throws \Exception si aucun handler ne peut traiter l'événement
     */
    public function dispatch(string $event, array $data): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($event, $data)) {
                Log::info('Jeko webhook: Handler matched', [
                    'event' => $event,
                    'handler' => get_class($handler),
                    'reference' => $data['transactionDetails']['reference'] ?? null,
                ]);

                $handler->handle($event, $data);
                return;
            }
        }

        Log::warning('Jeko webhook: No handler found', [
            'event' => $event,
            'reference' => $data['transactionDetails']['reference'] ?? null,
        ]);
    }

    /**
     * Retourne tous les handlers enregistrés (pour tests/debug).
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
}
```

### 2.5 Configuration dans AppServiceProvider

```php
// app/Providers/AppServiceProvider.php

use App\Services\Webhook\JekoWebhookRegistry;
use App\Services\Webhook\Handlers\{
    BookingPaymentHandler,
    SponsoredListingHandler,
    SubscriptionPaymentHandler,
    InsurancePaymentHandler,
    TransferHandler,
    GenericPaymentHandler,
};

public function register(): void
{
    // Registre singleton
    $this->app->singleton(JekoWebhookRegistry::class, function ($app) {
        $registry = new JekoWebhookRegistry();

        // Handlers spécifiques (priorité 10)
        $registry->register($app->make(BookingPaymentHandler::class));
        $registry->register($app->make(SponsoredListingHandler::class));
        $registry->register($app->make(SubscriptionPaymentHandler::class));
        $registry->register($app->make(InsurancePaymentHandler::class));
        $registry->register($app->make(TransferHandler::class));

        // Handler générique en dernier (priorité 100)
        $registry->register($app->make(GenericPaymentHandler::class));

        return $registry;
    });
}
```

### 2.6 JekoWebhookController simplifié

```php
<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\JekoPaymentService;
use App\Services\Webhook\JekoWebhookRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class JekoWebhookController extends Controller
{
    public function __construct(
        protected JekoPaymentService $jekoService,
        protected JekoWebhookRegistry $registry,
    ) {
    }

    public function handle(Request $request): Response
    {
        $rawBody = $request->getContent();
        $signature = $request->header('Jeko-Signature', '');

        // 1. Vérification signature
        if (!$this->jekoService->verifyWebhookSignature($rawBody, $signature)) {
            Log::channel('security')->warning('Jeko webhook: Invalid signature', [
                'ip' => $request->ip(),
                'signature' => substr($signature, 0, 20).'...',
            ]);
            return response('Invalid signature', 401);
        }

        // 2. Parsing payload
        $payload = $request->json()->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $eventId = $data['id'] ?? $data['transactionDetails']['reference'] ?? null;

        // 3. Idempotency check
        if ($eventId && !WebhookEvent::acquireLock('jeko', (string) $eventId, $event, $payload)) {
            Log::channel('payments')->info('Jeko webhook: Duplicate event ignored', [
                'event_id' => $eventId,
                'event' => $event,
            ]);
            return response('OK', 200);
        }

        Log::channel('payments')->info('Jeko webhook received', [
            'event' => $event,
            'event_id' => $eventId,
            'status' => $data['status'] ?? null,
            'reference' => $data['transactionDetails']['reference'] ?? null,
            'ip' => $request->ip(),
        ]);

        // 4. Dispatch vers le handler approprié
        try {
            $this->registry->dispatch($event, $data);
        } catch (\Throwable $e) {
            if ($eventId) {
                WebhookEvent::markFailed('jeko', (string) $eventId);
            }

            Log::channel('critical')->error('Jeko webhook: Processing error', [
                'event' => $event,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);
        }

        return response('OK', 200);
    }
}
```

**Gains** :
- ✅ Controller réduit de 400 → 80 lignes
- ✅ Ajout d'un nouveau type de paiement = 1 nouveau handler, pas de modification du controller
- ✅ Handlers isolés et testables unitairement
- ✅ Priorités configurables pour résoudre les ambiguïtés

---

## 3. Architecture : BookingService → Services spécialisés

### 3.1 Analyse des responsabilités actuelles

| Méthode actuelle | Responsabilité | Nouveau service |
|------------------|----------------|-----------------|
| `checkAvailability()` | Vérification disponibilité | `BookingAvailabilityService` |
| `getUnavailableDates()` | Calcul dates indisponibles | `BookingAvailabilityService` |
| `getAvailabilityCalendar()` | Génération calendrier | `BookingAvailabilityService` |
| `createBooking()` | Création réservation | `BookingCreationService` |
| `createInstantBooking()` | Création instant | `BookingCreationService` |
| `createBookingRequest()` | Création demande | `BookingCreationService` |
| `approveBookingRequest()` | Approbation demande | `BookingStateService` |
| `confirmBooking()` | Confirmation réservation | `BookingStateService` |
| `cancelBooking()` | Annulation | `BookingStateService` |
| `getOwnerBookingStats()` | Statistiques propriétaire | `BookingStatsService` |
| Notifications (inline) | Envoi notifications | `BookingNotificationService` |

### 3.2 Structure cible

```
app/Services/Booking/
├── BookingAvailabilityService.php    # Disponibilité & calendrier
├── BookingCreationService.php        # Création réservations
├── BookingStateService.php           # Transitions d'état
├── BookingNotificationService.php    # Notifications réservations
├── BookingStatsService.php           # Statistiques
└── BookingService.php                # Façade de compatibilité
```

### 3.3 Classes cibles avec signatures exactes

#### BookingAvailabilityService

```php
<?php

namespace App\Services\Booking;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\BookingRequest;
use Carbon\Carbon;

class BookingAvailabilityService
{
    /**
     * Vérifier la disponibilité d'une résidence.
     * 
     * @return array{available: bool, reason?: string, blocked_dates?: array, message: string, has_pending_request?: bool}
     */
    public function checkAvailability(
        int $residenceId,
        Carbon $checkIn,
        Carbon $checkOut,
    ): array;

    /**
     * Obtenir les dates indisponibles pour une résidence.
     * 
     * @return array<string> Liste de dates au format Y-m-d
     */
    public function getUnavailableDates(
        int $residenceId,
        Carbon $startDate,
        Carbon $endDate
    ): array;

    /**
     * Générer un calendrier de disponibilité.
     * 
     * @param int $residenceId
     * @param int $months Nombre de mois à générer (défaut: 3)
     * @return array Structure de calendrier mensuel
     */
    public function getAvailabilityCalendar(
        int $residenceId,
        int $months = 3
    ): array;
}
```

#### BookingCreationService

```php
<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use App\Models\User;
use App\Services\PricingService;
use App\Services\PaymentService;
use App\Services\CouponService;

class BookingCreationService
{
    public function __construct(
        protected PricingService $pricingService,
        protected PaymentService $paymentService,
        protected CouponService $couponService,
        protected BookingAvailabilityService $availabilityService,
        protected BookingNotificationService $notificationService,
    ) {
    }

    /**
     * Créer une réservation instantanée.
     * 
     * @throws \Exception si la résidence n'accepte pas l'instant booking
     */
    public function createInstantBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking;

    /**
     * Créer une réservation (instant ou demande selon la résidence).
     * IDEMPOTENT: Uses idempotency_key.
     * 
     * @throws \InvalidArgumentException pour validation des dates
     * @throws \Exception pour indisponibilité
     */
    public function createBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking;

    /**
     * Créer une demande de réservation (pour résidences non-instant).
     * 
     * @throws \InvalidArgumentException pour validation
     */
    public function createBookingRequest(
        Residence $residence,
        User $user,
        array $data,
    ): BookingRequest;

    /**
     * Générer une référence de réservation unique.
     */
    protected function generateBookingReference(): string;
}
```

#### BookingStateService

```php
<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Services\PaymentService;

class BookingStateService
{
    public function __construct(
        protected PaymentService $paymentService,
        protected BookingNotificationService $notificationService,
        protected BookingCreationService $creationService,
    ) {
    }

    /**
     * Approuver une demande de réservation et créer une réservation confirmée.
     * 
     * @throws \Exception si la demande n'est pas en attente
     */
    public function approveBookingRequest(
        BookingRequest $request,
        ?string $response = null
    ): Booking;

    /**
     * Confirmer une réservation après paiement.
     */
    public function confirmBooking(Booking $booking): Booking;

    /**
     * Annuler une réservation avec calcul de remboursement.
     * 
     * @param string $cancelledBy 'owner' ou 'guest'
     * @param string|null $reason Raison de l'annulation
     * @return array{booking: Booking, refund_amount: float}
     */
    public function cancelBooking(
        Booking $booking,
        string $cancelledBy,
        ?string $reason = null,
    ): array;

    /**
     * Calculer le montant du remboursement selon la politique d'annulation.
     */
    protected function calculateRefundAmount(
        Booking $booking,
        string $cancelledBy
    ): float;

    /**
     * Débloquer les dates d'une réservation annulée.
     */
    protected function unblockDatesForBooking(Booking $booking): void;
}
```

#### BookingNotificationService

```php
<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use App\Notifications\BookingRequestReceived;
use App\Notifications\BookingConfirmed;
use App\Notifications\BookingCancelled;

class BookingNotificationService
{
    /**
     * Envoyer les notifications pour une nouvelle demande de réservation.
     */
    public function notifyBookingRequestCreated(
        BookingRequest $request,
        Residence $residence
    ): void;

    /**
     * Envoyer les notifications pour une réservation confirmée.
     */
    public function notifyBookingConfirmed(Booking $booking): void;

    /**
     * Envoyer les notifications pour une réservation annulée.
     */
    public function notifyBookingCancelled(
        Booking $booking,
        string $cancelledBy,
        ?string $reason = null
    ): void;

    /**
     * Envoyer les notifications pour une demande approuvée.
     */
    public function notifyBookingRequestApproved(
        BookingRequest $request,
        Booking $booking
    ): void;
}
```

#### BookingStatsService

```php
<?php

namespace App\Services\Booking;

use App\Models\Residence;
use App\Models\Booking;
use App\Models\BookingRequest;

class BookingStatsService
{
    /**
     * Obtenir les statistiques de réservation pour un propriétaire.
     * 
     * @return array{
     *     pending_bookings: int,
     *     confirmed_bookings: int,
     *     monthly_revenue: float,
     *     pending_requests: int
     * }
     */
    public function getOwnerBookingStats(int $ownerId): array;
}
```

#### BookingService (Façade de compatibilité)

```php
<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use App\Models\User;
use App\Services\Booking\BookingAvailabilityService;
use App\Services\Booking\BookingCreationService;
use App\Services\Booking\BookingStateService;
use App\Services\Booking\BookingStatsService;
use Carbon\Carbon;

/**
 * Façade de compatibilité pour BookingService.
 * 
 * DEPRECATION NOTICE:
 * - Nouveau code doit utiliser directement les services spécialisés
 * - Cette façade sera supprimée dans une future version
 * 
 * @deprecated Utilisez les services spécialisés :
 *   - BookingAvailabilityService pour disponibilité
 *   - BookingCreationService pour créations
 *   - BookingStateService pour transitions d'état
 *   - BookingStatsService pour statistiques
 */
class BookingService
{
    public function __construct(
        protected BookingAvailabilityService $availabilityService,
        protected BookingCreationService $creationService,
        protected BookingStateService $stateService,
        protected BookingStatsService $statsService,
    ) {
    }

    // ── Delegation vers les services spécialisés ──

    public function checkAvailability(
        int $residenceId,
        Carbon $checkIn,
        Carbon $checkOut,
    ): array {
        return $this->availabilityService->checkAvailability(
            $residenceId,
            $checkIn,
            $checkOut
        );
    }

    public function getUnavailableDates(
        int $residenceId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        return $this->availabilityService->getUnavailableDates(
            $residenceId,
            $startDate,
            $endDate
        );
    }

    public function getAvailabilityCalendar(
        int $residenceId,
        int $months = 3
    ): array {
        return $this->availabilityService->getAvailabilityCalendar(
            $residenceId,
            $months
        );
    }

    public function createInstantBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking {
        return $this->creationService->createInstantBooking(
            $residence,
            $user,
            $data
        );
    }

    public function createBooking(
        Residence $residence,
        User $user,
        array $data,
    ): Booking {
        return $this->creationService->createBooking(
            $residence,
            $user,
            $data
        );
    }

    public function createBookingRequest(
        Residence $residence,
        User $user,
        array $data,
    ): BookingRequest {
        return $this->creationService->createBookingRequest(
            $residence,
            $user,
            $data
        );
    }

    public function approveBookingRequest(
        BookingRequest $request,
        ?string $response = null
    ): Booking {
        return $this->stateService->approveBookingRequest($request, $response);
    }

    public function confirmBooking(Booking $booking): Booking
    {
        return $this->stateService->confirmBooking($booking);
    }

    public function cancelBooking(
        Booking $booking,
        string $cancelledBy,
        ?string $reason = null,
    ): array {
        return $this->stateService->cancelBooking(
            $booking,
            $cancelledBy,
            $reason
        );
    }

    public function getOwnerBookingStats(int $ownerId): array
    {
        return $this->statsService->getOwnerBookingStats($ownerId);
    }
}
```

---

## 4. Stratégie de migration

### Phase 1 : Implémentation sans impact (1 semaine)

**Objectif** : Code nouveau disponible, ancien code inchangé.

1. **Créer la structure webhook**
   ```bash
   mkdir -p app/Services/Webhook/Handlers
   touch app/Services/Webhook/Handlers/JekoWebhookHandlerInterface.php
   touch app/Services/Webhook/JekoWebhookRegistry.php
   # ... créer tous les handlers
   ```

2. **Créer la structure booking**
   ```bash
   mkdir -p app/Services/Booking
   touch app/Services/Booking/BookingAvailabilityService.php
   touch app/Services/Booking/BookingCreationService.php
   touch app/Services/Booking/BookingStateService.php
   touch app/Services/Booking/BookingNotificationService.php
   touch app/Services/Booking/BookingStatsService.php
   ```

3. **Implémenter les nouveaux services**
   - Copier la logique métier depuis BookingService
   - Extraire les appels de notifications vers BookingNotificationService
   - Tests unitaires pour chaque nouveau service

4. **Créer la façade BookingService**
   - Déléguer tous les appels vers les services spécialisés
   - Aucun changement de signature publique

5. **Vérification**
   ```bash
   php artisan test tests/Unit/BookingServiceTest.php
   ```
   Tous les tests existants doivent passer ✅

### Phase 2 : Migration du controller webhook (1 semaine)

1. **Enregistrer le registre dans AppServiceProvider**
2. **Remplacer l'implémentation de JekoWebhookController**
   - Garder exactement la même signature `handle(Request $request): Response`
   - Remplacer le corps par l'appel au registre
3. **Tests d'intégration**
   ```php
   // tests/Feature/JekoWebhookTest.php
   public function test_booking_payment_webhook_is_dispatched_correctly()
   {
       // Simuler un payload Jeko avec REZI-BK-*
       // Vérifier que BookingPaymentHandler est appelé
   }
   ```

### Phase 3 : Migration progressive des contrôleurs (2 semaines)

**Règle** : Nouveau code utilise les services spécialisés, ancien code reste inchangé.

1. **Identifier les usages de BookingService dans les contrôleurs**
   ```bash
   grep -r "bookingService->" app/Http/Controllers/
   ```

2. **Migrer un contrôleur à la fois**
   ```php
   // Avant
   class BookingController
   {
       public function __construct(
           protected BookingService $bookingService
       ) {}
       
       public function store()
       {
           $booking = $this->bookingService->createBooking(...);
       }
   }
   
   // Après
   class BookingController
   {
       public function __construct(
           protected BookingCreationService $bookingCreationService,
           protected BookingAvailabilityService $availabilityService,
       ) {}
       
       public function store()
       {
           $booking = $this->bookingCreationService->createBooking(...);
       }
   }
   ```

3. **Tests après chaque migration**
   ```bash
   php artisan test --filter=BookingController
   ```

### Phase 4 : Dépréciation et suppression (1 semaine)

1. **Ajouter @deprecated à BookingService**
2. **Avertissement dans les logs pour usages restants**
   ```php
   public function createBooking(...)
   {
       Log::warning('BookingService::createBooking is deprecated, use BookingCreationService');
       return $this->creationService->createBooking(...);
   }
   ```
3. **Après 2 sprints sans régression → supprimer la façade**

---

## 5. Tests suggérés

### 5.1 Tests pour JekoWebhookRegistry

```php
// tests/Unit/JekoWebhookRegistryTest.php

class JekoWebhookRegistryTest extends TestCase
{
    protected JekoWebhookRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new JekoWebhookRegistry();
    }

    /** @test */
    public function it_dispatches_to_correct_handler_based_on_priority()
    {
        $handler1 = Mockery::mock(JekoWebhookHandlerInterface::class);
        $handler1->shouldReceive('priority')->andReturn(20);
        $handler1->shouldReceive('canHandle')->andReturn(true);
        $handler1->shouldReceive('handle')->once();

        $handler2 = Mockery::mock(JekoWebhookHandlerInterface::class);
        $handler2->shouldReceive('priority')->andReturn(10);
        $handler2->shouldReceive('canHandle')->andReturn(true);
        $handler2->shouldReceive('handle')->never(); // handler1 prend le relais

        $this->registry->register($handler1);
        $this->registry->register($handler2);

        $this->registry->dispatch('transaction.completed', [
            'transactionDetails' => ['reference' => 'TEST'],
        ]);
    }

    /** @test */
    public function it_throws_exception_when_no_handler_matches()
    {
        $handler = Mockery::mock(JekoWebhookHandlerInterface::class);
        $handler->shouldReceive('priority')->andReturn(10);
        $handler->shouldReceive('canHandle')->andReturn(false);

        $this->registry->register($handler);

        // Pas d'exception, juste un log warning
        $this->registry->dispatch('unknown.event', []);
        
        $this->assertTrue(true); // Pas de crash
    }
}
```

### 5.2 Tests pour BookingPaymentHandler

```php
// tests/Unit/Webhook/BookingPaymentHandlerTest.php

class BookingPaymentHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected BookingPaymentHandler $handler;
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = Mockery::mock(PaymentService::class);
        $this->handler = new BookingPaymentHandler($this->paymentService);
    }

    /** @test */
    public function it_can_handle_booking_payment_references()
    {
        $canHandle = $this->handler->canHandle('transaction.completed', [
            'transactionDetails' => ['reference' => 'REZI-BK-123456'],
        ]);

        $this->assertTrue($canHandle);
    }

    /** @test */
    public function it_rejects_non_booking_references()
    {
        $canHandle = $this->handler->canHandle('transaction.completed', [
            'transactionDetails' => ['reference' => 'REZI-SP-123456'],
        ]);

        $this->assertFalse($canHandle);
    }

    /** @test */
    public function it_marks_payment_as_completed_on_success()
    {
        $payment = Payment::factory()->create([
            'reference' => 'REZI-BK-123456',
            'type' => Payment::TYPE_BOOKING,
            'status' => 'pending',
        ]);

        $this->paymentService
            ->shouldReceive('onPaymentSuccess')
            ->once()
            ->with(Mockery::type(Payment::class));

        $this->handler->handle('transaction.completed', [
            'id' => 'jeko-tx-789',
            'status' => 'success',
            'transactionDetails' => ['reference' => 'REZI-BK-123456'],
            'paymentMethod' => 'mobile_money',
            'executedAt' => '2026-06-05T10:00:00Z',
        ]);

        $payment->refresh();
        $this->assertEquals('completed', $payment->status);
    }
}
```

### 5.3 Tests pour BookingAvailabilityService

```php
// tests/Unit/Booking/BookingAvailabilityServiceTest.php

class BookingAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingAvailabilityService::class);
    }

    /** @test */
    public function it_returns_available_when_no_conflicts()
    {
        $residence = Residence::factory()->create();
        $checkIn = now()->addDays(7);
        $checkOut = now()->addDays(10);

        $result = $this->service->checkAvailability(
            $residence->id,
            $checkIn,
            $checkOut
        );

        $this->assertTrue($result['available']);
    }

    /** @test */
    public function it_detects_blocked_dates()
    {
        $residence = Residence::factory()->create();
        $checkIn = now()->addDays(7);
        $checkOut = now()->addDays(10);

        BlockedDate::create([
            'residence_id' => $residence->id,
            'date' => $checkIn->copy()->addDay(),
            'reason' => 'maintenance',
        ]);

        $result = $this->service->checkAvailability(
            $residence->id,
            $checkIn,
            $checkOut
        );

        $this->assertFalse($result['available']);
        $this->assertEquals('dates_blocked', $result['reason']);
    }

    /** @test */
    public function it_detects_existing_bookings()
    {
        $residence = Residence::factory()->create();
        $checkIn = now()->addDays(7);
        $checkOut = now()->addDays(10);

        Booking::factory()->create([
            'residence_id' => $residence->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => 'confirmed',
        ]);

        $result = $this->service->checkAvailability(
            $residence->id,
            $checkIn,
            $checkOut
        );

        $this->assertFalse($result['available']);
        $this->assertEquals('already_booked', $result['reason']);
    }
}
```

### 5.4 Tests de compatibilité de la façade

```php
// tests/Unit/BookingServiceFacadeTest.php

/**
 * Tests de compatibilité pour s'assurer que la façade BookingService
 * délègue correctement vers les services spécialisés.
 */
class BookingServiceFacadeTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingService = app(BookingService::class);
    }

    /** @test */
    public function facade_delegates_check_availability_correctly()
    {
        $residence = Residence::factory()->create();
        $checkIn = now()->addDays(7);
        $checkOut = now()->addDays(10);

        $result = $this->bookingService->checkAvailability(
            $residence->id,
            $checkIn,
            $checkOut
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('available', $result);
    }

    /** @test */
    public function facade_delegates_create_booking_correctly()
    {
        $residence = Residence::factory()->create(['instant_book' => true]);
        $user = User::factory()->create();

        $booking = $this->bookingService->createBooking(
            $residence,
            $user,
            [
                'check_in' => now()->addDays(7)->toDateString(),
                'check_out' => now()->addDays(10)->toDateString(),
                'guests' => 2,
            ]
        );

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($residence->id, $booking->residence_id);
    }

    /** @test */
    public function facade_delegates_cancel_booking_correctly()
    {
        $booking = Booking::factory()->create([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $result = $this->bookingService->cancelBooking(
            $booking,
            'guest',
            'Changed plans'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('booking', $result);
        $this->assertArrayHasKey('refund_amount', $result);
    }
}
```

---

## 6. Critères de succès

### Métriques de qualité

| Critère | Cible | Mesure |
|---------|-------|--------|
| **Couverture tests** | ≥ 85% | PHPUnit coverage |
| **Régression** | 0 tests cassés | `php artisan test` |
| **Handlers webhook** | 6 classes | Registre |
| **Services booking** | 5 classes | Namespace `Booking/` |
| **Lignes par classe** | ≤ 300 | `wc -l` |
| **Cyclomatic complexity** | ≤ 10 | PHPStan level 5 |

### Checklist pré-merge

- [ ] Tous les tests passent (Unit + Feature)
- [ ] Couverture ≥ 85% sur nouveaux services
- [ ] Aucun appel direct aux méthodes privées de BookingService
- [ ] Documentation PHPDoc complète
- [ ] CHANGELOG.md mis à jour
- [ ] Migration guide pour les développeurs
- [ ] Revue de code par 2 seniors

---

## 7. Dépendances et risques

### Dépendances techniques
- ✅ Laravel 12.x (pas de breaking change)
- ✅ PHPUnit 11.x (tests compatibles)
- ✅ Aucune dépendance externe nouvelle

### Risques identifiés

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| **Tests existants cassent** | 🔴 Élevé | Faible | Façade de compatibilité + tests de non-régression |
| **Oubli d'un contrôleur utilisant BookingService** | 🟡 Moyen | Moyen | Recherche globale `grep -r "bookingService->"` |
| **Handlers webhook manquants** | 🟡 Moyen | Faible | Liste exhaustive + tests avec tous les préfixes |
| **Performance dégradée** | 🟢 Faible | Faible | Même logique métier, juste réorganisée |
| **Injection de dépendances circulaires** | 🟡 Moyen | Faible | Diagramme de dépendances + validation statique |

### Plan de rollback

Si régression détectée en production :

1. **Rollback immédiat** : Revert du commit de migration du controller
2. **Restauration BookingService** : Garder l'ancienne implémentation monolithique
3. **Analyse post-mortem** : Identifier le test manquant
4. **Re-tentative** : Corriger + nouvelle PR

---

## 8. Timeline estimé

| Phase | Durée | Jalons |
|-------|-------|--------|
| **Phase 1** : Implémentation sans impact | 1 semaine | Services créés, tests passent |
| **Phase 2** : Migration webhook controller | 1 semaine | Controller simplifié, tests webhooks OK |
| **Phase 3** : Migration contrôleurs | 2 semaines | Tous les contrôleurs migrés |
| **Phase 4** : Dépréciation | 1 semaine | Façade marquée deprecated |
| **Phase 5** : Suppression façade | Sprint +2 | Façade supprimée |
| **TOTAL** | **5 semaines** | Production stable |

---

## 9. Checklist d'implémentation

### JekoWebhookController
- [ ] Créer `JekoWebhookHandlerInterface`
- [ ] Créer `JekoWebhookRegistry`
- [ ] Implémenter `BookingPaymentHandler`
- [ ] Implémenter `SponsoredListingHandler`
- [ ] Implémenter `SubscriptionPaymentHandler`
- [ ] Implémenter `InsurancePaymentHandler`
- [ ] Implémenter `TransferHandler`
- [ ] Implémenter `GenericPaymentHandler`
- [ ] Enregistrer tous les handlers dans `AppServiceProvider`
- [ ] Simplifier `JekoWebhookController::handle()`
- [ ] Tests unitaires pour chaque handler
- [ ] Tests d'intégration pour le registre

### BookingService
- [ ] Créer `BookingAvailabilityService`
- [ ] Créer `BookingCreationService`
- [ ] Créer `BookingStateService`
- [ ] Créer `BookingNotificationService`
- [ ] Créer `BookingStatsService`
- [ ] Migrer la logique métier depuis `BookingService`
- [ ] Créer la façade `BookingService` (délégation)
- [ ] Tests unitaires pour chaque service
- [ ] Tests de compatibilité de la façade
- [ ] Migrer `BookingController`
- [ ] Migrer autres contrôleurs
- [ ] Ajouter `@deprecated` à la façade
- [ ] Supprimer la façade après 2 sprints

---

## 10. Documentation complémentaire

### Pour les développeurs

**Guide de migration des contrôleurs** :

```php
// Avant
class MesReservationsController
{
    public function __construct(BookingService $bookingService) {}
    
    public function index()
    {
        $stats = $this->bookingService->getOwnerBookingStats(Auth::id());
    }
}

// Après
class MesReservationsController
{
    public function __construct(BookingStatsService $statsService) {}
    
    public function index()
    {
        $stats = $this->statsService->getOwnerBookingStats(Auth::id());
    }
}
```

**Tableau de correspondance** :

| Ancienne méthode | Nouveau service | Nouvelle méthode |
|------------------|-----------------|------------------|
| `BookingService::checkAvailability()` | `BookingAvailabilityService` | `checkAvailability()` |
| `BookingService::createBooking()` | `BookingCreationService` | `createBooking()` |
| `BookingService::confirmBooking()` | `BookingStateService` | `confirmBooking()` |
| `BookingService::getOwnerBookingStats()` | `BookingStatsService` | `getOwnerBookingStats()` |

---

## Conclusion

Ce plan propose une refactorisation **sans risque** en 5 semaines :

1. ✅ **JekoWebhookController** : Registry/Strategy Pattern élimine if/elseif et duplication
2. ✅ **BookingService** : Décomposition en 5 services spécialisés avec façade de compatibilité
3. ✅ **Migration progressive** : Aucun contrôleur ni test cassé pendant la transition
4. ✅ **Tests exhaustifs** : Couverture ≥ 85% sur tous les nouveaux services
5. ✅ **Extensibilité** : Ajout de nouveaux types de webhooks en 1 fichier

**Prochain pas** : Validation du plan par l'équipe → Phase 1 implémentation.
