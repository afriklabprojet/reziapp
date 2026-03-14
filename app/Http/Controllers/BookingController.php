<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequestRequest;
use App\Http\Requests\StoreInstantBookingRequest;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use App\Services\BookingService;
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
        $checkIn = $request->query('check_in') ? Carbon::parse($request->query('check_in')) : null;
        $checkOut = $request->query('check_out') ? Carbon::parse($request->query('check_out')) : null;
        $guests = (int) $request->query('guests', 1);
        $adults = (int) $request->query('adults', 1);
        $children = (int) $request->query('children', 0);
        $infants = (int) $request->query('infants', 0);

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
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
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
            $booking = $this->bookingService->createInstantBooking(
                $residence,
                Auth::user(),
                $request->all(),
            );

            // Rediriger vers le paiement
            return redirect()->route('payments.checkout', ['booking' => $booking->id])
                ->with('success', 'Réservation créée ! Procédez au paiement pour confirmer.');
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
            $bookingRequest = $this->bookingService->createBookingRequest(
                $residence,
                Auth::user(),
                $request->all(),
            );

            return redirect()->route('bookings.requests.show', $bookingRequest)
                ->with('success', 'Demande envoyée ! Le propriétaire a 48h pour répondre.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
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
                $message .= ' Remboursement de '.number_format($result['refund_amount'], 0, ',', ' ').' FCFA en cours.';
            }

            return redirect()->route('bookings.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
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

        // Recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('residence', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $bookings = $query->orderBy($sort, $dir)->paginate(12)->withQueryString();

        // Stats enrichies
        $stats = $this->bookingService->getOwnerBookingStats(Auth::id());

        $residenceIds = \App\Models\Residence::where('owner_id', Auth::id())->pluck('id');

        $stats['total_bookings']   = Booking::whereIn('residence_id', $residenceIds)->count();
        $stats['completed_count']  = Booking::whereIn('residence_id', $residenceIds)->where('status', 'completed')->count();
        $stats['cancelled_count']  = Booking::whereIn('residence_id', $residenceIds)->whereIn('status', ['cancelled_by_user', 'cancelled_by_owner'])->count();
        $stats['total_revenue']    = Booking::whereIn('residence_id', $residenceIds)->where('status', 'completed')->sum('total_amount');
        $stats['total_nights']     = Booking::whereIn('residence_id', $residenceIds)->whereIn('status', ['confirmed', 'completed'])->sum('nights');
        $stats['avg_booking_value'] = Booking::whereIn('residence_id', $residenceIds)->where('status', 'completed')->avg('total_amount') ?? 0;

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
        if ($bookingRequest->residence->owner_id !== Auth::id()) {
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
        if ($bookingRequest->residence->owner_id !== Auth::id()) {
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
        if ($booking->residence->owner_id !== Auth::id()) {
            abort(403);
        }

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
            $timeline->push(['date' => $booking->cancelled_at, 'label' => 'Annulée — ' . ($booking->cancellation_reason ?? ''), 'icon' => 'x-mark']);
        }
        $timeline = $timeline->sortBy('date')->values();

        return view('owner.bookings.show', compact('booking', 'timeline'));
    }

    /**
     * Annuler une réservation (propriétaire)
     */
    public function ownerCancel(Request $request, Booking $booking)
    {
        if ($booking->residence->owner_id !== Auth::id()) {
            abort(403);
        }

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
        if ($booking->residence->owner_id !== Auth::id()) {
            abort(403);
        }

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
