<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class StatisticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.statistics-page';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Statistiques';

    protected static ?string $title = 'Statistiques de la plateforme';

    protected static ?int $navigationSort = 5;

    public function getViewData(): array
    {
        return [
            'globalStats' => $this->getGlobalStats(),
            'residencesByCommune' => $this->getResidencesByCommune(),
            'registrationsByDay' => $this->getRegistrationsByDay(),
            'topResidences' => $this->getTopResidences(),
            'bookingsByStatus' => $this->getBookingsByStatus(),
            'revenueByMonth' => $this->getRevenueByMonth(),
        ];
    }

    protected function getGlobalStats(): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        return [
            'total_users' => User::where('role', '!=', 'admin')->count(),
            'new_users_month' => User::where('role', '!=', 'admin')
                ->where('created_at', '>=', $startOfMonth)->count(),
            'total_owners' => User::where('role', 'owner')->count(),
            'total_residences' => Residence::count(),
            'active_residences' => Residence::where('status', 'active')->count(),
            'total_bookings' => Booking::count(),
            'bookings_month' => Booking::where('created_at', '>=', $startOfMonth)->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'revenue_month' => Payment::where('status', 'completed')
                ->where('created_at', '>=', $startOfMonth)->sum('amount'),
        ];
    }

    protected function getResidencesByCommune()
    {
        return Residence::where('status', 'active')
            ->select('commune', DB::raw('COUNT(*) as count'))
            ->groupBy('commune')
            ->orderByDesc('count')
            ->limit(15)
            ->get();
    }

    protected function getRegistrationsByDay()
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $data[] = [
                'date' => $date->format('d/m'),
                'users' => User::where('role', '!=', 'admin')
                    ->whereDate('created_at', $date)->count(),
                'owners' => User::where('role', 'owner')
                    ->whereDate('created_at', $date)->count(),
            ];
        }

        return $data;
    }

    protected function getTopResidences()
    {
        return Residence::where('status', 'active')
            ->with('owner:id,name')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get(['id', 'name', 'commune', 'views_count', 'average_rating', 'owner_id', 'price_per_day']);
    }

    protected function getBookingsByStatus()
    {
        return Booking::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status => $item->count]);
    }

    protected function getRevenueByMonth()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $data[] = [
                'month' => $month->translatedFormat('M Y'),
                'revenue' => Payment::where('status', 'completed')
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('amount'),
                'bookings' => Booking::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count(),
            ];
        }

        return $data;
    }
}
