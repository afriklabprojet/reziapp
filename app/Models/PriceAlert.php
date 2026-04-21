<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'original_price',
        'target_price',
        'current_price',
        'price_change',
        'alert_type',
        'is_active',
        'last_notified_at',
        'notification_count',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'target_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'price_change' => 'decimal:2',
        'is_active' => 'boolean',
        'last_notified_at' => 'datetime',
        'notification_count' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDecreaseOnly($query)
    {
        return $query->where('alert_type', 'decrease_only');
    }

    public function scopeNeedsNotification($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                // Haven't been notified in the last 24 hours
                $q->whereNull('last_notified_at')
                    ->orWhere('last_notified_at', '<', now()->subDay());
            });
    }

    // Methods
    public function updatePrice(float $newPrice): bool
    {
        $oldPrice = $this->current_price;
        $this->current_price = $newPrice;
        $this->price_change = $newPrice - $this->original_price;
        $this->save();

        return $this->shouldNotify($oldPrice, $newPrice);
    }

    public function shouldNotify(float $oldPrice, float $newPrice): bool
    {
        if (!$this->is_active) {
            return false;
        }

        switch ($this->alert_type) {
            case 'any_change':
                return $oldPrice !== $newPrice;

            case 'decrease_only':
                return $newPrice < $oldPrice;

            case 'target_reached':
                return $this->target_price !== null && $newPrice <= $this->target_price;

            default:
                return false;
        }
    }

    public function markNotified(): void
    {
        $this->update([
            'last_notified_at' => now(),
            'notification_count' => $this->notification_count + 1,
        ]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getPriceChangePercentage(): float
    {
        if ($this->original_price == 0) {
            return 0;
        }

        return round(($this->price_change / $this->original_price) * 100, 1);
    }

    public function hasPriceDropped(): bool
    {
        return $this->price_change < 0;
    }

    public function getPriceDropAmount(): float
    {
        return abs(min(0, $this->price_change));
    }
}
