<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Coupon extends Model
{
    protected $fillable = [
        'user_id',
        'residence_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'starts_at',
        'expires_at',
        'max_uses',
        'max_uses_per_user',
        'uses_count',
        'min_amount',
        'min_nights',
        'first_booking_only',
        'allowed_communes',
        'allowed_types',
        'allowed_user_ids',
        'scope',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'discount_value' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'first_booking_only' => 'boolean',
        'is_active' => 'boolean',
        'allowed_communes' => 'array',
        'allowed_types' => 'array',
        'allowed_user_ids' => 'array',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function uses(): HasMany
    {
        return $this->hasMany(CouponUse::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('uses_count', '<', 'max_uses');
            });
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    public function scopeGlobal($query)
    {
        return $query->where('scope', 'global');
    }

    // Helpers
    public static function generateCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->starts_at && $this->starts_at > now()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at < now()) {
            return false;
        }
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
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
        $userUses = $this->uses()->where('user_id', $user->id)->count();
        if ($userUses >= $this->max_uses_per_user) {
            return false;
        }

        // Vérifier si réservé à certains utilisateurs
        if ($this->allowed_user_ids && !in_array($user->id, $this->allowed_user_ids)) {
            return false;
        }

        // Vérifier première réservation uniquement
        if ($this->first_booking_only) {
            $hasBooking = \App\Models\Booking::where('user_id', $user->id)
                ->whereIn('status', ['confirmed', 'completed'])
                ->exists();
            if ($hasBooking) {
                return false;
            }
        }

        return true;
    }

    public function canBeUsedForResidence(Residence $residence): bool
    {
        // Si coupon spécifique à une résidence
        if ($this->residence_id && $this->residence_id !== $residence->id) {
            return false;
        }

        // Vérifier commune autorisée
        if ($this->allowed_communes && !in_array($residence->commune, $this->allowed_communes)) {
            return false;
        }

        // Vérifier type de résidence
        if ($this->allowed_types && !in_array($residence->type, $this->allowed_types)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        $discount = 0;

        // Vérifier montant minimum
        if ($this->min_amount && $amount < $this->min_amount) {
            return 0;
        }

        switch ($this->discount_type) {
            case 'percentage':
                $discount = $amount * ($this->discount_value / 100);
                if ($this->max_discount) {
                    $discount = min($discount, $this->max_discount);
                }
                break;

            case 'fixed':
                $discount = min($this->discount_value, $amount);
                break;
        }

        return round($discount, 2);
    }

    public function getDiscountLabelAttribute(): string
    {
        if ($this->discount_type === 'percentage') {
            $label = '-'.intval($this->discount_value).'%';
            if ($this->max_discount) {
                $label .= ' (max '.number_format($this->max_discount, 0, ',', ' ').' F)';
            }

            return $label;
        }

        return '-'.number_format($this->discount_value, 0, ',', ' ').' F';
    }

    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactif';
        }
        if ($this->expires_at && $this->expires_at < now()) {
            return 'Expiré';
        }
        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return 'Épuisé';
        }
        if ($this->starts_at && $this->starts_at > now()) {
            return 'Programmé';
        }

        return 'Actif';
    }

    public function recordUse(User $user, ?Contact $contact = null, float $discountApplied = 0): void
    {
        $this->uses()->create([
            'user_id' => $user->id,
            'contact_id' => $contact?->id,
            'discount_applied' => $discountApplied,
        ]);

        $this->increment('uses_count');
    }
}
