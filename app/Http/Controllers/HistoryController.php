<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use App\Services\FavoriteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function __construct(
        protected FavoriteService $favoriteService,
    ) {
    }

    /**
     * Historique des vues
     */
    public function index()
    {
        $history = $this->favoriteService->getRecentViews(Auth::id(), 50);

        return view('history.index', compact('history'));
    }

    /**
     * Enregistrer une vue (API)
     */
    public function recordView(Request $request)
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'duration_seconds' => 'nullable|integer|min:0|max:3600',
        ]);

        if (Auth::check()) {
            $history = $this->favoriteService->recordView(
                Auth::id(),
                $validated['residence_id'],
                $validated['duration_seconds'] ?? 0,
            );

            return response()->json([
                'success' => true,
                'view_count' => $history->view_count,
            ]);
        }

        return response()->json(['success' => false], 401);
    }

    /**
     * Effacer l'historique
     */
    public function clear()
    {
        $count = $this->favoriteService->clearViewHistory(Auth::id());

        return back()->with('success', "{$count} entrées supprimées de l'historique.");
    }

    /**
     * Alertes de prix
     */
    public function priceAlerts()
    {
        $alerts = $this->favoriteService->getUserPriceAlerts(Auth::id());

        return view('history.price-alerts', compact('alerts'));
    }

    /**
     * Créer une alerte de prix
     */
    public function createPriceAlert(Request $request)
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'alert_type' => 'required|in:any_change,decrease_only,target_reached',
            'target_price' => 'nullable|numeric|min:0',
        ]);

        $alert = $this->favoriteService->createPriceAlert(
            Auth::id(),
            $validated['residence_id'],
            $validated['alert_type'],
            $validated['target_price'] ?? null,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alerte de prix créée',
                'alert' => $alert,
            ]);
        }

        return back()->with('success', 'Alerte de prix activée.');
    }

    /**
     * Désactiver une alerte
     */
    public function deactivatePriceAlert(int $alertId)
    {
        $this->favoriteService->deactivatePriceAlert(Auth::id(), $alertId);

        return back()->with('success', 'Alerte désactivée.');
    }

    /**
     * Recherches sauvegardées
     */
    public function savedSearches()
    {
        $searches = SavedSearch::where('user_id', Auth::id())
            ->orderByDesc('last_searched_at')
            ->get();

        return view('history.saved-searches', compact('searches'));
    }

    /**
     * Sauvegarder une recherche
     */
    public function saveSearch(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'location' => 'nullable|string|max:200',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'check_in' => 'nullable|date',
            'check_out' => 'nullable|date|after:check_in',
            'guests' => 'nullable|integer|min:1',
            'filters' => 'nullable|array',
            'has_alerts' => 'boolean',
            'alert_frequency' => 'nullable|in:instant,daily,weekly',
        ]);

        $search = SavedSearch::createFromFilters(Auth::id(), $validated['name'], $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Recherche sauvegardée',
                'search' => $search,
            ]);
        }

        return back()->with('success', 'Recherche sauvegardée.');
    }

    /**
     * Exécuter une recherche sauvegardée
     */
    public function executeSearch(SavedSearch $search)
    {
        if ($search->user_id !== Auth::id()) {
            abort(403);
        }

        $search->markSearched();
        $search->resetNewResults();

        return redirect($search->buildSearchUrl());
    }

    /**
     * Modifier une recherche sauvegardée
     */
    public function updateSearch(Request $request, SavedSearch $search)
    {
        if ($search->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'has_alerts' => 'boolean',
            'alert_frequency' => 'nullable|in:instant,daily,weekly',
        ]);

        $search->update($validated);

        return back()->with('success', 'Recherche mise à jour.');
    }

    /**
     * Supprimer une recherche sauvegardée
     */
    public function deleteSearch(SavedSearch $search)
    {
        if ($search->user_id !== Auth::id()) {
            abort(403);
        }

        $search->delete();

        return back()->with('success', 'Recherche supprimée.');
    }
}
