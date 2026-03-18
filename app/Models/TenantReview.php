<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantReview extends Model
{
    protected $fillable = [
        'owner_id', 'tenant_id', 'booking_id', 'residence_id',
        'cleanliness_rating', 'respect_rules_rating', 'communication_rating', 'payment_rating',
        'overall_rating', 'comment', 'would_rent_again', 'is_public',
    ];

    protected $casts = [
        'cleanliness_rating'     => 'integer',
        'respect_rules_rating'   => 'integer',
        'communication_rating'   => 'integer',
        'payment_rating'         => 'integer',
        'overall_rating'         => 'integer',
        'would_rent_again' => 'boolean',
        'is_public'       => 'boolean',
    ];

    const RATING_DIMENSIONS = [
        'cleanliness_rating'   => 'Propreté',
        'respect_rules_rating' => 'Respect des règles',
        'communication_rating' => 'Communication',
        'payment_rating'       => 'Ponctualité paiement',
        'overall_rating'       => 'Note globale',
    ];

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // ===== ACCESSORS =====

    public function getAverageRatingAttribute(): float
    {
        return round(($this->cleanliness_rating + $this->respect_rules_rating +
            $this->communication_rating + $this->payment_rating + $this->overall_rating) / 5, 1);
    }

    public function getRatingStarsAttribute(): string
    {
        $rating = $this->average_rating;
        $full   = (int) floor($rating);
        $half   = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty  = 5 - $full - $half;

        return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePositive($query)
    {
        return $query->where('overall_rating', '>=', 4);
    }
}
