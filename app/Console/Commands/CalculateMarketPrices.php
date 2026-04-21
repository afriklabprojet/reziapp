<?php

namespace App\Console\Commands;

use App\Models\MarketPriceData;
use App\Models\Residence;
use Illuminate\Console\Command;

class CalculateMarketPrices extends Command
{
    protected $signature = 'rezi:calculate-market-prices {--country=CI : Country code}';

    protected $description = 'Calculate market prices from residence data automatically';

    public function handle(): int
    {
        $countryCode = $this->option('country');

        $this->info("Calculating market prices for {$countryCode}...");

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        // Get all unique combinations of city, commune, property type, bedrooms
        $combinations = Residence::query()
            ->where('country_code', $countryCode)
            ->where('status', 'approved')
            ->whereNotNull('price_per_day')
            ->select('city', 'commune', 'type', 'bedrooms')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(price_per_day) as avg_price')
            ->selectRaw('MIN(price_per_day) as min_price')
            ->selectRaw('MAX(price_per_day) as max_price')
            ->groupBy('city', 'commune', 'type', 'bedrooms')
            ->having('count', '>=', 1)
            ->get();

        $created = 0;
        $updated = 0;

        foreach ($combinations as $combo) {
            if (!$combo->city || !$combo->type) {
                continue;
            }

            // Calculate median
            $prices = Residence::where('country_code', $countryCode)
                ->where('status', 'approved')
                ->where('city', $combo->city)
                ->where('commune', $combo->commune)
                ->where('type', $combo->type)
                ->where('bedrooms', $combo->bedrooms)
                ->whereNotNull('price_per_day')
                ->pluck('price_per_day')
                ->sort()
                ->values();

            $median = $prices->count() > 0
                ? $prices->median()
                : $combo->avg_price;

            $data = [
                'country_code' => $countryCode,
                'city' => $combo->city,
                'commune' => $combo->commune,
                'residence_type' => $combo->type,
                'bedrooms' => $combo->bedrooms,
                'avg_price_per_night' => round($combo->avg_price ?? 0, 2),
                'min_price_per_night' => round($combo->min_price ?? 0, 2),
                'max_price_per_night' => round($combo->max_price ?? 0, 2),
                'median_price_per_night' => round($median ?? 0, 2),
                'sample_size' => $combo->count,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ];

            // Update or create
            $existing = MarketPriceData::where('country_code', $countryCode)
                ->where('city', $combo->city)
                ->where('commune', $combo->commune)
                ->where('residence_type', $combo->type)
                ->where('bedrooms', $combo->bedrooms)
                ->where('period_start', $periodStart)
                ->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                MarketPriceData::create($data);
                $created++;
            }
        }

        // Also calculate city-level aggregates (without commune/bedroom specificity)
        $cityAggregates = Residence::query()
            ->where('country_code', $countryCode)
            ->where('status', 'approved')
            ->whereNotNull('price_per_day')
            ->select('city', 'type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(price_per_day) as avg_price')
            ->selectRaw('MIN(price_per_day) as min_price')
            ->selectRaw('MAX(price_per_day) as max_price')
            ->groupBy('city', 'type')
            ->having('count', '>=', 2)
            ->get();

        foreach ($cityAggregates as $agg) {
            if (!$agg->city || !$agg->type) {
                continue;
            }

            $prices = Residence::where('country_code', $countryCode)
                ->where('status', 'approved')
                ->where('city', $agg->city)
                ->where('type', $agg->type)
                ->whereNotNull('price_per_day')
                ->pluck('price_per_day')
                ->sort()
                ->values();

            $median = $prices->count() > 0 ? $prices->median() : $agg->avg_price;

            $existing = MarketPriceData::where('country_code', $countryCode)
                ->where('city', $agg->city)
                ->whereNull('commune')
                ->where('residence_type', $agg->type)
                ->whereNull('bedrooms')
                ->where('period_start', $periodStart)
                ->first();

            $data = [
                'country_code' => $countryCode,
                'city' => $agg->city,
                'commune' => null,
                'residence_type' => $agg->type,
                'bedrooms' => null,
                'avg_price_per_night' => round($agg->avg_price ?? 0, 2),
                'min_price_per_night' => round($agg->min_price ?? 0, 2),
                'max_price_per_night' => round($agg->max_price ?? 0, 2),
                'median_price_per_night' => round($median ?? 0, 2),
                'sample_size' => $agg->count,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ];

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                MarketPriceData::create($data);
                $created++;
            }
        }

        $this->info("✅ Market prices calculated: {$created} created, {$updated} updated");

        return Command::SUCCESS;
    }
}
