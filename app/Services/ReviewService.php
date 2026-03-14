<?php

namespace App\Services;

use App\Models\PublicProfile;
use App\Models\Residence;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewService
{
    protected BadgeService $badgeService;
    protected SentimentAnalysisService $sentimentService;

    public function __construct(BadgeService $badgeService, SentimentAnalysisService $sentimentService)
    {
        $this->badgeService = $badgeService;
        $this->sentimentService = $sentimentService;
    }

    /**
     * Créer un nouvel avis vérifié
     */
    public function createReview(User $user, Residence $residence, array $data): Review
    {
        // Vérifier si l'utilisateur peut laisser un avis
        if (!$this->canUserReview($user, $residence)) {
            throw new \Exception('Vous ne pouvez pas laisser d\'avis sur cette résidence.');
        }

        // Analyser le sentiment du commentaire via Google Natural Language API
        $sentimentData = $this->sentimentService->analyzeReview($data['comment'] ?? '');

        $review = Review::create([
            'user_id' => $user->id,
            'residence_id' => $residence->id,
            'rating' => $data['rating'],
            'cleanliness_rating' => $data['cleanliness_rating'] ?? null,
            'location_rating' => $data['location_rating'] ?? null,
            'value_rating' => $data['value_rating'] ?? null,
            'communication_rating' => $data['communication_rating'] ?? null,
            'accuracy_rating' => $data['accuracy_rating'] ?? null,
            'checkin_rating' => $data['checkin_rating'] ?? null,
            'comment' => $data['comment'],
            'stay_start_date' => $data['stay_start_date'] ?? null,
            'stay_end_date' => $data['stay_end_date'] ?? null,
            'is_verified' => $this->isVerifiedStay($user, $residence, $data),
            'status' => Review::STATUS_PENDING,
            'sentiment_score' => $sentimentData['sentiment_score'],
            'sentiment_label' => $sentimentData['sentiment_label'],
            'needs_moderation' => $sentimentData['needs_moderation'],
        ]);

        // Traiter les photos si présentes
        if (!empty($data['photos'])) {
            $this->addPhotosToReview($review, $data['photos']);
        }

        // Mettre à jour le profil public du reviewer
        $this->updateReviewerProfile($user);

        return $review;
    }

    /**
     * Vérifier si l'utilisateur peut laisser un avis
     */
    public function canUserReview(User $user, Residence $residence): bool
    {
        // Ne peut pas commenter sa propre résidence
        if ($residence->owner_id === $user->id) {
            return false;
        }

        // Vérifier si un avis existe déjà
        $existingReview = Review::where('user_id', $user->id)
            ->where('residence_id', $residence->id)
            ->exists();

        if ($existingReview) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si c'est un séjour vérifié
     */
    protected function isVerifiedStay(User $user, Residence $residence, array $data): bool
    {
        // Logique de vérification du séjour
        // À adapter selon le système de réservation
        // Pour l'instant, on vérifie si des dates de séjour sont fournies
        return !empty($data['stay_start_date']) && !empty($data['stay_end_date']);
    }

    /**
     * Ajouter des photos à un avis
     */
    public function addPhotosToReview(Review $review, array $photos): void
    {
        $photoPaths = [];

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $path = $photo->store('reviews/'.$review->id, 'public');
                $photoPaths[] = $path;
            } elseif (is_string($photo)) {
                $photoPaths[] = $photo;
            }
        }

        if (!empty($photoPaths)) {
            $review->addPhotos($photoPaths);
        }
    }

    /**
     * Mettre à jour le profil public d'un reviewer
     */
    protected function updateReviewerProfile(User $user): void
    {
        $profile = PublicProfile::getOrCreateForUser($user);

        $givenCount = Review::where('user_id', $user->id)->approved()->count();
        $receivedCount = Review::whereHas('residence', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->approved()->count();

        $profile->updateReviewCounts($givenCount, $receivedCount);

        // Réévaluer les badges
        $this->badgeService->evaluateTopReviewer($user);
    }

    /**
     * Ajouter une réponse du propriétaire
     */
    public function addOwnerResponse(Review $review, User $owner, string $response): void
    {
        // Vérifier que c'est bien le propriétaire
        if ($review->residence->owner_id !== $owner->id) {
            throw new \Exception('Vous n\'êtes pas autorisé à répondre à cet avis.');
        }

        $review->addOwnerResponse($response);

        // Réévaluer les badges du propriétaire
        $this->badgeService->calculateResponseMetrics($owner);
    }

    /**
     * Ajouter l'avis du propriétaire sur le voyageur
     */
    public function addOwnerReviewForGuest(Review $review, User $owner, string $reviewText): void
    {
        if ($review->residence->owner_id !== $owner->id) {
            throw new \Exception('Vous n\'êtes pas autorisé à évaluer ce voyageur.');
        }

        $review->addOwnerReviewForGuest($reviewText);
    }

    /**
     * Approuver un avis (admin)
     */
    public function approveReview(Review $review): void
    {
        $review->approve();

        // Mettre à jour les statistiques de la résidence
        $this->updateResidenceStats($review->residence);

        // Réévaluer les badges du propriétaire
        $owner = $review->residence->owner;
        $this->badgeService->evaluateSuperhost($owner);
        $this->badgeService->evaluateExperiencedHost($owner);

        // Mettre à jour le profil du propriétaire
        $this->updateOwnerProfile($owner);
    }

    /**
     * Rejeter un avis (admin)
     */
    public function rejectReview(Review $review): void
    {
        $review->reject();
    }

    /**
     * Mettre à jour les statistiques de la résidence
     */
    protected function updateResidenceStats(Residence $residence): void
    {
        $stats = Review::where('residence_id', $residence->id)
            ->approved()
            ->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                AVG(cleanliness_rating) as avg_cleanliness,
                AVG(location_rating) as avg_location,
                AVG(value_rating) as avg_value,
                AVG(communication_rating) as avg_communication,
                AVG(accuracy_rating) as avg_accuracy,
                AVG(checkin_rating) as avg_checkin
            ')
            ->first();

        // Mettre à jour la résidence
        $residence->update([
            'average_rating' => round($stats->avg_rating, 1),
            'reviews_count' => $stats->total_reviews,
        ]);
    }

    /**
     * Mettre à jour le profil public du propriétaire
     */
    protected function updateOwnerProfile(User $owner): void
    {
        $profile = PublicProfile::getOrCreateForUser($owner);

        $givenCount = Review::where('user_id', $owner->id)->approved()->count();
        $receivedCount = Review::whereHas('residence', function ($q) use ($owner) {
            $q->where('owner_id', $owner->id);
        })->approved()->count();

        $profile->updateReviewCounts($givenCount, $receivedCount);
    }

    /**
     * Signaler un avis
     */
    public function reportReview(Review $review, User $reporter, string $reason, ?string $details = null): ReviewReport
    {
        // Vérifier si l'utilisateur n'a pas déjà signalé cet avis
        if ($review->isReportedBy($reporter)) {
            throw new \Exception('Vous avez déjà signalé cet avis.');
        }

        return $review->report($reporter, $reason, $details);
    }

    /**
     * Résoudre un signalement (admin)
     */
    public function resolveReport(ReviewReport $report, User $admin, string $notes, bool $removeReview = false): void
    {
        $report->resolve($admin, $notes, $removeReview);
    }

    /**
     * Rejeter un signalement (admin)
     */
    public function dismissReport(ReviewReport $report, User $admin, ?string $notes = null): void
    {
        $report->dismiss($admin, $notes);
    }

    /**
     * Voter qu'un avis est utile
     */
    public function voteHelpful(Review $review, User $user): void
    {
        $review->voteHelpful($user);
    }

    /**
     * Retirer son vote
     */
    public function removeHelpfulVote(Review $review, User $user): void
    {
        $review->removeHelpfulVote($user);
    }

    /**
     * Mettre en avant un avis (admin/propriétaire)
     */
    public function featureReview(Review $review): void
    {
        $review->feature();
    }

    /**
     * Retirer la mise en avant
     */
    public function unfeatureReview(Review $review): void
    {
        $review->unfeature();
    }

    /**
     * Obtenir les avis d'une résidence avec statistiques
     */
    public function getResidenceReviews(Residence $residence, array $options = []): array
    {
        $query = Review::where('residence_id', $residence->id)
            ->approved()
            ->with(['user', 'helpfulVotes']);

        // Tri
        $sortBy = $options['sort'] ?? 'recent';
        switch ($sortBy) {
            case 'helpful':
                $query->mostHelpful();
                break;
            case 'rating_high':
                $query->orderByDesc('rating');
                break;
            case 'rating_low':
                $query->orderBy('rating');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        // Filtres
        if (!empty($options['verified_only'])) {
            $query->verified();
        }

        if (!empty($options['with_photos'])) {
            $query->withPhotos();
        }

        if (!empty($options['min_rating'])) {
            $query->where('rating', '>=', $options['min_rating']);
        }

        // Pagination
        $perPage = $options['per_page'] ?? 10;
        $reviews = $query->paginate($perPage);

        // Calculer les statistiques
        $stats = $this->calculateResidenceReviewStats($residence);

        return [
            'reviews' => $reviews,
            'stats' => $stats,
        ];
    }

    /**
     * Calculer les statistiques d'avis d'une résidence
     */
    public function calculateResidenceReviewStats(Residence $residence): array
    {
        $reviews = Review::where('residence_id', $residence->id)->approved();

        $stats = $reviews->selectRaw('
            COUNT(*) as total,
            AVG(rating) as avg_rating,
            AVG(cleanliness_rating) as avg_cleanliness,
            AVG(location_rating) as avg_location,
            AVG(value_rating) as avg_value,
            AVG(communication_rating) as avg_communication,
            AVG(accuracy_rating) as avg_accuracy,
            AVG(checkin_rating) as avg_checkin,
            SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
        ')->first();

        $verifiedCount = Review::where('residence_id', $residence->id)
            ->approved()
            ->verified()
            ->count();

        return [
            'total_reviews' => $stats->total ?? 0,
            'average_rating' => $stats->avg_rating ? round($stats->avg_rating, 1) : null,
            'verified_count' => $verifiedCount,
            'rating_breakdown' => [
                5 => $stats->five_star ?? 0,
                4 => $stats->four_star ?? 0,
                3 => $stats->three_star ?? 0,
                2 => $stats->two_star ?? 0,
                1 => $stats->one_star ?? 0,
            ],
            'criteria_ratings' => [
                'cleanliness' => $stats->avg_cleanliness ? round($stats->avg_cleanliness, 1) : null,
                'location' => $stats->avg_location ? round($stats->avg_location, 1) : null,
                'value' => $stats->avg_value ? round($stats->avg_value, 1) : null,
                'communication' => $stats->avg_communication ? round($stats->avg_communication, 1) : null,
                'accuracy' => $stats->avg_accuracy ? round($stats->avg_accuracy, 1) : null,
                'checkin' => $stats->avg_checkin ? round($stats->avg_checkin, 1) : null,
            ],
        ];
    }

    /**
     * Obtenir les avis d'un utilisateur (donnés et reçus)
     */
    public function getUserReviews(User $user, string $type = 'given'): LengthAwarePaginator
    {
        if ($type === 'given') {
            return Review::where('user_id', $user->id)
                ->approved()
                ->with(['residence', 'residence.photos'])
                ->orderByDesc('created_at')
                ->paginate(10);
        }

        // Avis reçus (pour les propriétaires)
        return Review::whereHas('residence', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
            ->approved()
            ->with(['user', 'residence'])
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    /**
     * Obtenir les avis en attente de modération (admin)
     */
    public function getPendingReviews(): LengthAwarePaginator
    {
        return Review::pending()
            ->with(['user', 'residence', 'reports'])
            ->orderBy('created_at')
            ->paginate(20);
    }

    /**
     * Obtenir les signalements en attente (admin)
     */
    public function getPendingReports(): LengthAwarePaginator
    {
        return ReviewReport::pending()
            ->with(['review', 'review.user', 'reporter'])
            ->orderBy('created_at')
            ->paginate(20);
    }

    /**
     * Marquer un avis comme vérifié
     */
    public function verifyReview(Review $review): void
    {
        $review->markAsVerified();
    }
}
