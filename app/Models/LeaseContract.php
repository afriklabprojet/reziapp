<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeaseContract extends Model
{
    use SoftDeletes;

    // ===== STATUTS =====
    public const STATUS_DRAFT           = 'draft';
    public const STATUS_PENDING_TENANT  = 'pending_tenant';
    public const STATUS_PENDING_OWNER   = 'pending_owner';
    public const STATUS_ACTIVE          = 'active';
    public const STATUS_TERMINATED      = 'terminated';
    public const STATUS_EXPIRED         = 'expired';

    // ===== TYPES =====
    public const TYPE_SHORT_TERM = 'short_term';
    public const TYPE_MONTHLY    = 'monthly';
    public const TYPE_FIXED_TERM = 'fixed_term';

    protected $fillable = [
        'reference',
        'owner_id',
        'tenant_id',
        'residence_id',
        'booking_id',
        'start_date',
        'end_date',
        'lease_type',
        'monthly_rent',
        'deposit_amount',
        'charges_amount',
        'currency',
        'payment_day',
        'special_clauses',
        'included_services',
        'status',
        'owner_signed_at',
        'tenant_signed_at',
        'owner_signature_ip',
        'tenant_signature_ip',
        'pdf_path',
        'pdf_generated_at',
        'terminated_at',
        'termination_reason',
        'terminated_by',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'end_date'            => 'date',
        'terminated_at'       => 'date',
        'owner_signed_at'     => 'datetime',
        'tenant_signed_at'    => 'datetime',
        'pdf_generated_at'    => 'datetime',
        'monthly_rent'        => 'decimal:2',
        'deposit_amount'      => 'decimal:2',
        'charges_amount'      => 'decimal:2',
        'included_services'   => 'array',
        'payment_day'         => 'integer',
    ];

    // ===== BOOT =====

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $contract) {
            if (empty($contract->reference)) {
                $contract->reference = self::generateReference();
            }
        });
    }

    // ===== ACCESSORS =====

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsFullySignedAttribute(): bool
    {
        return $this->owner_signed_at !== null && $this->tenant_signed_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT          => 'Brouillon',
            self::STATUS_PENDING_TENANT => 'En attente signature locataire',
            self::STATUS_PENDING_OWNER  => 'En attente signature propriétaire',
            self::STATUS_ACTIVE         => 'Actif',
            self::STATUS_TERMINATED     => 'Résilié',
            self::STATUS_EXPIRED        => 'Expiré',
            default                     => 'Inconnu',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE         => 'green',
            self::STATUS_PENDING_TENANT,
            self::STATUS_PENDING_OWNER  => 'yellow',
            self::STATUS_TERMINATED,
            self::STATUS_EXPIRED        => 'red',
            default                     => 'gray',
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->lease_type) {
            self::TYPE_SHORT_TERM => 'Court terme',
            self::TYPE_MONTHLY    => 'Mensuel',
            self::TYPE_FIXED_TERM => 'Durée déterminée',
            default               => 'Inconnu',
        };
    }

    public function getDurationInMonthsAttribute(): ?int
    {
        if (! $this->end_date) {
            return null;
        }

        return (int) $this->start_date->diffInMonths($this->end_date);
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

    public function rentReceipts(): HasMany
    {
        return $this->hasMany(RentReceipt::class);
    }

    public function securityDeposit(): HasMany
    {
        return $this->hasMany(SecurityDeposit::class);
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePendingSignature($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING_TENANT, self::STATUS_PENDING_OWNER]);
    }

    // ===== HELPERS =====

    public static function generateReference(): string
    {
        do {
            $ref = 'LC-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    public function canBeSignedByOwner(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING_OWNER])
            && $this->owner_signed_at === null;
    }

    public function canBeSignedByTenant(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_TENANT])
            && $this->tenant_signed_at === null;
    }

    public function markAsActive(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }
}
