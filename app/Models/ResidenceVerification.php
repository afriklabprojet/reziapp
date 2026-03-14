<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidenceVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'user_id',
        'verification_type',
        'proof_document',
        'document_type',
        'verified_latitude',
        'verified_longitude',
        'gps_accuracy',
        'distance_from_declared',
        'verification_photos',
        'visit_scheduled_at',
        'visit_completed_at',
        'visit_notes',
        'visited_by',
        'status',
        'rejection_reason',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
        'expires_at',
    ];

    protected $casts = [
        'verified_latitude' => 'decimal:8',
        'verified_longitude' => 'decimal:8',
        'gps_accuracy' => 'decimal:2',
        'distance_from_declared' => 'decimal:2',
        'verification_photos' => 'array',
        'visit_scheduled_at' => 'datetime',
        'visit_completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function visitor()
    {
        return $this->belongsTo(User::class, 'visited_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'documents_submitted', 'under_review']);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeNeedsVisit($query)
    {
        return $query->where('status', 'visit_scheduled');
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
     * Soumettre les documents
     */
    public function submitDocuments(): void
    {
        $this->update(['status' => 'documents_submitted']);
    }

    /**
     * Programmer une visite
     */
    public function scheduleVisit(\DateTime $date, int $visitorId): void
    {
        $this->update([
            'status' => 'visit_scheduled',
            'visit_scheduled_at' => $date,
            'visited_by' => $visitorId,
        ]);
    }

    /**
     * Compléter la visite
     */
    public function completeVisit(string $notes, ?array $photos = null): void
    {
        $this->update([
            'visit_completed_at' => now(),
            'visit_notes' => $notes,
            'verification_photos' => $photos,
            'status' => 'under_review',
        ]);
    }

    /**
     * Vérifier la localisation GPS
     */
    public function verifyGps(float $lat, float $lng, float $accuracy): bool
    {
        $this->update([
            'verified_latitude' => $lat,
            'verified_longitude' => $lng,
            'gps_accuracy' => $accuracy,
        ]);

        // Calculer la distance par rapport à l'adresse déclarée
        $residence = $this->residence;
        if ($residence->latitude && $residence->longitude) {
            $distance = $this->calculateDistance(
                $lat,
                $lng,
                $residence->latitude,
                $residence->longitude,
            );

            $this->update(['distance_from_declared' => $distance]);

            // Moins de 100m = OK
            return $distance < 100;
        }

        return true;
    }

    /**
     * Calculer la distance entre deux points
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // mètres

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2 +
             cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Approuver
     */
    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
            'expires_at' => now()->addYear(),
        ]);
    }

    /**
     * Rejeter
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
    }

    /**
     * Obtenir le label du type de vérification
     */
    public function getVerificationTypeLabel(): string
    {
        return match($this->verification_type) {
            'document' => 'Document justificatif',
            'visit' => 'Visite sur place',
            'video_call' => 'Appel vidéo',
            'gps_check' => 'Vérification GPS',
            default => $this->verification_type,
        };
    }

    /**
     * Obtenir le label du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'documents_submitted' => 'Documents soumis',
            'visit_scheduled' => 'Visite programmée',
            'under_review' => 'En cours de vérification',
            'approved' => 'Vérifiée',
            'rejected' => 'Rejetée',
            'expired' => 'Expirée',
            default => $this->status,
        };
    }
}
