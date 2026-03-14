<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DailyPrice;
use App\Models\Residence;
use App\Models\SeasonalPrice;
use App\Services\DynamicPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    /**
     * Affiche le calendrier des prix pour une résidence
     */
    public function index(Residence $residence)
    {
        $this->authorize('update', $residence);

        $seasonalPrices = $residence->seasonalPrices()
            ->orderBy('start_date')
            ->get();

        // Récupérer les prix journaliers des 3 prochains mois
        $startDate = now()->startOfMonth();
        $endDate = now()->addMonths(3)->endOfMonth();

        $dailyPrices = $residence->dailyPrices()
            ->forPeriod($startDate, $endDate)
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        return view('owner.pricing.index', compact('residence', 'seasonalPrices', 'dailyPrices', 'startDate', 'endDate'));
    }

    /**
     * Affiche le formulaire de création d'une saison
     */
    public function createSeason(Residence $residence)
    {
        $this->authorize('update', $residence);

        return view('owner.pricing.create-season', compact('residence'));
    }

    /**
     * Enregistre une nouvelle saison
     */
    public function storeSeason(Request $request, Residence $residence)
    {
        $this->authorize('update', $residence);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'price_per_night' => 'required|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'min_nights' => 'nullable|integer|min:1',
            'priority' => 'nullable|in:low,normal,high',
            'notes' => 'nullable|string|max:500',
        ]);

        $residence->seasonalPrices()->create($validated);

        return redirect()
            ->route('owner.pricing.index', $residence)
            ->with('success', 'Saison tarifaire créée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'une saison
     */
    public function editSeason(Residence $residence, SeasonalPrice $season)
    {
        $this->authorize('update', $residence);

        return view('owner.pricing.edit-season', compact('residence', 'season'));
    }

    /**
     * Met à jour une saison
     */
    public function updateSeason(Request $request, Residence $residence, SeasonalPrice $season)
    {
        $this->authorize('update', $residence);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'price_per_night' => 'required|numeric|min:0',
            'price_per_week' => 'nullable|numeric|min:0',
            'price_per_month' => 'nullable|numeric|min:0',
            'min_nights' => 'nullable|integer|min:1',
            'priority' => 'nullable|in:low,normal,high',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $season->update($validated);

        return redirect()
            ->route('owner.pricing.index', $residence)
            ->with('success', 'Saison tarifaire mise à jour.');
    }

    /**
     * Supprime une saison
     */
    public function destroySeason(Residence $residence, SeasonalPrice $season)
    {
        $this->authorize('update', $residence);

        $season->delete();

        return redirect()
            ->route('owner.pricing.index', $residence)
            ->with('success', 'Saison tarifaire supprimée.');
    }

    /**
     * Met à jour les prix journaliers (AJAX)
     */
    public function updateDaily(Request $request, Residence $residence)
    {
        $this->authorize('update', $residence);

        $validated = $request->validate([
            'dates' => 'required|array',
            'dates.*' => 'date',
            'price' => 'nullable|numeric|min:0',
            'is_available' => 'boolean',
            'reason' => 'nullable|string|max:100',
        ]);

        foreach ($validated['dates'] as $date) {
            DailyPrice::updateOrCreate(
                [
                    'residence_id' => $residence->id,
                    'date' => $date,
                ],
                [
                    'price' => $validated['price'] ?? $residence->price_per_day,
                    'is_available' => $validated['is_available'] ?? true,
                    'reason' => $validated['reason'] ?? null,
                ],
            );
        }

        return response()->json(['success' => true, 'message' => 'Prix mis à jour.']);
    }

    /**
     * Récupère les données du calendrier (AJAX)
     */
    public function calendarData(Request $request, Residence $residence)
    {
        $this->authorize('view', $residence);

        $startDate = Carbon::parse($request->get('start', now()->startOfMonth()));
        $endDate = Carbon::parse($request->get('end', now()->addMonths(3)->endOfMonth()));

        // Prix de base
        $basePrice = $residence->price_per_day;

        // Prix saisonniers actifs
        $seasonalPrices = $residence->seasonalPrices()
            ->active()
            ->forPeriod($startDate, $endDate)
            ->get();

        // Prix journaliers
        $dailyPrices = $residence->dailyPrices()
            ->forPeriod($startDate, $endDate)
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        // Construire les données du calendrier
        $calendarData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');

            // Prix par défaut
            $price = $basePrice;
            $isAvailable = true;
            $priceType = 'base';
            $seasonName = null;

            // Vérifier les prix journaliers (priorité max)
            if (isset($dailyPrices[$dateStr])) {
                $daily = $dailyPrices[$dateStr];
                $price = $daily->price;
                $isAvailable = $daily->is_available;
                $priceType = 'daily';
            } else {
                // Vérifier les prix saisonniers
                foreach ($seasonalPrices as $season) {
                    if ($currentDate >= $season->start_date && $currentDate <= $season->end_date) {
                        $price = $season->price_per_night;
                        $priceType = 'seasonal';
                        $seasonName = $season->name;
                        break;
                    }
                }
            }

            $calendarData[] = [
                'date' => $dateStr,
                'price' => $price,
                'is_available' => $isAvailable,
                'price_type' => $priceType,
                'season_name' => $seasonName,
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'calendar' => $calendarData,
            'base_price' => $basePrice,
            'seasons' => $seasonalPrices,
        ]);
    }

    /**
     * Calcule le prix pour une période donnée
     */
    public function calculatePrice(Request $request, Residence $residence)
    {
        $validated = $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = Carbon::parse($validated['check_in']);
        $checkOut = Carbon::parse($validated['check_out']);
        $nights = $checkIn->diffInDays($checkOut);

        $totalPrice = 0;
        $priceBreakdown = [];
        $currentDate = $checkIn->copy();

        // Prix saisonniers
        $seasonalPrices = $residence->seasonalPrices()
            ->active()
            ->forPeriod($checkIn, $checkOut)
            ->orderBy('priority', 'desc')
            ->get();

        // Prix journaliers
        $dailyPrices = $residence->dailyPrices()
            ->forPeriod($checkIn, $checkOut)
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));

        while ($currentDate < $checkOut) {
            $dateStr = $currentDate->format('Y-m-d');
            $price = $residence->price_per_day;
            $source = 'base';

            // Vérifier prix journalier
            if (isset($dailyPrices[$dateStr])) {
                $daily = $dailyPrices[$dateStr];
                if (!$daily->is_available) {
                    return response()->json([
                        'error' => true,
                        'message' => "La résidence n'est pas disponible le {$currentDate->format('d/m/Y')}.",
                    ], 422);
                }
                $price = $daily->price;
                $source = 'daily';
            } else {
                // Vérifier prix saisonnier
                foreach ($seasonalPrices as $season) {
                    if ($currentDate >= $season->start_date && $currentDate <= $season->end_date) {
                        $price = $season->price_per_night;
                        $source = "season:{$season->name}";
                        break;
                    }
                }
            }

            $totalPrice += $price;
            $priceBreakdown[] = [
                'date' => $dateStr,
                'price' => $price,
                'source' => $source,
            ];

            $currentDate->addDay();
        }

        // Réductions longue durée
        $discount = 0;
        $discountLabel = null;

        if ($nights >= 30 && $residence->price_per_month) {
            $monthlyTotal = ceil($nights / 30) * $residence->price_per_month;
            if ($monthlyTotal < $totalPrice) {
                $discount = $totalPrice - $monthlyTotal;
                $totalPrice = $monthlyTotal;
                $discountLabel = 'Réduction mensuelle';
            }
        } elseif ($nights >= 7 && $residence->price_per_week) {
            $weeklyTotal = ceil($nights / 7) * $residence->price_per_week;
            if ($weeklyTotal < $totalPrice) {
                $discount = $totalPrice - $weeklyTotal;
                $totalPrice = $weeklyTotal;
                $discountLabel = 'Réduction hebdomadaire';
            }
        }

        return response()->json([
            'nights' => $nights,
            'total_price' => $totalPrice,
            'average_per_night' => round($totalPrice / $nights, 2),
            'discount' => $discount,
            'discount_label' => $discountLabel,
            'breakdown' => $priceBreakdown,
        ]);
    }

    /**
     * Affiche les suggestions de prix dynamiques IA
     */
    public function suggestions(Residence $residence, DynamicPricingService $pricingService)
    {
        $this->authorize('update', $residence);

        $suggestions = $pricingService->generateSuggestions($residence, 90);

        return view('owner.pricing.suggestions', compact('residence', 'suggestions'));
    }

    /**
     * Applique les suggestions de prix (AJAX)
     */
    public function applySuggestions(Request $request, Residence $residence, DynamicPricingService $pricingService)
    {
        $this->authorize('update', $residence);

        $validated = $request->validate([
            'suggestions' => 'required|array',
            'suggestions.*.type' => 'required|in:daily,seasonal',
            'suggestions.*.date' => 'required_if:suggestions.*.type,daily|date',
            'suggestions.*.start_date' => 'required_if:suggestions.*.type,seasonal|date',
            'suggestions.*.end_date' => 'required_if:suggestions.*.type,seasonal|date',
            'suggestions.*.price' => 'required|numeric|min:0',
            'suggestions.*.name' => 'nullable|string|max:100',
        ]);

        $applied = 0;

        foreach ($validated['suggestions'] as $suggestion) {
            if ($suggestion['type'] === 'daily') {
                DailyPrice::updateOrCreate(
                    [
                        'residence_id' => $residence->id,
                        'date' => $suggestion['date'],
                    ],
                    [
                        'price' => $suggestion['price'],
                        'reason' => 'Suggestion IA appliquée',
                    ],
                );
                $applied++;
            } elseif ($suggestion['type'] === 'seasonal') {
                SeasonalPrice::updateOrCreate(
                    [
                        'residence_id' => $residence->id,
                        'start_date' => $suggestion['start_date'],
                        'end_date' => $suggestion['end_date'],
                    ],
                    [
                        'name' => $suggestion['name'] ?? 'Saison IA',
                        'price_per_night' => $suggestion['price'],
                        'is_active' => true,
                        'notes' => 'Créé automatiquement par suggestions IA',
                    ],
                );
                $applied++;
            }
        }

        return response()->json([
            'success' => true,
            'applied' => $applied,
            'message' => "{$applied} suggestion(s) appliquée(s) avec succès.",
        ]);
    }

    /**
     * Applique automatiquement toutes les suggestions saisonnières
     */
    public function applyAllSuggestions(Residence $residence, DynamicPricingService $pricingService)
    {
        $this->authorize('update', $residence);

        $suggestions = $pricingService->generateSuggestions($residence, 90);
        $applied = $pricingService->applySuggestions($residence, $suggestions);

        return redirect()
            ->route('owner.pricing.index', $residence)
            ->with('success', "{$applied} saison(s) tarifaire(s) créée(s) automatiquement.");
    }
}
