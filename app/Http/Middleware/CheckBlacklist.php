<?php

namespace App\Http\Middleware;

use App\Models\Blacklist;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBlacklist
{
    /**
     * Vérifie si l'utilisateur ou l'IP est blacklisté
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier l'IP
        $ipBlacklisted = Blacklist::where('type', 'ip')
            ->where('value', $request->ip())
            ->active()
            ->exists();

        if ($ipBlacklisted) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Accès refusé.',
                    'message' => 'Votre adresse IP a été bloquée.',
                ], 403);
            }

            abort(403, 'Accès refusé. Votre adresse IP a été bloquée.');
        }

        // Vérifier l'utilisateur connecté
        if ($request->user()) {
            $userBlacklist = Blacklist::where('user_id', $request->user()->id)
                ->active()
                ->first();

            if ($userBlacklist) {
                $restrictionLevel = $userBlacklist->restriction_level;

                // Bannissement total
                if ($restrictionLevel === 'banned') {
                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Compte suspendu.',
                            'message' => 'Votre compte a été suspendu pour: '.$userBlacklist->reason,
                        ], 403);
                    }

                    return redirect()->route('login')
                        ->with('error', 'Votre compte a été suspendu. Raison: '.$userBlacklist->reason);
                }

                // Suspension temporaire
                if ($restrictionLevel === 'suspended') {
                    $expiresAt = $userBlacklist->expires_at;
                    $message = 'Votre compte est temporairement suspendu';

                    if ($expiresAt) {
                        $message .= ' jusqu\'au '.$expiresAt->format('d/m/Y H:i');
                    }

                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'Compte suspendu.',
                            'message' => $message,
                        ], 403);
                    }

                    return redirect()->route('login')->with('error', $message);
                }

                // Accès limité - stocker pour utilisation dans les vues
                if ($restrictionLevel === 'limited') {
                    $request->merge(['_user_restricted' => true]);
                    view()->share('userRestricted', true);
                    view()->share('userRestrictionReason', $userBlacklist->reason);
                }

                // Avertissement - juste afficher un message
                if ($restrictionLevel === 'warning') {
                    session()->flash('warning', 'Votre compte a reçu un avertissement: '.$userBlacklist->reason);
                }
            }
        }

        return $next($request);
    }
}
