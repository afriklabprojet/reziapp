<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Services\InsuranceClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InsuranceClaimController extends Controller
{
    public function __construct(
        private InsuranceClaimService $claimService,
    ) {}

    public function index(Request $request): View
    {
        $claims = $this->claimService->getClaims($request->user(), $request->only('status'));

        return view('owner.insurance-claims.index', compact('claims'));
    }

    public function create(): View
    {
        $user       = request()->user();
        $insurances = \App\Models\BookingInsurance::whereHas('booking', fn ($q) => $q->where('owner_id', $user->id))
            ->with('booking.residence')
            ->where('status', 'active')
            ->get();

        return view('owner.insurance-claims.create', compact('insurances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_insurance_id' => 'required|exists:booking_insurances,id',
            'claim_type'           => 'required|in:damage,theft,cancellation,accident,other',
            'description'          => 'required|string|max:5000',
            'claimed_amount'       => 'required|numeric|min:1',
            'incident_date'        => 'required|date|before_or_equal:today',
            'evidence'             => 'nullable|array|max:5',
            'evidence.*'           => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $evidence = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $evidence[] = $file->store('insurance-claims', 'public');
            }
        }
        $validated['evidence'] = $evidence;

        $claim = $this->claimService->create($request->user(), $validated);

        return redirect()->route('owner.insurance-claims.index')
            ->with('success', 'Réclamation soumise (réf: ' . $claim->claim_number . ').');
    }

    public function show(InsuranceClaim $claim): View
    {
        $claim->load('bookingInsurance.booking');

        return view('owner.insurance-claims.show', compact('claim'));
    }
}
