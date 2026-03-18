<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Models\CleaningTask;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    /**
     * Multi-residence dashboard with side-by-side comparison
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $residences = $user->residences()
            ->with(['photos', 'bookings' => fn($q) => $q->where('status', 'completed')])
            ->withCount(['bookings', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->get();

        $portfolioData = [];
        foreach ($residences as $residence) {
            $monthRevenue = Booking::where('residence_id', $residence->id)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            $monthExpenses = Expense::where('residence_id', $residence->id)
                ->whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('amount');

            $openMaintenance = MaintenanceRequest::where('residence_id', $residence->id)
                ->open()
                ->count();

            $pendingCleaning = CleaningTask::where('residence_id', $residence->id)
                ->where('status', CleaningTask::STATUS_PENDING)
                ->count();

            $occupancyRate = $this->calculateOccupancyRate($residence->id);

            $portfolioData[] = [
                'residence'        => $residence,
                'month_revenue'    => $monthRevenue,
                'month_expenses'   => $monthExpenses,
                'net_income'       => $monthRevenue - $monthExpenses,
                'occupancy_rate'   => $occupancyRate,
                'open_maintenance' => $openMaintenance,
                'pending_cleaning' => $pendingCleaning,
                'avg_rating'       => $residence->reviews_count > 0
                    ? round((float) $residence->reviews_avg_rating, 1)
                    : null,
            ];
        }

        // Global summary
        $summary = [
            'total_residences'  => $residences->count(),
            'total_revenue'     => collect($portfolioData)->sum('month_revenue'),
            'total_expenses'    => collect($portfolioData)->sum('month_expenses'),
            'total_net'         => collect($portfolioData)->sum('net_income'),
            'avg_occupancy'     => collect($portfolioData)->avg('occupancy_rate'),
            'total_maintenance' => collect($portfolioData)->sum('open_maintenance'),
        ];

        return view('owner.portfolio.index', compact('portfolioData', 'summary'));
    }

    private function calculateOccupancyRate(int $residenceId): float
    {
        $daysInMonth = now()->daysInMonth;
        $bookedDays  = Booking::where('residence_id', $residenceId)
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereMonth('check_in', now()->month)
            ->whereYear('check_in', now()->year)
            ->selectRaw('SUM(DATEDIFF(LEAST(check_out, LAST_DAY(NOW())), GREATEST(check_in, DATE_FORMAT(NOW(), "%Y-%m-01"))) + 1) as days')
            ->value('days') ?? 0;

        return $daysInMonth > 0 ? round(min(100, ($bookedDays / $daysInMonth) * 100), 1) : 0;
    }
}
