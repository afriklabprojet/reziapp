<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour Booking — format mobile-optimisé.
 */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user() && (
            $request->user()->id === $this->residence?->owner_id ||
            $request->user()->role === 'admin'
        );

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reference' => $this->reference,

            // Dates
            'check_in' => $this->check_in?->format('Y-m-d'),
            'check_out' => $this->check_out?->format('Y-m-d'),
            'nights' => $this->nights,

            // Guests
            'guests' => $this->guests,
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,

            // Pricing
            'pricing' => [
                'price_per_night' => (float) $this->price_per_night,
                'subtotal' => (float) $this->subtotal,
                'cleaning_fee' => (float) ($this->cleaning_fee ?? 0),
                'service_fee' => (float) ($this->service_fee ?? 0),
                'discount' => (float) ($this->discount_amount ?? 0),
                'total' => (float) $this->total_amount,
                'currency' => $this->currency ?? 'XOF',
                'formatted_total' => number_format((float) $this->total_amount, 0, ',', ' ') . ' FCFA',
            ],

            // Status
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'booking_type' => $this->booking_type,

            // Relations
            'residence' => $this->whenLoaded('residence', fn () => [
                'id' => $this->residence->id,
                'name' => $this->residence->name,
                'address' => $this->residence->address,
                'commune' => $this->residence->commune,
                'thumbnail' => $this->residence->photos->first() 
                    ? url('storage/' . $this->residence->photos->first()->path)
                    : null,
            ]),

            'guest' => $this->when($isOwner, fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'phone' => $this->user?->phone,
            ]),

            // Messages
            'guest_message' => $this->guest_message,
            'special_requests' => $this->special_requests,

            // Timestamps
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
