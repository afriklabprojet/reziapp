<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceivedReviewController extends Controller
{
    /**
     * Liste des avis reçus par le propriétaire sur ses résidences.
     */
    public function index(Request $request): View
    {
        $user        = $request->user();
        $residenceIds = $user->residences()->pluck('id');

        $filter = $request->get('filter', 'all'); // all | pending | responded

        $query = Review::with(['user', 'residence'])
            ->whereIn('residence_id', $residenceIds)
            ->where('status', 'approved')
            ->latest();

        if ($filter === 'pending') {
            $query->whereNull('owner_response');
        } elseif ($filter === 'responded') {
            $query->whereNotNull('owner_response');
        }

        $reviews = $query->paginate(15)->withQueryString();

        $stats = [
            'total'     => Review::whereIn('residence_id', $residenceIds)->where('status', 'approved')->count(),
            'pending'   => Review::whereIn('residence_id', $residenceIds)->where('status', 'approved')->whereNull('owner_response')->count(),
            'responded' => Review::whereIn('residence_id', $residenceIds)->where('status', 'approved')->whereNotNull('owner_response')->count(),
            'avg_rating' => Review::whereIn('residence_id', $residenceIds)->where('status', 'approved')->avg('rating'),
        ];

        return view('owner.received-reviews.index', compact('reviews', 'stats', 'filter'));
    }

    /**
     * Enregistrer la réponse du propriétaire à un avis.
     */
    public function respond(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();

        // Vérifier que l'avis concerne bien une résidence du propriétaire
        abort_unless(
            $user->residences()->where('id', $review->residence_id)->exists(),
            403,
        );

        $validated = $request->validate([
            'owner_response' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $review->update([
            'owner_response'    => $validated['owner_response'],
            'owner_response_at' => now(),
        ]);

        return redirect()
            ->route('owner.received-reviews.index')
            ->with('success', 'Votre réponse a été publiée.');
    }
}
