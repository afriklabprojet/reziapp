<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProvider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'logo',
        'description',
        'supported_countries',
        'supported_currencies',
        'min_amount',
        'max_amount',
        'fee_percentage',
        'fee_fixed',
        'api_config',
        'is_active',
        'is_sandbox',
        'display_order',
    ];

    protected $casts = [
        'supported_countries' => 'array',
        'supported_currencies' => 'array',
        'api_config' => 'encrypted:array',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'is_active' => 'boolean',
        'is_sandbox' => 'boolean',
    ];

    // ===== RELATIONSHIPS =====

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    // ===== SCOPES =====

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCountry($query, string $countryCode)
    {
        return $query->whereJsonContains('supported_countries', strtoupper($countryCode));
    }

    public function scopeForCurrency($query, string $currencyCode)
    {
        return $query->whereJsonContains('supported_currencies', strtoupper($currencyCode));
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    // ===== METHODS =====

    /**
     * Calculer les frais pour un montant donné
     */
    public function calculateFees(float $amount): array
    {
        $percentageFee = $amount * ($this->fee_percentage / 100);
        $totalFee = $percentageFee + $this->fee_fixed;

        return [
            'amount' => $amount,
            'percentage_fee' => round($percentageFee, 2),
            'fixed_fee' => $this->fee_fixed,
            'total_fee' => round($totalFee, 2),
            'total_amount' => round($amount + $totalFee, 2),
        ];
    }

    /**
     * Vérifier si le montant est dans les limites
     */
    public function isAmountValid(float $amount): bool
    {
        return $amount >= $this->min_amount && $amount <= $this->max_amount;
    }

    /**
     * Vérifier si le pays est supporté
     */
    public function supportsCountry(string $countryCode): bool
    {
        return in_array(strtoupper($countryCode), $this->supported_countries ?? []);
    }

    /**
     * Vérifier si la devise est supportée
     */
    public function supportsCurrency(string $currencyCode): bool
    {
        return in_array(strtoupper($currencyCode), $this->supported_currencies ?? []);
    }

    /**
     * Obtenir la configuration API
     */
    public function getApiKey(): ?string
    {
        return $this->api_config['api_key'] ?? null;
    }

    public function getApiSecret(): ?string
    {
        return $this->api_config['api_secret'] ?? null;
    }

    public function getApiEndpoint(): string
    {
        if ($this->is_sandbox) {
            return $this->api_config['sandbox_url'] ?? $this->api_config['base_url'] ?? '';
        }

        return $this->api_config['production_url'] ?? $this->api_config['base_url'] ?? '';
    }

    // ===== STATIC METHODS =====

    public static function getJeko(): ?self
    {
        return static::where('code', 'jeko')->first();
    }

    public static function getActiveForCI(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->forCountry('CI')
            ->forCurrency('XOF')
            ->ordered()
            ->get();
    }
}
