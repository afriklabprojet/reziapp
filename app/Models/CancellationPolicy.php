<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'refund_rules',
        'service_fee_refundable_percent',
        'owner_cancellation_refund_percent',
        'owner_cancellation_penalty_percent',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'refund_rules' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'service_fee_refundable_percent' => 'decimal:2',
        'owner_cancellation_refund_percent' => 'decimal:2',
        'owner_cancellation_penalty_percent' => 'decimal:2',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Residences using this policy
     */
    public function residences()
    {
        return $this->hasMany(Residence::class);
    }

    // ===== SCOPES =====

    /**
     * Active policies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ===== METHODS =====

    /**
     * Get refund percentage for given hours before check-in
     */
    public function getRefundPercentage(int $hoursBeforeCheckin): int
    {
        $rules = $this->refund_rules ?? [];
        $daysBeforeCheckin = $hoursBeforeCheckin / 24;

        // Sort by days_before descending to find the right tier
        usort($rules, fn ($a, $b) => ($b['days_before'] ?? 0) <=> ($a['days_before'] ?? 0));

        foreach ($rules as $rule) {
            $days = $rule['days_before'] ?? 0;
            $percentage = $rule['refund_percent'] ?? 0;
            if ($daysBeforeCheckin >= $days) {
                return (int) $percentage;
            }
        }

        return 0;
    }

    /**
     * Calculate refund amount
     */
    public function calculateRefund(float $totalAmount, int $hoursBeforeCheckin): float
    {
        $percentage = $this->getRefundPercentage($hoursBeforeCheckin);

        return round($totalAmount * ($percentage / 100), 2);
    }

    /**
     * Calculate penalty amount for owner cancellation
     */
    public function calculateOwnerPenalty(float $totalAmount, int $hoursBeforeCheckin): float
    {
        // Owners pay penalty based on short notice cancellations
        if ($hoursBeforeCheckin < 24) {
            return round($totalAmount * 0.20, 2); // 20% penalty for last minute
        } elseif ($hoursBeforeCheckin < 72) {
            return round($totalAmount * 0.10, 2); // 10% for less than 3 days
        }

        return 0;
    }

    /**
     * Get policy badge/label
     */
    public function getBadgeAttribute(): string
    {
        return match($this->name) {
            'flexible' => 'Annulation gratuite',
            'moderate' => 'Conditions modérées',
            'strict' => 'Conditions strictes',
            default => $this->display_name ?? $this->name,
        };
    }

    /**
     * Get policy color for UI
     */
    public function getColorAttribute(): string
    {
        return match($this->name) {
            'flexible' => 'green',
            'moderate' => 'yellow',
            'strict' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted description for guests
     */
    public function getFormattedDescriptionAttribute(): string
    {
        $lines = [];
        $rules = $this->refund_rules ?? [];

        // Sort by days_before descending so highest days come first
        usort($rules, fn ($a, $b) => ($b['days_before'] ?? 0) <=> ($a['days_before'] ?? 0));

        foreach ($rules as $rule) {
            $days = $rule['days_before'] ?? 0;
            $percentage = $rule['refund_percent'] ?? 0;

            if ($days == 0) {
                $lines[] = "Le jour de l'arrivée : {$percentage}% de remboursement";
            } elseif ($days == 1) {
                $lines[] = "Jusqu'à 24h avant : {$percentage}% de remboursement";
            } else {
                $lines[] = "Jusqu'à {$days} jours avant : {$percentage}% de remboursement";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Check if free cancellation is available for check-in date
     */
    public function hasFreeCancellation(\DateTime $checkinDate): bool
    {
        $hoursUntilCheckin = (new \DateTime())->diff($checkinDate)->h +
                            ((new \DateTime())->diff($checkinDate)->days * 24);

        // Check if there's a rule with 100% refund that applies
        $rules = $this->refund_rules ?? [];
        foreach ($rules as $rule) {
            $daysRequired = $rule['days_before'] ?? 0;
            $refundPercent = $rule['refund_percent'] ?? 0;
            if ($refundPercent >= 100 && $hoursUntilCheckin >= ($daysRequired * 24)) {
                return true;
            }
        }

        return false;
    }

    // ===== STATIC HELPERS =====

    /**
     * Get default policy
     */
    public static function getDefault(): ?self
    {
        return static::where('name', 'moderate')->first()
            ?? static::active()->first();
    }

    /**
     * Get flexible policy
     */
    public static function flexible(): ?self
    {
        return static::where('name', 'flexible')->first();
    }

    /**
     * Get moderate policy
     */
    public static function moderate(): ?self
    {
        return static::where('name', 'moderate')->first();
    }

    /**
     * Get strict policy
     */
    public static function strict(): ?self
    {
        return static::where('name', 'strict')->first();
    }
}
