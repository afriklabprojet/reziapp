<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\FiscalReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class FiscalReportController extends Controller
{
    public function __construct(
        private FiscalReportService $fiscalService,
    ) {}

    public function index(Request $request): View
    {
        $year   = (int) $request->get('year', now()->year);
        $report = $this->fiscalService->generate($request->user(), $year);

        return view('owner.fiscal-reports.index', compact('report', 'year'));
    }

    public function exportPdf(Request $request): Response
    {
        $year   = (int) $request->get('year', now()->year);
        $report = $this->fiscalService->generate($request->user(), $year);

        $pdf = Pdf::loadView('owner.fiscal-reports.pdf', compact('report'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("rapport-fiscal-{$year}.pdf");
    }
}
