<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreSecurityDepositRequest;
use App\Models\SecurityDeposit;
use App\Models\User;
use App\Services\SecurityDepositService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion des dépôts de garantie (caution) pour les propriétaires.
 */
class SecurityDepositController extends Controller
{
    public function __construct(
        private readonly SecurityDepositService $depositService,
    ) {
    }

    // ===== INDEX =====

    public function index(Request $request): View
    {
        $owner = $request->user();

        $deposits = SecurityDeposit::forOwner($owner->id)
            ->with(['tenant:id,name,email,phone', 'residence:id,name,commune'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = $this->depositService->getOwnerStats($owner->id);

        return view('owner.security-deposits.index', compact('deposits', 'stats'));
    }

    // ===== CRÉATION =====

    public function create(Request $request): View
    {
        $owner      = $request->user();
        $residences = $owner->residences()
            ->where('status', 'active')
            ->select('id', 'name', 'commune')
            ->get();

        // Récupérer les locataires ayant un contrat avec ce propriétaire
        $tenants = User::whereIn('id', function ($query) use ($owner) {
            $query->select('tenant_id')
                ->from('lease_contracts')
                ->where('owner_id', $owner->id)
                ->whereNotNull('tenant_id');
        })->select('id', 'name')->get();

        return view('owner.security-deposits.create', compact('residences', 'tenants'));
    }

    public function store(StoreSecurityDepositRequest $request): RedirectResponse
    {
        $data             = $request->validated();
        $data['owner_id'] = $request->user()->id;

        $deposit = $this->depositService->create($data);

        return redirect()
            ->route('owner.security-deposits.show', $deposit)
            ->with('success', "Dépôt de garantie {$deposit->reference} enregistré.");
    }

    // ===== SHOW =====

    public function show(SecurityDeposit $securityDeposit): View
    {
        $this->authorize('view', $securityDeposit);

        $securityDeposit->load([
            'tenant:id,name,email,phone',
            'owner:id,name,email',
            'residence:id,name,commune,address',
            'leaseContract',
        ]);

        return view('owner.security-deposits.show', [
            'deposit' => $securityDeposit,
        ]);
    }

    // ===== MARQUER COMME PAYÉ =====

    public function markPaid(Request $request, SecurityDeposit $securityDeposit): RedirectResponse
    {
        $this->authorize('update', $securityDeposit);

        $request->validate([
            'payment_method'    => ['required', 'string'],
            'payment_reference' => ['required', 'string', 'max:100'],
        ]);

        $this->depositService->markAsPaid(
            $securityDeposit,
            $request->payment_method,
            $request->payment_reference,
        );

        return back()->with('success', 'Paiement de la caution enregistré.');
    }

    // ===== RESTITUTION TOTALE =====

    public function returnFull(Request $request, SecurityDeposit $securityDeposit): RedirectResponse
    {
        $this->authorize('return', $securityDeposit);

        $request->validate([
            'return_payment_method' => ['required', 'string'],
            'return_reference'      => ['required', 'string', 'max:100'],
        ]);

        $this->depositService->returnFull(
            deposit:          $securityDeposit,
            paymentMethod:    $request->return_payment_method,
            paymentReference: $request->return_reference,
        );

        return back()->with('success', 'Caution restituée intégralement. Le locataire a été notifié.');
    }

    // ===== RESTITUTION PARTIELLE =====

    public function returnPartial(Request $request, SecurityDeposit $securityDeposit): RedirectResponse
    {
        $this->authorize('return', $securityDeposit);

        $request->validate([
            'returned_amount'       => ['required', 'numeric', 'min:0'],
            'deduction_reason'      => ['required', 'string', 'min:10', 'max:1000'],
            'deduction_items'       => ['nullable', 'array'],
            'deduction_items.*.item' => ['required', 'string'],
            'deduction_items.*.amount' => ['required', 'numeric', 'min:0'],
            'return_payment_method' => ['required', 'string'],
            'return_reference'      => ['required', 'string', 'max:100'],
        ]);

        $this->depositService->returnPartial(
            deposit:          $securityDeposit,
            returnedAmount:   (float) $request->returned_amount,
            deductionReason:  $request->deduction_reason,
            deductionItems:   $request->deduction_items ?? [],
            paymentMethod:    $request->return_payment_method,
            paymentReference: $request->return_reference,
        );

        return back()->with('success', 'Restitution partielle enregistrée. Le locataire a été notifié.');
    }
}
