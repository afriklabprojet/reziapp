<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResidenceRequest;
use App\Http\Requests\UpdateResidenceRequest;
use App\Models\Amenity;
use App\Models\City;
use App\Models\Country;
use App\Models\Photo;
use App\Models\Residence;
use App\Services\PhotoUploadService;
use App\Services\ResidenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller pour la gestion des résidences (propriétaire)
 */
class ResidenceController extends Controller
{
    public function __construct(
        private ResidenceService $residenceService,
        private PhotoUploadService $photoService,
    ) {
    }

    /**
     * Liste de mes résidences
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $user->residences()->with(['photos', 'amenities']);

        // Filtre par type de location
        $query->when($request->type, fn ($q, $type) => $q->where('type_location', $type));

        // Filtre par statut
        $query->when($request->status, fn ($q, $status) => $q->where('status', $status));

        // Filtre par disponibilité
        $query->when($request->filled('available'), fn ($q) => $q->where('is_available', $request->boolean('available')));

        // Recherche par nom/commune (wildcards LIKE échappés)
        $query->when($request->search, function ($q, $search) {
            $safe = str_replace(['%', '_'], ['\%', '\_'], $search);
            $q->where(function ($q) use ($safe) {
                $q->where('name', 'like', "%{$safe}%")
                    ->orWhere('commune', 'like', "%{$safe}%")
                    ->orWhere('quartier', 'like', "%{$safe}%");
            });
        });

        // Tri
        $sortField = match ($request->sort) {
            'price' => 'price_per_month',
            'views' => 'views_count',
            'contacts' => 'contacts_count',
            'name' => 'name',
            default => 'created_at',
        };
        $sortDir = $request->dir === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDir);

        $residences = $query->paginate(12)->withQueryString();

        // Compteurs (filtrés par type si spécifié)
        $baseQuery = $user->residences();
        if ($request->type) {
            $baseQuery = $baseQuery->where('type_location', $request->type);
        }
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'rejected' => (clone $baseQuery)->whereIn('status', ['rejected', 'needs_changes'])->count(),
            'available' => (clone $baseQuery)->where('is_available', true)->count(),
        ];

        // Type actuel pour la vue
        $currentType = $request->type;

        return view('owner.residences.index', compact('residences', 'counts', 'currentType'));
    }

    /**
     * Formulaire de création
     */
    public function create(): View
    {
        Gate::authorize('create', Residence::class);

        $amenities = Amenity::orderBy('name')->get();
        $communes = config('rezi.communes');
        $countries = Country::active()->orderBy('name')->get();
        $cities = City::active()->ordered()->with('communes')->get();

        return view('owner.residences.create', compact('amenities', 'communes', 'countries', 'cities'));
    }

