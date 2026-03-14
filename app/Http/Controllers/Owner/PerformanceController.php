<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Residence;
use App\Services\PerformanceDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerformanceController extends Controller
{
    public function __construct(
        private PerformanceDashboardService $performanceService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        $kpis          = $this->performanceService->getKPIs($user, $from, $to);
        $perResidence  = $this->performanceService->getPerResidenceKPIs($user, $from, $to);
        $trend         = $this->performanceService->getMonthlyTrend($user);

        return view('owner.performance.index', compact('kpis', 'perResidence', 'trend', 'from', 'to'));
    }

    public function benchmark(Request $request, Residence $residence): View
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to   = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();

        $benchmark = $this->performanceService->getBenchmark($residence, $from, $to);

        return view('owner.performance.benchmark', compact('residence', 'benchmark', 'from', 'to'));
    }
}
