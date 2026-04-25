<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingModification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingModificationController extends Controller
{
    /**
     * Formulaire de demande de modification (côté client).
     */
    public function create(Booking $booking): View
    {
        abort_unless($booking->user_id === Auth::id(), 403);
        abort_if(in_array($booking->status, ['cancelled', 'completed', 'rejected'], true), 403, 'Cette réservation ne peut plus être modifiée.');

        $pending = $booking->modifications()->where('status', 'pending')->first();

        return view('bookings.modify', compact('booking', 'pending'));
    }

    /**
     * Enregistre la demande de modification.
     */
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === Auth::id(), 403);

        $validated = $request->validate([
            'requested_check_in' => ['required', 'date', 'after_or_equal:today'],
            'requested_check_out' => ['required', 'date', 'after:requested_check_in'],
            'requested_guests' => ['required', 'integer', 'min:1', 'max:'.($booking->residence->max_guests ?? 20)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Refuser si une demande pending existe déjà
        if ($booking->modifications()->where('status', 'pending')->exists()) {
            return back()->withErrors(['error' => 'Une demande de modification est déjà en attente.']);
        }

        // Calcul rapide du delta de prix
        $newNights = \Carbon\Carbon::parse($validated['requested_check_in'])
            ->diffInDays(\Carbon\Carbon::parse($validated['requested_check_out']));
        $newSubtotal = $newNights * (float) $booking->price_per_night;
        $priceDiff = $newSubtotal - (float) $booking->subtotal;

        BookingModification::create([
            'booking_id' => $booking->id,
            'requested_by_user_id' => Auth::id(),
            'original_check_in' => $booking->check_in,
            'original_check_out' => $booking->check_out,
            'original_guests' => $booking->guests,
            'requested_check_in' => $validated['requested_check_in'],
            'requested_check_out' => $validated['requested_check_out'],
            'requested_guests' => $validated['requested_guests'],
            'price_diff' => $priceDiff,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Demande de modification envoyée au propriétaire.');
    }

    /**
     * Le propriétaire approuve la demande.
     */
    public function approve(BookingModification $modification): RedirectResponse
    {
        $booking = $modification->booking;
        abort_unless($booking->residence->owner_id === Auth::id(), 403);
        abort_unless($modification->isPending(), 422, 'Demande déjà traitée.');

        DB::transaction(function () use ($booking, $modification) {
            // Calcul du nouveau total
            $newNights = $modification->requested_check_in->diffInDays($modification->requested_check_out);
            $booking->update([
                'check_in' => $modification->requested_check_in,
                'check_out' => $modification->requested_check_out,
                'guests' => $modification->requested_guests,
                'nights' => $newNights,
                'subtotal' => (float) $booking->price_per_night * $newNights,
                'total_amount' => (float) $booking->total_amount + (float) $modification->price_diff,
            ]);

            $modification->update([
                'status' => 'approved',
                'responded_at' => now(),
            ]);
        });

        return back()->with('success', 'Modification appliquée et réservation mise à jour.');
    }

    /**
     * Le propriétaire rejette la demande.
     */
    public function reject(Request $request, BookingModification $modification): RedirectResponse
    {
        $booking = $modification->booking;
        abort_unless($booking->residence->owner_id === Auth::id(), 403);
        abort_unless($modification->isPending(), 422);

        $validated = $request->validate([
            'owner_response' => ['nullable', 'string', 'max:1000'],
        ]);

        $modification->update([
            'status' => 'rejected',
            'owner_response' => $validated['owner_response'] ?? null,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Demande de modification refusée.');
    }
}
