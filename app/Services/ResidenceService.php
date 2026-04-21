<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Residence;
use App\Repositories\ResidenceRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des résidences
 *
 * Contient toute la logique métier liée aux résidences
 */
class ResidenceService
{
    public function __construct(
        private ResidenceRepository $repository,
        private PhotoUploadService $photoService,
    ) {
    }

    /**
     * Crée une nouvelle résidence avec photos et équipements
     *
     * @param \App\Models\User $owner Propriétaire de la résidence
     * @param array $data Données de la résidence
     * @return Residence
     * @throws \Exception
     */
    public function create(\App\Models\User $owner, array $data): Residence
    {
        return DB::transaction(function () use ($owner, $data) {
            // Calculer les prix selon la période sélectionnée
            $prices = $this->calculatePrices($data);

            // Auto-assigner la catégorie selon le type de résidence
            $categoryId = $data['category_id'] ?? $this->resolveCategoryId($data['type'] ?? null);

            // Créer la résidence
            $residence = $this->repository->create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'address' => $data['address'],
                'country_code' => $data['country_code'] ?? 'CI',
                'city' => $data['city'] ?? null,
                'commune' => $data['commune'],
                'quartier' => $data['quartier'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'price_per_day' => $prices['price_per_day'],
                'price_per_week' => $prices['price_per_week'],
                'price_per_month' => $prices['price_per_month'],
                'type_location' => $data['type_location'] ?? 'residence_meublee',
                'price_period' => $data['price_period'] ?? Residence::TYPE_LOCATION_PRICE_MAP[$data['type_location'] ?? 'residence_meublee'],
                'category_id' => $categoryId,
                'status' => 'pending', // Attend validation admin
                'is_available' => $data['is_available'] ?? true,
            ]);

            // Upload photos si présentes
            if (isset($data['photos']) && is_array($data['photos'])) {
                $this->photoService->uploadMultiple($residence, $data['photos']);
            }

            // Attacher les équipements
            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $residence->amenities()->sync($data['amenities']);
            }

            Log::info('Residence created', [
                'residence_id' => $residence->id,
                'owner_id' => $owner->id,
            ]);

            return $residence->fresh(['photos', 'amenities']);
        });
    }

    /**
     * Met à jour une résidence existante
     *
     * @param Residence $residence
     * @param array $data
     * @return Residence
     * @throws \Exception
     */
    public function update(Residence $residence, array $data): Residence
    {
        DB::beginTransaction();

        try {
            // Calculer les prix si fournis
            $priceData = [
                'price_period' => $data['price_period'] ?? $residence->price_period,
                'price_per_day' => $data['price_per_day'] ?? $residence->price_per_day,
                'price_per_week' => $data['price_per_week'] ?? $residence->price_per_week,
                'price_per_month' => $data['price_per_month'] ?? $residence->price_per_month,
            ];
            $prices = $this->calculatePrices($priceData);

            // Mettre à jour les infos de base
            $categoryId = $data['category_id'] ?? $this->resolveCategoryId($data['type'] ?? null) ?? $residence->category_id;

            // IMPORTANT : Le statut n'est PAS modifié ici intentionnellement.
            // Une résidence déjà approuvée (active/approved) conserve son statut
            // après modification par le propriétaire. Pas de re-approbation nécessaire.
            $updateData = [
                // Informations générales
                'name' => $data['name'] ?? $residence->name,
                'description' => $data['description'] ?? $residence->description,
                'house_rules' => $data['house_rules'] ?? $residence->house_rules,
                'virtual_tour_url' => $data['virtual_tour_url'] ?? $residence->virtual_tour_url,

                // Localisation
                'address' => $data['address'] ?? $residence->address,
                'country_code' => $data['country_code'] ?? $residence->country_code,
                'city' => $data['city'] ?? $residence->city,
                'commune' => $data['commune'] ?? $residence->commune,
                'quartier' => $data['quartier'] ?? $residence->quartier,
                'latitude' => $data['latitude'] ?? $residence->latitude,
                'longitude' => $data['longitude'] ?? $residence->longitude,

                // Tarification
                'price_per_day' => $prices['price_per_day'],
                'price_per_week' => $prices['price_per_week'],
                'price_per_month' => $prices['price_per_month'],
                'type_location' => $data['type_location'] ?? $residence->type_location,
                'price_period' => $data['price_period'] ?? $residence->price_period,
                'deposit_negotiable' => $data['deposit_negotiable'] ?? $residence->deposit_negotiable,
                'deposit_terms' => $data['deposit_terms'] ?? $residence->deposit_terms,

                // Caractéristiques
                'category_id' => $categoryId,
                'bedrooms' => $data['bedrooms'] ?? $residence->bedrooms,
                'bathrooms' => $data['bathrooms'] ?? $residence->bathrooms,
                'max_guests' => $data['max_guests'] ?? $residence->max_guests,
                'surface_area' => $data['surface_area'] ?? $residence->surface_area,
                'floor' => $data['floor'] ?? $residence->floor,
                'has_elevator' => $data['has_elevator'] ?? $residence->has_elevator,

                // Disponibilité
                'is_available' => $data['is_available'] ?? $residence->is_available,
                'available_from' => $data['available_from'] ?? $residence->available_from,
                'min_nights' => $data['min_nights'] ?? $residence->min_nights,
                'max_nights' => $data['max_nights'] ?? $residence->max_nights,
                'instant_book' => $data['instant_book'] ?? $residence->instant_book,

                // Horaires
                'check_in_time' => $data['check_in_time'] ?? $residence->check_in_time,
                'check_out_time' => $data['check_out_time'] ?? $residence->check_out_time,

                // Règles
                'pets_allowed' => $data['pets_allowed'] ?? $residence->pets_allowed,
                'smoking_allowed' => $data['smoking_allowed'] ?? $residence->smoking_allowed,
                'parties_allowed' => $data['parties_allowed'] ?? $residence->parties_allowed,

                // Location
                'lease_type' => $data['lease_type'] ?? $residence->lease_type,
                'target_tenants' => $data['target_tenants'] ?? $residence->target_tenants,

                // Accessibilité
                'is_accessible' => $data['is_accessible'] ?? $residence->is_accessible,
                'accessibility_features' => $data['accessibility_features'] ?? $residence->accessibility_features,
            ];

            // Exclure explicitement le champ 'status' s'il est présent dans $data
            // pour éviter toute régression accidentelle du statut
            unset($data['status']);

            $this->repository->update($residence, $updateData);

            // Ajouter nouvelles photos si présentes
            if (isset($data['photos']) && is_array($data['photos'])) {
                $this->photoService->uploadMultiple($residence, $data['photos']);
            }

            // Mettre à jour les équipements
            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $residence->amenities()->sync($data['amenities']);
            }

            DB::commit();

            Log::info('Residence updated', [
                'residence_id' => $residence->id,
            ]);

            return $residence->fresh(['photos', 'amenities']);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update residence', [
                'residence_id' => $residence->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Supprime une résidence
     *
     * @param Residence $residence
     * @return bool
     */
    public function delete(Residence $residence): bool
    {
        try {
            // Les photos seront supprimées automatiquement via le boot() du modèle Photo
            $result = $this->repository->delete($residence);

            Log::info('Residence deleted', [
                'residence_id' => $residence->id,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to delete residence', [
                'residence_id' => $residence->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Approuve une résidence
     *
     * @param Residence $residence
     * @return bool
     */
    public function approve(Residence $residence): bool
    {
        $result = $this->repository->approve($residence);

        if ($result) {
            Log::info('Residence approved', [
                'residence_id' => $residence->id,
            ]);

            // Notifier le propriétaire (email + in-app)
            $owner = $residence->owner;
            $owner->notify(new \App\Notifications\ResidenceApproved($residence));
            \App\Models\Notification::send(
                $owner,
                'residence',
                'Résidence approuvée',
                "Votre résidence « {$residence->name} » a été approuvée et est maintenant visible.",
                route('residences.show', $residence),
                ['residence_id' => $residence->id],
            );
        }

        return $result;
    }

    /**
     * Rejette une résidence
     *
     * @param Residence $residence
     * @param string|null $reason
     * @return bool
     */
    public function reject(Residence $residence, ?string $reason = null): bool
    {
        $result = $this->repository->reject($residence, $reason);

        if ($result) {
            Log::info('Residence rejected', [
                'residence_id' => $residence->id,
                'reason' => $reason,
            ]);

            // Notifier le propriétaire (email + in-app)
            $owner = $residence->owner;
            $owner->notify(new \App\Notifications\ResidenceRejected($residence, $reason));
            \App\Models\Notification::send(
                $owner,
                'residence',
                'Résidence non approuvée',
                "Votre résidence « {$residence->name} » n'a pas été approuvée.".($reason ? " Motif : {$reason}" : ''),
                route('owner.residences.edit', $residence),
                ['residence_id' => $residence->id, 'reason' => $reason],
            );
        }

        return $result;
    }

    /**
     * Enregistre une vue de résidence
     *
     * @param Residence $residence
     */
    public function recordView(Residence $residence): void
    {
        $this->repository->incrementViews($residence);
    }

    /**
     * Enregistre un contact pour une résidence
     *
     * @param Residence $residence
     */
    public function recordContact(Residence $residence): void
    {
        $this->repository->incrementContacts($residence);

        // Enregistrer le contact sponsorisé si applicable
        if ($residence->isSponsored()) {
            $activeSponsoredListing = $residence->activeSponsoredListing();
            if ($activeSponsoredListing) {
                $activeSponsoredListing->recordContact(request()->ip(), auth()->id());
            }
        }

        Log::info('Residence contact recorded', [
            'residence_id' => $residence->id,
        ]);
    }

    /**
     * Calcule les prix selon la période sélectionnée
     * Si un seul prix est fourni, estime les autres
     *
     * @param array $data
     * @return array
     */
    private function calculatePrices(array $data): array
    {
        $pricePeriod = $data['price_period'] ?? 'day';
        $priceDay = $data['price_per_day'] ?? null;
        $priceWeek = $data['price_per_week'] ?? null;
        $priceMonth = $data['price_per_month'] ?? null;

        // Si le prix principal est fourni selon la période, calculer les autres
        switch ($pricePeriod) {
            case 'day':
            case 'night':
                if ($priceDay !== null && $priceDay > 0) {
                    // Calcul avec réduction progressive
                    $priceWeek = $priceWeek ?? round($priceDay * 6, 0);   // ~15% réduction
                    $priceMonth = $priceMonth ?? round($priceDay * 22, 0); // ~27% réduction
                }
                break;

            case 'week':
                if ($priceWeek !== null && $priceWeek > 0) {
                    $priceDay = $priceDay ?? round($priceWeek / 6, 0);
                    $priceMonth = $priceMonth ?? round($priceWeek * 3.5, 0); // ~12% réduction
                }
                break;

            case 'month':
                if ($priceMonth !== null && $priceMonth > 0) {
                    $priceWeek = $priceWeek ?? round($priceMonth / 3.5, 0);
                    $priceDay = $priceDay ?? round($priceMonth / 22, 0);
                }
                break;
        }

        // Garantir des valeurs non-null (min 0)
        return [
            'price_per_day' => $priceDay ?? 0,
            'price_per_week' => $priceWeek ?? 0,
            'price_per_month' => $priceMonth ?? 0,
        ];
    }

    /**
     * Résout la catégorie (category_id) à partir du type de résidence.
     * Mapping : studio→Studio, apartment→Appartement, house→Maison, villa→Villa, duplex→Duplex
     */
    private function resolveCategoryId(?string $type): ?int
    {
        if (! $type) {
            return null;
        }

        $slugMap = [
            'studio'    => 'studio',
            'apartment' => 'appartement',
            'house'     => 'maison',
            'villa'     => 'villa',
            'duplex'    => 'duplex',
        ];

        $slug = $slugMap[$type] ?? null;

        if (! $slug) {
            return null;
        }

        return Category::where('slug', $slug)->value('id');
    }
}
