<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ReviewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ReviewObserver::class])]
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'user_id',
        'booking_id',
        'rating',
        'cleanliness_rating',
        'location_rating',
        'value_rating',
        'communication_rating',
        'accuracy_rating',
        'checkin_rating',
        'comment',
        'pros',
        'cons',
        'would_recommend',
        'owner_response',
        'owner_response_at',
        'owner_review_for_guest',
        'is_verified',
        'status',
        'stay_date_start',
        'stay_date_end',
        'photos',
        'helpful_count',
        'is_featured',
        'sentiment_score',
        'sentiment_label',
        'needs_moderation',
        'moderation_notes',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'owner_response_at' => 'datetime',
        'is_verified' => 'boolean',
        'would_recommend' => 'boolean',
        'stay_date_start' => 'date',
        'stay_date_end' => 'date',
        'photos' => 'array',
        'is_featured' => 'boolean',
        'needs_moderation' => 'boolean',
        'sentiment_score' => 'float',
        'moderated_at' => 'datetime',
    ];

    protected $attributes = [
        'helpful_count' => 0,
        'is_featured' => false,
        'is_verified' => false,
        'status' => 'pending',
    ];

    /**
     * Statuts d'avis
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * La résidence concernée
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * L'auteur de l'avis
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * La réservation associée
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Les votes "utile" pour cet avis
     */
    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulVote::class);
    }

    /**
     * Les signalements pour cet avis
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }

    /**
     * Calcul de la note moyenne détaillée
     */
    public function getAverageDetailedRating(): float
    {
        $ratings = array_filter([
            $this->cleanliness_rating,
            $this->location_rating,
            $this->value_rating,
            $this->communication_rating,
            $this->accuracy_rating,
            $this->checkin_rating,
        ]);

        if (empty($ratings)) {
            return $this->rating;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    /**
     * Obtenir toutes les notes détaillées
     */
    public function getDetailedRatings(): array
    {
        return [
            'cleanliness' => [
                'label' => 'Propreté',
                'value' => $this->cleanliness_rating,
                'icon' => 'sparkles',
            ],
            'location' => [
                'label' => 'Emplacement',
                'value' => $this->location_rating,
                'icon' => 'map-pin',
            ],
            'value' => [
                'label' => 'Rapport qualité-prix',
                'value' => $this->value_rating,
                'icon' => 'currency-dollar',
            ],
            'communication' => [
                'label' => 'Communication',
                'value' => $this->communication_rating,
                'icon' => 'chat-bubble-left-right',
            ],
            'accuracy' => [
                'label' => 'Exactitude',
                'value' => $this->accuracy_rating,
                'icon' => 'check-badge',
            ],
            'checkin' => [
                'label' => 'Arrivée',
                'value' => $this->checkin_rating,
                'icon' => 'key',
            ],
        ];
    }

    /**
     * Calculer la durée du séjour en jours
     */
    public function getStayDurationAttribute(): ?int
    {
        if (!$this->stay_date_start || !$this->stay_date_end) {
            return null;
        }

        return $this->stay_date_start->diffInDays($this->stay_date_end);
    }

    /**
     * Obtenir la période de séjour formatée
     */
    public function getStayPeriodFormattedAttribute(): ?string
    {
        if (!$this->stay_date_start || !$this->stay_date_end) {
            return null;
        }

        return $this->stay_date_start->translatedFormat('d M').' - '.
               $this->stay_date_end->translatedFormat('d M Y');
    }

    /**
     * Scope pour les avis approuvés
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope pour les avis en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope pour les avis vérifiés
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope pour les avis mis en avant
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope pour les avis avec photos
     */
    public function scopeWithPhotos($query)
    {
        return $query->whereNotNull('photos')
            ->where('photos', '!=', '[]');
    }

    /**
     * Scope tri par utilité
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderByDesc('helpful_count');
    }

    /**
     * Ajouter une réponse du propriétaire
     */
    public function addOwnerResponse(string $response): void
    {
        $this->update([
            'owner_response' => $response,
            'owner_response_at' => now(),
        ]);
    }

    /**
     * Ajouter un avis du propriétaire sur le voyageur
     */
    public function addOwnerReviewForGuest(string $review): void
    {
        $this->update(['owner_review_for_guest' => $review]);
    }

    /**
     * Approuver l'avis
     */
    public function approve(): void
    {
        $this->update(['status' => self::STATUS_APPROVED]);
    }

    /**
     * Rejeter l'avis
     */
    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    /**
     * Marquer comme vérifié
     */
    public function markAsVerified(): void
    {
        $this->update(['is_verified' => true]);
    }

    /**
     * Mettre en avant l'avis
     */
    public function feature(): void
    {
        $this->update(['is_featured' => true]);
    }

    /**
     * Retirer la mise en avant
     */
    public function unfeature(): void
    {
        $this->update(['is_featured' => false]);
    }

    /**
     * Incrémenter le compteur d'utilité
     */
    public function incrementHelpfulCount(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Décrémenter le compteur d'utilité
     */
    public function decrementHelpfulCount(): void
    {
        if ($this->helpful_count > 0) {
            $this->decrement('helpful_count');
        }
    }

    /**
     * Ajouter des photos à l'avis
     */
    public function addPhotos(array $photoPaths): void
    {
        $currentPhotos = $this->photos ?? [];
        $this->update([
            'photos' => array_merge($currentPhotos, $photoPaths),
        ]);
    }

    /**
     * Supprimer une photo de l'avis
     */
    public function removePhoto(string $photoPath): void
    {
        $photos = $this->photos ?? [];
        $photos = array_filter($photos, fn ($p) => $p !== $photoPath);
        $this->update(['photos' => array_values($photos)]);
    }

    /**
     * Vérifier si l'utilisateur peut laisser un avis (post-séjour)
     */
    public static function canUserReview(User $user, Residence $residence): bool
    {
        // Vérifier si l'utilisateur a déjà laissé un avis
        $hasExistingReview = self::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->exists();

        if ($hasExistingReview) {
            return false;
        }

        // Vérifier si l'utilisateur a eu un séjour confirmé
        // (À adapter selon le système de réservation)
        return true;
    }

    /**
     * Signaler l'avis
     */
    public function report(User $reporter, string $reason, ?string $details = null): ReviewReport
    {
        return $this->reports()->create([
            'reporter_id' => $reporter->id,
            'reason' => $reason,
            'details' => $details,
            'status' => ReviewReport::STATUS_PENDING,
        ]);
    }

    /**
     * Vérifier si l'avis a été signalé par un utilisateur
     */
    public function isReportedBy(User $user): bool
    {
        return $this->reports()
            ->where('reporter_id', $user->id)
            ->exists();
    }

    /**
     * Voter qu'un avis est utile
     */
    public function voteHelpful(User $user): void
    {
        if (!ReviewHelpfulVote::hasVoted($this, $user)) {
            ReviewHelpfulVote::voteHelpful($this, $user);
            $this->incrementHelpfulCount();
        }
    }

    /**
     * Retirer son vote utile
     */
    public function removeHelpfulVote(User $user): void
    {
        if (ReviewHelpfulVote::removeVote($this, $user)) {
            $this->decrementHelpfulCount();
        }
    }

    /**
     * Vérifier si l'utilisateur a voté pour cet avis
     */
    public function hasUserVoted(User $user): bool
    {
        return ReviewHelpfulVote::hasVoted($this, $user);
    }
}
