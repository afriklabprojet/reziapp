<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour ajouter des headers de sécurité
 *
 * Protection contre XSS, Clickjacking, MIME sniffing, etc.
 */
class SecurityHeaders
{
    /**
     * Headers de sécurité à ajouter
     */
    private array $securityHeaders = [
        // Protection XSS
        'X-XSS-Protection' => '1; mode=block',

        // Empêcher le MIME sniffing
        'X-Content-Type-Options' => 'nosniff',

        // Protection Clickjacking
        'X-Frame-Options' => 'SAMEORIGIN',

        // Referrer Policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Permissions Policy (désactiver caméra/micro non nécessaires)
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self)',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ajouter les headers de sécurité
        foreach ($this->securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // HSTS (seulement en production avec HTTPS)
        if (app()->environment('production') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }
}
