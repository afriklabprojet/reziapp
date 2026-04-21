<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Badge extends Model
{
    use HasFactory;

    protected $table = 'user_badges';

    protected $fillable = [
        'user_id',
        'badge_type',
        'earned_at',
        'expires_at',
        'criteria_met',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'expires_at' => 'datetime',
        'criteria_met' => 'array',
    ];

    /**
     * Types de badges disponibles
     */
    public const TYPE_SUPERHOST = 'superhost';
    public const TYPE_FAST_RESPONDER = 'fast_responder';
    public const TYPE_VERIFIED = 'verified';
    public const TYPE_EXPERIENCED_HOST = 'experienced_host';
    public const TYPE_TOP_REVIEWER = 'top_reviewer';
    public const TYPE_TRUSTED_GUEST = 'trusted_guest';

    /**
     * Obtenir tous les types de badges
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SUPERHOST => [
                'name' => 'Superhost',
                'description' => 'Propriétaire d\'excellence avec des évaluations exceptionnelles',
                'icon' => 'star',
                'color' => 'gold',
            ],
            self::TYPE_FAST_RESPONDER => [
                'name' => 'Réponse rapide',
                'description' => 'Répond aux messages en moins d\'une heure',
                'icon' => 'bolt',
                'color' => 'blue',
            ],
            self::TYPE_VERIFIED => [
                'name' => 'Vérifié',
                'description' => 'Identité et informations vérifiées',
                'icon' => 'check-badge',
                'color' => 'green',
            ],
            self::TYPE_EXPERIENCED_HOST => [
                'name' => 'Hôte expérimenté',
                'description' => 'Plus de 10 séjours réussis',
                'icon' => 'home',
                'color' => 'purple',
            ],
            self::TYPE_TOP_REVIEWER => [
                'name' => 'Contributeur actif',
                'description' => 'A laissé plus de 5 avis détaillés',
                'icon' => 'pencil',
                'color' => 'orange',
            ],
            self::TYPE_TRUSTED_GUEST => [
                'name' => 'Voyageur de confiance',
                'description' => 'Excellent historique de réservations',
                'icon' => 'user-check',
                'color' => 'teal',
            ],
        ];
    }

    /**
     * L'utilisateur qui possède ce badge
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si le badge est actif (non expiré)
     */
    public function isActive(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return $this->expires_at->isFuture();
    }

    /**
     * Obtenir les informations du type de badge
     */
    public function getTypeInfoAttribute(): array
    {
        return self::getTypes()[$this->badge_type] ?? [];
    }

    /**
     * Obtenir le nom du badge
     */
    public function getNameAttribute(): string
    {
        return $this->type_info['name'] ?? ucfirst($this->badge_type);
    }

    /**
     * Obtenir la description du badge
     */
    public function getDescriptionAttribute(): string
    {
        return $this->type_info['description'] ?? '';
    }

    /**
     * Obtenir l'icône du badge
     */
    public function getIconAttribute(): string
    {
        return $this->type_info['icon'] ?? 'badge';
    }

    /**
     * Obtenir la couleur du badge
     */
    public function getColorAttribute(): string
    {
        return $this->type_info['color'] ?? 'gray';
    }

    /**
     * Scope pour les badges actifs
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope par type de badge
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('badge_type', $type);
    }

    /**
     * Renouveler le badge
     */
    public function renew(int $days = 365): self
    {
        $this->update([
            'earned_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        return $this;
    }
}
