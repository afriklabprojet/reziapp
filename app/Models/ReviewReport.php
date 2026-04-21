<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'reporter_id',
        'reason',
        'details',
        'status',
        'admin_notes',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Raisons de signalement
     */
    public const REASON_FAKE = 'fake';
    public const REASON_OFFENSIVE = 'offensive';
    public const REASON_SPAM = 'spam';
    public const REASON_IRRELEVANT = 'irrelevant';
    public const REASON_HARASSMENT = 'harassment';
    public const REASON_PERSONAL_INFO = 'personal_info';
    public const REASON_OTHER = 'other';

    /**
     * Statuts du signalement
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    /**
     * Obtenir les raisons disponibles
     */
    public static function getReasons(): array
    {
        return [
            self::REASON_FAKE => 'Avis frauduleux ou faux',
            self::REASON_OFFENSIVE => 'Contenu offensant ou inapproprié',
            self::REASON_SPAM => 'Spam ou publicité',
            self::REASON_IRRELEVANT => 'Hors sujet ou non pertinent',
            self::REASON_HARASSMENT => 'Harcèlement ou menaces',
            self::REASON_PERSONAL_INFO => 'Informations personnelles exposées',
            self::REASON_OTHER => 'Autre raison',
        ];
    }

    /**
     * Obtenir les statuts disponibles
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'En attente',
            self::STATUS_INVESTIGATING => 'En cours d\'examen',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_DISMISSED => 'Rejeté',
        ];
    }

    /**
     * L'avis signalé
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * L'utilisateur qui a signalé
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * L'administrateur qui a résolu le signalement
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Obtenir le libellé de la raison
     */
    public function getReasonLabelAttribute(): string
    {
        return self::getReasons()[$this->reason] ?? $this->reason;
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    /**
     * Vérifier si le signalement est en attente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si le signalement est résolu
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_DISMISSED]);
    }

    /**
     * Marquer comme en cours d'examen
     */
    public function markAsInvestigating(): void
    {
        $this->update(['status' => self::STATUS_INVESTIGATING]);
    }

    /**
     * Résoudre le signalement
     */
    public function resolve(User $admin, ?string $notes = null, bool $removeReview = false): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'admin_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
        ]);

        if ($removeReview) {
            $this->review->reject();
        }
    }

    /**
     * Rejeter le signalement
     */
    public function dismiss(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DISMISSED,
            'admin_notes' => $notes,
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
        ]);
    }

    /**
     * Scope pour les signalements en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les signalements non résolus
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_INVESTIGATING]);
    }

    /**
     * Scope par raison
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }
}
