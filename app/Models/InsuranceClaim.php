<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_insurance_id',
        'user_id',
        'claim_number',
        'claim_type',
        'description',
        'claimed_amount',
        'approved_amount',
        'status',
        'incident_date',
        'evidence',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'paid_at',
    ];

    protected $casts = [
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'incident_date' => 'datetime',
        'evidence' => 'array',
        'reviewed_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($claim) {
            if (empty($claim->claim_number)) {
                $claim->claim_number = 'CLM-'.date('Y').'-'.strtoupper(Str::random(8));
            }
        });
    }

    /**
     * L'assurance associée
     */
    public function bookingInsurance(): BelongsTo
    {
        return $this->belongsTo(BookingInsurance::class);
    }

    /**
     * L'utilisateur qui a fait la réclamation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'admin qui a traité la réclamation
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Réclamations en attente
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    /**
     * Réclamations approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Vérifier si la réclamation peut être modifiée
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Mettre en cours d'examen
     */
    public function startReview(User $admin): bool
    {
        if ($this->status !== 'submitted') {
            return false;
        }

        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $admin->id,
        ]);

        return true;
    }

    /**
     * Approuver la réclamation
     */
    public function approve(User $admin, float $approvedAmount, ?string $notes = null): bool
    {
        if (!in_array($this->status, ['submitted', 'under_review'])) {
            return false;
        }

        // Vérifier que le montant ne dépasse pas la couverture restante
        $maxAmount = $this->bookingInsurance->remainingCoverage();
        $approvedAmount = min($approvedAmount, $maxAmount);

        $this->update([
            'status' => 'approved',
            'approved_amount' => $approvedAmount,
            'admin_notes' => $notes,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return true;
    }

    /**
     * Rejeter la réclamation
     */
    public function reject(User $admin, string $reason): bool
    {
        if (!in_array($this->status, ['submitted', 'under_review'])) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'admin_notes' => $reason,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        return true;
    }

    /**
     * Marquer comme payé
     */
    public function markAsPaid(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return true;
    }

    /**
     * Types de réclamation avec labels
     */
    public static function getClaimTypeLabels(): array
    {
        return [
            'damage' => 'Dommages',
            'theft' => 'Vol',
            'cancellation' => 'Annulation',
            'accident' => 'Accident',
            'other' => 'Autre',
        ];
    }

    /**
     * Statuts avec labels
     */
    public static function getStatusLabels(): array
    {
        return [
            'submitted' => 'Soumise',
            'under_review' => 'En examen',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'paid' => 'Payée',
        ];
    }
}
