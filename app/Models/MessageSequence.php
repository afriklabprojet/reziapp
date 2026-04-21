<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageSequence extends Model
{
    public const TRIGGER_BOOKING_CONFIRMED    = 'booking_confirmed';
    public const TRIGGER_CHECK_IN_APPROACHING = 'check_in_approaching';
    public const TRIGGER_POST_CHECKOUT        = 'post_checkout';
    public const TRIGGER_PRE_CHECKOUT         = 'pre_checkout';

    public const TRIGGERS = [
        self::TRIGGER_BOOKING_CONFIRMED    => 'Réservation confirmée',
        self::TRIGGER_CHECK_IN_APPROACHING => 'Arrivée approche',
        self::TRIGGER_POST_CHECKOUT        => 'Après le départ',
        self::TRIGGER_PRE_CHECKOUT         => 'Avant le départ',
    ];

    protected $fillable = [
        'user_id', 'residence_id', 'name', 'trigger_event', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(MessageSequenceStep::class)->orderBy('step_order');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MessageSequenceLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOwner($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTrigger($query, string $trigger)
    {
        return $query->where('trigger_event', $trigger);
    }

    public function getTriggerLabelAttribute(): string
    {
        return self::TRIGGERS[$this->trigger_event] ?? $this->trigger_event;
    }
}
