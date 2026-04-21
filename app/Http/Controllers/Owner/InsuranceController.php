<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\InsuranceSubscription;
use App\Models\Residence;
use App\Services\InsuranceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceController extends Controller
{
    public function __construct(
        private InsuranceService $insuranceService,
    ) {
    }

    public function index(Request $request): View
    {
        $user             = $request->user();
        $subscriptions    = $this->insuranceService->getSubscriptions($user, $request->only(['status']));
        $expiringSoon     = $this->insuranceService->getExpiringSoon($user);
        $totalMonthlyCost = $this->insuranceService->getTotalMonthlyCost($user);
        $stats            = $this->insuranceService->getOwnerStats($user);
        $residences       = $user->residences()->orderBy('name')->get();

        return view('owner.insurance.index', compact('subscriptions', 'expiringSoon', 'totalMonthlyCost', 'stats', 'residences'));
    }

    public function show(InsuranceSubscription $insurance): View
    {
        abort_unless($insurance->owner_id === auth()->id(), 403);
        $insurance->load(['residence', 'events', 'renewedFrom', 'renewals']);

        return view('owner.insurance.show', compact('insurance'));
    }

    public function create(Request $request): View
    {
        $residences    = $request->user()->residences()->orderBy('name')->get();
        $coverageTypes = InsuranceSubscription::COVERAGE_TYPES;

        return view('owner.insurance.create', compact('residences', 'coverageTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'residence_id'    => ['required', 'integer', 'exists:residences,id'],
            'provider'        => ['required', 'string', 'max:200'],
            'policy_number'   => ['nullable', 'string', 'max:100'],
            'coverage_type'   => ['required', 'in:'.implode(',', array_keys(InsuranceSubscription::COVERAGE_TYPES))],
            'monthly_premium' => ['required', 'numeric', 'min:0'],
            'start_date'      => ['required', 'date'],
            'end_date'        => ['required', 'date', 'after:start_date'],
            'auto_renew'      => ['boolean'],
        ]);
        // Validate the residence belongs to the authenticated user
        $residence = Residence::where('id', $data['residence_id'])
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        $subscription = $this->insuranceService->create($request->user(), $data);

        return redirect()->route('owner.insurance.show', $subscription)
            ->with('success', 'Contrat d\'assurance enregistré avec succès.');
    }

    /**
     * Affiche le simulateur de devis pour une résidence.
     */
    public function quote(Request $request): View
    {
        $user       = $request->user();
        $residences = $user->residences()->orderBy('name')->get();
        $quote      = null;
        $residence  = null;

        if ($request->filled('residence_id')) {
            $residence = Residence::where('id', $request->input('residence_id'))
                ->where('owner_id', $user->id)
                ->first();

            if ($residence) {
                $quote = $this->insuranceService->generateQuote($residence, $user);
            }
        }

        return view('owner.insurance.quote', compact('residences', 'residence', 'quote'));
    }

    /**
     * Renouvelle un contrat existant.
     */
    public function renew(InsuranceSubscription $insurance): RedirectResponse
    {
        abort_unless($insurance->owner_id === auth()->id(), 403);

        try {
            $renewed = $this->insuranceService->renew($insurance);

            return redirect()->route('owner.insurance.show', $renewed)
                ->with('success', 'Contrat renouvelé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(InsuranceSubscription $insurance, Request $request): RedirectResponse
    {
        abort_unless($insurance->owner_id === auth()->id(), 403);

        $reason = $request->input('reason', 'Résiliation à la demande du propriétaire');
        $this->insuranceService->cancel($insurance, $reason);

        return back()->with('success', 'Contrat résilié.');
    }
}
