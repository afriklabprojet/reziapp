<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\FavoriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CollectionController extends Controller
{
    public function __construct(
        protected FavoriteService $favoriteService,
    ) {
    }

    /**
     * Liste des collections
     */
    public function index()
    {
        $collections = $this->favoriteService->getUserCollections(Auth::id());

        return view('collections.index', compact('collections'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        return view('collections.create');
    }

    /**
     * Créer une collection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        $collection = $this->favoriteService->createCollection(
            Auth::id(),
            $validated['name'],
            $validated['description'] ?? null,
            $validated['is_public'] ?? false,
        );

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('collections', 'public');
            $collection->update(['cover_image' => $path]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection créée',
                'collection' => $collection,
            ]);
        }

        return redirect()->route('collections.show', $collection)
            ->with('success', 'Collection créée avec succès.');
    }

    /**
     * Afficher une collection
     */
    public function show(Collection $collection)
    {
        // Vérifier l'accès
        if (!$collection->is_public && $collection->user_id !== Auth::id()) {
            abort(403, 'Cette collection est privée.');
        }

        $favorites = $collection->favorites()
            ->with(['residence.photos'])
            ->get();

        return view('collections.show', compact('collection', 'favorites'));
    }

    /**
     * Collection partagée par token
     */
    public function shared(string $token)
    {
        $collection = Collection::where('share_token', $token)->firstOrFail();

        if (!$collection->is_public) {
            abort(404, 'Cette collection n\'est pas disponible.');
        }

        $favorites = $collection->favorites()
            ->with(['residence.photos'])
            ->get();

        return view('collections.shared', compact('collection', 'favorites'));
    }

    /**
     * Modifier une collection
     */
    public function update(Request $request, Collection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'cover_image' => 'nullable|image|max:2048',
        ]);

        $collection->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
        ]);

        if ($request->hasFile('cover_image')) {
            // Delete old image
            if ($collection->cover_image) {
                Storage::disk('public')->delete($collection->cover_image);
            }
            $path = $request->file('cover_image')->store('collections', 'public');
            $collection->update(['cover_image' => $path]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collection mise à jour',
                'collection' => $collection,
            ]);
        }

        return back()->with('success', 'Collection mise à jour.');
    }

    /**
     * Supprimer une collection
     */
    public function destroy(Collection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            abort(403);
        }

        $this->favoriteService->deleteCollection(Auth::id(), $collection->id);

        return redirect()->route('favorites.index')
            ->with('success', 'Collection supprimée. Les favoris ont été déplacés.');
    }

    /**
     * Régénérer le token de partage
     */
    public function regenerateToken(Collection $collection)
    {
        if ($collection->user_id !== Auth::id()) {
            abort(403);
        }

        $collection->regenerateShareToken();

        return response()->json([
            'success' => true,
            'share_url' => $collection->getShareUrl(),
        ]);
    }
}
