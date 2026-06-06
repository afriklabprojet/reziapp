<?php

declare(strict_types=1);

namespace App\Services\Bookings;

use App\Models\BookingRequest;
use App\Models\Residence;

class BookingNotificationService
{
    public function notifyOwnerAboutBookingRequest(Residence $residence, BookingRequest $request): void
    {
        $owner = $residence->owner;

        if (! $owner) {
            return;
        }

        $owner->notify(new \App\Notifications\BookingRequestReceived($request, $residence));

        \App\Models\Notification::send(
            $owner,
            'booking',
            'Nouvelle demande de réservation',
            ($request->user?->name ?? 'Un client').' souhaite réserver '.$residence->name,
            route('owner.bookings.requests'),
            ['booking_request_id' => $request->id, 'residence_id' => $residence->id],
        );
    }
}

