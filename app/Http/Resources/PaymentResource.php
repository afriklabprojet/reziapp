<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource pour Payment — format mobile-optimisé.
 * Ne retourne JAMAIS de données sensibles (webhook secrets, internal IDs).
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'reference' => $this->reference,

            // Montant
            'amount' => (float) $this->amount,
            'currency' => $this->currency ?? 'XOF',
            'formatted_amount' => number_format((float) $this->amount, 0, ',', ' ').' FCFA',

            // Status
            'status' => $this->status,
            'status_label' => $this->status_label ?? $this->status,
            'is_completed' => (bool) ($this->status === 'completed'),
            'is_pending' => (bool) ($this->status === 'pending'),
            'is_failed' => (bool) in_array($this->status, ['failed', 'cancelled']),

            // Méthode
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod->type),
            'provider' => $this->whenLoaded('provider', fn () => $this->provider->code, 'jeko'),

            // Relations
            'booking' => $this->whenLoaded('booking', fn () => [
                'id' => $this->booking->id,
                'reference' => $this->booking->reference,
            ]),

            // Timestamps
            'paid_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
