<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreLeaseContractRequest;
use App\Models\LeaseContract;
use App\Models\Residence;
use App\Models\User;
use App\Services\LeaseContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Gestion des contrats de bail numériques pour les propriétaires.
 */
class LeaseContractController extends Controller
{
    public function __construct(
        private readonly LeaseContractService $contractService,
    ) {}

    // ===== INDEX =====

    public function index(Request $request): View
    {
        $owner = $request->user();

        $contracts = LeaseContract::forOwner($owner->id)
            ->with(['tenant:id,name,email,phone', 'residence:id,title,commune'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->whereHas('tenant', fn ($sq) =>
                $sq->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = $this->contractService->getOwnerStats($owner->id);

        return view('owner.lease-contracts.index', compact('contracts', 'stats'));
    }

    // ===== CRÉATION =====

    public function create(Request $request): View
    {
        $owner     = $request->user();
        $residences = Residence::where('user_id', $owner->id)
            ->where('status', 'active')
            ->select('id', 'title', 'commune')
            ->orderBy('title')
            ->get();

        $bookingId = $request->query('booking_id');
        $booking   = $bookingId ? $owner->residences()
            ->join('bookings', 'residences.id', '=', 'bookings.residence_id')
            ->where('bookings.id', $bookingId)
            ->firstOrFail() : null;

        return view('owner.lease-contracts.create', compact('residences', 'booking'));
    }

    public function store(StoreLeaseContractRequest $request): RedirectResponse
    {
        $data             = $request->validated();
        $data['owner_id'] = $request->user()->id;

        $contract = $this->contractService->create($data);

        return redirect()
            ->route('owner.lease-contracts.show', $contract)
            ->with('success', "Contrat {$contract->reference} créé avec succès.");
    }

    // ===== SHOW =====

    public function show(LeaseContract $leaseContract): View
    {
        $this->authorize('view', $leaseContract);

        $leaseContract->load([
            'tenant:id,name,email,phone',
            'owner:id,name,email,phone',
            'residence:id,title,commune,address',
            'booking',
            'rentReceipts' => fn ($q) => $q->orderByDesc('period_start')->limit(12),
        ]);

        return view('owner.lease-contracts.show', [
            'contract' => $leaseContract,
        ]);
    }

    // ===== SIGNATURE =====

    public function sign(Request $request, LeaseContract $leaseContract): RedirectResponse
    {
        $this->authorize('sign', $leaseContract);

        try {
            $this->contractService->sign(
                contract: $leaseContract,
                signer:   $request->user(),
                ip:       $request->ip(),
            );

            $role = $request->user()->id === $leaseContract->owner_id ? 'propriétaire' : 'locataire';

            return back()->with('success', "Contrat signé en tant que {$role}.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ===== ENVOI AU LOCATAIRE =====

    public function sendToTenant(LeaseContract $leaseContract): RedirectResponse
    {
        $this->authorize('update', $leaseContract);

        try {
            $this->contractService->sendToTenant($leaseContract);

            return back()->with('success', "Contrat envoyé à {$leaseContract->tenant->name} pour signature.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ===== TÉLÉCHARGEMENT PDF =====

    public function download(LeaseContract $leaseContract): Response
    {
        $this->authorize('download', $leaseContract);

        $pdfContent = $this->contractService->downloadPdf($leaseContract);
        $filename   = "contrat-{$leaseContract->reference}.pdf";

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ===== RÉSILIATION =====

    public function terminateForm(LeaseContract $leaseContract): View
    {
        $this->authorize('terminate', $leaseContract);

        return view('owner.lease-contracts.terminate', compact('leaseContract'));
    }

    public function terminate(Request $request, LeaseContract $leaseContract): RedirectResponse
    {
        $this->authorize('terminate', $leaseContract);

        $request->validate([
            'termination_reason' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        $this->contractService->terminate(
            contract: $leaseContract,
            initiator: $request->user(),
            reason: $request->termination_reason,
        );

        return redirect()
            ->route('owner.lease-contracts.index')
            ->with('success', "Contrat {$leaseContract->reference} résilié.");
    }
}
