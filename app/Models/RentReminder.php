<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentReminder extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_SENT     = 'sent';
    public const STATUS_PAID     = 'paid';
    public const STATUS_OVERDUE  = 'overdue';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_SENT    => 'Envoyé',
        self::STATUS_PAID    => 'Payé',
        self::STATUS_OVERDUE => 'En retard',
    ];

    public const LEVEL_NONE      = 'none';
    public const LEVEL_J5        = 'j5';
    public const LEVEL_J3        = 'j3';
    public const LEVEL_J1        = 'j1';
    public const LEVEL_OVERDUE   = 'overdue';
    public const LEVEL_ESCALATED = 'escalated';

    public const CHANNELS = ['email', 'sms', 'whatsapp'];

    protected $fillable = [
        'owner_id', 'tenant_id', 'residence_id', 'lease_contract_id',
        'amount_due', 'currency', 'due_date', 'paid_date', 'status',
        'reminder_level', 'last_reminder_at', 'reminder_count',
        'channel', 'notes',
    ];

    protected $casts = [
        'amount_due'       => 'decimal:2',
        'due_date'         => 'date',
        'paid_date'        => 'date',
        'last_reminder_at' => 'datetime',
        'reminder_count'   => 'integer',
    ];

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class);
    }

    // ===== SCOPES =====

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
                     ->where('due_date', '<', now());
    }

    public function scopeUpcoming($query, int $days = 5)
    {
        return $query->where('status', self::STATUS_PENDING)
                     ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    // ===== HELPERS =====

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isOverdue(): bool
    {
        return !$this->isPaid() && $this->due_date->isPast();
    }

    public function daysUntilDue(): int
    {
        return (int) now()->diffInDays($this->due_date, false);
    }

    public function markPaid(): void
    {
        $this->update([
            'status'    => self::STATUS_PAID,
            'paid_date' => now()->toDateString(),
        ]);
    }
}
