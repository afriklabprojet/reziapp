<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LongStayDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'min_nights',
        'discount_percent',
        'is_active',
    ];

    protected $casts = [
        'min_nights' => 'integer',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    public function scopeApplicableToNights($query, int $nights)
    {
        return $query->where('min_nights', '<=', $nights);
    }

    // Methods
    public static function getApplicableDiscount(int $residenceId, int $nights): ?self
    {
        return self::forResidence($residenceId)
            ->active()
            ->applicableToNights($nights)
            ->orderBy('min_nights', 'desc')
            ->first();
    }

    public function calculateDiscount(float $amount): float
    {
        return round($amount * ($this->discount_percent / 100), 0);
    }

    // Helpers
    public function getLabel(): string
    {
        $weeks = floor($this->min_nights / 7);
        $months = floor($this->min_nights / 30);

        if ($months >= 1) {
            return $months.' mois+';
        }

        if ($weeks >= 1) {
            return $weeks.' semaine'.($weeks > 1 ? 's' : '').'+';
        }

        return $this->min_nights.' nuits+';
    }
}
