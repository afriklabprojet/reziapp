<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtilityAlert extends Model
{
    public const ALERT_HIGH_CONSUMPTION   = 'high_consumption';
    public const ALERT_ABNORMAL_SPIKE     = 'abnormal_spike';
    public const ALERT_THRESHOLD_EXCEEDED = 'threshold_exceeded';

    public const STATUS_ACTIVE       = 'active';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_RESOLVED     = 'resolved';

    protected $fillable = [
        'residence_id', 'user_id', 'utility_type', 'alert_type',
        'threshold_value', 'current_value', 'status', 'message',
        'triggered_at', 'acknowledged_at',
    ];

    protected $casts = [
        'threshold_value' => 'decimal:2',
        'current_value'   => 'decimal:2',
        'triggered_at'    => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acknowledge(): void
    {
        $this->update([
            'status'          => self::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
