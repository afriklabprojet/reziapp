<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Residence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repository pour les résidences
 *
 * Abstraction de la couche d'accès aux données
 */
class ResidenceRepository
{
    /**
     * Trouve une résidence par son ID
     */
    public function find(int $id): ?Residence
    {
        return Residence::find($id);
    }

    /**
     * Trouve une résidence par ID avec relations
     */
    public function findWithRelations(int $id, array $relations = []): ?Residence
    {
        return Residence::with($relations)->find($id);
    }

    /**
     * Récupère toutes les résidences approuvées
     */
    public function findApproved(): Collection
    {
        return Residence::approved()
            ->with(['photos', 'amenities', 'owner'])
            ->get();
    }

    /**
     * Récupère les résidences disponibles
     */
    public function findAvailable(): Collection
    {
        return Residence::approved()
            ->available()
            ->with(['photos', 'amenities', 'owner'])
            ->get();
    }

    /**
     * Trouve les résidences dans un rayon géographique
     */
    public function findWithinRadius(float $latitude, float $longitude, int $radius): Collection
    {
        return Residence::approved()
            ->available()
            ->withinRadius($latitude, $longitude, $radius)
            ->with(['photos', 'amenities', 'owner'])
            ->get();
    }

    /**
     * Trouve les résidences par commune
     */
    public function findByCommune(string $commune): Collection
    {
        return Residence::approved()
            ->available()
            ->where('commune', 'like', "%{$commune}%")
            ->with(['photos', 'amenities'])
            ->get();
    }

    /**
     * Trouve les résidences par quartier
     */
    public function findByQuartier(string $quartier): Collection
    {
        return Residence::approved()
            ->available()
            ->where('quartier', 'like', "%{$quartier}%")
            ->with(['photos', 'amenities'])
            ->get();
    }

    /**
     * Crée une nouvelle résidence.
     *
     * MySQL SPATIAL INDEX requires location NOT NULL, but Eloquent cannot set a
     * POINT column via fill(). We INSERT with ST_GeomFromText inline when both
     * lat/lng are present, then reload the model so callers get a fully-hydrated
     * instance with the auto-generated id.
     */
    public function create(array $data): Residence
    {
        $lat = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $lng = isset($data['longitude']) ? (float) $data['longitude'] : null;

        if ($lat !== null && $lng !== null && DB::getDriverName() === 'mysql') {
            $data['location'] = DB::raw(
                "ST_GeomFromText('POINT({$lng} {$lat})', 4326)"
            );
        }

        return Residence::create($data);
    }

    /**
     * Met à jour une résidence
     */
    public function update(Residence $residence, array $data): bool
    {
        return $residence->update($data);
    }

    /**
     * Supprime une résidence (soft delete)
     */
    public function delete(Residence $residence): bool
    {
        return $residence->delete();
    }

    /**
     * Récupère les résidences d'un propriétaire
     */
    public function findByOwner(int $ownerId): Collection
    {
        return Residence::where('owner_id', $ownerId)
            ->with(['photos', 'amenities'])
            ->latest()
            ->get();
    }

    /**
     * Récupère les résidences en attente de modération
     */
    public function findPending(): Collection
    {
        return Residence::where('status', 'pending')
            ->with(['photos', 'amenities', 'owner'])
            ->latest()
            ->get();
    }

    /**
     * Approuve une résidence
     */
    public function approve(Residence $residence): bool
    {
        return $residence->update(['status' => 'approved']);
    }

    /**
     * Rejette une résidence
     */
    public function reject(Residence $residence, ?string $reason = null): bool
    {
        return $residence->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Incrémente le compteur de vues
     */
    public function incrementViews(Residence $residence): void
    {
        $residence->incrementViews();
    }

    /**
     * Incrémente le compteur de contacts
     */
    public function incrementContacts(Residence $residence): void
    {
        $residence->incrementContacts();
    }
}
