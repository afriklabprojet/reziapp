<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RentReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'owner_id',
        'tenant_id',
        'residence_id',
        'booking_id',
        'lease_contract_id',
        'period_start',
        'period_end',
        'rent_amount',
        'charges_amount',
        'total_amount',
        'currency',
        'payment_method',
        'payment_reference',
        'payment_date',
        'is_paid',
        'pdf_path',
        'pdf_generated_at',
        'sent_by_email',
        'sent_by_whatsapp',
        'sent_at',
        'charges_detail',
        'notes',
    ];

    protected $casts = [
        'period_start'      => 'date',
        'period_end'        => 'date',
        'payment_date'      => 'date',
        'pdf_generated_at'  => 'datetime',
        'sent_at'           => 'datetime',
        'rent_amount'       => 'decimal:2',
        'charges_amount'    => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'is_paid'           => 'boolean',
        'sent_by_email'     => 'boolean',
        'sent_by_whatsapp'  => 'boolean',
        'charges_detail'    => 'array',
    ];

    // ===== BOOT =====

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $receipt) {
            if (empty($receipt->reference)) {
                $receipt->reference = self::generateReference();
            }
        });
    }

    // ===== ACCESSORS =====

    public function getPeriodLabelAttribute(): string
    {
        if ($this->period_start->format('Y-m') === $this->period_end->format('Y-m')) {
            return $this->period_start->translatedFormat('F Y');
        }

        return $this->period_start->translatedFormat('d/m/Y') . ' – ' . $this->period_end->translatedFormat('d/m/Y');
    }

    public function getHasPdfAttribute(): bool
    {
        return ! empty($this->pdf_path);
    }

    public function getWasSentAttribute(): bool
    {
        return $this->sent_by_email || $this->sent_by_whatsapp;
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

    public function scopeForPeriod($query, int $year, ?int $month = null)
    {
        $query->whereYear('period_start', $year);
        if ($month) {
            $query->whereMonth('period_start', $month);
        }

        return $query;
    }

    // ===== HELPERS =====

    public static function generateReference(): string
    {
        $year  = now()->format('Y');
        $month = now()->format('m');
        $count = self::whereYear('created_at', $year)->whereMonth('created_at', $month)->count() + 1;

        return sprintf('QUITT-%s%s-%04d', $year, $month, $count);
    }

    public function markAsSent(string $channel): void
    {
        $this->update([
            "sent_by_{$channel}" => true,
            'sent_at'            => now(),
        ]);
    }
}
