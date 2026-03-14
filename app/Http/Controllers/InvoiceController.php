<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    protected InvoiceService $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Liste des factures
     */
    public function index(Request $request)
    {
        $invoices = $this->invoiceService->getUserInvoices(Auth::user(), [
            'status' => $request->status,
            'from' => $request->from,
            'to' => $request->to,
            'search' => $request->search,
            'per_page' => 15,
        ]);

        $stats = $this->invoiceService->getStatistics(Auth::id());

        return view('invoices.index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => $request->only(['status', 'from', 'to', 'search']),
        ]);
    }

    /**
     * Afficher une facture
     */
    public function show(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $invoice->load(['payment', 'booking.residence']);

        return view('invoices.show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Télécharger le PDF
     */
    public function download(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        return $this->invoiceService->downloadPdf($invoice);
    }

    /**
     * Voir le PDF en ligne
     */
    public function view(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        // Générer si nécessaire
        if (!$invoice->pdf_path) {
            $invoice->generatePdf();
            $invoice->refresh();
        }

        return response()->file(
            storage_path('app/public/'.$invoice->pdf_path),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * Régénérer le PDF
     */
    public function regenerate(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $this->invoiceService->regeneratePdf($invoice);

        return back()->with('success', 'Facture régénérée avec succès.');
    }

    /**
     * Envoyer par email
     */
    public function sendEmail(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        if ($this->invoiceService->sendByEmail($invoice)) {
            return back()->with('success', 'Facture envoyée par email.');
        }

        return back()->with('error', 'Erreur lors de l\'envoi de la facture.');
    }
}
