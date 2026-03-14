<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\UserLocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de détection de localisation (style Airbnb)
 *
 * Résout la localisation de l'utilisateur et la partage
 * avec toutes les vues Blade via View::share.
 *
 * Ordre de priorité :
 * 1. Session existante (déjà détectée ou choisie manuellement)
 * 2. Fallback → localisation par défaut (CI / Abidjan)
 *
 * La détection GPS se fait côté client via JS + API /api/v1/locations/detect
 */
class DetectLocation
{
    public function handle(Request $request, Closure $next): Response
    {
        $location = UserLocationService::current();

        // Partager avec toutes les vues
        View::share('userLocation', $location);
        View::share('availableLocations', UserLocationService::availableLocations());

        return $next($request);
    }
}