    /**
     * Enregistrer une nouvelle résidence
     */
    public function store(StoreResidenceRequest $request): RedirectResponse
    {
        Gate::authorize('create', Residence::class);

        $residence = $this->residenceService->create(
            $request->user(),
            $request->validated(),
        );

        // Upload des photos si présentes
        if ($request->hasFile('photos')) {
            $firstPhoto = null;
            foreach ($request->file('photos') as $index => $photo) {
                $uploadedPhoto = $this->photoService->upload($residence, $photo, $index);
                if ($index === 0) {
                    $firstPhoto = $uploadedPhoto;
                }
            }
            // Définir la première photo comme primaire
            if ($firstPhoto) {
                $this->photoService->setPrimary($firstPhoto);
            }
        }

        // Attacher les équipements
        if ($request->has('amenities')) {
            $residence->amenities()->sync($request->amenities);
        }

        return redirect()
            ->route('owner.residences.index')
            ->with('success', 'Résidence créée avec succès. Elle sera visible après validation par notre équipe.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(Residence $residence): View
    {
        Gate::authorize('update', $residence);

        $residence->load(['photos', 'amenities']);
        $amenities = Amenity::orderBy('name')->get();
        $communes = config('rezi.communes');
        $countries = Country::active()->orderBy('name')->get();
        $cities = City::active()->ordered()->with('communes')->get();

        return view('owner.residences.edit', compact('residence', 'amenities', 'communes', 'countries', 'cities'));
    }

    /**
     * Mettre à jour une résidence
     */
    public function update(UpdateResidenceRequest $request, Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $data = $request->validated();

        // Les checkboxes non cochées n'envoient rien — forcer à false/0
        $booleanFields = ['is_available', 'has_elevator', 'instant_book', 'pets_allowed', 'smoking_allowed', 'parties_allowed', 'is_accessible', 'deposit_negotiable'];
        foreach ($booleanFields as $field) {
            if (!isset($data[$field])) {
                $data[$field] = false;
            }
        }

        $this->residenceService->update($residence, $data);

        // Mettre à jour les équipements
        if ($request->has('amenities')) {
            $residence->amenities()->sync($request->amenities);
        }

        return redirect()
            ->route('owner.residences.edit', $residence)
            ->with('success', 'Résidence mise à jour avec succès.');
    }

    /**
     * Supprimer une résidence
     */
    public function destroy(Residence $residence): RedirectResponse
    {
        Gate::authorize('delete', $residence);

        $this->residenceService->delete($residence);

        return redirect()
            ->route('owner.residences.index')
            ->with('success', 'Résidence supprimée avec succès.');
    }

    /**
     * Upload de photos
     */
    public function uploadPhotos(Request $request, Residence $residence): RedirectResponse
    {
        Gate::authorize('uploadPhotos', $residence);

        $request->validate([
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB max
        ]);

        foreach ($request->file('photos') as $photo) {
            $this->photoService->upload($residence, $photo);
        }

        return back()->with('success', 'Photos ajoutées avec succès.');
    }

    /**
     * Supprimer une photo
     */
    public function deletePhoto(Residence $residence, Photo $photo): RedirectResponse
    {
        Gate::authorize('update', $residence);

        // Vérifier que la photo appartient à la résidence
        if ($photo->residence_id !== $residence->id) {
            abort(403);
        }

        $this->photoService->delete($photo);

        return back()->with('success', 'Photo supprimée.');
    }

    /**
     * Afficher une résidence
     */
    public function show(Residence $residence): View
    {
        Gate::authorize('view', $residence);

        $residence->load(['photos', 'amenities', 'contacts' => function ($query) {
            $query->latest()->take(10);
        }]);

        return view('owner.residences.show', compact('residence'));
    }

    /**
     * Basculer la disponibilité
     */
    public function toggleAvailability(Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $residence->update([
            'is_available' => !$residence->is_available,
        ]);

        $status = $residence->is_available ? 'disponible' : 'occupée';

        return back()->with('success', "La résidence est maintenant marquée comme {$status}.");
    }

    /**
     * Définir une photo comme principale
     */
    public function setPrimaryPhoto(Residence $residence, Photo $photo): RedirectResponse
    {
        Gate::authorize('update', $residence);

        if ($photo->residence_id !== $residence->id) {
            abort(403);
        }

        $this->photoService->setPrimary($photo);

        return back()->with('success', 'Photo principale mise à jour.');
    }

    /**
     * Réordonner les photos
     */
    public function reorderPhotos(Request $request, Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:photos,id'],
        ]);

        foreach ($request->order as $index => $photoId) {
            Photo::where('id', $photoId)
                ->where('residence_id', $residence->id)
                ->update(['order' => $index]);
        }

        return back()->with('success', 'Ordre des photos mis à jour.');
    }

    /**
     * Formulaire de création guidée (wizard)
     */
    public function wizard(): View
    {
        Gate::authorize('create', Residence::class);

        return view('owner.residences.wizard');
    }

