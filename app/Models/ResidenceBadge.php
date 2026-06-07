<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'badge_type',
        'earned_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const BADGE_TYPES = [
        'verified' => [
            'name' => 'Résidence vérifiée',
            'icon' => '✓',
            'color' => 'green',
            'description' => 'Cette résidence a été vérifiée par notre équipe',
        ],
        'top_residence' => [
            'name' => 'Top Résidence',
            'icon' => '⭐',
            'color' => 'yellow',
            'description' => 'Note moyenne supérieure à 4.5/5',
        ],
        'superhost' => [
            'name' => 'Superhost',
            'icon' => '🏆',
            'color' => 'purple',
            'description' => 'Hôte exceptionnel avec d\'excellentes évaluations',
        ],
        'instant_booking' => [
            'name' => 'Réservation instantanée',
            'icon' => '⚡',
            'color' => 'blue',
            'description' => 'Réservez immédiatement sans attente',
        ],
        'new_listing' => [
            'name' => 'Nouvelle annonce',
            'icon' => '🆕',
            'color' => 'teal',
            'description' => 'Récemment ajoutée sur Rezi Studio Meublé Faya',
        ],
        'responsive_host' => [
            'name' => 'Hôte réactif',
            'icon' => '💬',
            'color' => 'indigo',
            'description' => 'Répond en moins d\'une heure',
        ],
        'eco_friendly' => [
            'name' => 'Éco-responsable',
            'icon' => '🌿',
            'color' => 'green',
            'description' => 'Pratiques respectueuses de l\'environnement',
        ],
        'family_friendly' => [
            'name' => 'Adapté aux familles',
            'icon' => '👨‍👩‍👧‍👦',
            'color' => 'orange',
            'description' => 'Équipements pour les familles avec enfants',
        ],
        'business_ready' => [
            'name' => 'Prêt pour les affaires',
            'icon' => '💼',
            'color' => 'gray',
            'description' => 'Espace de travail et WiFi haut débit',
        ],
    ];

    // Relationships
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // Accessors
    public function getInfoAttribute(): array
    {
        return self::BADGE_TYPES[$this->badge_type] ?? [
            'name' => ucfirst($this->badge_type),
            'icon' => '🏅',
            'color' => 'gray',
            'description' => '',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->info['name'];
    }

    public function getIconAttribute(): string
    {
        return $this->info['icon'];
    }

    public function getColorAttribute(): string
    {
        return $this->info['color'];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('badge_type', $type);
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public static function awardBadge(int $residenceId, string $badgeType, ?array $metadata = null): self
    {
        return self::updateOrCreate(
            ['residence_id' => $residenceId, 'badge_type' => $badgeType],
            [
                'earned_at' => now(),
                'expires_at' => $badgeType === 'new_listing' ? now()->addDays(30) : null,
                'metadata' => $metadata,
            ],
        );
    }

    public static function revokeBadge(int $residenceId, string $badgeType): bool
    {
        return self::where('residence_id', $residenceId)
            ->where('badge_type', $badgeType)
            ->delete() > 0;
    }
}
