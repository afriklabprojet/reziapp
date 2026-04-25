<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChannelListing;
use App\Models\Residence;
use Illuminate\Support\Facades\Log;

/**
 * Service de synchronisation avec les canaux externes (Airbnb, Booking, etc.).
 *
 * NOTE : Les API officielles d'Airbnb et Booking.com ne sont accessibles qu'aux
 * partenaires PMS agréés. Cette implémentation est un STUB qui :
 *  - logue toutes les opérations
 *  - met à jour le statut de synchronisation en base
 *  - prépare la structure pour un branchement futur sur :
 *    - Airbnb Channel API (https://www.airbnb.com/partners/listings)
 *    - Booking Connect API (https://connect.booking.com)
 *    - Hosthub / Hostaway / Smoobu en intermédiaire
 */
class ChannelManagerService
{
    /**
     * Pousse la disponibilité (calendrier) vers le canal.
     */
    public function pushAvailability(ChannelListing $listing): bool
    {
        return $this->logAndUpdate($listing, 'push_availability', 'success', 'Calendrier synchronisé (stub)');
    }

    /**
     * Pousse les prix vers le canal.
     */
    public function pushPrice(ChannelListing $listing): bool
    {
        return $this->logAndUpdate($listing, 'push_price', 'success', 'Prix synchronisés (stub)');
    }

    /**
     * Récupère les nouvelles réservations depuis le canal.
     */
    public function fetchBookings(ChannelListing $listing): array
    {
        $this->logAndUpdate($listing, 'fetch_bookings', 'success', 'Réservations vérifiées (stub) — 0 nouvelle');

        return [];
    }

    /**
     * Active une connexion canal pour une résidence.
     */
    public function connect(Residence $residence, string $channel, ?string $externalId = null): ChannelListing
    {
        return ChannelListing::updateOrCreate(
            ['residence_id' => $residence->id, 'channel' => $channel],
            [
                'external_id' => $externalId,
                'is_active' => true,
                'sync_status' => 'pending',
                'sync_message' => 'Connexion en attente d\'activation API officielle',
            ],
        );
    }

    public function disconnect(ChannelListing $listing): void
    {
        $listing->update(['is_active' => false, 'sync_status' => 'pending']);
        Log::channel('single')->info('Channel disconnected', ['id' => $listing->id, 'channel' => $listing->channel]);
    }

    private function logAndUpdate(ChannelListing $listing, string $operation, string $status, string $message): bool
    {
        Log::channel('single')->info('ChannelManager '.$operation, [
            'listing_id' => $listing->id,
            'channel' => $listing->channel,
            'residence_id' => $listing->residence_id,
        ]);

        $listing->update([
            'sync_status' => $status,
            'sync_message' => $message,
            'last_sync_at' => now(),
        ]);

        return $status === 'success';
    }
}
