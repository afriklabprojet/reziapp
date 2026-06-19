<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\City;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $stats = Cache::remember('login_page_stats', 3600, function () {
            $residenceCount = Residence::approved()->available()->count();
            $userCount = User::count();
            $cityCount = City::active()->count();

            return [
                'residences' => $residenceCount >= 1000
                    ? round($residenceCount / 1000, 1) . 'k+'
                    : $residenceCount . '+',
                'users' => $userCount >= 1000
                    ? round($userCount / 1000, 0) . 'k+'
                    : $userCount . '+',
                'cities' => $cityCount,
            ];
        });

        $featuredResidence = Cache::remember('login_featured_residence', 3600, function () {
            return Residence::approved()
                ->available()
                ->has('photos')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->with('photos')
                ->first();
        });

        return view('auth.login', compact('stats', 'featuredResidence'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
