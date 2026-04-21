<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreRentReceiptRequest;
use App\Models\RentReceipt;
use App\Models\Residence;
use App\Services\RentReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Gestion des quittances de loyer pour les propriétaires.
 */
class RentReceiptController extends Controller
{
    public function __construct(
        private readonly RentReceiptService $receiptService,
    ) {
    }

    // ===== INDEX =====

    public function index(Request $request): View
    {
        $owner = $request->user();

        $year  = (int) $request->get('year', now()->year);
        $month = $request->filled('month') ? (int) $request->month : null;

        $receipts = RentReceipt::forOwner($owner->id)
            ->with(['tenant:id,name,email', 'residence:id,name,commune'])
            ->forPeriod($year, $month)
            ->orderByDesc('period_start')
            ->paginate(20)
            ->withQueryString();

        $summary        = $this->receiptService->getMonthlyRevenueSummary($owner->id);
        $availableYears = range(now()->year, max(now()->year - 4, 2024));

        return view('owner.rent-receipts.index', compact('receipts', 'summary', 'year', 'month', 'availableYears'));
    }

    // ===== CRÉATION =====

    public function create(Request $request): View
    {
        $owner      = $request->user();
        $residences = Residence::where('owner_id', $owner->id)
            ->where('status', 'active')
            ->select('id', 'name', 'commune')
            ->get();

        return view('owner.rent-receipts.create', compact('residences'));
    }

    public function store(StoreRentReceiptRequest $request): RedirectResponse
    {
        $data             = $request->validated();
        $data['owner_id'] = $request->user()->id;

        $receipt = $this->receiptService->createManual($data);

        // Envoi immédiat si demandé
        $viaWhatsApp = (bool) $request->boolean('send_by_whatsapp');
        if ($request->boolean('send_by_email') || $viaWhatsApp) {
            $this->receiptService->sendToTenant($receipt, $viaWhatsApp);
        }

        return redirect()
            ->route('owner.rent-receipts.show', $receipt)
            ->with('success', "Quittance {$receipt->reference} créée avec succès.");
    }

    // ===== SHOW =====

    public function show(RentReceipt $rentReceipt): View
    {
        $this->authorize('view', $rentReceipt);

        $rentReceipt->load([
            'tenant:id,name,email,phone',
            'owner:id,name,email,phone',
            'residence:id,name,commune,address',
        ]);

        return view('owner.rent-receipts.show', compact('rentReceipt'));
    }

    // ===== TÉLÉCHARGEMENT PDF =====

    public function download(RentReceipt $rentReceipt): Response
    {
        // Les locataires peuvent aussi télécharger leur propre quittance
        abort_unless(
            $rentReceipt->owner_id === auth()->id()
            || $rentReceipt->tenant_id === auth()->id(),
            403,
        );

        $pdfContent = $this->receiptService->downloadPdf($rentReceipt);
        $filename   = "quittance-{$rentReceipt->reference}.pdf";

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ===== RENVOI =====

    public function resend(Request $request, RentReceipt $rentReceipt): RedirectResponse
    {
        abort_unless($rentReceipt->owner_id === $request->user()->id, 403);

        $viaWhatsApp = $request->boolean('via_whatsapp');
        $this->receiptService->sendToTenant($rentReceipt, $viaWhatsApp);

        return back()->with('success', 'Quittance renvoyée au locataire.');
    }
}
