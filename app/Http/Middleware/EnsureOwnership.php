<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour vérifier que l'utilisateur est propriétaire de la ressource
 *
 * @usage middleware('ensure.owner:residence') ou middleware('ensure.owner:photo')
 */
class EnsureOwnership
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $resourceType Type de ressource (residence, photo, contact)
     */
    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request, 'Authentification requise.');
        }

        // Les admins ont accès à tout
        if ($user->isAdmin()) {
            return $next($request);
        }

        $resource = $this->getResource($request, $resourceType);

        if (!$resource) {
            return $this->notFound($request, 'Ressource introuvable.');
        }

        // Vérifier la propriété selon le type de ressource
        if (!$this->isOwner($user, $resource, $resourceType)) {
            Log::warning('Tentative d\'accès à une ressource non possédée', [
                'user_id' => $user->id,
                'resource_type' => $resourceType,
                'resource_id' => $resource->id ?? null,
                'ip' => $request->ip(),
            ]);

            return $this->forbidden($request, 'Vous n\'êtes pas autorisé à accéder à cette ressource.');
        }

        return $next($request);
    }

    /**
     * Récupérer la ressource depuis la requête
     */
    private function getResource(Request $request, string $resourceType): mixed
    {
        return match ($resourceType) {
            'residence' => $request->route('residence'),
            'photo' => $request->route('photo'),
            'contact' => $request->route('contact'),
            default => null,
        };
    }

    /**
     * Vérifier si l'utilisateur est propriétaire de la ressource
     */
    private function isOwner($user, $resource, string $resourceType): bool
    {
        return match ($resourceType) {
            'residence' => (int) $resource->owner_id === (int) $user->id,
            'photo' => (int) $resource->residence->owner_id === (int) $user->id,
            'contact' => (int) $resource->owner_id === (int) $user->id || (int) $resource->user_id === (int) $user->id,
            default => false,
        };
    }

    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 401);
        }

        return redirect()->route('login')->with('error', $message);
    }

    private function forbidden(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }
        abort(403, $message);
    }

    private function notFound(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 404);
        }
        abort(404, $message);
    }
}
