<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\PublicProfile;
use App\Models\Review;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicProfileController extends Controller
{
    protected BadgeService $badgeService;
    protected ReviewService $reviewService;

    public function __construct(BadgeService $badgeService, ReviewService $reviewService)
    {
        $this->badgeService = $badgeService;
        $this->reviewService = $reviewService;
    }

    /**
     * Afficher le profil d'un utilisateur — accessible uniquement par lui-même.
     */
    public function show(User $user)
    {
        // Seul l'utilisateur peut voir son propre profil
        if (Auth::id() !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter ce profil.');
        }

        // Obtenir ou créer le profil
        $profile = PublicProfile::getOrCreateForUser($user);

        // Obtenir les badges actifs
        $badges = $user->activeBadges()->get();

        // Données supplémentaires selon le rôle
        $data = [
            'user' => $user,
            'profile' => $profile,
            'badges' => $badges,
            'badgeTypes' => Badge::getTypes(),
        ];

        if ($user->isOwner() || $user->hasAnyRole(['admin'])) {
            // Pour les propriétaires : leurs résidences et avis reçus
            $data['residences'] = $user->residences()
                ->where('status', 'active')
                ->with(['photos', 'reviews' => fn ($q) => $q->approved()])
                ->latest()
                ->take(6)
                ->get();

            $data['receivedReviews'] = Review::whereHas('residence', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })
                ->approved()
                ->with(['user', 'residence'])
                ->latest()
                ->take(5)
                ->get();

            $data['averageRating'] = $user->getAverageReceivedRating();
            $data['totalReviews'] = $user->getTotalReceivedReviewsCount();
        } else {
            // Pour les utilisateurs : leurs avis donnés
            $data['givenReviews'] = $user->reviews()
                ->approved()
                ->with(['residence', 'residence.photos'])
                ->latest()
                ->take(5)
                ->get();

            $data['averageGivenRating'] = $user->getAverageGivenRating();
            $data['totalGivenReviews'] = $user->getTotalGivenReviewsCount();
        }

        return view('profiles.show', $data);
    }

    /**
     * Afficher les avis reçus — accessible uniquement par l'utilisateur lui-même.
     */
    public function receivedReviews(User $user)
    {
        if (Auth::id() !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter ces informations.');
        }

        if (!$user->isOwner()) {
            abort(404);
        }

        $reviews = $this->reviewService->getUserReviews($user, 'received');

        return view('profiles.received-reviews', [
            'user' => $user,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Afficher les avis donnés — accessible uniquement par l'utilisateur lui-même.
     */
    public function givenReviews(User $user)
    {
        if (Auth::id() !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter ces informations.');
        }

        $reviews = $this->reviewService->getUserReviews($user, 'given');

        return view('profiles.given-reviews', [
            'user' => $user,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Éditer son profil public
     */
    public function edit()
    {
        $user = Auth::user();
        $profile = PublicProfile::getOrCreateForUser($user);

        return view('profiles.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Mettre à jour son profil public
     */
    public function update(Request $request)
    {
        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'languages' => 'nullable|array',
            'languages.*' => 'string|max:50',
            'location' => 'nullable|string|max:255',
            'work' => 'nullable|string|max:255',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
        ]);

        $user = Auth::user();
        $profile = PublicProfile::getOrCreateForUser($user);

        $profile->update([
            'bio' => $request->input('bio'),
            'languages' => $request->input('languages', []),
            'location' => $request->input('location'),
            'work' => $request->input('work'),
            'show_email' => $request->boolean('show_email'),
            'show_phone' => $request->boolean('show_phone'),
        ]);

        return redirect()->route('profile.public', $user)
            ->with('success', 'Votre profil public a été mis à jour.');
    }

    /**
     * Afficher les badges — accessible uniquement par l'utilisateur lui-même.
     */
    public function badges(User $user)
    {
        if (Auth::id() !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter ces informations.');
        }

        $activeBadges = $user->activeBadges()->get();
        $allBadgeTypes = Badge::getTypes();

        // Calculer la progression vers les badges non obtenus
        $badgeProgress = $this->calculateBadgeProgress($user);

        return view('profiles.badges', [
            'user' => $user,
            'activeBadges' => $activeBadges,
            'allBadgeTypes' => $allBadgeTypes,
            'badgeProgress' => $badgeProgress,
        ]);
    }

    /**
     * Calculer la progression vers les badges
     */
    protected function calculateBadgeProgress(User $user): array
    {
        $progress = [];

        // Superhost
        if ($user->isOwner()) {
            $reviewsCount = Review::whereHas('residence', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->approved()->count();

            $avgRating = Review::whereHas('residence', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->approved()->avg('rating') ?? 0;

            $progress[Badge::TYPE_SUPERHOST] = [
                'reviews' => [
                    'current' => $reviewsCount,
                    'required' => BadgeService::SUPERHOST_MIN_REVIEWS,
                    'percentage' => min(100, ($reviewsCount / BadgeService::SUPERHOST_MIN_REVIEWS) * 100),
                ],
                'rating' => [
                    'current' => round($avgRating, 1),
                    'required' => BadgeService::SUPERHOST_MIN_RATING,
                    'percentage' => $avgRating > 0 ? min(100, ($avgRating / BadgeService::SUPERHOST_MIN_RATING) * 100) : 0,
                ],
            ];

            // Hôte expérimenté
            $progress[Badge::TYPE_EXPERIENCED_HOST] = [
                'stays' => [
                    'current' => $reviewsCount,
                    'required' => BadgeService::EXPERIENCED_HOST_MIN_STAYS,
                    'percentage' => min(100, ($reviewsCount / BadgeService::EXPERIENCED_HOST_MIN_STAYS) * 100),
                ],
            ];
        }

        // Contributeur actif
        $detailedReviews = Review::where('user_id', $user->id)
            ->approved()
            ->whereRaw('LENGTH(comment) >= ?', [BadgeService::TOP_REVIEWER_MIN_CHARS])
            ->count();

        $progress[Badge::TYPE_TOP_REVIEWER] = [
            'reviews' => [
                'current' => $detailedReviews,
                'required' => BadgeService::TOP_REVIEWER_MIN_REVIEWS,
                'percentage' => min(100, ($detailedReviews / BadgeService::TOP_REVIEWER_MIN_REVIEWS) * 100),
            ],
        ];

        // Voyageur de confiance
        $staysCount = Review::where('user_id', $user->id)->approved()->count();
        $progress[Badge::TYPE_TRUSTED_GUEST] = [
            'stays' => [
                'current' => $staysCount,
                'required' => BadgeService::TRUSTED_GUEST_MIN_STAYS,
                'percentage' => min(100, ($staysCount / BadgeService::TRUSTED_GUEST_MIN_STAYS) * 100),
            ],
        ];

        return $progress;
    }

    /**
     * Réévaluer ses badges (pour rafraîchir)
     */
    public function refreshBadges()
    {
        $user = Auth::user();
        $awarded = $this->badgeService->evaluateAllBadges($user);

        if (count($awarded) > 0) {
            $message = 'Félicitations ! Vous avez obtenu : '.implode(', ', array_map(
                fn ($type) => Badge::getTypes()[$type]['name'] ?? $type,
                $awarded,
            ));
        } else {
            $message = 'Vos badges ont été actualisés.';
        }

        return back()->with('success', $message);
    }
}
