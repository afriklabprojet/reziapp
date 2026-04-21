<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OwnerAlert;
use App\Models\Residence;
use App\Models\User;

class OwnerAlertService
{
    /**
     * Vérifier le SLA temps de réponse pour tous les propriétaires
     */
    public function checkResponseTimeSLA(): int
    {
        $alerts = 0;

        // Réservations en attente depuis > 4h sans réponse
        $pendingRequests = \App\Models\BookingRequest::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(4))
            ->with('residence.owner')
            ->get();

        foreach ($pendingRequests as $request) {
            $owner = $request->residence->owner ?? null;
            if (!$owner) {
                continue;
            }

            // Éviter les doublons
            $existing = OwnerAlert::where('user_id', $owner->id)
                ->where('alert_type', OwnerAlert::TYPE_RESPONSE_TIME_SLA)
                ->where('status', 'active')
                ->where('metadata->booking_request_id', $request->id)
                ->exists();

            if (!$existing) {
                OwnerAlert::create([
                    'user_id'      => $owner->id,
                    'residence_id' => $request->residence_id,
                    'alert_type'   => OwnerAlert::TYPE_RESPONSE_TIME_SLA,
                    'severity'     => 'warning',
                    'title'        => 'Demande en attente de réponse',
                    'message'      => 'Une demande de réservation attend votre réponse depuis plus de 4h. Un temps de réponse rapide améliore votre classement.',
                    'metadata'     => ['booking_request_id' => $request->id],
                    'action_url'   => route('owner.bookings.requests'),
                ]);
                $alerts++;
            }
        }

        return $alerts;
    }

    /**
     * Alerter sur les nuits orphelines détectées
     */
    public function checkBookingGaps(): int
    {
        $yieldService = app(YieldManagementService::class);
        $alerts = 0;

        $residences = Residence::where('status', 'approved')
            ->where('is_available', true)
            ->get();

        foreach ($residences as $residence) {
            $gaps = $yieldService->findGapNights($residence);

            foreach ($gaps as $gap) {
                $existing = OwnerAlert::where('residence_id', $residence->id)
                    ->where('alert_type', OwnerAlert::TYPE_BOOKING_GAP)
                    ->where('status', 'active')
                    ->where('metadata->dates', json_encode($gap['dates']))
                    ->exists();

                if (!$existing) {
                    OwnerAlert::create([
                        'user_id'      => $residence->owner_id,
                        'residence_id' => $residence->id,
                        'alert_type'   => OwnerAlert::TYPE_BOOKING_GAP,
                        'severity'     => 'info',
                        'title'        => $gap['gap_days'].' nuit(s) orpheline(s) détectée(s)',
                        'message'      => "Il y a {$gap['gap_days']} nuit(s) libre(s) entre deux réservations ({$gap['dates'][0]}). Activez le gap-night pricing pour maximiser votre occupation.",
                        'metadata'     => ['dates' => $gap['dates']],
                    ]);
                    $alerts++;
                }
            }
        }

        return $alerts;
    }

    /**
     * Alerter sur les avis en attente de réponse
     */
    public function checkPendingReviews(): int
    {
        $alerts = 0;

        $reviews = \App\Models\Review::whereNull('owner_response')
            ->where('created_at', '<', now()->subDays(2))
            ->with('residence.owner')
            ->get();

        foreach ($reviews as $review) {
            $owner = $review->residence->owner ?? null;
            if (!$owner) {
                continue;
            }

            $existing = OwnerAlert::where('user_id', $owner->id)
                ->where('alert_type', OwnerAlert::TYPE_REVIEW_PENDING)
                ->where('status', 'active')
                ->where('metadata->review_id', $review->id)
                ->exists();

            if (!$existing) {
                OwnerAlert::create([
                    'user_id'      => $owner->id,
                    'residence_id' => $review->residence_id,
                    'alert_type'   => OwnerAlert::TYPE_REVIEW_PENDING,
                    'severity'     => 'info',
                    'title'        => 'Avis en attente de réponse',
                    'message'      => "Un avis de {$review->user->name} attend votre réponse depuis ".$review->created_at->diffForHumans().'.',
                    'metadata'     => ['review_id' => $review->id],
                ]);
                $alerts++;
            }
        }

        return $alerts;
    }

    /**
     * Obtenir les alertes actives d'un propriétaire
     */
    public function getActiveAlerts(User $owner): \Illuminate\Database\Eloquent\Collection
    {
        return OwnerAlert::forOwner($owner->id)
            ->active()
            ->with('residence')
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('created_at')
            ->get();
    }
}
