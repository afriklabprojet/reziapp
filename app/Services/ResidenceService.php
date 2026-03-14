<?php

declare(strict_types=1);

namespace App\Services;

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
                'price_per_day' => $data['price_per_day'] ?? null,
                'price_per_week' => $data['price_per_week'] ?? null,
                'price_per_month' => $data['price_per_month'] ?? null,
                'type_location' => $data['type_location'] ?? 'residence_meublee',
                'price_period' => $data['price_period'] ?? Residence::TYPE_LOCATION_PRICE_MAP[$data['type_location'] ?? 'residence_meublee'],
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
            // Mettre à jour les infos de base
            $this->repository->update($residence, [
                'name' => $data['name'] ?? $residence->name,
                'description' => $data['description'] ?? $residence->description,
                'address' => $data['address'] ?? $residence->address,
                'country_code' => $data['country_code'] ?? $residence->country_code,
                'city' => $data['city'] ?? $residence->city,
                'commune' => $data['commune'] ?? $residence->commune,
                'quartier' => $data['quartier'] ?? $residence->quartier,
                'latitude' => $data['latitude'] ?? $residence->latitude,
                'longitude' => $data['longitude'] ?? $residence->longitude,
                'price_per_day' => $data['price_per_day'] ?? $residence->price_per_day,
                'price_per_week' => $data['price_per_week'] ?? $residence->price_per_week,
                'price_per_month' => $data['price_per_month'] ?? $residence->price_per_month,
                'type_location' => $data['type_location'] ?? $residence->type_location,
                'price_period' => $data['price_period'] ?? $residence->price_period,
                'is_available' => $data['is_available'] ?? $residence->is_available,
            ]);

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
                ['residence_id' => $residence->id]
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
                "Votre résidence « {$residence->name} » n'a pas été approuvée." . ($reason ? " Motif : {$reason}" : ''),
                route('owner.residences.edit', $residence),
                ['residence_id' => $residence->id, 'reason' => $reason]
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
                $activeSponsoredListing->recordContact();
            }
        }

        Log::info('Residence contact recorded', [
            'residence_id' => $residence->id,
        ]);
    }
}
