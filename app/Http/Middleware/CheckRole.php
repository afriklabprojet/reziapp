<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de vérification des rôles utilisateur
 *
 * @usage middleware('role:owner') ou middleware('role:owner,admin')
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles Rôles autorisés
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!$request->user()) {
            // Log tentative d'accès non authentifié
            Log::channel('security')->warning('Tentative d\'accès non authentifié', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => substr((string) $request->userAgent(), 0, 200),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise.',
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        // Vérifier si l'utilisateur a l'un des rôles requis
        if (!empty($roles) && !in_array($request->user()->role, $roles, true)) {
            // Log tentative d'accès non autorisé
            Log::channel('security')->warning('Tentative d\'accès non autorisé', [
                'user_id' => $request->user()->id,
                'user_role' => $request->user()->role,
                'required_roles' => $roles,
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès interdit. Permissions insuffisantes.',
                ], 403);
            }

            abort(403, 'Accès interdit. Vous n\'avez pas les permissions nécessaires.');
        }

        return $next($request);
    }
}
