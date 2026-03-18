<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type', // percentage, fixed
        'value',
        'min_amount',
        'max_discount',
        'valid_from',
        'valid_until',
        'max_uses',
        'uses_count',
        'max_uses_per_user',
        'residence_id',
        'user_id',
        'first_booking_only',
        'min_nights',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
        'max_uses_per_user' => 'integer',
        'first_booking_only' => 'boolean',
        'min_nights' => 'integer',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    // Relations
    public function usages()
    {
        return $this->hasMany(PromoCodeUse::class, 'promo_code_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Methods
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function canBeUsedBy(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Vérifier limite par utilisateur
        if ($this->per_user_limit) {
            $userUsageCount = $this->usages()
                ->where('user_id', $user->id)
                ->count();

            if ($userUsageCount >= $this->per_user_limit) {
                return false;
            }
        }

        // Vérifier si réservé à certains utilisateurs
        if ($this->user_ids && !in_array($user->id, $this->user_ids)) {
            return false;
        }

        // Vérifier si première réservation seulement
        if ($this->first_booking_only) {
            $hasBooking = Booking::where('user_id', $user->id)
                ->where('status', 'completed')
                ->exists();

            if ($hasBooking) {
                return false;
            }
        }

        return true;
    }

    public function isApplicableToResidence(int $residenceId): bool
    {
        if (empty($this->residence_ids)) {
            return true;
        }

        return in_array($residenceId, $this->residence_ids);
    }

    public function isApplicableToNights(int $nights): bool
    {
        if (!$this->min_nights) {
            return true;
        }

        return $nights >= $this->min_nights;
    }

    public function isApplicableToAmount(float $amount): bool
    {
        if (!$this->min_amount) {
            return true;
        }

        return $amount >= $this->min_amount;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            $discount = $amount * ($this->value / 100);
        } else {
            $discount = $this->value;
        }

        // Appliquer le plafond
        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return round($discount, 0);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function recordUsage(User $user, Booking $booking): PromoCodeUse
    {
        $this->incrementUsage();

        return $this->usages()->create([
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'discount_amount' => $booking->promo_discount,
        ]);
    }

    // Helpers
    public function getFormattedValue(): string
    {
        if ($this->type === 'percentage') {
            return $this->value.'%';
        }

        return number_format($this->value, 0, ',', ' ').' FCFA';
    }

    public function getStatusBadge(): string
    {
        if (!$this->is_active) {
            return '<span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Inactif</span>';
        }

        if (!$this->isValid()) {
            return '<span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Expiré</span>';
        }

        return '<span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Actif</span>';
    }

    public function getRemainingUsages(): ?int
    {
        if (!$this->usage_limit) {
            return null;
        }

        return max(0, $this->usage_limit - $this->usage_count);
    }
}
