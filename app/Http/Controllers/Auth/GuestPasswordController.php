<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class GuestPasswordController extends Controller
{
    /**
     * Afficher le formulaire de création de mot de passe
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = User::where('email', $request->email)
            ->where('guest_token', $request->token)
            ->where('is_guest', true)
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Lien invalide ou expiré. Veuillez vous connecter ou créer un compte.');
        }

        return view('auth.guest-password', [
            'email' => $request->email,
            'token' => $request->token,
            'user' => $user,
        ]);
    }

    /**
     * Enregistrer le mot de passe et convertir le compte invité
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)
            ->where('guest_token', $request->token)
            ->where('is_guest', true)
            ->first();

        if (!$user) {
            return back()->with('error', 'Lien invalide ou expiré.');
        }

        // Convertir le compte invité en compte normal
        $user->update([
            'password' => Hash::make($request->password),
            'is_guest' => false,
            'guest_token' => null,
            'email_verified_at' => now(), // L'email est vérifié par le token
        ]);

        // Connecter l'utilisateur
        Auth::login($user);

        return redirect()->route('bookings.index')
            ->with('success', 'Votre compte a été activé ! Vous pouvez maintenant gérer vos réservations.');
    }
}
