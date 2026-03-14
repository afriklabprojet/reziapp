<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'languages',
        'location',
        'work',
        'response_time_hours',
        'response_rate',
        'is_superhost',
        'superhost_since',
        'total_reviews_given',
        'total_reviews_received',
        'member_since',
        'show_email',
        'show_phone',
        'profile_views',
        'last_active_at',
    ];

    protected $casts = [
        'languages' => 'array',
        'response_rate' => 'decimal:2',
        'is_superhost' => 'boolean',
        'superhost_since' => 'date',
        'member_since' => 'date',
        'show_email' => 'boolean',
        'show_phone' => 'boolean',
        'last_active_at' => 'datetime',
    ];

    protected $attributes = [
        'response_rate' => 0,
        'response_time_hours' => null,
        'is_superhost' => false,
        'show_email' => false,
        'show_phone' => false,
        'profile_views' => 0,
        'total_reviews_given' => 0,
        'total_reviews_received' => 0,
    ];

    /**
     * L'utilisateur associé à ce profil public
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Incrémenter le compteur de vues du profil
     */
    public function incrementViews(): void
    {
        $this->increment('profile_views');
    }

    /**
     * Mettre à jour le temps de réponse
     */
    public function updateResponseMetrics(float $hours, float $rate): void
    {
        $this->update([
            'response_time_hours' => $hours,
            'response_rate' => $rate,
        ]);
    }

    /**
     * Mettre à jour le statut Superhost
     */
    public function updateSuperhostStatus(bool $isSuperhost): void
    {
        $data = ['is_superhost' => $isSuperhost];

        if ($isSuperhost && !$this->superhost_since) {
            $data['superhost_since'] = now();
        } elseif (!$isSuperhost) {
            $data['superhost_since'] = null;
        }

        $this->update($data);
    }

    /**
     * Mettre à jour les compteurs d'avis
     */
    public function updateReviewCounts(int $given, int $received): void
    {
        $this->update([
            'total_reviews_given' => $given,
            'total_reviews_received' => $received,
        ]);
    }

    /**
     * Obtenir la description du temps de réponse
     */
    public function getResponseTimeDescriptionAttribute(): string
    {
        if ($this->response_time_hours === null) {
            return 'Non disponible';
        }

        if ($this->response_time_hours < 1) {
            return 'En quelques minutes';
        }

        if ($this->response_time_hours < 24) {
            $hours = round($this->response_time_hours);

            return "En environ {$hours} heure".($hours > 1 ? 's' : '');
        }

        $days = round($this->response_time_hours / 24);

        return "En environ {$days} jour".($days > 1 ? 's' : '');
    }

    /**
     * Obtenir le pourcentage de réponse formaté
     */
    public function getResponseRateFormattedAttribute(): string
    {
        return number_format($this->response_rate, 0).'%';
    }

    /**
     * Obtenir les langues formatées
     */
    public function getLanguagesFormattedAttribute(): string
    {
        if (empty($this->languages)) {
            return 'Français';
        }

        return implode(', ', $this->languages);
    }

    /**
     * Obtenir l'ancienneté du membre
     */
    public function getMemberSinceFormattedAttribute(): string
    {
        if (!$this->member_since) {
            return 'Récemment inscrit';
        }

        $years = $this->member_since->diffInYears(now());
        $months = $this->member_since->diffInMonths(now()) % 12;

        if ($years > 0) {
            return "Membre depuis {$years} an".($years > 1 ? 's' : '');
        }

        if ($months > 0) {
            return "Membre depuis {$months} mois";
        }

        return 'Nouveau membre';
    }

    /**
     * Scope pour les Superhosts
     */
    public function scopeSuperhosts($query)
    {
        return $query->where('is_superhost', true);
    }

    /**
     * Scope pour les profils avec réponse rapide
     */
    public function scopeFastResponders($query, int $maxHours = 1)
    {
        return $query->where('response_time_hours', '<=', $maxHours);
    }

    /**
     * Mettre à jour la dernière activité
     */
    public function touchActivity(): void
    {
        $this->update(['last_active_at' => now()]);
    }

    /**
     * Créer ou mettre à jour le profil public pour un utilisateur
     */
    public static function getOrCreateForUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            [
                'member_since' => $user->created_at,
                'languages' => ['Français'],
            ],
        );
    }
}
