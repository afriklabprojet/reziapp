<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DigitalCheckin extends Model
{
    public const TYPE_CHECK_IN  = 'check_in';
    public const TYPE_CHECK_OUT = 'check_out';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'booking_id', 'residence_id', 'guest_id', 'type',
        'qr_token', 'status', 'confirmed_at', 'confirmed_by',
        'arrival_instructions', 'latitude', 'longitude',
        'notes', 'photos',
    ];

    protected $casts = [
        'arrival_instructions' => 'array',
        'photos'               => 'array',
        'confirmed_at'         => 'datetime',
        'latitude'             => 'decimal:7',
        'longitude'            => 'decimal:7',
    ];

    // ===== BOOT =====

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (!$model->qr_token) {
                $model->qr_token = Str::random(64);
            }
        });
    }

    // ===== RELATIONS =====

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_id');
    }

    // ===== HELPERS =====

    public function isCheckIn(): bool
    {
        return $this->type === self::TYPE_CHECK_IN;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function confirm(string $by = 'guest'): void
    {
        $this->update([
            'status'       => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $by,
        ]);
    }

    public function getQrCodeUrlAttribute(): string
    {
        return route('checkin.verify', $this->qr_token);
    }
}
