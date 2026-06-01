<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('admin.statistics_page', 300, fn () => [
            'generatedAt' => now(),
            'globalStats' => $this->getGlobalStats(),
            'residencesByCommune' => $this->getResidencesByCommune(),
            'registrationsByDay' => $this->getRegistrationsByDay(),
            'topResidences' => $this->getTopResidences(),
            'bookingsByStatus' => $this->getBookingsByStatus(),
            'revenueByMonth' => $this->getRevenueByMonth(),
        ]);
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
        $startDate = Carbon::now()->subDays(29)->startOfDay();

        $users = User::where('role', '!=', 'admin')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $owners = User::where('role', 'owner')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(created_at)')
            ->pluck('count', 'date');

        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $data[] = [
                'date' => Carbon::parse($date)->format('d/m'),
                'users' => $users[$date] ?? 0,
                'owners' => $owners[$date] ?? 0,
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
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();

        $revenues = Payment::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $bookings = Booking::where('created_at', '>=', $startDate)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $data[] = [
                'month' => $month->translatedFormat('M Y'),
                'revenue' => $revenues[$key] ?? 0,
                'bookings' => $bookings[$key] ?? 0,
            ];
        }

        return $data;
    }
}
