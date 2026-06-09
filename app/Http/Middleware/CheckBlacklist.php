<?php

namespace App\Http\Middleware;

use App\Models\Blacklist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                    Log::channel('security')->info('User account banned — access denied', [
                        'user_id' => $request->user()->id,
                        'reason'  => $userBlacklist->reason,
                    ]);

                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error'   => 'Compte suspendu.',
                            'message' => 'Votre compte a été suspendu. Contactez le support : support@reziapp.ci',
                        ], 403);
                    }

                    return redirect()->route('login')
                        ->with('error', 'Votre compte a été suspendu. Contactez le support : support@reziapp.ci');
                }

                // Suspension temporaire
                if ($restrictionLevel === 'suspended') {
                    $expiresAt = $userBlacklist->expires_at;
                    $message   = 'Votre compte est temporairement suspendu';

                    if ($expiresAt) {
                        $message .= ' jusqu\'au '.$expiresAt->format('d/m/Y H:i');
                    }

                    $message .= '. Contactez le support : support@reziapp.ci';

                    auth()->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error'   => 'Compte suspendu.',
                            'message' => $message,
                        ], 403);
                    }

                    return redirect()->route('login')->with('error', $message);
                }

                // Accès limité — stocker pour utilisation dans les vues (sans exposer la raison)
                if ($restrictionLevel === 'limited') {
                    $request->merge(['_user_restricted' => true]);
                    view()->share('userRestricted', true);
                    view()->share('userRestrictionReason', 'Accès limité. Contactez le support.');
                }

                // Avertissement — message générique
                if ($restrictionLevel === 'warning') {
                    session()->flash('warning', 'Votre compte a reçu un avertissement. Contactez le support si besoin.');
                }
            }
        }

        return $next($request);
    }
}
