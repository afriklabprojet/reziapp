<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\DigitalCheckin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DigitalCheckinController extends Controller
{
    public function show(Booking $booking): View
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        $checkin = DigitalCheckin::where('booking_id', $booking->id)
            ->where('guest_id', Auth::id())
            ->where('type', DigitalCheckin::TYPE_CHECK_IN)
            ->latest()
            ->firstOrFail();

        $booking->load(['residence.photos', 'residence.owner']);

        return view('bookings.checkin', compact('booking', 'checkin'));
    }

    public function verify(string $token): View
    {
        $checkin = DigitalCheckin::where('qr_token', $token)->firstOrFail();
        $checkin->load(['booking.user', 'booking.residence']);

        return view('bookings.checkin-verify', compact('checkin'));
    }

    public function confirm(string $token): RedirectResponse
    {
        $checkin = DigitalCheckin::where('qr_token', $token)->firstOrFail();

        $residence = $checkin->booking?->residence;
        if (! $residence || $residence->owner_id !== Auth::id()) {
            abort(403);
        }

        if (($checkin->booking?->status ?? '') !== 'confirmed') {
            abort(422, 'Le check-in ne peut être confirmé que pour une réservation confirmée.');
        }

        $checkin->update([
            'status'       => DigitalCheckin::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => Auth::id(),
        ]);

        return redirect()
            ->route('checkin.verify', $token)
            ->with('success', 'Check-in confirmé avec succès.');
    }
}
