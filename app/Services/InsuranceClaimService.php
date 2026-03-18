<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InsuranceClaim;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class InsuranceClaimService
{
    public function getClaims(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = InsuranceClaim::where('user_id', $user->id)
            ->with('bookingInsurance.booking.residence');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function create(User $user, array $data): InsuranceClaim
    {
        $data['user_id'] = $user->id;
        $data['status']  = 'submitted';

        // Convertir la date si nécessaire
        if (isset($data['incident_date']) && is_string($data['incident_date'])) {
            $data['incident_date'] = \Carbon\Carbon::parse($data['incident_date']);
        }

        return InsuranceClaim::create($data);
    }

    public function update(InsuranceClaim $claim, array $data): InsuranceClaim
    {
        if (!$claim->canBeEdited()) {
            throw new \Exception('Cette réclamation ne peut plus être modifiée.');
        }

        $claim->update($data);
        return $claim->fresh();
    }

    public function addEvidence(InsuranceClaim $claim, array $newEvidence): InsuranceClaim
    {
        $evidence = $claim->evidence ?? [];
        $evidence = array_merge($evidence, $newEvidence);

        $claim->update(['evidence' => $evidence]);
        return $claim->fresh();
    }
}
