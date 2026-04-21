<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    public const CAT_WATER       = 'water';
    public const CAT_ELECTRICITY = 'electricity';
    public const CAT_MAINTENANCE = 'maintenance';
    public const CAT_TAX         = 'tax';
    public const CAT_INSURANCE   = 'insurance';
    public const CAT_CLEANING    = 'cleaning';
    public const CAT_OTHER       = 'other';

    public const CATEGORIES = [
        self::CAT_WATER       => 'Eau',
        self::CAT_ELECTRICITY => 'Électricité',
        self::CAT_MAINTENANCE => 'Entretien',
        self::CAT_TAX         => 'Taxe / Impôt',
        self::CAT_INSURANCE   => 'Assurance',
        self::CAT_CLEANING    => 'Ménage',
        self::CAT_OTHER       => 'Autre',
    ];

    public const FREQUENCIES = [
        'monthly'   => 'Mensuel',
        'quarterly' => 'Trimestriel',
        'yearly'    => 'Annuel',
    ];

    protected $fillable = [
        'owner_id', 'residence_id', 'category', 'label', 'amount',
        'currency', 'expense_date', 'receipt_path', 'notes',
        'is_recurring', 'recurring_frequency',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
        'is_recurring' => 'boolean',
    ];

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // ===== ACCESSORS =====

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ').' FCFA';
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('expense_date', [$start, $end]);
    }
}
