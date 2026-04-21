<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit trail complet de tous les événements assurance.
 * Obligatoire pour conformité CIMA (traçabilité des opérations).
 */
class InsuranceEvent extends Model
{
    // Types d'événements normalisés
    public const TYPE_SOUSCRIPTION         = 'souscription';
    public const TYPE_RENOUVELLEMENT       = 'renouvellement';
    public const TYPE_RESILIATION          = 'resiliation';
    public const TYPE_SINISTRE_SOUMIS      = 'sinistre_soumis';
    public const TYPE_SINISTRE_EN_EXAMEN   = 'sinistre_en_examen';
    public const TYPE_SINISTRE_APPROUVE    = 'sinistre_approuve';
    public const TYPE_SINISTRE_REJETE      = 'sinistre_rejete';
    public const TYPE_PAIEMENT_SINISTRE    = 'paiement_sinistre';
    public const TYPE_EXPIRATION_ALERTE    = 'expiration_alerte';
    public const TYPE_EXPIRATION           = 'expiration';
    public const TYPE_DEVIS_GENERE         = 'devis_genere';
    public const TYPE_SCORE_CALCULE        = 'score_calcule';
    public const TYPE_MODIFICATION         = 'modification';

    public const EVENT_LABELS = [
        self::TYPE_SOUSCRIPTION       => 'Souscription du contrat',
        self::TYPE_RENOUVELLEMENT     => 'Renouvellement du contrat',
        self::TYPE_RESILIATION        => 'Résiliation du contrat',
        self::TYPE_SINISTRE_SOUMIS    => 'Sinistre déclaré',
        self::TYPE_SINISTRE_EN_EXAMEN => 'Sinistre en cours d\'examen',
        self::TYPE_SINISTRE_APPROUVE  => 'Sinistre approuvé',
        self::TYPE_SINISTRE_REJETE    => 'Sinistre rejeté',
        self::TYPE_PAIEMENT_SINISTRE  => 'Indemnité versée',
        self::TYPE_EXPIRATION_ALERTE  => 'Alerte expiration proche',
        self::TYPE_EXPIRATION         => 'Contrat expiré',
        self::TYPE_DEVIS_GENERE       => 'Devis généré',
        self::TYPE_SCORE_CALCULE      => 'Score de risque calculé',
        self::TYPE_MODIFICATION       => 'Contrat modifié',
    ];

    public const EVENT_ICONS = [
        self::TYPE_SOUSCRIPTION       => '📋',
        self::TYPE_RENOUVELLEMENT     => '🔄',
        self::TYPE_RESILIATION        => '❌',
        self::TYPE_SINISTRE_SOUMIS    => '⚠️',
        self::TYPE_SINISTRE_EN_EXAMEN => '🔍',
        self::TYPE_SINISTRE_APPROUVE  => '✅',
        self::TYPE_SINISTRE_REJETE    => '🚫',
        self::TYPE_PAIEMENT_SINISTRE  => '💰',
        self::TYPE_EXPIRATION_ALERTE  => '⏰',
        self::TYPE_EXPIRATION         => '⌛',
        self::TYPE_DEVIS_GENERE       => '📊',
        self::TYPE_SCORE_CALCULE      => '🎯',
        self::TYPE_MODIFICATION       => '✏️',
    ];

    public const EVENT_COLORS = [
        self::TYPE_SOUSCRIPTION       => 'blue',
        self::TYPE_RENOUVELLEMENT     => 'blue',
        self::TYPE_RESILIATION        => 'red',
        self::TYPE_SINISTRE_SOUMIS    => 'orange',
        self::TYPE_SINISTRE_EN_EXAMEN => 'yellow',
        self::TYPE_SINISTRE_APPROUVE  => 'green',
        self::TYPE_SINISTRE_REJETE    => 'red',
        self::TYPE_PAIEMENT_SINISTRE  => 'green',
        self::TYPE_EXPIRATION_ALERTE  => 'amber',
        self::TYPE_EXPIRATION         => 'gray',
        self::TYPE_DEVIS_GENERE       => 'indigo',
        self::TYPE_SCORE_CALCULE      => 'purple',
        self::TYPE_MODIFICATION       => 'gray',
    ];

    protected $fillable = [
        'eventable_type',
        'eventable_id',
        'event_type',
        'title',
        'description',
        'metadata',
        'user_id',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────────

    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getLabelAttribute(): string
    {
        return self::EVENT_LABELS[$this->event_type] ?? $this->event_type;
    }

    public function getIconAttribute(): string
    {
        return self::EVENT_ICONS[$this->event_type] ?? '📌';
    }

    public function getColorAttribute(): string
    {
        return self::EVENT_COLORS[$this->event_type] ?? 'gray';
    }

    // ── Factory method ───────────────────────────────────────────────────

    public static function record(
        Model $model,
        string $eventType,
        string $title,
        ?string $description = null,
        array $metadata = [],
        ?\App\Models\User $user = null,
    ): self {
        return self::create([
            'eventable_type' => get_class($model),
            'eventable_id'   => $model->id,
            'event_type'     => $eventType,
            'title'          => $title,
            'description'    => $description,
            'metadata'       => array_merge($metadata, ['timestamp' => now()->toISOString()]),
            'user_id'        => $user?->id ?? auth()->id(),
            'ip_address'     => request()?->ip(),
        ]);
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeForModel($query, Model $model)
    {
        return $query->where('eventable_type', get_class($model))
                     ->where('eventable_id', $model->id);
    }
}
