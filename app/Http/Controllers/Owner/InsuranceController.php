<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\InsuranceSubscription;
use App\Services\InsuranceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceController extends Controller
{
    public function __construct(
        private InsuranceService $insuranceService,
    ) {}

    public function index(Request $request): View
    {
        $user          = $request->user();
        $subscriptions = $this->insuranceService->getSubscriptions($user, $request->only(['status']));
        $expiring      = $this->insuranceService->getExpiringSoon($user);
        $monthlyCost   = $this->insuranceService->getTotalMonthlyCost($user);
        $residences    = $user->residences()->orderBy('name')->get();

        return view('owner.insurance.index', compact('subscriptions', 'expiring', 'monthlyCost', 'residences'));
    }

    public function create(Request $request): View
    {
        $residences = $request->user()->residences()->orderBy('name')->get();
        $coverageTypes = InsuranceSubscription::COVERAGE_TYPES;

        return view('owner.insurance.create', compact('residences', 'coverageTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'residence_id'   => ['required', 'integer', 'exists:residences,id'],
            'provider'       => ['required', 'string', 'max:200'],
            'policy_number'  => ['nullable', 'string', 'max:100'],
            'coverage_type'  => ['required', 'in:' . implode(',', array_keys(InsuranceSubscription::COVERAGE_TYPES))],
            'monthly_premium' => ['required', 'numeric', 'min:0'],
            'start_date'     => ['required', 'date'],
            'end_date'       => ['required', 'date', 'after:start_date'],
            'auto_renew'     => ['boolean'],
            'coverage_details' => ['nullable', 'array'],
        ]);

        $this->insuranceService->create($request->user(), $data);

        return redirect()->route('owner.insurance.index')
            ->with('success', 'Assurance ajoutée.');
    }

    public function cancel(InsuranceSubscription $insurance): RedirectResponse
    {
        abort_unless($insurance->owner_id === auth()->id(), 403);
        $this->insuranceService->cancel($insurance);

        return back()->with('success', 'Assurance résiliée.');
    }
}
