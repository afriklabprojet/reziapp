<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\InsuranceEvent;
use App\Models\InsuranceSubscription;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceService
{
    public function __construct(
        private RiskScoringService      $riskScoring,
        private InsurancePricingService $pricing,
    ) {
    }

    // ── Lecture ──────────────────────────────────────────────────────────

    public function getSubscriptions(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = InsuranceSubscription::forOwner($owner->id)->with('residence');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function getExpiringSoon(User $owner, int $days = 30): Collection
    {
        return InsuranceSubscription::forOwner($owner->id)
            ->expiringSoon($days)
            ->with('residence')
            ->get();
    }

    public function getTotalMonthlyCost(User $owner): float
    {
        return (float) InsuranceSubscription::forOwner($owner->id)
            ->active()
            ->sum('monthly_premium');
    }

    // ── Tarification ─────────────────────────────────────────────────────

    /**
     * Calcule le devis complet pour une résidence donnée.
     * Tous les types de couverture + score de risque.
     */
    public function generateQuote(Residence $residence, User $owner): array
    {
        $quote = $this->pricing->calculateAll($residence, $owner);

        // Enregistrer l'événement de devis
        try {
            InsuranceEvent::create([
                'eventable_type' => Residence::class,
                'eventable_id'   => $residence->id,
                'event_type'     => InsuranceEvent::TYPE_DEVIS_GENERE,
                'title'          => 'Devis assurance généré',
                'description'    => 'Simulation de prime pour '.($residence->name ?? 'résidence #'.$residence->id),
                'metadata'       => [
                    'risk_score'   => $quote[InsuranceSubscription::TYPE_STANDARD]['risk_score'] ?? null,
                    'risk_grade'   => $quote[InsuranceSubscription::TYPE_STANDARD]['risk_grade'] ?? null,
                    'premiums'     => array_map(fn ($q) => $q['suggested_premium'], $quote),
                ],
                'user_id'        => $owner->id,
                'ip_address'     => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('InsuranceService: could not record quote event', ['error' => $e->getMessage()]);
        }

        return $quote;
    }

    // ── Création ─────────────────────────────────────────────────────────

    public function create(User $owner, array $data): InsuranceSubscription
    {
        return DB::transaction(function () use ($owner, $data) {
            $data['owner_id'] = $owner->id;
            $data['status']   = InsuranceSubscription::STATUS_ACTIVE;

            // Calcul du score de risque si une résidence est fournie
            if (!empty($data['residence_id'])) {
                $residence = Residence::find($data['residence_id']);
                if ($residence) {
                    $riskResult = $this->riskScoring->calculate($residence, $owner);
                    $pricing    = $this->pricing->calculate($residence, $owner, $data['coverage_type'] ?? InsuranceSubscription::TYPE_STANDARD);

                    $data['risk_score']        = $riskResult['score'];
                    $data['risk_factors']      = $riskResult['factors'];
                    $data['suggested_premium'] = $pricing['suggested_premium'];
                }
            }

            $subscription = InsuranceSubscription::create($data);

            // Audit trail
            InsuranceEvent::record(
                $subscription,
                InsuranceEvent::TYPE_SOUSCRIPTION,
                'Contrat souscrit — '.($subscription->coverage_type_label ?? $subscription->coverage_type),
                'Nouveau contrat d\'assurance souscrit chez '.$subscription->provider.
                    ($subscription->risk_score ? ' (score risque: '.$subscription->risk_score.'/100)' : ''),
                [
                    'provider'          => $subscription->provider,
                    'policy_number'     => $subscription->policy_number,
                    'coverage_type'     => $subscription->coverage_type,
                    'monthly_premium'   => (float)$subscription->monthly_premium,
                    'suggested_premium' => (float)($subscription->suggested_premium ?? 0),
                    'risk_score'        => $subscription->risk_score,
                    'start_date'        => $subscription->start_date?->toDateString(),
                    'end_date'          => $subscription->end_date?->toDateString(),
                ],
                $owner,
            );

            return $subscription;
        });
    }

    // ── Résiliation ───────────────────────────────────────────────────────

    public function cancel(InsuranceSubscription $subscription, string $reason = 'Résiliation à la demande du propriétaire'): void
    {
        DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status'               => InsuranceSubscription::STATUS_CANCELLED,
                'cancellation_reason'  => $reason,
                'cancelled_at'         => now(),
            ]);

            InsuranceEvent::record(
                $subscription,
                InsuranceEvent::TYPE_RESILIATION,
                'Contrat résilié',
                $reason,
                ['policy_number' => $subscription->policy_number, 'reason' => $reason],
            );
        });
    }

    // ── Renouvellement ────────────────────────────────────────────────────

    public function renew(InsuranceSubscription $subscription): InsuranceSubscription
    {
        if (!$subscription->canBeRenewed()) {
            throw new \Exception('Ce contrat ne peut pas être renouvelé dans son état actuel.');
        }

        return DB::transaction(function () use ($subscription) {
            $subscription->load('residence');

            // Recalcul du score de risque au renouvellement
            $riskResult = null;
            $pricing    = null;
            if ($subscription->residence) {
                $riskResult = $this->riskScoring->calculate($subscription->residence, $subscription->owner);
                $pricing    = $this->pricing->calculate($subscription->residence, $subscription->owner, $subscription->coverage_type);
            }

            $newEnd = ($subscription->end_date ?? now())->addYear();

            $renewed = InsuranceSubscription::create([
                'owner_id'          => $subscription->owner_id,
                'residence_id'      => $subscription->residence_id,
                'provider'          => $subscription->provider,
                'policy_number'     => null, // généré automatiquement
                'external_policy_ref' => null,
                'coverage_type'     => $subscription->coverage_type,
                'status'            => InsuranceSubscription::STATUS_ACTIVE,
                'monthly_premium'   => $subscription->monthly_premium,
                'suggested_premium' => $pricing ? $pricing['suggested_premium'] : $subscription->monthly_premium,
                'currency'          => $subscription->currency ?? 'XOF',
                'start_date'        => $subscription->end_date ?? now(),
                'end_date'          => $newEnd,
                'coverage_details'  => $subscription->coverage_details,
                'auto_renew'        => $subscription->auto_renew,
                'risk_score'        => $riskResult ? $riskResult['score'] : $subscription->risk_score,
                'risk_factors'      => $riskResult ? $riskResult['factors'] : $subscription->risk_factors,
                'renewed_from_id'   => $subscription->id,
            ]);

            InsuranceEvent::record(
                $renewed,
                InsuranceEvent::TYPE_RENOUVELLEMENT,
                'Contrat renouvelé',
                'Renouvellement du contrat #'.$subscription->policy_number.' jusqu\'au '.$newEnd->format('d/m/Y'),
                [
                    'previous_policy'   => $subscription->policy_number,
                    'new_policy'        => $renewed->policy_number,
                    'new_risk_score'    => $renewed->risk_score,
                    'previous_premium'  => (float)$subscription->monthly_premium,
                    'new_premium'       => (float)$renewed->monthly_premium,
                ],
            );

            return $renewed;
        });
    }

    // ── Statistiques ─────────────────────────────────────────────────────

    public function getOwnerStats(User $owner): array
    {
        $subscriptions = InsuranceSubscription::forOwner($owner->id)->with('residence')->get();
        $allClaims = InsuranceClaim::whereHas(
            'bookingInsurance.booking.residence',
            fn ($q) => $q->where('owner_id', $owner->id),
        )->get();

        $activeCount    = $subscriptions->where('status', InsuranceSubscription::STATUS_ACTIVE)->count();
        $expiringCount  = $subscriptions->filter(fn ($s) => $s->isExpiringSoon())->count();
        $avgRiskScore   = $subscriptions->whereNotNull('risk_score')->avg('risk_score');
        $totalPremium   = $subscriptions->where('status', InsuranceSubscription::STATUS_ACTIVE)->sum('monthly_premium');
        $totalClaims    = $allClaims->count();
        $paidClaims     = $allClaims->whereIn('status', ['approved', 'paid'])->sum('final_payment_amount');

        return [
            'active_contracts'    => $activeCount,
            'expiring_soon'       => $expiringCount,
            'total_monthly_cost'  => (float)$totalPremium,
            'avg_risk_score'      => $avgRiskScore ? round((float)$avgRiskScore) : null,
            'total_claims'        => $totalClaims,
            'total_paid_claims'   => (float)$paidClaims,
            'coverage_ratio'      => $activeCount > 0
                ? round(($subscriptions->where('status', InsuranceSubscription::STATUS_ACTIVE)->count() / max(1, $owner->residences()->count())) * 100)
                : 0,
        ];
    }
}
