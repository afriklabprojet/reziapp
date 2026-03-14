<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreUtilityReadingRequest;
use App\Services\UtilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UtilityController extends Controller
{
    public function __construct(
        private UtilityService $utilityService,
    ) {}

    public function index(Request $request): View
    {
        $user       = $request->user();
        $readings   = $this->utilityService->getReadings($user, $request->only(['residence_id', 'utility_type']));
        $residences = $user->residences()->orderBy('name')->get();

        // Résumé pour la résidence sélectionnée
        $summary = null;
        if ($request->filled('residence_id')) {
            $summary = $this->utilityService->getConsumptionSummary((int) $request->residence_id);
        }

        // Alertes actives
        $alerts = \App\Models\UtilityAlert::whereIn('residence_id', $residences->pluck('id'))
            ->where('status', 'active')
            ->orderByDesc('triggered_at')
            ->get();

        return view('owner.utilities.index', compact('readings', 'residences', 'summary', 'alerts'));
    }

    public function store(StoreUtilityReadingRequest $request): RedirectResponse
    {
        $this->utilityService->create($request->user(), $request->validated());

        return redirect()->route('owner.utilities.index')
            ->with('success', 'Relevé enregistré.');
    }

    public function acknowledgeAlert(\App\Models\UtilityAlert $alert): RedirectResponse
    {
        $alert->acknowledge();

        return back()->with('success', 'Alerte acquittée.');
    }
}
