<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantReview extends Model
{
    protected $fillable = [
        'owner_id', 'tenant_id', 'booking_id', 'residence_id',
        'cleanliness', 'respect_rules', 'communication', 'payment',
        'overall', 'comment', 'would_rent_again', 'is_public',
    ];

    protected $casts = [
        'cleanliness'     => 'integer',
        'respect_rules'   => 'integer',
        'communication'   => 'integer',
        'payment'         => 'integer',
        'overall'         => 'integer',
        'would_rent_again' => 'boolean',
        'is_public'       => 'boolean',
    ];

    const RATING_DIMENSIONS = [
        'cleanliness'   => 'Propreté',
        'respect_rules' => 'Respect des règles',
        'communication' => 'Communication',
        'payment'       => 'Ponctualité paiement',
        'overall'       => 'Note globale',
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
        return round(($this->cleanliness + $this->respect_rules +
            $this->communication + $this->payment + $this->overall) / 5, 1);
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
        return $query->where('overall', '>=', 4);
    }
}
