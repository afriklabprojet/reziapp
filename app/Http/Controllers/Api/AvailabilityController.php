<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityCalendar;
use App\Models\Residence;
use App\Models\SeasonalPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    /**
     * Récupère le calendrier d'une résidence
     */
    public function index(Request $request, Residence $residence): JsonResponse
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : now()->startOfMonth();
        
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : now()->addMonths(3)->endOfMonth();

        $calendar = AvailabilityCalendar::getCalendar($residence->id, $startDate, $endDate);
        $seasonalPricing = SeasonalPricing::getActiveForPeriod($residence->id, $startDate, $endDate);

        return response()->json([
            'residence_id' => $residence->id,
            'default_price' => $residence->price_per_day,
            'calendar' => $calendar,
            'seasonal_pricing' => $seasonalPricing,
            'blocked_dates' => AvailabilityCalendar::getBlockedDates($residence->id, $startDate, $endDate),
        ]);
    }

    /**
     * Vérifie la disponibilité pour une période
     */
    public function checkAvailability(Request $request, Residence $residence): JsonResponse
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        $isAvailable = AvailabilityCalendar::isAvailable($residence->id, $checkIn, $checkOut);
        $priceDetails = AvailabilityCalendar::calculateTotalPrice($residence, $checkIn, $checkOut);

        // Vérifier minimum de nuits
        $minNightsRequired = 1;
        $seasonalPricing = SeasonalPricing::getActiveForPeriod($residence->id, $checkIn, $checkOut);
        foreach ($seasonalPricing as $season) {
            if ($season->min_nights > $minNightsRequired) {
                $minNightsRequired = $season->min_nights;
            }
        }

        $meetsMinNights = $priceDetails['nights'] >= $minNightsRequired;

        return response()->json([
            'available' => $isAvailable && $meetsMinNights,
            'is_available' => $isAvailable,
            'meets_min_nights' => $meetsMinNights,
            'min_nights_required' => $minNightsRequired,
            'price' => $priceDetails,
            'residence' => [
                'id' => $residence->id,
                'title' => $residence->title,
                'default_price_per_day' => $residence->price_per_day,
            ],
        ]);
    }

    /**
     * Bloque des dates (propriétaire uniquement)
     */
    public function blockDates(Request $request, Residence $residence): JsonResponse
    {
        $this->authorize('update', $residence);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'note' => 'nullable|string|max:255',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $count = AvailabilityCalendar::blockDates(
            $residence->id, 
            $startDate, 
            $endDate, 
            $request->note
        );

        return response()->json([
            'message' => "{$count} date(s) bloquée(s)",
            'blocked_dates' => AvailabilityCalendar::getBlockedDates($residence->id, $startDate, $endDate),
        ]);
    }

    /**
     * Débloque des dates (propriétaire uniquement)
     */
    public function unblockDates(Request $request, Residence $residence): JsonResponse
    {
        $this->authorize('update', $residence);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $count = AvailabilityCalendar::unblockDates($residence->id, $startDate, $endDate);

        return response()->json([
            'message' => "{$count} date(s) débloquée(s)",
        ]);
    }

    /**
     * Définit un prix personnalisé pour des dates
     */
    public function setCustomPrice(Request $request, Residence $residence): JsonResponse
    {
        $this->authorize('update', $residence);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'price' => 'required|numeric|min:0',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $count = AvailabilityCalendar::setCustomPrice(
            $residence->id, 
            $startDate, 
            $endDate, 
            $request->price
        );

        return response()->json([
            'message' => "Prix personnalisé défini pour {$count} date(s)",
        ]);
    }

    /**
     * Gère les tarifs saisonniers
     */
    public function seasonalPricing(Request $request, Residence $residence): JsonResponse
    {
        $this->authorize('update', $residence);

        if ($request->isMethod('get')) {
            return response()->json([
                'seasonal_pricing' => SeasonalPricing::where('residence_id', $residence->id)
                    ->orderBy('start_date')
                    ->get(),
                'templates' => SeasonalPricing::getSeasonTemplates(),
            ]);
        }

        // POST - Créer un tarif saisonnier
        $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'price_per_day' => 'nullable|numeric|min:0',
            'price_multiplier' => 'nullable|numeric|min:0.1|max:5',
            'min_nights' => 'nullable|integer|min:1',
        ]);

        $seasonalPricing = SeasonalPricing::create([
            'residence_id' => $residence->id,
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'price_per_day' => $request->price_per_day,
            'price_multiplier' => $request->price_multiplier ?? 1.00,
            'min_nights' => $request->min_nights ?? 1,
        ]);

        return response()->json([
            'message' => 'Tarif saisonnier créé',
            'seasonal_pricing' => $seasonalPricing,
        ], 201);
    }

    /**
     * Supprime un tarif saisonnier
     */
    public function deleteSeasonalPricing(Request $request, Residence $residence, SeasonalPricing $pricing): JsonResponse
    {
        $this->authorize('update', $residence);

        if ($pricing->residence_id !== $residence->id) {
            abort(403, 'Ce tarif n\'appartient pas à cette résidence');
        }

        $pricing->delete();

        return response()->json([
            'message' => 'Tarif saisonnier supprimé',
        ]);
    }

    /**
     * Importe un template de saison
     */
    public function importSeasonTemplate(Request $request, Residence $residence): JsonResponse
    {
        $this->authorize('update', $residence);

        $request->validate([
            'template' => 'required|string',
            'year' => 'required|integer|min:2024|max:2030',
        ]);

        $seasonalPricing = SeasonalPricing::createFromTemplate(
            $residence->id,
            $request->template,
            $request->year
        );

        if (!$seasonalPricing) {
            return response()->json([
                'message' => 'Template non trouvé',
            ], 404);
        }

        return response()->json([
            'message' => 'Tarif saisonnier importé',
            'seasonal_pricing' => $seasonalPricing,
        ], 201);
    }
}
