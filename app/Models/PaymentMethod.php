<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'payment_provider_id',
        'type',
        'label',
        'phone_number',
        'phone_country_code',
        'card_last_four',
        'card_brand',
        'card_exp_month',
        'card_exp_year',
        'bank_name',
        'account_number_masked',
        'token',
        'is_default',
        'is_verified',
        'verified_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    // ===== CONSTANTS =====

    public const TYPE_MOBILE_MONEY = 'mobile_money';
    public const TYPE_CARD = 'card';
    public const TYPE_BANK_TRANSFER = 'bank_transfer';

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ===== SCOPES =====

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeMobileMoney($query)
    {
        return $query->where('type', self::TYPE_MOBILE_MONEY);
    }

    public function scopeCards($query)
    {
        return $query->where('type', self::TYPE_CARD);
    }

    // ===== ACCESSORS =====

    /**
     * Obtenir le numéro de téléphone complet
     */
    public function getFullPhoneNumberAttribute(): ?string
    {
        if (!$this->phone_number) {
            return null;
        }

        return $this->phone_country_code.$this->phone_number;
    }

    /**
     * Obtenir le numéro masqué pour affichage
     */
    public function getMaskedPhoneAttribute(): ?string
    {
        if (!$this->phone_number) {
            return null;
        }
        $length = strlen($this->phone_number);
        if ($length <= 4) {
            return $this->phone_number;
        }

        return str_repeat('•', $length - 4).substr($this->phone_number, -4);
    }

    /**
     * Obtenir le nom d'affichage
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->label) {
            return $this->label;
        }

        if ($this->type === self::TYPE_MOBILE_MONEY) {
            return ($this->provider->name ?? 'Mobile Money').' '.$this->masked_phone;
        }

        if ($this->type === self::TYPE_CARD) {
            return ($this->card_brand ?? 'Carte').' •••• '.$this->card_last_four;
        }

        if ($this->type === self::TYPE_BANK_TRANSFER) {
            return ($this->bank_name ?? 'Virement').' '.$this->account_number_masked;
        }

        return 'Méthode de paiement';
    }

    /**
     * Obtenir l'icône appropriée
     */
    public function getIconAttribute(): string
    {
        return match ($this->provider?->code) {
            'orange_money' => 'orange-money',
            'mtn_momo' => 'mtn-momo',
            'wave' => 'wave',
            'moov_money' => 'moov-money',
            default => match ($this->type) {
                self::TYPE_MOBILE_MONEY => 'mobile',
                self::TYPE_CARD => 'credit-card',
                self::TYPE_BANK_TRANSFER => 'bank',
                default => 'wallet',
            },
        };
    }

    // ===== METHODS =====

    /**
     * Définir comme méthode par défaut
     */
    public function setAsDefault(): void
    {
        // Retirer le défaut des autres méthodes
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Marquer comme utilisée
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Marquer comme vérifiée
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Vérifier si la méthode peut être utilisée
     */
    public function canBeUsed(): bool
    {
        return $this->is_verified && $this->provider?->is_active;
    }

    /**
     * Vérifier si c'est du Mobile Money
     */
    public function isMobileMoney(): bool
    {
        return $this->type === self::TYPE_MOBILE_MONEY;
    }

    /**
     * Vérifier si c'est une carte
     */
    public function isCard(): bool
    {
        return $this->type === self::TYPE_CARD;
    }
}
