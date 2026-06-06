<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchResidenceRequest;
use App\Http\Requests\StoreResidenceRequest;
use App\Http\Requests\UpdateResidenceRequest;
use App\Http\Resources\ResidenceCollection;
use App\Http\Resources\ResidenceResource;
use App\Models\Residence;
use App\Services\CacheInvalidationService;
use App\Services\GeolocationService;
use App\Services\ResidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResidenceController extends Controller
{
    private const ADMIN_STATS_CACHE_KEY = 'api:admin:stats';

    private const LIST_CACHE_VERSION_KEY = 'api:residences:list:version';

    public function __construct(
        private ResidenceService $residenceService,
        private GeolocationService $geolocationService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResidenceCollection
    {
        $perPage = min(50, max(1, (int) $request->get('per_page', 20)));
        $page = max(1, (int) $request->get('page', 1));
        $cacheVersion = (int) Cache::rememberForever(self::LIST_CACHE_VERSION_KEY, fn () => 1);
        $cacheKey = sprintf('api:residences:list:v%d:page_%d:per_%d', $cacheVersion, $page, $perPage);

        $residences = Cache::remember($cacheKey, 300, function () use ($perPage) {
            return Residence::approved()
                ->with(['owner:id,name,profile_photo', 'photos', 'amenities'])
                ->latest()
                ->paginate($perPage);
        });

        return new ResidenceCollection($residences);
    }

    /**
     * Recherche géolocalisée de résidences.
     */
    public function search(SearchResidenceRequest $request): ResidenceCollection
    {
        $filters = $request->validated();

        $result = $this->geolocationService->search(
            latitude: $filters['latitude'] ?? null,
            longitude: $filters['longitude'] ?? null,
            radius: $filters['radius'] ?? 300,
            filters: $filters,
            sort: $filters['sort'] ?? 'distance',
            perPage: $filters['per_page'] ?? 15,
        );

        return new ResidenceCollection($result['residences']);
    }

    /**
     * Recherche dans un rayon spécifique.
     */
    public function nearby(SearchResidenceRequest $request): ResidenceCollection
    {
        return $this->search($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResidenceRequest $request): JsonResponse
    {
        try {
            $residence = $this->residenceService->create(
                $request->validated(),
                $request->user(),
            );

            Log::info('Résidence créée via API', [
                'residence_id' => $residence->id,
                'owner_id' => $request->user()->id,
            ]);

            CacheInvalidationService::invalidateResidence($residence->id);
            Cache::forever(self::LIST_CACHE_VERSION_KEY, ((int) Cache::get(self::LIST_CACHE_VERSION_KEY, 1)) + 1);

            return response()->json([
                'success' => true,
                'message' => 'Résidence créée avec succès. En attente d\'approbation.',
                'data' => new ResidenceResource($residence->load(['owner', 'photos', 'amenities'])),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur création résidence API', [
                'owner_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la résidence.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Residence $residence): ResidenceResource
    {
        $residence->load(['owner:id,name,profile_photo,created_at', 'photos', 'amenities']);

        return new ResidenceResource($residence);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResidenceRequest $request, Residence $residence): JsonResponse
    {
        try {
            $updated = $this->residenceService->update($residence, $request->validated());

            Log::info('Résidence mise à jour via API', [
                'residence_id' => $residence->id,
                'owner_id' => $request->user()->id,
            ]);

            CacheInvalidationService::invalidateResidence($residence->id);
            Cache::forever(self::LIST_CACHE_VERSION_KEY, ((int) Cache::get(self::LIST_CACHE_VERSION_KEY, 1)) + 1);

            return response()->json([
                'success' => true,
                'message' => 'Résidence mise à jour avec succès.',
                'data' => new ResidenceResource($updated->load(['owner', 'photos', 'amenities'])),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour résidence API', [
                'residence_id' => $residence->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la résidence.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Residence $residence): JsonResponse
    {
        try {
            $residenceId = $residence->id;
            $this->residenceService->delete($residence);

            Log::info('Résidence supprimée via API', ['residence_id' => $residenceId]);

            CacheInvalidationService::invalidateResidence($residenceId);
            Cache::forever(self::LIST_CACHE_VERSION_KEY, ((int) Cache::get(self::LIST_CACHE_VERSION_KEY, 1)) + 1);

            return response()->json([
                'success' => true,
                'message' => 'Résidence supprimée avec succès.',
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur suppression résidence API', [
                'residence_id' => $residence->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la résidence.',
            ], 500);
        }
    }

    /**
     * Récupérer les résidences du propriétaire connecté.
     */
    public function ownerResidences(Request $request): ResidenceCollection
    {
        $perPage = min(50, max(1, (int) $request->get('per_page', 20)));

        $residences = Residence::where('owner_id', $request->user()->id)
            ->with(['photos', 'amenities'])
            ->latest()
            ->paginate($perPage);

        return new ResidenceCollection($residences);
    }

    /**
     * Liste des communes disponibles.
     */
    public function communes(): JsonResponse
    {
        $communes = Cache::remember('api:communes', 3600, function () {
            return Residence::approved()
                ->distinct()
                ->pluck('commune')
                ->sort()
                ->values();
        });

        return response()->json([
            'success' => true,
            'data' => $communes,
        ]);
    }

    /**
     * Liste des quartiers pour une commune donnée.
     */
    public function quartiers(string $commune): JsonResponse
    {
        $safeCom = substr($commune, 0, 100);
        $quartiers = Cache::remember('api:quartiers:'.md5($safeCom), 3600, function () use ($safeCom) {
            return Residence::approved()
                ->where('commune', $safeCom)
                ->distinct()
                ->pluck('quartier')
                ->filter()
                ->sort()
                ->values();
        });

        return response()->json([
            'success' => true,
            'data' => $quartiers,
        ]);
    }

    /**
     * Liste des équipements disponibles.
     */
    public function amenities(): JsonResponse
    {
        $amenities = Cache::remember('api:amenities', 3600, function () {
            return \App\Models\Amenity::orderBy('name')
                ->select(['id', 'name', 'icon'])
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $amenities,
        ]);
    }

    /**
     * Liste des politiques d'annulation.
     */
    public function cancellationPolicies(): JsonResponse
    {
        $policies = Cache::remember('api:cancellation_policies', 3600, function () {
            return \App\Models\CancellationPolicy::orderBy('name')
                ->select(['id', 'name', 'description', 'refund_percentage'])
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    /**
     * Upload photos pour une résidence (owner).
     */
    public function uploadPhotos(Request $request, Residence $residence): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,webp|max:5120',
        ]);

        $uploaded = [];
        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('residences/'.$residence->id, 'public');
            $uploaded[] = $residence->photos()->create([
                'path' => $path,
                'is_primary' => $residence->photos()->count() === 0,
            ]);
        }

        return response()->json([
            'message' => count($uploaded).' photo(s) ajoutée(s).',
            'data' => $uploaded,
        ], 201);
    }

    /**
     * Statistiques des résidences du propriétaire connecté.
     */
    public function ownerStatistics(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Single query with conditional aggregation (pas de N+1)
        $stats = Residence::where('owner_id', $userId)
            ->selectRaw("
                COUNT(*) as total_residences,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_residences,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_residences,
                COALESCE(SUM(views_count), 0) as total_views,
                COALESCE(SUM(contacts_count), 0) as total_contacts
            ")
            ->first();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Statistiques globales (admin).
     */
    public function adminStatistics(): JsonResponse
    {
        $stats = Cache::remember(self::ADMIN_STATS_CACHE_KEY, 300, function () {
            $residenceStats = Residence::selectRaw("
                COUNT(*) as total_residences,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_residences,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_residences,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_residences
            ")->first();

            $userStats = \App\Models\User::selectRaw("
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'owner' THEN 1 ELSE 0 END) as total_owners
            ")->first();

            return array_merge(
                $residenceStats->toArray(),
                $userStats->toArray(),
            );
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Liste des résidences en attente de modération (admin).
     */
    public function pendingResidences(Request $request): ResidenceCollection
    {
        $perPage = min(50, max(1, (int) $request->get('per_page', 20)));

        $residences = Residence::where('status', 'pending')
            ->with(['owner:id,name,email', 'photos'])
            ->latest()
            ->paginate($perPage);

        return new ResidenceCollection($residences);
    }

    /**
     * Approuver une résidence (admin).
     */
    public function approve(Request $request, Residence $residence): JsonResponse
    {
        $residence->update(['status' => 'active']);

        Log::info('Résidence approuvée', [
            'admin_id' => $request->user()->id,
            'residence_id' => $residence->id,
        ]);

        CacheInvalidationService::invalidateResidence($residence->id);
        Cache::forever(self::LIST_CACHE_VERSION_KEY, ((int) Cache::get(self::LIST_CACHE_VERSION_KEY, 1)) + 1);

        return response()->json([
            'success' => true,
            'message' => 'Résidence approuvée.',
            'data' => new ResidenceResource($residence),
        ]);
    }

    /**
     * Rejeter une résidence (admin).
     */
    public function reject(Request $request, Residence $residence): JsonResponse
    {
        $request->validate([
            'reason' => 'sometimes|string|max:500',
        ]);

        $residence->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('reason'),
        ]);

        Log::info('Résidence rejetée', [
            'admin_id' => $request->user()->id,
            'residence_id' => $residence->id,
            'reason' => $request->input('reason'),
        ]);

        CacheInvalidationService::invalidateResidence($residence->id);
        Cache::forever(self::LIST_CACHE_VERSION_KEY, ((int) Cache::get(self::LIST_CACHE_VERSION_KEY, 1)) + 1);

        return response()->json([
            'success' => true,
            'message' => 'Résidence rejetée.',
            'data' => new ResidenceResource($residence),
        ]);
    }

}
