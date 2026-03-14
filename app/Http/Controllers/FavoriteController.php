<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Favorite;
use App\Models\Residence;
use App\Services\FavoriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function __construct(
        protected FavoriteService $favoriteService,
    ) {
    }

    /**
     * Liste des favoris
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $collectionId = $request->input('collection');

        $favorites = $this->favoriteService->getUserFavorites($user->id, $collectionId);
        $collections = $this->favoriteService->getUserCollections($user->id);

        return view('favorites.index', compact('favorites', 'collections', 'collectionId'));
    }

    /**
     * Ajouter/retirer un favori (toggle)
     */
    public function toggle(Request $request, Residence $residence)
    {
        $result = $this->favoriteService->toggleFavorite(Auth::id(), $residence->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $result['is_favorite'],
                'favorited' => $result['is_favorite'],
                'message' => $result['is_favorite']
                    ? 'Ajouté aux favoris'
                    : 'Retiré des favoris',
            ]);
        }

        return back()->with(
            'success',
            $result['is_favorite'] ? 'Résidence ajoutée aux favoris.' : 'Résidence retirée des favoris.',
        );
    }

    /**
     * Ajouter avec collection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'collection_id' => 'nullable|exists:collections,id',
            'notes' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
        ]);

        $favorite = $this->favoriteService->addToFavorites(
            Auth::id(),
            $validated['residence_id'],
            $validated['collection_id'] ?? null,
            $validated['notes'] ?? null,
            $validated['tags'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ajouté aux favoris',
                'favorite' => $favorite,
            ]);
        }

        return back()->with('success', 'Résidence ajoutée aux favoris.');
    }

    /**
     * Ajouter une note à un favori
     */
    public function updateNote(Request $request, Favorite $favorite)
    {
        if ($favorite->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $favorite->update(['notes' => $request->notes]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Note mise à jour.');
    }

    /**
     * Déplacer vers une collection
     */
    public function moveToCollection(Request $request, int $residenceId)
    {
        $validated = $request->validate([
            'collection_id' => 'nullable|exists:collections,id',
        ]);

        $favorite = $this->favoriteService->moveToCollection(
            Auth::id(),
            $residenceId,
            $validated['collection_id'],
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Déplacé vers la collection',
                'favorite' => $favorite->load('collection'),
            ]);
        }

        return back()->with('success', 'Favori déplacé.');
    }

    /**
     * Vérifier si c'est un favori (API)
     */
    public function check(int $residenceId)
    {
        $isFavorite = Auth::check()
            ? $this->favoriteService->isFavorite(Auth::id(), $residenceId)
            : false;

        return response()->json(['is_favorite' => $isFavorite]);
    }

    /**
     * Supprimer un favori
     */
    public function destroy(Favorite $favorite)
    {
        if ($favorite->user_id !== Auth::id()) {
            abort(403);
        }

        $collection = $favorite->collection;
        $favorite->delete();

        if ($collection) {
            $collection->updateFavoritesCount();
        }

        return back()->with('success', 'Résidence retirée des favoris.');
    }
}
