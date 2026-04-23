<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\YieldManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YieldController extends Controller
{
    public function __construct(
        private YieldManagementService $yieldService,
    ) {
    }

    public function index(Request $request): View
    {
        $user       = $request->user();
        $residences = $user->residences()
            ->where('status', 'approved')
            ->withCount(['bookings' => fn ($q) => $q->where('check_in', '>=', now())])
            ->get();

        $yieldData = [];
        foreach ($residences as $residence) {
            $suggestions = $this->yieldService->applyForResidence($residence, 7);
            if ($suggestions > 0) {
                $basePrice = $residence->price_per_day ?? 0;
                $yieldData[$residence->id] = [
                    'suggested_price' => $basePrice,
                    'multiplier'      => 1,
                    'reason'          => 'Prix de base',
                ];
            }
        }

        $settings = $user->yield_settings ?? [];
        $gaps     = [];

        foreach ($residences as $residence) {
            foreach ($this->yieldService->findGapNights($residence) as $gap) {
                foreach ($gap['dates'] as $date) {
                    $gaps[] = [
                        'residence'    => $residence->name,
                        'residence_id' => $residence->id,
                        'date'         => \Carbon\Carbon::parse($date),
                        'nights'       => $gap['gap_days'],
                    ];
                }
            }
        }

        return view('owner.yield.index', compact('residences', 'yieldData', 'settings', 'gaps'));
    }

    public function toggleAutoPricing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'residence_id'     => 'required|exists:residences,id',
            'auto_pricing_min' => 'nullable|numeric|min:0',
            'auto_pricing_max' => 'nullable|numeric|min:0',
        ]);

        $residence = $request->user()->residences()->findOrFail($validated['residence_id']);
        $residence->update([
            'auto_pricing_enabled' => !$residence->auto_pricing_enabled,
            'auto_pricing_min'     => $validated['auto_pricing_min'] ?? $residence->auto_pricing_min,
            'auto_pricing_max'     => $validated['auto_pricing_max'] ?? $residence->auto_pricing_max,
        ]);

        $status = $residence->auto_pricing_enabled ? 'activé' : 'désactivé';

        return back()->with('success', "Yield management $status.");
    }

    public function toggleGapNight(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'residence_id'               => 'required|exists:residences,id',
            'gap_night_discount_percent' => 'nullable|numeric|min:5|max:50',
            'gap_night_max_days'         => 'nullable|integer|min:1|max:5',
        ]);

        $residence = $request->user()->residences()->findOrFail($validated['residence_id']);
        $residence->update([
            'gap_night_pricing_enabled'  => !$residence->gap_night_pricing_enabled,
            'gap_night_discount_percent' => $validated['gap_night_discount_percent'] ?? $residence->gap_night_discount_percent,
            'gap_night_max_days'         => $validated['gap_night_max_days'] ?? $residence->gap_night_max_days,
        ]);

        $status = $residence->gap_night_pricing_enabled ? 'activé' : 'désactivé';

        return back()->with('success', "Gap-night pricing $status.");
    }

    public function gaps(Request $request): View
    {
        $user       = $request->user();
        $residences = $user->residences()->where('status', 'approved')->get();
        $allGaps    = [];

        foreach ($residences as $residence) {
            $gaps = $this->yieldService->findGapNights($residence);
            if (!empty($gaps)) {
                $allGaps[] = [
                    'residence' => $residence,
                    'gaps'      => $gaps,
                ];
            }
        }

        return view('owner.yield.gaps', compact('allGaps'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_increase'        => 'nullable|integer|min:0|max:200',
            'max_decrease'        => 'nullable|integer|min:0|max:50',
            'weekend_premium'     => 'nullable|integer|min:0|max:100',
            'high_season_premium' => 'nullable|integer|min:0|max:100',
            'auto_apply'          => 'nullable|boolean',
        ]);

        $request->user()->update([
            'yield_settings' => $validated,
        ]);

        return back()->with('success', 'Paramètres de tarification sauvegardés.');
    }

    public function applyGapDiscount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'residence_id' => 'required|exists:residences,id',
            'date'         => 'required|date',
        ]);

        $residence = $request->user()->residences()->findOrFail($validated['residence_id']);

        $basePrice    = $residence->price_per_day ?? 0;
        $discountPct  = $residence->gap_night_discount_percent ?? 20;
        $discounted   = (int) round($basePrice * (1 - $discountPct / 100));

        \App\Models\DailyPrice::updateOrCreate(
            ['residence_id' => $residence->id, 'date' => $validated['date']],
            ['price' => $discounted, 'note' => "Gap-night -{$discountPct}%"],
        );

        return back()->with('success', 'Réduction appliquée sur la nuit isolée.');
    }
}
