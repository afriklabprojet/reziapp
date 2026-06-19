<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Residence;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $stats = Cache::remember('login_page_stats', 3600, function () {
            $residenceCount = Residence::approved()->available()->count();
            $userCount = User::count();
            $cityCount = City::active()->count();
            return [
                'residences' => $residenceCount >= 1000 ? round($residenceCount / 1000, 1) . 'k+' : $residenceCount . '+',
                'users'      => $userCount >= 1000 ? round($userCount / 1000, 0) . 'k+' : $userCount . '+',
                'cities'     => $cityCount,
            ];
        });

        $featuredResidence = Cache::remember('register_featured_residence', 3600, function () {
            return Residence::approved()->available()->has('photos')
                ->withAvg('reviews', 'rating')
                ->orderByDesc('reviews_avg_rating')
                ->with('photos')
                ->first();
        });

        return view('auth.register', compact('stats', 'featuredResidence'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'string', 'in:user,owner'],
            'ref' => ['nullable', 'string', 'max:20'],
        ]);

        // Chercher le parrain si un code est fourni
        $referrerId = null;
        $referralCode = $request->input('ref');
        if ($referralCode) {
            $referrer = User::where('referral_code', $referralCode)->first();
            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'referred_by' => $referrerId,
        ]);
        // SECURITE : seuls 'user' et 'owner' sont autorisés à l'inscription (jamais 'admin')
        $user->role = in_array($request->input('role'), ['user', 'owner']) ? $request->input('role') : 'user';
        $user->save();

        // Créer le parrainage si code valide
        if ($referralCode) {
            app(ReferralService::class)->processReferral($user, $referralCode);
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirection selon le rôle
        if ($user->role === 'owner') {
            return redirect(route('owner.dashboard', absolute: false));
        }

        // Locataire → page de confirmation d'inscription
        return redirect(route('register.success'))
            ->with('registration', 'success');
    }

    /**
     * Page de confirmation d'inscription.
     */
    public function success(): View
    {
        return view('auth.register-success');
    }
}
