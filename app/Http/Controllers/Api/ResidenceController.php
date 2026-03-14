<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchResidenceRequest;
use App\Http\Requests\StoreResidenceRequest;
use App\Http\Requests\UpdateResidenceRequest;
use App\Http\Resources\ResidenceCollection;
use App\Http\Resources\ResidenceResource;
use App\Models\Residence;
use App\Services\GeolocationService;
use App\Services\ResidenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResidenceController extends Controller
{
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
        $residences = Residence::approved()
            ->with(['owner', 'photos', 'amenities'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return new ResidenceCollection($residences);
    }

    /**
     * Recherche géolocalisée de résidences.
     */
    public function search(SearchResidenceRequest $request): ResidenceCollection
    {
        $filters = $request->validated();

        $result = $this->geolocationService->search(
            latitude: $filters['latitude'],
            longitude: $filters['longitude'],
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

            return response()->json([
                'message' => 'Résidence créée avec succès. En attente d\'approbation.',
                'data' => new ResidenceResource($residence->load(['owner', 'photos', 'amenities'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la résidence.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Residence $residence): ResidenceResource
    {
        $residence->load(['owner', 'photos', 'amenities']);

        return new ResidenceResource($residence);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResidenceRequest $request, Residence $residence): JsonResponse
    {
        try {
            $updated = $this->residenceService->update($residence, $request->validated());

            return response()->json([
                'message' => 'Résidence mise à jour avec succès.',
                'data' => new ResidenceResource($updated->load(['owner', 'photos', 'amenities'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la résidence.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Residence $residence): JsonResponse
    {
        try {
            $this->residenceService->delete($residence);

            return response()->json([
                'message' => 'Résidence supprimée avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression de la résidence.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enregistrer une vue de résidence.
     */
    public function recordView(Residence $residence): JsonResponse
    {
        $this->residenceService->recordView($residence);

        return response()->json([
            'message' => 'Vue enregistrée.',
        ]);
    }

    /**
     * Enregistrer un contact pour une résidence.
     */
    public function recordContact(Request $request, Residence $residence): JsonResponse
    {
        $this->residenceService->recordContact($residence, $request->user());

        return response()->json([
            'message' => 'Contact enregistré.',
        ]);
    }

    /**
     * Récupérer les résidences du propriétaire connecté.
     */
    public function ownerResidences(Request $request): ResidenceCollection
    {
        $residences = Residence::where('owner_id', $request->user()->id)
            ->with(['photos', 'amenities'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return new ResidenceCollection($residences);
    }

    /**
     * Liste des communes disponibles.
     */
    public function communes(): JsonResponse
    {
        $communes = Residence::approved()
            ->distinct()
            ->pluck('commune')
            ->sort()
            ->values();

        return response()->json([
            'data' => $communes,
        ]);
    }

    /**
     * Liste des quartiers pour une commune donnée.
     */
    public function quartiers(string $commune): JsonResponse
    {
        $quartiers = Residence::approved()
            ->where('commune', $commune)
            ->distinct()
            ->pluck('quartier')
            ->filter()
            ->sort()
            ->values();

        return response()->json([
            'data' => $quartiers,
        ]);
    }

    /**
     * Liste des équipements disponibles.
     */
    public function amenities(): JsonResponse
    {
        $amenities = \App\Models\Amenity::orderBy('name')
            ->select(['id', 'name', 'icon', 'category'])
            ->get();

        return response()->json([
            'data' => $amenities,
        ]);
    }

    /**
     * Liste des politiques d'annulation.
     */
    public function cancellationPolicies(): JsonResponse
    {
        $policies = \App\Models\CancellationPolicy::orderBy('name')
            ->select(['id', 'name', 'description', 'refund_percentage'])
            ->get();

        return response()->json([
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
            $path = $photo->store('residences/' . $residence->id, 'public');
            $uploaded[] = $residence->photos()->create([
                'path' => $path,
                'is_primary' => $residence->photos()->count() === 0,
            ]);
        }

        return response()->json([
            'message' => count($uploaded) . ' photo(s) ajoutée(s).',
            'data' => $uploaded,
        ], 201);
    }

    /**
     * Statistiques des résidences du propriétaire connecté.
     */
    public function ownerStatistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $residences = Residence::where('owner_id', $user->id);

        return response()->json([
            'data' => [
                'total_residences' => $residences->count(),
                'active_residences' => (clone $residences)->where('status', 'active')->count(),
                'pending_residences' => (clone $residences)->where('status', 'pending')->count(),
                'total_views' => (clone $residences)->sum('views_count'),
                'total_contacts' => (clone $residences)->sum('contacts_count'),
            ],
        ]);
    }

    /**
     * Statistiques globales (admin).
     */
    public function adminStatistics(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_residences' => Residence::count(),
                'active_residences' => Residence::where('status', 'active')->count(),
                'pending_residences' => Residence::where('status', 'pending')->count(),
                'rejected_residences' => Residence::where('status', 'rejected')->count(),
                'total_users' => \App\Models\User::count(),
                'total_owners' => \App\Models\User::where('role', 'owner')->count(),
            ],
        ]);
    }

    /**
     * Liste des résidences en attente de modération (admin).
     */
    public function pendingResidences(Request $request): ResidenceCollection
    {
        $residences = Residence::where('status', 'pending')
            ->with(['owner', 'photos'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return new ResidenceCollection($residences);
    }

    /**
     * Approuver une résidence (admin).
     */
    public function approve(Residence $residence): JsonResponse
    {
        $residence->update(['status' => 'active']);

        return response()->json([
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

        return response()->json([
            'message' => 'Résidence rejetée.',
            'data' => new ResidenceResource($residence),
        ]);
    }
}
