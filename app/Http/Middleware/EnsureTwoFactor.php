<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactor
{
    /** Re-challenge after this many seconds of inactivity. */
    private const TTL_SECONDS = 1_800; // 30 minutes

    /**
     * Vérifie que l'utilisateur a complété la 2FA si elle est activée.
     * Re-challenge automatiquement après 30 minutes d'inactivité.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->two_factor_enabled) {
            return $next($request);
        }

        // Ne pas rediriger si on est déjà sur la page 2FA (éviter boucle)
        if ($request->routeIs('two-factor.*')) {
            return $next($request);
        }

        $verifiedAt = session('2fa_verified_at');

        if ($verifiedAt && (time() - $verifiedAt) < self::TTL_SECONDS) {
            // Rafraîchir le timestamp à chaque requête active
            session(['2fa_verified_at' => time()]);

            return $next($request);
        }

        // Expiration ou jamais vérifié : invalider et rediriger
        session()->forget(['2fa_verified', '2fa_verified_at']);

        return redirect()->route('two-factor.challenge');
    }
}
