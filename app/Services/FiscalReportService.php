<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

class FiscalReportService
{
    /**
     * Generate fiscal report for Côte d'Ivoire tax compliance
     */
    public function generate(User $owner, int $year): array
    {
        $residenceIds = $owner->residences()->pluck('id');

        // Revenue from bookings
        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->whereYear('created_at', $year)
            ->where('status', 'completed')
            ->get();

        $totalRevenue = $bookings->sum('total_amount');
        $platformFees = $bookings->sum('service_fee');
        $netRevenue   = $totalRevenue - $platformFees;

        // Expenses
        $expenses = Expense::forOwner($owner->id)
            ->whereYear('expense_date', $year)
            ->get();

        $totalExpenses = $expenses->sum('amount');

        // Tax calculations for CI
        $taxableIncome    = max(0, $netRevenue - $totalExpenses);
        $impotFoncier     = $taxableIncome * 0.15; // 15% impôt foncier CI
        $tvaCollected     = $totalRevenue * 0.18;  // 18% TVA CI

        // Revenue by month
        $revenueByMonth = [];
        $expensesByMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueByMonth[$m]  = $bookings->filter(fn($b) => Carbon::parse($b->created_at)->month === $m)->sum('total_amount');
            $expensesByMonth[$m] = $expenses->filter(fn($e) => $e->expense_date->month === $m)->sum('amount');
        }

        // Revenue by residence
        $revenueByResidence = [];
        foreach ($owner->residences as $residence) {
            $resBookings = $bookings->where('residence_id', $residence->id);
            $resExpenses = $expenses->where('residence_id', $residence->id);
            $revenueByResidence[] = [
                'residence'    => $residence->name,
                'revenue'      => $resBookings->sum('total_amount'),
                'expenses'     => $resExpenses->sum('amount'),
                'net'          => $resBookings->sum('total_amount') - $resExpenses->sum('amount'),
                'bookings'     => $resBookings->count(),
            ];
        }

        // Expense breakdown by deductible categories
        $expensesByCategory = [];
        foreach (Expense::CATEGORIES as $key => $label) {
            $catAmount = $expenses->where('category', $key)->sum('amount');
            if ($catAmount > 0) {
                $expensesByCategory[$key] = [
                    'label'  => $label,
                    'amount' => $catAmount,
                ];
            }
        }

        return [
            'year'                => $year,
            'owner'               => $owner->name,
            'total_revenue'       => $totalRevenue,
            'platform_fees'       => $platformFees,
            'net_revenue'         => $netRevenue,
            'total_expenses'      => $totalExpenses,
            'taxable_income'      => $taxableIncome,
            'impot_foncier'       => round($impotFoncier),
            'tva_collected'       => round($tvaCollected),
            'revenue_by_month'    => $revenueByMonth,
            'expenses_by_month'   => $expensesByMonth,
            'revenue_by_residence' => $revenueByResidence,
            'expenses_by_category' => $expensesByCategory,
            'total_bookings'      => $bookings->count(),
        ];
    }
}
