<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour vérifier que le propriétaire a soumis une vérification d'identité.
 *
 * Mode "soft" (défaut) : redirige avec un message flash mais ne bloque pas
 * Mode "strict" : bloque l'accès et redirige vers la page de vérification
 *
 * Usage dans les routes :
 *   ->middleware('identity.verified')          // mode soft
 *   ->middleware('identity.verified:strict')   // mode strict (bloque)
 */
class EnsureIdentityVerified
{
    public function handle(Request $request, Closure $next, string $mode = 'strict'): Response
    {
        $user = $request->user();

        // Les admins passent toujours
        if ($user?->isAdmin()) {
            return $next($request);
        }

        // Vérifier si l'identité est vérifiée
        if ($user && !$user->identity_verified) {
            if ($mode === 'strict') {
                return redirect()
                    ->route('verification.dashboard')
                    ->with('warning', 'Vous devez vérifier votre identité pour accéder à cette fonctionnalité.');
            }

            // Mode soft : on laisse passer mais on met un flash message
            session()->flash('identity_warning', true);
        }

        return $next($request);
    }
}
