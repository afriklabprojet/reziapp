<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect to provider for authentication
     */
    public function redirect(string $provider)
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle callback from provider
     */
    public function callback(string $provider)
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Une erreur est survenue lors de la connexion avec '.ucfirst($provider));
        }

        // Find or create user
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Update provider info if needed
            $user->update([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        } else {
            // Create new user
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'Utilisateur',
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(24)),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'role' => 'user',
            ]);

            // Mark email as verified (OAuth emails are pre-verified by provider)
            $user->markEmailAsVerified();
        }

        Auth::login($user, true);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Connexion réussie avec '.ucfirst($provider).' !');
    }

    /**
     * Validate the provider
     */
    protected function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404, 'Provider non supporté');
        }
    }
}
