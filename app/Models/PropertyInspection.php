<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PropertyInspection extends Model
{
    use SoftDeletes;

    // ===== TYPES =====
    public const TYPE_CHECK_IN  = 'check_in';
    public const TYPE_CHECK_OUT = 'check_out';
    public const TYPE_PERIODIC  = 'periodic';

    // ===== STATUTS =====
    public const STATUS_DRAFT       = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_SIGNED      = 'signed';

    protected $fillable = [
        'reference',
        'owner_id',
        'tenant_id',
        'residence_id',
        'booking_id',
        'lease_contract_id',
        'type',
        'status',
        'inspection_date',
        'inspector_name',
        'tenant_present',
        'electricity_meter',
        'water_meter',
        'gas_meter',
        'keys_count',
        'keys_returned',
        'badges_count',
        'badges_returned',
        'global_observations',
        'estimated_repair_cost',
        'owner_signed_at',
        'tenant_signed_at',
        'owner_signature_ip',
        'tenant_signature_ip',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'inspection_date'       => 'datetime',
        'owner_signed_at'       => 'datetime',
        'tenant_signed_at'      => 'datetime',
        'pdf_generated_at'      => 'datetime',
        'tenant_present'        => 'boolean',
        'electricity_meter'     => 'decimal:2',
        'water_meter'           => 'decimal:2',
        'gas_meter'             => 'decimal:2',
        'estimated_repair_cost' => 'decimal:2',
    ];

    // ===== BOOT =====

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $inspection) {
            if (empty($inspection->reference)) {
                $inspection->reference = self::generateReference($inspection->type);
            }
        });
    }

    // ===== ACCESSORS =====

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_CHECK_IN  => 'État des lieux d\'entrée',
            self::TYPE_CHECK_OUT => 'État des lieux de sortie',
            self::TYPE_PERIODIC  => 'Visite périodique',
            default              => 'État des lieux',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT       => 'Brouillon',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_COMPLETED   => 'Complété',
            self::STATUS_SIGNED      => 'Signé',
            default                  => 'Inconnu',
        };
    }

    public function getIsFullySignedAttribute(): bool
    {
        return $this->owner_signed_at !== null && $this->tenant_signed_at !== null;
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getDamagedItemsCountAttribute(): int
    {
        return $this->items()->whereIn('condition', ['damaged', 'missing'])->count();
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

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class)->orderBy('sort_order');
    }

    public function itemsByRoom(): \Illuminate\Support\Collection
    {
        return $this->items->groupBy('room');
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ===== HELPERS =====

    public static function generateReference(string $type = 'check_in'): string
    {
        $prefix = match ($type) {
            self::TYPE_CHECK_IN  => 'EDL-E',
            self::TYPE_CHECK_OUT => 'EDL-S',
            default              => 'EDL-P',
        };

        do {
            $ref = $prefix.'-'.now()->format('Y').'-'.strtoupper(Str::random(5));
        } while (self::where('reference', $ref)->exists());

        return $ref;
    }

    public function complete(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    public function sign(string $role, string $ip): void
    {
        $data = [
            "{$role}_signed_at"    => now(),
            "{$role}_signature_ip" => $ip,
        ];

        if ($this->owner_signed_at && $this->tenant_signed_at) {
            $data['status'] = self::STATUS_SIGNED;
        }

        $this->update($data);
    }
}
