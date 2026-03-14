<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Message;
use App\Models\PublicProfile;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * Critères pour le badge Superhost
     */
    public const SUPERHOST_MIN_REVIEWS = 5;
    public const SUPERHOST_MIN_RATING = 4.5;
    public const SUPERHOST_MAX_CANCELLATION_RATE = 1; // 1%
    public const SUPERHOST_MIN_RESPONSE_RATE = 90; // 90%

    /**
     * Critères pour le badge Réponse rapide
     */
    public const FAST_RESPONDER_MAX_HOURS = 1;
    public const FAST_RESPONDER_MIN_RESPONSE_RATE = 90;
    public const FAST_RESPONDER_MIN_MESSAGES = 10;

    /**
     * Critères pour le badge Hôte expérimenté
     */
    public const EXPERIENCED_HOST_MIN_STAYS = 10;

    /**
     * Critères pour le badge Contributeur actif
     */
    public const TOP_REVIEWER_MIN_REVIEWS = 5;
    public const TOP_REVIEWER_MIN_CHARS = 100;

    /**
     * Critères pour le badge Voyageur de confiance
     */
    public const TRUSTED_GUEST_MIN_STAYS = 3;
    public const TRUSTED_GUEST_MIN_RATING = 4.0;

    /**
     * Évaluer et attribuer tous les badges pour un utilisateur
     */
    public function evaluateAllBadges(User $user): array
    {
        $awarded = [];

        // Badges pour propriétaires
        if ($user->isOwner() || $user->isAdmin()) {
            if ($this->evaluateSuperhost($user)) {
                $awarded[] = Badge::TYPE_SUPERHOST;
            }

            if ($this->evaluateFastResponder($user)) {
                $awarded[] = Badge::TYPE_FAST_RESPONDER;
            }

            if ($this->evaluateExperiencedHost($user)) {
                $awarded[] = Badge::TYPE_EXPERIENCED_HOST;
            }
        }

        // Badges pour tous les utilisateurs
        if ($this->evaluateVerified($user)) {
            $awarded[] = Badge::TYPE_VERIFIED;
        }

        if ($this->evaluateTopReviewer($user)) {
            $awarded[] = Badge::TYPE_TOP_REVIEWER;
        }

        if ($this->evaluateTrustedGuest($user)) {
            $awarded[] = Badge::TYPE_TRUSTED_GUEST;
        }

        return $awarded;
    }

    /**
     * Évaluer le badge Superhost
     */
    public function evaluateSuperhost(User $user): bool
    {
        // Vérifier le nombre d'avis
        $reviewsCount = Review::whereHas('residence', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->approved()->count();

        if ($reviewsCount < self::SUPERHOST_MIN_REVIEWS) {
            $this->revokeBadge($user, Badge::TYPE_SUPERHOST);

            return false;
        }

        // Vérifier la note moyenne
        $avgRating = Review::whereHas('residence', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->approved()->avg('rating');

        if ($avgRating < self::SUPERHOST_MIN_RATING) {
            $this->revokeBadge($user, Badge::TYPE_SUPERHOST);

            return false;
        }

        // Vérifier le profil public pour le taux de réponse
        $profile = $user->publicProfile;
        if ($profile && $profile->response_rate < self::SUPERHOST_MIN_RESPONSE_RATE) {
            $this->revokeBadge($user, Badge::TYPE_SUPERHOST);

            return false;
        }

        // Attribuer le badge avec date d'expiration d'un an
        $this->awardBadge($user, Badge::TYPE_SUPERHOST, [
            'reviews_count' => $reviewsCount,
            'average_rating' => round($avgRating, 2),
            'response_rate' => $profile?->response_rate,
        ], 365);

        // Mettre à jour le profil public
        if ($profile) {
            $profile->updateSuperhostStatus(true);
        }

        return true;
    }

    /**
     * Évaluer le badge Réponse rapide
     */
    public function evaluateFastResponder(User $user): bool
    {
        // Calculer les métriques de réponse
        $metrics = $this->calculateResponseMetrics($user);

        if (
            $metrics['total_received'] < self::FAST_RESPONDER_MIN_MESSAGES ||
            $metrics['response_rate'] < self::FAST_RESPONDER_MIN_RESPONSE_RATE ||
            ($metrics['avg_response_hours'] && $metrics['avg_response_hours'] > self::FAST_RESPONDER_MAX_HOURS)
        ) {
            $this->revokeBadge($user, Badge::TYPE_FAST_RESPONDER);

            return false;
        }

        $this->awardBadge($user, Badge::TYPE_FAST_RESPONDER, [
            'avg_response_hours' => round($metrics['avg_response_hours'], 2),
            'response_rate' => round($metrics['response_rate'], 2),
            'total_messages' => $metrics['total_received'],
        ], 90); // Expire après 90 jours

        return true;
    }

    /**
     * Évaluer le badge Vérifié
     */
    public function evaluateVerified(User $user): bool
    {
        // Vérifier l'email
        if (!$user->email_verified_at) {
            $this->revokeBadge($user, Badge::TYPE_VERIFIED);

            return false;
        }

        // Vérifier le téléphone (si le champ existe)
        $hasPhoneVerified = $user->phone && DB::table('phone_verifications')
            ->where('user_id', $user->id)
            ->where('verified_at', '!=', null)
            ->exists();

        // Vérifier l'identité
        $hasIdentityVerified = DB::table('identity_verifications')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        if (!$hasPhoneVerified && !$hasIdentityVerified) {
            $this->revokeBadge($user, Badge::TYPE_VERIFIED);

            return false;
        }

        $this->awardBadge($user, Badge::TYPE_VERIFIED, [
            'email_verified' => true,
            'phone_verified' => $hasPhoneVerified,
            'identity_verified' => $hasIdentityVerified,
        ], null); // Pas d'expiration

        return true;
    }

    /**
     * Évaluer le badge Hôte expérimenté
     */
    public function evaluateExperiencedHost(User $user): bool
    {
        // Compter les séjours réussis (avis approuvés comme proxy)
        $completedStays = Review::whereHas('residence', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })->approved()->count();

        if ($completedStays < self::EXPERIENCED_HOST_MIN_STAYS) {
            $this->revokeBadge($user, Badge::TYPE_EXPERIENCED_HOST);

            return false;
        }

        $this->awardBadge($user, Badge::TYPE_EXPERIENCED_HOST, [
            'completed_stays' => $completedStays,
        ], null);

        return true;
    }

    /**
     * Évaluer le badge Contributeur actif (Top Reviewer)
     */
    public function evaluateTopReviewer(User $user): bool
    {
        // Compter les avis détaillés
        $detailedReviews = Review::where('user_id', $user->id)
            ->approved()
            ->whereRaw('LENGTH(comment) >= ?', [self::TOP_REVIEWER_MIN_CHARS])
            ->count();

        if ($detailedReviews < self::TOP_REVIEWER_MIN_REVIEWS) {
            $this->revokeBadge($user, Badge::TYPE_TOP_REVIEWER);

            return false;
        }

        $this->awardBadge($user, Badge::TYPE_TOP_REVIEWER, [
            'detailed_reviews' => $detailedReviews,
            'min_chars_required' => self::TOP_REVIEWER_MIN_CHARS,
        ], null);

        return true;
    }

    /**
     * Évaluer le badge Voyageur de confiance
     */
    public function evaluateTrustedGuest(User $user): bool
    {
        // Nombre d'avis laissés (comme proxy pour les séjours)
        $staysCount = Review::where('user_id', $user->id)->approved()->count();

        if ($staysCount < self::TRUSTED_GUEST_MIN_STAYS) {
            $this->revokeBadge($user, Badge::TYPE_TRUSTED_GUEST);

            return false;
        }

        // Note moyenne reçue des propriétaires
        // (owner_review_for_guest est le commentaire du proprio sur le voyageur)
        $guestRating = Review::where('user_id', $user->id)
            ->whereNotNull('owner_review_for_guest')
            ->count();

        $this->awardBadge($user, Badge::TYPE_TRUSTED_GUEST, [
            'completed_stays' => $staysCount,
            'owner_reviews' => $guestRating,
        ], null);

        return true;
    }

    /**
     * Attribuer un badge à un utilisateur
     */
    public function awardBadge(User $user, string $badgeType, array $criteria = [], ?int $expirationDays = null): Badge
    {
        $data = [
            'badge_type' => $badgeType,
            'earned_at' => now(),
            'criteria_met' => $criteria,
        ];

        if ($expirationDays) {
            $data['expires_at'] = now()->addDays($expirationDays);
        }

        return Badge::updateOrCreate(
            ['user_id' => $user->id, 'badge_type' => $badgeType],
            $data,
        );
    }

    /**
     * Révoquer un badge
     */
    public function revokeBadge(User $user, string $badgeType): bool
    {
        $badge = Badge::where('user_id', $user->id)
            ->where('badge_type', $badgeType)
            ->first();

        if ($badge) {
            // Mettre à jour le profil public si c'est le badge Superhost
            if ($badgeType === Badge::TYPE_SUPERHOST && $user->publicProfile) {
                $user->publicProfile->updateSuperhostStatus(false);
            }

            return $badge->delete();
        }

        return false;
    }

    /**
     * Calculer les métriques de réponse d'un utilisateur
     */
    public function calculateResponseMetrics(User $user): array
    {
        // Messages reçus dans les 30 derniers jours
        $since = Carbon::now()->subDays(30);

        $receivedMessages = Message::where('sender_id', '!=', $user->id)
            ->whereHas('conversation', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('owner_id', $user->id);
            })
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();

        $totalReceived = $receivedMessages->count();
        $responded = 0;
        $totalResponseTime = 0;

        foreach ($receivedMessages as $received) {
            // Trouver la première réponse
            $response = Message::where('sender_id', $user->id)
                ->where('conversation_id', $received->conversation_id)
                ->where('created_at', '>', $received->created_at)
                ->orderBy('created_at')
                ->first();

            if ($response) {
                $responded++;
                $responseTime = $received->created_at->diffInMinutes($response->created_at);
                $totalResponseTime += $responseTime;
            }
        }

        $responseRate = $totalReceived > 0 ? ($responded / $totalReceived) * 100 : 0;
        $avgResponseMinutes = $responded > 0 ? $totalResponseTime / $responded : null;
        $avgResponseHours = $avgResponseMinutes ? $avgResponseMinutes / 60 : null;

        // Mettre à jour le profil public
        $profile = PublicProfile::getOrCreateForUser($user);
        $profile->updateResponseMetrics($avgResponseHours ?? 0, $responseRate);

        return [
            'total_received' => $totalReceived,
            'total_responded' => $responded,
            'response_rate' => $responseRate,
            'avg_response_minutes' => $avgResponseMinutes,
            'avg_response_hours' => $avgResponseHours,
        ];
    }

    /**
     * Réévaluer les badges expirés pour tous les utilisateurs
     */
    public function reevaluateExpiredBadges(): int
    {
        $count = 0;

        // Récupérer les badges expirés
        $expiredBadges = Badge::where('expires_at', '<', now())->get();

        foreach ($expiredBadges as $badge) {
            $user = $badge->user;

            // Réévaluer le badge
            $method = 'evaluate'.str_replace('_', '', ucwords($badge->badge_type, '_'));
            if (method_exists($this, $method)) {
                $this->$method($user);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Obtenir le résumé des badges d'un utilisateur
     */
    public function getBadgeSummary(User $user): array
    {
        $badges = $user->activeBadges()->get();

        return [
            'total' => $badges->count(),
            'badges' => $badges->map(function ($badge) {
                return [
                    'type' => $badge->badge_type,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'color' => $badge->color,
                    'earned_at' => $badge->earned_at,
                    'expires_at' => $badge->expires_at,
                    'criteria' => $badge->criteria_met,
                ];
            }),
            'is_superhost' => $user->isSuperhost(),
            'is_fast_responder' => $user->isFastResponder(),
        ];
    }
}
