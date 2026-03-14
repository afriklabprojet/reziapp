<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactor
{
    /**
     * Vérifie que l'utilisateur a complété la 2FA si elle est activée.
     * Redirige vers la page de challenge sinon.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Si 2FA n'est pas activée, laisser passer
        if (! $user->two_factor_enabled) {
            return $next($request);
        }

        // Si déjà vérifié dans cette session, laisser passer
        if (session('2fa_verified')) {
            return $next($request);
        }

        // Ne pas rediriger si on est déjà sur la page 2FA (éviter boucle)
        if ($request->routeIs('two-factor.*')) {
            return $next($request);
        }

        // Rediriger vers la page de challenge
        return redirect()->route('two-factor.challenge');
    }
}
