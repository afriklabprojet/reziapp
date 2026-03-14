<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SecurityDeposit extends Model
{
    use SoftDeletes;

    // ===== STATUTS =====
    const STATUS_PENDING        = 'pending';
    const STATUS_HELD           = 'held';
    const STATUS_PARTIAL_RETURN = 'partial_return';
    const STATUS_RETURNED       = 'returned';
    const STATUS_FORFEITED      = 'forfeited';
    const STATUS_DISPUTED       = 'disputed';

    protected $fillable = [
        'reference',
        'owner_id',
        'tenant_id',
        'residence_id',
        'booking_id',
        'lease_contract_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'returned_amount',
        'returned_at',
        'return_payment_method',
        'return_reference',
        'deduction_reasons',
        'deduction_items',
        'return_deadline',
        'receipt_path',
        'notes',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'returned_amount'  => 'decimal:2',
        'paid_at'          => 'datetime',
        'returned_at'      => 'datetime',
        'return_deadline'  => 'date',
        'deduction_items'  => 'array',
    ];

    // ===== BOOT =====

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $deposit) {
            if (empty($deposit->reference)) {
                $deposit->reference = self::generateReference();
            }

            // Délai légal CI: 30 jours pour restituer la caution
            if (empty($deposit->return_deadline)) {
                $deposit->return_deadline = now()->addDays(30);
            }
        });
    }

    // ===== ACCESSORS =====

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING        => 'En attente',
            self::STATUS_HELD           => 'Retenue',
            self::STATUS_PARTIAL_RETURN => 'Retour partiel',
            self::STATUS_RETURNED       => 'Restituée',
            self::STATUS_FORFEITED      => 'Confisquée',
            self::STATUS_DISPUTED       => 'En litige',
            default                     => 'Inconnu',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RETURNED       => 'green',
            self::STATUS_HELD           => 'blue',
            self::STATUS_PENDING        => 'yellow',
            self::STATUS_PARTIAL_RETURN => 'orange',
            self::STATUS_DISPUTED       => 'red',
            self::STATUS_FORFEITED      => 'gray',
            default                     => 'gray',
        };
    }

    public function getRetainedAmountAttribute(): float
    {
        return (float) $this->amount - (float) ($this->returned_amount ?? 0);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->return_deadline
            && $this->return_deadline->isPast()
            && ! in_array($this->status, [self::STATUS_RETURNED, self::STATUS_FORFEITED]);
    }

    // ===== RELATIONSHIPS =====

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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class);
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_HELD]);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('return_deadline')
            ->where('return_deadline', '<', now())
            ->whereNotIn('status', [self::STATUS_RETURNED, self::STATUS_FORFEITED]);
    }

    // ===== HELPERS =====

    public static function generateReference(): string
    {
        do {
            $ref = 'SD-' . now()->format('Y') . '-' . strtoupper(Str::random(6));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }
}
