<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\CancellationPolicy;
use App\Services\CancellationService;
use Illuminate\Http\Request;

class CancellationController extends Controller
{
    protected CancellationService $cancellationService;

    public function __construct(CancellationService $cancellationService)
    {
        $this->cancellationService = $cancellationService;
    }

    /**
     * Show cancellation policies
     */
    public function policies()
    {
        $policies = CancellationPolicy::active()->get();

        return view('cancellations.policies', compact('policies'));
    }

    /**
     * Preview cancellation for a booking
     */
    public function preview(Booking $booking)
    {
        $user = auth()->user();

        // Check authorization
        $isGuest = $booking->user_id === $user->id;
        $isOwner = $booking->residence->owner_id === $user->id;

        if (!$isGuest && !$isOwner) {
            abort(403, 'Non autorisé');
        }

        $cancelledBy = $isOwner ? 'owner' : 'user';
        $preview = $this->cancellationService->previewCancellation($booking, $cancelledBy);
        $reasons = $isOwner ? Cancellation::getOwnerReasons() : Cancellation::getGuestReasons();

        return view('cancellations.preview', compact('booking', 'preview', 'reasons', 'isOwner'));
    }

    /**
     * Cancel booking (by guest)
     */
    public function cancelAsGuest(Request $request, Booking $booking)
    {
        $this->authorize('cancel', $booking);

        $validated = $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(Cancellation::getGuestReasons())),
            'detailed_reason' => 'nullable|string|max:1000',
        ]);

        try {
            $cancellation = $this->cancellationService->cancelByGuest(
                $booking,
                $validated['reason'],
                $validated['detailed_reason'] ?? null,
            );

            return redirect()
                ->route('bookings.show', $booking)
                ->with(
                    'success',
                    'Votre réservation a été annulée. '.
                    (
                        $cancellation->refund_amount > 0
                        ? 'Un remboursement de '.number_format($cancellation->refund_amount, 0, ',', ' ').' FCFA sera traité.'
                        : ''
                    ),
                );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel booking (by owner)
     */
    public function cancelAsOwner(Request $request, Booking $booking)
    {
        $user = auth()->user();

        if ($booking->residence->owner_id !== $user->id) {
            abort(403, 'Non autorisé');
        }

        $validated = $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(Cancellation::getOwnerReasons())),
            'detailed_reason' => 'nullable|string|max:1000',
        ]);

        try {
            $cancellation = $this->cancellationService->cancelByOwner(
                $booking,
                $validated['reason'],
                $validated['detailed_reason'] ?? null,
            );

            $message = 'La réservation a été annulée.';
            if ($cancellation->penalty_amount > 0) {
                $message .= ' Une pénalité de '.number_format($cancellation->penalty_amount, 0, ',', ' ').' FCFA sera appliquée.';
            }

            return redirect()
                ->route('owner.bookings.show', $booking)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show user's cancellation history
     */
    public function history()
    {
        $user = auth()->user();

        $cancellations = Cancellation::whereHas('booking', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->with(['booking.residence', 'refunds'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = $this->cancellationService->getGuestStats($user->id);

        return view('cancellations.history', compact('cancellations', 'stats'));
    }

    /**
     * Show cancellation details
     */
    public function show(Cancellation $cancellation)
    {
        $user = auth()->user();

        // Check authorization
        $isGuest = $cancellation->booking->user_id === $user->id;
        $isOwner = $cancellation->booking->residence->owner_id === $user->id;

        if (!$isGuest && !$isOwner && !$user->isAdmin()) {
            abort(403, 'Non autorisé');
        }

        $cancellation->load(['booking.residence', 'refunds', 'disputes']);

        return view('cancellations.show', compact('cancellation', 'isGuest', 'isOwner'));
    }

    // ===== OWNER SECTION =====

    /**
     * Show owner's cancellations dashboard
     */
    public function ownerIndex()
    {
        $user = auth()->user();

        $cancellations = Cancellation::whereHas('booking.residence', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
            ->with(['booking.residence', 'booking.user'])
            ->orderByDesc('created_at')
            ->paginate(10);

        $stats = $this->cancellationService->getOwnerStats($user->id);

        return view('owner.cancellations.index', compact('cancellations', 'stats'));
    }

    /**
     * Update residence cancellation policy
     */
    public function updateResidencePolicy(Request $request)
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'cancellation_policy_id' => 'required|exists:cancellation_policies,id',
        ]);

        $residence = \App\Models\Residence::findOrFail($validated['residence_id']);

        if ($residence->owner_id !== auth()->id()) {
            abort(403);
        }

        $residence->update(['cancellation_policy_id' => $validated['cancellation_policy_id']]);

        return back()->with('success', 'Politique d\'annulation mise à jour.');
    }

    // ===== API ENDPOINTS =====

    /**
     * API: Preview cancellation
     */
    public function apiPreview(Booking $booking)
    {
        $user = auth()->user();
        $isOwner = $booking->residence->owner_id === $user->id;
        $isGuest = $booking->user_id === $user->id;

        if (!$isOwner && !$isGuest) {
            abort(403);
        }

        $cancelledBy = $isOwner ? 'owner' : 'user';

        return response()->json(
            $this->cancellationService->previewCancellation($booking, $cancelledBy),
        );
    }

    /**
     * API: Get cancellation reasons
     */
    public function apiReasons(Request $request)
    {
        $type = $request->get('type', 'guest');

        $reasons = match($type) {
            'owner' => Cancellation::getOwnerReasons(),
            'admin' => Cancellation::getAdminReasons(),
            default => Cancellation::getGuestReasons(),
        };

        return response()->json($reasons);
    }
}
