<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoHostActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'co_host_id',
        'residence_id',
        'action',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relation avec le co-hôte
     */
    public function coHost(): BelongsTo
    {
        return $this->belongsTo(CoHost::class);
    }

    /**
     * Relation avec la résidence
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Obtenir le libellé de l'action
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'price_updated' => 'Prix modifié',
            'calendar_updated' => 'Calendrier mis à jour',
            'booking_accepted' => 'Réservation acceptée',
            'booking_declined' => 'Réservation refusée',
            'message_sent' => 'Message envoyé',
            'listing_edited' => 'Annonce modifiée',
            'availability_changed' => 'Disponibilité modifiée',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Obtenir l'icône de l'action
     */
    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'price_updated' => '💰',
            'calendar_updated' => '📅',
            'booking_accepted' => '✅',
            'booking_declined' => '❌',
            'message_sent' => '💬',
            'listing_edited' => '✏️',
            'availability_changed' => '🔄',
            default => '📋',
        };
    }
}
