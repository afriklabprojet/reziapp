<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequestRequest;
use App\Http\Requests\StoreInstantBookingRequest;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\User;
use App\Services\BookingService;
use App\Services\JekoPaymentService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected PricingService $pricingService;

    public function __construct(BookingService $bookingService, PricingService $pricingService)
    {
        $this->bookingService = $bookingService;
        $this->pricingService = $pricingService;
    }

    /**
     * Afficher le formulaire de réservation
     */
    public function create(Residence $residence, Request $request)
    {
        // Validation des inputs query string
        $checkIn = null;
        $checkOut = null;

        try {
            if ($request->query('check_in')) {
                $checkIn = Carbon::parse($request->query('check_in'));
            }
            if ($request->query('check_out')) {
                $checkOut = Carbon::parse($request->query('check_out'));
            }
            // Cohérence : check_out doit être après check_in
            if ($checkIn && $checkOut && $checkOut->lte($checkIn)) {
                $checkOut = $checkIn->copy()->addDay();
            }
        } catch (\Exception $e) {
            // Dates invalides — on ignore silencieusement
            $checkIn = null;
            $checkOut = null;
        }
        $guests = max(1, min(50, (int) $request->query('guests', 1)));
        $adults = max(1, min(50, (int) $request->query('adults', 1)));
        $children = max(0, min(20, (int) $request->query('children', 0)));
        $infants = max(0, min(10, (int) $request->query('infants', 0)));

        $pricePreview = null;
        if ($checkIn && $checkOut) {
            $pricePreview = $this->pricingService->calculatePrice(
                $residence,
                $checkIn,
                $checkOut,
                $guests,
                null,
                Auth::user(),
            );
        }

        // Calendrier de disponibilité
        $calendar = $this->bookingService->getAvailabilityCalendar($residence->id);

        // Réductions long séjour
        $longStayDiscounts = $this->pricingService->getAvailableLongStayDiscounts($residence);

        // Eager load relations for Airbnb-style page
        $residence->load(['owner', 'photos', 'category', 'cancellationPolicy']);

        // Résolution du prix pour affichage
        $pricePerNight = $residence->price_per_night ?? 0;

        return view('bookings.create', compact(
            'residence',
            'checkIn',
            'checkOut',
            'guests',
            'adults',
            'children',
            'infants',
            'pricePreview',
            'pricePerNight',
            'calendar',
            'longStayDiscounts',
        ));
    }

    /**
     * Calculer le prix (AJAX)
     */
    public function calculatePrice(Residence $residence, Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'promo_code' => 'nullable|string',
            'coupon_code' => 'nullable|string',
        ]);

        try {
            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);

            // Vérifier la disponibilité
            $availability = $this->bookingService->checkAvailability(
                $residence->id,
                $checkIn,
                $checkOut,
            );

            if (!$availability['available']) {
                return response()->json([
                    'success' => false,
                    'error' => $availability['message'],
                    'blocked_dates' => $availability['blocked_dates'] ?? [],
                ], 422);
            }

            // Calculer le prix
            $price = $this->pricingService->calculatePrice(
                $residence,
                $checkIn,
                $checkOut,
                $request->guests,
                $request->promo_code,
                Auth::user(),
                $request->coupon_code,
            );

            return response()->json([
                'success' => true,
                'price' => $price,
                'availability' => $availability,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors du calcul du prix.',
            ], 422);
        }
    }

    /**
     * Vérifier la disponibilité (AJAX)
     */
    public function checkAvailability(Residence $residence, Request $request)
    {
        $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        $availability = $this->bookingService->checkAvailability(
            $residence->id,
            $checkIn,
            $checkOut,
        );

        return response()->json($availability);
    }

    /**
     * Créer une réservation instantanée
     */
    public function storeInstant(StoreInstantBookingRequest $request, Residence $residence)
    {
        try {
            $booking = $this->bookingService->createBooking(
                $residence,
                Auth::user(),
                array_merge($request->all(), ['booking_type' => 'instant']),
            );

            return $this->redirectToPayment($booking, $request->input('payment_method', 'wave'), [
                'use_wallet_credit'   => (bool) $request->input('use_wallet_credit', false),
                'use_referral_credit' => (bool) $request->input('use_referral_credit', false),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Créer une demande de réservation
     */
    public function storeRequest(StoreBookingRequestRequest $request, Residence $residence)
    {
        try {
            $booking = $this->bookingService->createBooking(
                $residence,
                Auth::user(),
                array_merge($request->all(), ['booking_type' => 'request']),
            );

            return $this->redirectToPayment($booking, $request->input('payment_method', 'wave'), [
                'use_wallet_credit'   => (bool) $request->input('use_wallet_credit', false),
                'use_referral_credit' => (bool) $request->input('use_referral_credit', false),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Créer une demande de réservation en tant qu'invité
     */
    public function storeGuestRequest(Request $request, Residence $residence)
    {
        $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'required|string|max:20',
            'check_in' => 'required|date|after:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1',
            'message' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|in:wave,orange,mtn,moov,djamo',
        ]);

        try {
            // Vérifier que l'email ne correspond pas à un compte réel existant
            $existingUser = \App\Models\User::where('email', $request->guest_email)->first();
            if ($existingUser && !$existingUser->is_guest) {
                return back()
                    ->withErrors(['guest_email' => 'Un compte existe déjà avec cet email. Veuillez vous connecter pour réserver.'])
                    ->withInput();
            }

            // Créer ou récupérer le compte invité
            $guestUser = User::createOrFindGuest(
                $request->guest_email,
                $request->guest_name,
                $request->guest_phone,
            );

            // Créer la réservation (pas de BookingRequest, directement un Booking)
            $booking = $this->bookingService->createBooking(
                $residence,
                $guestUser,
                [
                    'check_in' => $request->check_in,
                    'check_out' => $request->check_out,
                    'guests' => $request->guests,
                    'adults' => $request->adults ?? $request->guests,
                    'children' => $request->children ?? 0,
                    'infants' => $request->infants ?? 0,
                    'message' => $request->message,
                    'booking_type' => $residence->instant_book ? 'instant' : 'request',
                ],
            );

            return $this->redirectToPayment($booking, $request->input('payment_method', 'wave'), [
                'use_wallet_credit'   => (bool) $request->input('use_wallet_credit', false),
                'use_referral_credit' => (bool) $request->input('use_referral_credit', false),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Afficher la page de confirmation après réservation
     */
    public function confirmation(Booking $booking)
    {
        $booking->load(['residence.photos', 'residence.owner', 'user']);

        return view('bookings.confirmation', compact('booking'));
    }

    /**
     * Initier le paiement Jeko et rediriger vers la page de paiement.
     *
     * @param  array  $walletOptions  ['use_wallet_credit' => bool, 'use_referral_credit' => bool]
     */
    protected function redirectToPayment(Booking $booking, string $paymentMethod, array $walletOptions = [])
    {
        $jeko = app(JekoPaymentService::class);

        if ($jeko->isEnabled()) {
            // Create the Payment record first — atomically deducts wallet/referral credits
            $payment = app(PaymentService::class)->createBookingPayment(
                $booking,
                $booking->user,
                $walletOptions,
            );

            // Credits cover the full amount — no Jeko charge needed
            if ((float) $payment->total_amount < 100) {
                $payment->update(['status' => Payment::STATUS_COMPLETED, 'paid_at' => now()]);

                return redirect()->route('bookings.confirmation', $booking->uuid);
            }

            // Pass the post-credit charge amount to Jeko
            $result = $jeko->createBookingPaymentRequest($booking, $paymentMethod, $payment);

            if ($result['success'] && ! empty($result['redirect_url'])) {
                return redirect()->away($result['redirect_url']);
            }

            // Jeko failed — mark payment as failed so PaymentObserver restores credits
            try {
                $payment->update(['status' => Payment::STATUS_FAILED]);
                $booking->delete();
            } catch (\Throwable $e) {
                report($e);
            }

            return back()
                ->withErrors(['payment' => $result['error'] ?? 'Le paiement a échoué. Veuillez réessayer.'])
                ->withInput();
        }

        // Jeko not enabled — redirect to confirmation page
        return redirect()->route('bookings.confirmation', $booking->uuid);
    }

    /**
     * Afficher les réservations de l'utilisateur
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Booking::where('user_id', Auth::id())
            ->with(['residence', 'residence.photos'])
            ->orderBy('check_in', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bookings = $query->paginate(config('rezi.pagination.bookings'));

        // Statistiques (1 requête agrégée au lieu de 3)
        $now = now()->toDateTimeString();
        $rawStats = Booking::where('user_id', Auth::id())
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'confirmed' AND check_in > ? THEN 1 ELSE 0 END) as upcoming", [$now])
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->first();

        $stats = [
            'total' => (int) $rawStats->total,
            'upcoming' => (int) $rawStats->upcoming,
            'completed' => (int) $rawStats->completed,
        ];

        return view('bookings.index', compact('bookings', 'stats', 'status'));
    }

    /**
     * Afficher une demande de réservation (côté voyageur)
     */
    public function showRequest(BookingRequest $bookingRequest)
    {
        if ($bookingRequest->user_id !== Auth::id()) {
            abort(403);
        }

        $bookingRequest->load([
            'residence',
            'residence.photos',
            'residence.owner',
            'user',
            'booking',
        ]);

        return view('bookings.request-show', compact('bookingRequest'));
    }

    /**
     * Afficher une réservation
     */
    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load([
            'residence',
            'residence.photos',
            'residence.owner',
            'cancellationPolicy',
        ]);

        return view('bookings.show', compact('booking'));
    }

    /**
     * Annuler une réservation
     */
    public function cancel(Request $request, Booking $booking)
    {
        $this->authorize('cancel', $booking);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->bookingService->cancelBooking(
                $booking,
                $request->reason,
                'user',
            );

            $message = 'Réservation annulée.';
            if ($result['refund_amount'] > 0) {
                $message .= ' Remboursement de '.number_format((float) $result['refund_amount'], 0, ',', ' ').' FCFA en cours.';
            }

            return redirect()->route('bookings.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Télécharger le reçu de location (réservations terminées uniquement)
     */
    public function downloadReceipt(Booking $booking): \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    {
        $this->authorize('view', $booking);

        if ($booking->status !== 'completed') {
            abort(404);
        }

        $booking->loadMissing('residence');

        $safeFilename = 'receipt-booking-' . preg_replace('/[^a-zA-Z0-9\-]/', '', $booking->uuid) . '.txt';

        $content = implode("\n", [
            '===========================',
            '       REZI - REÇU DE LOCATION',
            '===========================',
            '',
            'Référence : ' . $booking->uuid,
            'Date       : ' . now()->format('d/m/Y'),
            '',
            '--- BIEN ---',
            'Résidence  : ' . ($booking->residence->title ?? 'N/A'),
            'Adresse    : ' . ($booking->residence->address ?? 'N/A'),
            '',
            '--- SÉJOUR ---',
            'Arrivée    : ' . $booking->check_in?->format('d/m/Y'),
            'Départ     : ' . $booking->check_out?->format('d/m/Y'),
            'Nuits      : ' . ($booking->nights ?? 'N/A'),
            '',
            '--- PAIEMENT ---',
            'Montant    : ' . number_format((float) $booking->total_amount, 0, ',', ' ') . ' FCFA',
            'Statut     : Payé',
            '',
            '===========================',
            'Merci de votre confiance.',
            '===========================',
        ]);

        return response($content, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $safeFilename . '"',
        ]);
    }

    // =============================================
    // PARTIE PROPRIÉTAIRE
    // =============================================

    /**
     * Réservations pour le propriétaire (enrichi)
     */
    public function ownerIndex(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $sort   = $request->query('sort', 'created_at');
        $dir    = $request->query('dir', 'desc');

        // Allowed sort columns
        $allowedSorts = ['created_at', 'check_in', 'total_amount', 'nights'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $query = Booking::whereHas('residence', function ($q) {
            $q->where('owner_id', Auth::id());
        })->with(['residence.photos', 'user']);

        // Filtre statut
        if ($status !== 'all') {
            if ($status === 'cancelled') {
                $query->whereIn('status', ['cancelled_by_user', 'cancelled_by_owner']);
            } else {
                $query->where('status', $status);
            }
        }

        // Recherche (wildcards LIKE échappés pour la sécurité)
        if ($search) {
            $safe = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where(function ($q) use ($safe) {
                $q->where('reference', 'like', "%{$safe}%")
                  ->orWhereHas('user', function ($q2) use ($safe) {
                      $q2->where('name', 'like', "%{$safe}%")
                         ->orWhere('first_name', 'like', "%{$safe}%")
                         ->orWhere('last_name', 'like', "%{$safe}%");
                  })
                  ->orWhereHas('residence', function ($q2) use ($safe) {
                      $q2->where('name', 'like', "%{$safe}%");
                  });
            });
        }

        $bookings = $query->orderBy($sort, $dir)->paginate(12)->withQueryString();

        // Stats enrichies (1 requête agrégée au lieu de 7)
        $stats = $this->bookingService->getOwnerBookingStats(Auth::id());

        $residenceIds = \App\Models\Residence::where('owner_id', Auth::id())->pluck('id');

        $aggregated = Booking::whereIn('residence_id', $residenceIds)
            ->selectRaw("
                COUNT(*) as total_bookings,
                SUM(status = 'completed') as completed_count,
                SUM(status IN ('cancelled_by_user', 'cancelled_by_owner')) as cancelled_count,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status IN ('confirmed', 'completed') THEN nights ELSE 0 END) as total_nights,
                AVG(CASE WHEN status = 'completed' THEN total_amount END) as avg_booking_value
            ")
            ->first();

        $stats['total_bookings']   = (int) $aggregated->total_bookings;
        $stats['completed_count']  = (int) $aggregated->completed_count;
        $stats['cancelled_count']  = (int) $aggregated->cancelled_count;
        $stats['total_revenue']    = (float) ($aggregated->total_revenue ?? 0);
        $stats['total_nights']     = (int) $aggregated->total_nights;
        $stats['avg_booking_value'] = (float) ($aggregated->avg_booking_value ?? 0);

        return view('owner.bookings.index', compact('bookings', 'stats', 'status', 'search', 'sort', 'dir'));
    }

    /**
     * Demandes de réservation en attente
     */
    public function ownerRequests(Request $request)
    {
        $requests = BookingRequest::forOwner(Auth::id())
            ->with(['residence', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(config('rezi.pagination.bookings'));

        return view('owner.bookings.requests', compact('requests'));
    }

    /**
     * Approuver une demande de réservation
     */
    public function approveRequest(Request $request, BookingRequest $bookingRequest)
    {
        // Vérifier que la résidence appartient à l'utilisateur
        if ((int) ($bookingRequest->residence?->owner_id ?? 0) !== (int) Auth::id()) {
            abort(403);
        }

        $request->validate([
            'response' => 'nullable|string|max:500',
        ]);

        try {
            $booking = $this->bookingService->approveBookingRequest(
                $bookingRequest,
                $request->response,
            );

            return redirect()->route('owner.bookings.show', $booking)
                ->with('success', 'Demande approuvée ! Le voyageur va recevoir une notification pour procéder au paiement.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Refuser une demande de réservation
     */
    public function rejectRequest(Request $request, BookingRequest $bookingRequest)
    {
        if ((int) ($bookingRequest->residence?->owner_id ?? 0) !== (int) Auth::id()) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $bookingRequest->reject($request->reason);

        return redirect()->route('owner.bookings.requests')
            ->with('success', 'Demande refusée.');
    }

    /**
     * Détails d'une réservation pour le propriétaire
     */
    public function ownerShow(Booking $booking)
    {
        $this->authorize('manageAsOwner', $booking);

        $booking->load(['residence.photos', 'user', 'cancellationPolicy', 'review', 'coupon']);

        // Timeline events
        $timeline = collect();
        $timeline->push(['date' => $booking->created_at, 'label' => 'Réservation créée', 'icon' => 'calendar']);
        if ($booking->paid_at) {
            $timeline->push(['date' => $booking->paid_at, 'label' => 'Paiement reçu', 'icon' => 'banknotes']);
        }
        if ($booking->confirmed_at) {
            $timeline->push(['date' => $booking->confirmed_at, 'label' => 'Confirmée par le propriétaire', 'icon' => 'check']);
        }
        if ($booking->actual_check_in) {
            $timeline->push(['date' => $booking->actual_check_in, 'label' => 'Check-in effectué', 'icon' => 'arrow-right']);
        }
        if ($booking->actual_check_out) {
            $timeline->push(['date' => $booking->actual_check_out, 'label' => 'Check-out effectué', 'icon' => 'arrow-left']);
        }
        if ($booking->cancelled_at) {
            $timeline->push(['date' => $booking->cancelled_at, 'label' => 'Annulée — '.($booking->cancellation_reason ?? ''), 'icon' => 'x-mark']);
        }
        $timeline = $timeline->sortBy('date')->values();

        return view('owner.bookings.show', compact('booking', 'timeline'));
    }

    /**
     * Annuler une réservation (propriétaire)
     */
    public function ownerCancel(Request $request, Booking $booking)
    {
        $this->authorize('manageAsOwner', $booking);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $result = $this->bookingService->cancelBooking(
                $booking,
                $request->reason,
                'owner',
            );

            return redirect()->route('owner.bookings.index')
                ->with('success', 'Réservation annulée. Le voyageur sera remboursé intégralement.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirmer une réservation en attente (propriétaire)
     */
    public function ownerConfirm(Booking $booking)
    {
        $this->authorize('manageAsOwner', $booking);

        if ($booking->status !== 'pending') {
            return back()->withErrors(['error' => 'Seules les réservations en attente peuvent être confirmées.']);
        }

        $booking->update(['status' => 'confirmed']);

        // Notifier le voyageur
        $booking->user->notify(new \App\Notifications\BookingConfirmed($booking));

        // Auto-qualifier le parrainage si le propriétaire est un filleul avec un parrainage en attente
        $owner = Auth::user();
        if ($owner->referred_by) {
            $referral = \App\Models\Referral::where('referred_id', $owner->id)
                ->where('status', 'pending')
                ->first();

            if ($referral) {
                $referral->qualify();

                // Notifier le parrain que son filleul a reçu sa première réservation
                if ($referral->referrer) {
                    $referral->referrer->notify(new \App\Notifications\ReferralQualified($referral));
                }
            }
        }

        return redirect()->route('owner.bookings.show', $booking)
            ->with('success', 'Réservation confirmée !');
    }

    /**
     * Calendrier des réservations
     */
    public function calendar(Residence $residence)
    {
        if ($residence->owner_id !== Auth::id()) {
            abort(403);
        }

        $calendar = $this->bookingService->getAvailabilityCalendar($residence->id, 6);

        $bookings = Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_out', '>=', today())
            ->with('user')
            ->get();

        return view('owner.bookings.calendar', compact('residence', 'calendar', 'bookings'));
    }
}
