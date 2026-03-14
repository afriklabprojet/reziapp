<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerBadge extends Model
{
    protected $fillable = [
        'user_id',
        'badge_type',
        'badge_name',
        'badge_icon',
        'badge_color',
        'status',
        'reason',
        'awarded_by',
        'earned_at',
        'expires_at',
        'is_visible',
        'metadata',
    ];

    protected $casts = [
        'earned_at' => 'date',
        'expires_at' => 'date',
        'is_visible' => 'boolean',
        'metadata' => 'array',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_REVOKED = 'revoked';

    // Badge types
    const TYPE_VERIFIED_IDENTITY = 'verified_identity';
    const TYPE_VERIFIED_PHONE = 'verified_phone';
    const TYPE_VERIFIED_RESIDENCE = 'verified_residence';
    const TYPE_SUPERHOST = 'superhost';
    const TYPE_TRUSTED = 'trusted';
    const TYPE_RESPONSIVE = 'responsive';
    const TYPE_TOP_RATED = 'top_rated';

    // Available badges
    public static array $badges = [
        self::TYPE_VERIFIED_IDENTITY => [
            'name' => 'Identité vérifiée',
            'icon' => 'heroicon-o-identification',
            'color' => 'blue',
            'description' => 'Ce propriétaire a vérifié son identité',
        ],
        self::TYPE_VERIFIED_PHONE => [
            'name' => 'Téléphone vérifié',
            'icon' => 'heroicon-o-phone',
            'color' => 'green',
            'description' => 'Numéro de téléphone confirmé',
        ],
        self::TYPE_VERIFIED_RESIDENCE => [
            'name' => 'Logement vérifié',
            'icon' => 'heroicon-o-home',
            'color' => 'purple',
            'description' => 'Au moins un logement a été vérifié sur place',
        ],
        self::TYPE_SUPERHOST => [
            'name' => 'Superhôte',
            'icon' => 'heroicon-o-star',
            'color' => 'yellow',
            'description' => 'Propriétaire excellent avec 4.8+ de note moyenne',
        ],
        self::TYPE_TRUSTED => [
            'name' => 'Hôte de confiance',
            'icon' => 'heroicon-o-shield-check',
            'color' => 'orange',
            'description' => 'Propriétaire actif depuis plus d\'un an',
        ],
        self::TYPE_RESPONSIVE => [
            'name' => 'Réponse rapide',
            'icon' => 'heroicon-o-bolt',
            'color' => 'cyan',
            'description' => 'Répond en moins d\'une heure en moyenne',
        ],
        self::TYPE_TOP_RATED => [
            'name' => 'Top noté',
            'icon' => 'heroicon-o-trophy',
            'color' => 'gold',
            'description' => 'Dans le top 10% des propriétaires',
        ],
    ];

    // ===== RELATIONSHIPS =====

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function awardedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'awarded_by');
    }

    // ===== SCOPES =====

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

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

    // ===== HELPERS =====

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getBadgeInfo(): array
    {
        return self::$badges[$this->badge_type] ?? [
            'name' => $this->badge_name,
            'icon' => $this->badge_icon,
            'color' => $this->badge_color,
            'description' => '',
        ];
    }

    /**
     * Attribuer un badge à un utilisateur
     */
    public static function award(User $user, string $badgeType, ?int $expiresInDays = null): self
    {
        $badgeInfo = self::$badges[$badgeType] ?? null;
        
        if (!$badgeInfo) {
            throw new \InvalidArgumentException("Badge type '{$badgeType}' does not exist");
        }

        return self::updateOrCreate(
            [
                'user_id' => $user->id,
                'badge_type' => $badgeType,
            ],
            [
                'badge_name' => $badgeInfo['name'],
                'badge_icon' => $badgeInfo['icon'],
                'badge_color' => $badgeInfo['color'],
                'earned_at' => now(),
                'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
                'is_visible' => true,
            ]
        );
    }

    /**
     * Vérifier et attribuer les badges automatiques
     */
    public static function checkAndAwardBadges(User $user): array
    {
        $awarded = [];

        // Badge identité vérifiée
        if ($user->identity_verified_at) {
            $awarded[] = self::award($user, self::TYPE_VERIFIED_IDENTITY);
        }

        // Badge téléphone vérifié
        if ($user->phone_verified) {
            $awarded[] = self::award($user, self::TYPE_VERIFIED_PHONE);
        }

        // Badge logement vérifié (au moins un logement vérifié)
        $hasVerifiedResidence = $user->residences()
            ->whereNotNull('verified_at')
            ->exists();
        if ($hasVerifiedResidence) {
            $awarded[] = self::award($user, self::TYPE_VERIFIED_RESIDENCE);
        }

        // Badge Superhôte (note moyenne >= 4.8 et au moins 10 avis)
        $avgRating = $user->receivedReviews()->avg('rating');
        $reviewsCount = $user->receivedReviews()->count();
        if ($avgRating >= 4.8 && $reviewsCount >= 10) {
            $awarded[] = self::award($user, self::TYPE_SUPERHOST, 365); // Expire après 1 an
        }

        // Badge hôte de confiance (compte créé il y a plus d'un an)
        if ($user->created_at->diffInYears(now()) >= 1) {
            $awarded[] = self::award($user, self::TYPE_TRUSTED);
        }

        // Badge réponse rapide (temps de réponse moyen < 60 min)
        // Nécessite le calcul du temps de réponse moyen
        
        return $awarded;
    }
}
