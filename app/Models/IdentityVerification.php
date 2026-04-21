<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\IdentityVerificationApproved;
use App\Notifications\IdentityVerificationRejected;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdentityVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'document_country',
        'document_front',
        'document_back',
        'selfie_photo',
        'first_name',
        'last_name',
        'birth_date',
        'document_expiry',
        'extracted_data',
        'face_match_score',
        'face_match_passed',
        'status',
        'rejection_reason',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
        'attempt_count',
        'last_attempt_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'document_expiry' => 'date',
        'extracted_data' => 'array',
        'face_match_score' => 'decimal:2',
        'face_match_passed' => 'boolean',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    protected $hidden = [
        'document_number',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'submitted', 'processing']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeNeedsReview($query)
    {
        return $query->whereIn('status', ['submitted', 'manual_review']);
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    /**
     * Vérifier si la vérification est valide
     */
    public function isValid(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si le document est expiré
     */
    public function isDocumentExpired(): bool
    {
        return $this->document_expiry && $this->document_expiry->isPast();
    }

    /**
     * Peut soumettre une nouvelle tentative
     */
    public function canRetry(): bool
    {
        // Max 3 tentatives
        if ($this->attempt_count >= 3) {
            return false;
        }

        // Attendre 24h entre les tentatives après un rejet
        if ($this->status === 'rejected' && $this->last_attempt_at) {
            return $this->last_attempt_at->addHours(24)->isPast();
        }

        return in_array($this->status, ['pending', 'rejected']);
    }

    /**
     * Soumettre les documents
     */
    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'attempt_count' => $this->attempt_count + 1,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Approuver la vérification
     */
    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
            'expires_at' => now()->addYears(config('rezi.kyc.identity.verification_validity_years', 2)),
        ]);

        // Mettre à jour l'utilisateur
        $this->user->update([
            'identity_verified' => true,
            'verification_level' => $this->user->getNextVerificationLevel(),
        ]);

        // Notifier l'utilisateur
        $this->user->notify(new IdentityVerificationApproved($this));

        // Créer une notification in-app
        \App\Models\Notification::send(
            $this->user,
            'verification',
            '✅ Identité vérifiée',
            'Votre vérification d\'identité a été approuvée. Profitez de toutes les fonctionnalités REZI !',
            route('verification.dashboard'),
            ['verification_id' => $this->id],
        );
    }

    /**
     * Rejeter la vérification
     */
    public function reject(int $reviewerId, string $reason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);

        // Notifier l'utilisateur
        $this->user->notify(new IdentityVerificationRejected($this));

        // Créer une notification in-app
        \App\Models\Notification::send(
            $this->user,
            'verification',
            '❌ Vérification rejetée',
            "Votre vérification a été rejetée. Motif : {$reason}",
            route('verification.identity.start'),
            ['verification_id' => $this->id, 'reason' => $reason],
        );
    }

    /**
     * Obtenir le label du type de document
     */
    public function getDocumentTypeLabel(): string
    {
        return match($this->document_type) {
            'cni' => 'Carte Nationale d\'Identité',
            'passport' => 'Passeport',
            'driver_license' => 'Permis de conduire',
            'residence_permit' => 'Titre de séjour',
            default => $this->document_type,
        };
    }

    /**
     * Obtenir le label du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'submitted' => 'Soumis',
            'processing' => 'En traitement',
            'manual_review' => 'Vérification manuelle',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'expired' => 'Expiré',
            default => $this->status,
        };
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'submitted' => 'blue',
            'processing' => 'yellow',
            'manual_review' => 'orange',
            'approved' => 'green',
            'rejected' => 'red',
            'expired' => 'gray',
            default => 'gray',
        };
    }
}
