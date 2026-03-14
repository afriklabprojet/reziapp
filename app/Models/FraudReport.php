<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraudReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reporter_id',
        'reporter_ip',
        'reporter_user_agent',
        'target_type',
        'target_id',
        'target_user_id',
        'fraud_type',
        'description',
        'evidence',
        'risk_score',
        'risk_factors',
        'is_auto_detected',
        'detection_rule',
        'status',
        'actions_taken',
        'resolution_notes',
        'assigned_to',
        'resolved_by',
        'resolved_at',
        'priority',
    ];

    protected $casts = [
        'evidence' => 'array',
        'risk_factors' => 'array',
        'actions_taken' => 'array',
        'is_auto_detected' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Obtenir la cible du signalement
     */
    public function target()
    {
        return match($this->target_type) {
            'user' => User::find($this->target_id),
            'residence' => Residence::find($this->target_id),
            'review' => Review::find($this->target_id),
            'message' => Message::find($this->target_id),
            'contact' => Contact::find($this->target_id),
            default => null,
        };
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'investigating']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'critical']);
    }

    public function scopeAutoDetected($query)
    {
        return $query->where('is_auto_detected', true);
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    /**
     * Assigner à un modérateur
     */
    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'investigating',
        ]);
    }

    /**
     * Confirmer la fraude
     */
    public function confirm(int $resolverId, array $actions, ?string $notes = null): void
    {
        $this->update([
            'status' => 'confirmed',
            'actions_taken' => $actions,
            'resolution_notes' => $notes,
            'resolved_by' => $resolverId,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Rejeter le signalement
     */
    public function dismiss(int $resolverId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'resolution_notes' => $notes,
            'resolved_by' => $resolverId,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Ajouter une preuve
     */
    public function addEvidence(string $url, string $type = 'screenshot'): void
    {
        $evidence = $this->evidence ?? [];
        $evidence[] = [
            'url' => $url,
            'type' => $type,
            'added_at' => now()->toIso8601String(),
        ];

        $this->update(['evidence' => $evidence]);
    }

    /**
     * Ajouter une action
     */
    public function addAction(string $action, ?string $details = null): void
    {
        $actions = $this->actions_taken ?? [];
        $actions[] = [
            'action' => $action,
            'details' => $details,
            'performed_at' => now()->toIso8601String(),
        ];

        $this->update(['actions_taken' => $actions]);
    }

    /**
     * Calculer le score de risque
     */
    public function calculateRiskScore(): int
    {
        $score = 0;
        $factors = [];

        // Type de fraude
        $highRiskTypes = ['scam', 'fake_identity', 'payment_fraud'];
        if (in_array($this->fraud_type, $highRiskTypes)) {
            $score += 30;
            $factors[] = 'Type de fraude à haut risque';
        }

        // Plusieurs signalements sur la même cible
        $previousReports = self::where('target_type', $this->target_type)
            ->where('target_id', $this->target_id)
            ->where('id', '!=', $this->id)
            ->count();

        if ($previousReports > 0) {
            $score += min($previousReports * 15, 45);
            $factors[] = "Signalement récurrent ({$previousReports} précédents)";
        }

        // Utilisateur cible déjà signalé
        if ($this->target_user_id) {
            $userReports = self::where('target_user_id', $this->target_user_id)
                ->where('status', 'confirmed')
                ->count();

            if ($userReports > 0) {
                $score += min($userReports * 20, 40);
                $factors[] = "Utilisateur déjà confirmé fraudeur ({$userReports} cas)";
            }
        }

        // Détection automatique
        if ($this->is_auto_detected) {
            $score += 15;
            $factors[] = 'Détecté automatiquement';
        }

        $this->update([
            'risk_score' => min($score, 100),
            'risk_factors' => $factors,
            'priority' => $this->calculatePriority($score),
        ]);

        return $score;
    }

    /**
     * Calculer la priorité
     */
    protected function calculatePriority(int $score): string
    {
        return match(true) {
            $score >= 80 => 'critical',
            $score >= 60 => 'high',
            $score >= 30 => 'medium',
            default => 'low',
        };
    }

    /**
     * Obtenir le label du type de fraude
     */
    public function getFraudTypeLabel(): string
    {
        return match($this->fraud_type) {
            'fake_identity' => 'Fausse identité',
            'fake_listing' => 'Annonce fictive',
            'scam' => 'Arnaque',
            'spam' => 'Spam',
            'harassment' => 'Harcèlement',
            'fake_review' => 'Faux avis',
            'price_manipulation' => 'Manipulation de prix',
            'duplicate_listing' => 'Annonce dupliquée',
            'misleading_photos' => 'Photos trompeuses',
            'wrong_location' => 'Mauvaise localisation',
            'no_show' => 'Absence au RDV',
            'payment_fraud' => 'Fraude au paiement',
            'other' => 'Autre',
            default => $this->fraud_type,
        };
    }

    /**
     * Obtenir le label du statut
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'investigating' => 'En investigation',
            'confirmed' => 'Confirmé',
            'dismissed' => 'Rejeté',
            'resolved' => 'Résolu',
            default => $this->status,
        };
    }

    /**
     * Obtenir la couleur de priorité
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'gray',
            default => 'gray',
        };
    }
}