    /**
     * Formulaire de suspension
     */
    public function suspendForm(Residence $residence): View
    {
        Gate::authorize('update', $residence);

        return view('owner.residences.suspend', compact('residence'));
    }

    /**
     * Suspendre une annonce
     */
    public function suspend(Request $request, Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $validated = $request->validate([
            'suspension_reason' => 'required|string|max:50',
            'resume_at' => 'nullable|date|after:today',
            'suspension_note' => 'nullable|string|max:500',
        ]);

        $residence->update([
            'is_suspended' => true,
            'is_available' => false,
            'suspension_reason' => $validated['suspension_reason'],
            'suspended_at' => now(),
            'resume_at' => $validated['resume_at'] ?? null,
            'suspension_note' => $validated['suspension_note'] ?? null,
        ]);

        return redirect()
            ->route('owner.residences.show', $residence)
            ->with('success', 'Annonce suspendue avec succès.');
    }

    /**
     * Réactiver une annonce
     */
    public function resume(Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $residence->update([
            'is_suspended' => false,
            'is_available' => true,
            'suspension_reason' => null,
            'suspended_at' => null,
            'resume_at' => null,
            'suspension_note' => null,
        ]);

        return redirect()
            ->route('owner.residences.show', $residence)
            ->with('success', 'Annonce réactivée avec succès !');
    }

    /**
     * Dupliquer une résidence existante
     */
    public function duplicate(Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $clone = $residence->replicate([
            'status',
            'approved_at',
            'is_verified',
            'verified_at',
            'average_rating',
            'reviews_count',
            'views_count',
            'contacts_count',
            'is_top_residence',
            'is_suspended',
            'suspended_at',
            'suspension_reason',
            'suspension_note',
            'resume_at',
            'rejection_reason',
            'rejection_details',
        ]);

        $clone->name = $residence->name . ' (copie)';
        $clone->status = 'pending';
        $clone->is_available = false;
        $clone->save();

        // Dupliquer les équipements
        $clone->amenities()->sync($residence->amenities->pluck('id'));

        return redirect()
            ->route('owner.residences.edit', $clone)
            ->with('success', 'Résidence dupliquée avec succès. Modifiez les informations avant de la publier.');
    }

    /**
     * Opérations groupées sur les résidences
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:enable,disable,delete'],
            'residence_ids' => ['required', 'array', 'min:1'],
            'residence_ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $residences = Residence::where('owner_id', $user->id)
            ->whereIn('id', $validated['residence_ids'])
            ->get();

        if ($residences->isEmpty()) {
            return back()->with('error', 'Aucune résidence sélectionnée.');
        }

        $count = $residences->count();

        switch ($validated['action']) {
            case 'enable':
                Residence::where('owner_id', $user->id)
                    ->whereIn('id', $validated['residence_ids'])
                    ->update(['is_available' => true]);
                $message = "{$count} résidence(s) marquée(s) comme disponible(s).";
                break;

            case 'disable':
                Residence::where('owner_id', $user->id)
                    ->whereIn('id', $validated['residence_ids'])
                    ->update(['is_available' => false]);
                $message = "{$count} résidence(s) marquée(s) comme indisponible(s).";
                break;

            case 'delete':
                foreach ($residences as $residence) {
                    $this->residenceService->delete($residence);
                }
                $message = "{$count} résidence(s) supprimée(s).";
                break;

            default:
                return back()->with('error', 'Action inconnue.');
        }

        return back()->with('success', $message);
    }

    /**
     * Bloquer des dates sur le calendrier
     */
    public function blockDates(Request $request, Residence $residence): RedirectResponse
    {
        Gate::authorize('update', $residence);

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|in:personal,maintenance,renovation,other',
            'notes' => 'nullable|string|max:500',
        ]);

        \App\Models\BlockedDate::create([
            'residence_id' => $residence->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'blocked_by_user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('owner.bookings.calendar', $residence)
            ->with('success', 'Dates bloquées avec succès.');
    }
}
