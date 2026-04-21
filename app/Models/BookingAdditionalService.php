<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAdditionalService extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'additional_service_id',
        'residence_additional_service_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'scheduled_at',
        'notes',
        'provider_notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'scheduled_at' => 'datetime',
    ];

    /**
     * La réservation
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Le service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class, 'additional_service_id');
    }

    /**
     * Le service de la résidence (si personnalisé)
     */
    public function residenceService(): BelongsTo
    {
        return $this->belongsTo(ResidenceAdditionalService::class, 'residence_additional_service_id');
    }

    /**
     * Services en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Services confirmés
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Confirmer le service
     */
    public function confirm(?string $providerNotes = null): bool
    {
        $this->update([
            'status' => 'confirmed',
            'provider_notes' => $providerNotes,
        ]);

        return true;
    }

    /**
     * Marquer comme livré
     */
    public function markAsDelivered(?string $providerNotes = null): bool
    {
        $this->update([
            'status' => 'delivered',
            'provider_notes' => $providerNotes ?? $this->provider_notes,
        ]);

        return true;
    }

    /**
     * Annuler
     */
    public function cancel(?string $reason = null): bool
    {
        $this->update([
            'status' => 'cancelled',
            'provider_notes' => $reason,
        ]);

        return true;
    }

    /**
     * Créer un service pour une réservation
     */
    public static function createForBooking(
        Booking $booking,
        AdditionalService $service,
        int $quantity = 1,
        ?float $customPrice = null,
        ?\DateTime $scheduledAt = null,
        ?string $notes = null,
    ): self {
        $unitPrice = $customPrice ?? $service->price;
        $totalPrice = $service->calculatePrice($quantity, $booking->nights, $booking->guests);

        if ($customPrice) {
            // Recalculer avec le prix personnalisé
            $totalPrice = match ($service->pricing_type) {
                'fixed' => $customPrice,
                'per_night' => $customPrice * $booking->nights,
                'per_guest' => $customPrice * $booking->guests,
                'per_item' => $customPrice * $quantity,
                default => $customPrice,
            };
        }

        return self::create([
            'booking_id' => $booking->id,
            'additional_service_id' => $service->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt,
            'notes' => $notes,
        ]);
    }

    /**
     * Labels des statuts
     */
    public static function getStatusLabels(): array
    {
        return [
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
        ];
    }
}
