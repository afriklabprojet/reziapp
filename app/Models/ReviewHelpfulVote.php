<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewHelpfulVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'user_id',
        'is_helpful',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /**
     * L'avis concerné
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * L'utilisateur qui a voté
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Voter qu'un avis est utile
     */
    public static function voteHelpful(Review $review, User $user): self
    {
        return self::updateOrCreate(
            [
                'review_id' => $review->id,
                'user_id' => $user->id,
            ],
            ['is_helpful' => true],
        );
    }

    /**
     * Retirer son vote
     */
    public static function removeVote(Review $review, User $user): bool
    {
        return self::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    /**
     * Vérifier si un utilisateur a voté pour un avis
     */
    public static function hasVoted(Review $review, User $user): bool
    {
        return self::where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Compter les votes utiles pour un avis
     */
    public static function countHelpful(Review $review): int
    {
        return self::where('review_id', $review->id)
            ->where('is_helpful', true)
            ->count();
    }
}
