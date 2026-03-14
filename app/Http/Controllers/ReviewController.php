<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Residence;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Afficher les avis d'une résidence
     */
    public function index(Request $request, Residence $residence)
    {
        $options = [
            'sort' => $request->get('sort', 'recent'),
            'verified_only' => $request->boolean('verified_only'),
            'with_photos' => $request->boolean('with_photos'),
            'min_rating' => $request->get('min_rating'),
            'per_page' => 10,
        ];

        $data = $this->reviewService->getResidenceReviews($residence, $options);

        if ($request->ajax()) {
            return response()->json($data);
        }

        return view('reviews.index', [
            'residence' => $residence,
            'reviews' => $data['reviews'],
            'stats' => $data['stats'],
        ]);
    }

    /**
     * Formulaire pour créer un avis
     */
    public function create(Residence $residence)
    {
        $user = Auth::user();

        if (!$this->reviewService->canUserReview($user, $residence)) {
            return redirect()->route('residences.show', $residence)
                ->with('error', 'Vous ne pouvez pas laisser d\'avis sur cette résidence.');
        }

        return view('reviews.create', compact('residence'));
    }

    /**
     * Enregistrer un avis
     */
    public function store(Request $request, Residence $residence)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'location_rating' => 'nullable|integer|min:1|max:5',
            'value_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'accuracy_rating' => 'nullable|integer|min:1|max:5',
            'checkin_rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'required|string|min:20|max:2000',
            'stay_start_date' => 'nullable|date',
            'stay_end_date' => 'nullable|date|after_or_equal:stay_start_date',
            'photos.*' => 'nullable|image|max:5120',
        ]);

        try {
            $review = $this->reviewService->createReview($user, $residence, $validated);

            // Notifier le propriétaire
            Notification::send(
                $residence->owner,
                'review',
                'Nouvel avis reçu',
                $user->name.' a laissé un avis sur '.$residence->title,
                route('owner.reviews.index'),
                ['residence_id' => $residence->id, 'rating' => $validated['rating']],
            );

            return redirect()->route('residences.show', $residence)
                ->with('success', 'Votre avis a été soumis et sera publié après modération.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Afficher un avis
     */
    public function show(Review $review)
    {
        $review->load(['user', 'residence', 'residence.owner', 'helpfulVotes']);

        return view('reviews.show', compact('review'));
    }

    /**
     * Réponse du propriétaire
     */
    public function respond(Request $request, Review $review)
    {
        $request->validate([
            'owner_response' => 'required|string|min:10|max:1000',
        ]);

        try {
            $this->reviewService->addOwnerResponse(
                $review,
                Auth::user(),
                $request->owner_response,
            );

            // Notifier l'auteur de l'avis
            Notification::send(
                $review->user,
                'review',
                'Réponse à votre avis',
                'Le propriétaire a répondu à votre avis sur '.$review->residence->title,
                route('residences.show', $review->residence),
            );

            return back()->with('success', 'Votre réponse a été publiée.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Avis du propriétaire sur le voyageur
     */
    public function reviewGuest(Request $request, Review $review)
    {
        $request->validate([
            'review' => 'required|string|min:10|max:1000',
        ]);

        try {
            $this->reviewService->addOwnerReviewForGuest(
                $review,
                Auth::user(),
                $request->input('review'),
            );

            return back()->with('success', 'Votre évaluation du voyageur a été ajoutée.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Voter qu'un avis est utile
     */
    public function voteHelpful(Review $review)
    {
        $user = Auth::user();

        if ($review->hasUserVoted($user)) {
            $this->reviewService->removeHelpfulVote($review, $user);
            $message = 'Votre vote a été retiré.';
        } else {
            $this->reviewService->voteHelpful($review, $user);
            $message = 'Merci pour votre vote !';
        }

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'helpful_count' => $review->fresh()->helpful_count,
                'has_voted' => $review->fresh()->hasUserVoted($user),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Signaler un avis
     */
    public function report(Request $request, Review $review)
    {
        $request->validate([
            'reason' => 'required|string|in:'.implode(',', array_keys(ReviewReport::getReasons())),
            'details' => 'nullable|string|max:1000',
        ]);

        try {
            $this->reviewService->reportReview(
                $review,
                Auth::user(),
                $request->input('reason'),
                $request->input('details'),
            );

            return back()->with('success', 'L\'avis a été signalé. Notre équipe l\'examinera.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Liste des avis de l'utilisateur connecté
     */
    public function myReviews(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type', 'given');

        $reviews = $this->reviewService->getUserReviews($user, $type);

        return view('reviews.my-reviews', compact('reviews', 'type'));
    }
}
