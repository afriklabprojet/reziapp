<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UtilityAlert;
use App\Models\UtilityReading;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class UtilityService
{
    public function getReadings(User $owner, array $filters = []): LengthAwarePaginator
    {
        $residenceIds = $owner->residences()->pluck('id');

        $query = UtilityReading::whereIn('residence_id', $residenceIds)
            ->with('residence');

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        if (!empty($filters['utility_type'])) {
            $query->where('utility_type', $filters['utility_type']);
        }

        return $query->orderByDesc('reading_date')->paginate(20);
    }

    public function create(User $user, array $data): UtilityReading
    {
        $data['user_id'] = $user->id;

        $reading = UtilityReading::create($data);

        // Vérifier les alertes
        $this->checkForAlerts($reading);

        return $reading;
    }

    /**
     * Résumé de consommation par résidence
     */
    public function getConsumptionSummary(int $residenceId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to   = $to ?? now()->endOfMonth();

        $types = [UtilityReading::TYPE_ELECTRICITY, UtilityReading::TYPE_WATER, UtilityReading::TYPE_GAS];
        $summary = [];

        foreach ($types as $type) {
            $consumption = UtilityReading::calculateConsumption($residenceId, $type, $from, $to);
            $summary[$type] = [
                'consumption' => $consumption,
                'unit'        => UtilityReading::UNITS[$type] ?? '',
                'label'       => UtilityReading::TYPES[$type] ?? $type,
            ];
        }

        return $summary;
    }

    /**
     * Vérifier si un relevé déclenche une alerte
     */
    private function checkForAlerts(UtilityReading $reading): void
    {
        // Comparer avec le relevé précédent
        $previous = UtilityReading::where('residence_id', $reading->residence_id)
            ->where('utility_type', $reading->utility_type)
            ->where('id', '<', $reading->id)
            ->orderByDesc('reading_date')
            ->first();

        if (!$previous) {
            return;
        }

        $diff = $reading->reading_value - $previous->reading_value;
        $daysBetween = $previous->reading_date->diffInDays($reading->reading_date) ?: 1;
        $dailyAvg = $diff / $daysBetween;

        // Seuil de consommation anormale (2x la moyenne journalière habituelle)
        $historicalAvg = $this->getHistoricalDailyAvg($reading->residence_id, $reading->utility_type);

        if ($historicalAvg > 0 && $dailyAvg > ($historicalAvg * 2)) {
            UtilityAlert::create([
                'residence_id'    => $reading->residence_id,
                'user_id'         => $reading->user_id,
                'utility_type'    => $reading->utility_type,
                'alert_type'      => UtilityAlert::ALERT_ABNORMAL_SPIKE,
                'threshold_value' => $historicalAvg * 2,
                'current_value'   => $dailyAvg,
                'status'          => 'active',
                'message'         => "Consommation anormalement élevée de {$reading->type_label} : ".round($dailyAvg, 1)." {$reading->unit}/jour (moyenne : ".round($historicalAvg, 1).')',
                'triggered_at'    => now(),
            ]);
        }
    }

    private function getHistoricalDailyAvg(int $residenceId, string $type): float
    {
        $readings = UtilityReading::where('residence_id', $residenceId)
            ->where('utility_type', $type)
            ->orderBy('reading_date')
            ->take(10)
            ->pluck('reading_value', 'reading_date')
            ->toArray();

        if (count($readings) < 2) {
            return 0;
        }

        $dates = array_keys($readings);
        $values = array_values($readings);
        $totalConsumption = end($values) - reset($values);
        $totalDays = Carbon::parse(end($dates))->diffInDays(Carbon::parse(reset($dates))) ?: 1;

        return max(0, $totalConsumption / $totalDays);
    }
}
