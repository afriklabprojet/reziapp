<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookingInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'insurance_plan_id',
        'user_id',
        'premium_amount',
        'coverage_amount',
        'status',
        'policy_number',
        'coverage_start',
        'coverage_end',
        'covered_items',
        'payment_reference',
        'metadata',
    ];

    protected $casts = [
        'premium_amount' => 'decimal:2',
        'coverage_amount' => 'decimal:2',
        'coverage_start' => 'datetime',
        'coverage_end' => 'datetime',
        'covered_items' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($insurance) {
            if (empty($insurance->policy_number)) {
                $insurance->policy_number = 'POL-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    /**
     * La réservation associée
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Le plan d'assurance
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    /**
     * L'utilisateur (le voyageur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Les réclamations
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }

    /**
     * Assurances actives
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('coverage_end', '>', now());
    }

    /**
     * Vérifier si l'assurance est active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->coverage_end > now();
    }

    /**
     * Vérifier si l'assurance peut être réclamée
     */
    public function canFileClaim(): bool
    {
        return $this->isActive() || 
               ($this->status === 'active' && $this->coverage_end->addDays(30) > now());
    }

    /**
     * Montant restant de couverture
     */
    public function remainingCoverage(): float
    {
        $claimedAmount = $this->claims()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('approved_amount');

        return max(0, $this->coverage_amount - $claimedAmount);
    }

    /**
     * Créer une assurance pour une réservation
     */
    public static function createForBooking(Booking $booking, InsurancePlan $plan): self
    {
        $bookingAmount = $booking->total_price;

        return self::create([
            'booking_id' => $booking->id,
            'insurance_plan_id' => $plan->id,
            'user_id' => $booking->user_id,
            'premium_amount' => $plan->calculatePremium($bookingAmount),
            'coverage_amount' => $plan->calculateCoverage($bookingAmount),
            'status' => 'active',
            'coverage_start' => $booking->check_in,
            'coverage_end' => $booking->check_out->addDays(7), // 7 jours après le départ
            'covered_items' => [
                'booking_amount' => $bookingAmount,
                'residence_id' => $booking->residence_id,
                'coverage_types' => $plan->coverage_types,
            ],
        ]);
    }
}
