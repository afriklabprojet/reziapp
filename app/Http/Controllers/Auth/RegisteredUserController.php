<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MarketingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return view('auth.register');
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
            app(MarketingService::class)->processReferral($user, $referralCode);
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirection selon le rôle
        if ($user->role === 'owner') {
            return redirect(route('owner.dashboard', absolute: false));
        }

        return redirect(route('home', absolute: false));
    }
}
