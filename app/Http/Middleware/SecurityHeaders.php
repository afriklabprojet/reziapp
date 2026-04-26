<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour ajouter des headers de sécurité
 *
 * Protection contre XSS, Clickjacking, MIME sniffing, CSP, etc.
 */
class SecurityHeaders
{
    /**
     * Headers de sécurité fixes à ajouter à chaque réponse.
     */
    private array $securityHeaders = [
        // Protection XSS legacy
        'X-XSS-Protection' => '1; mode=block',

        // Empêcher le MIME sniffing
        'X-Content-Type-Options' => 'nosniff',

        // Protection Clickjacking
        'X-Frame-Options' => 'SAMEORIGIN',

        // Referrer Policy
        'Referrer-Policy' => 'strict-origin-when-cross-origin',

        // Permissions Policy (désactiver caméra/micro non nécessaires)
        // Note: camera=(self) est surchargé sur les pages de vérification d'identité
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(self)',
    ];

    /**
     * Content Security Policy.
     *
     * NOTE : unsafe-inline et unsafe-eval sont requis pour Livewire, Alpine.js et Filament.
     * À remplacer progressivement par des nonces CSP pour renforcer la protection.
     *
     * En développement, le serveur Vite HMR (port 4000 par défaut) est
     * autorisé sur toutes ses variantes d'adresse locale.
     */
    private function buildCsp(bool $isProduction): string
    {
        // Polices externes utilisées par le projet
        $externalFontHosts = 'https://fonts.bunny.net';

        // CDN Mapbox (carte interactive)
        $mapboxCdn = 'https://api.mapbox.com';
        // Google Maps JS API + Places (autocomplétion adresses)
        $googleMaps = 'https://maps.googleapis.com https://maps.gstatic.com';
        // Google Fonts (DM Serif Display + Outfit)
        $googleFonts = 'https://fonts.googleapis.com';
        // Microsoft Clarity (analytics) — scripts chargés depuis scripts.clarity.ms
        $clarity = 'https://www.clarity.ms https://scripts.clarity.ms';

        $directives = [
            "default-src 'self'",
            // Alpine.js, Livewire et Filament nécessitent inline/eval
            // Mapbox GL JS + Google Maps Places API + Microsoft Clarity
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$mapboxCdn} {$googleMaps} {$clarity}",
            // Tailwind v4 injecte des styles inline ; fonts.bunny.net pour Figtree
            // Mapbox GL CSS + Google Fonts
            "style-src 'self' 'unsafe-inline' {$externalFontHosts} {$mapboxCdn} {$googleFonts}",
            // Images locales + data URIs (avatars base64) + CDN externe (photos résidences)
            "img-src 'self' data: blob: https:",
            // Polices locales, data URIs et fonts.bunny.net + Google Fonts
            "font-src 'self' data: {$externalFontHosts} https://fonts.gstatic.com",
            // WebSockets Pusher + API Jeko + Mapbox + Google APIs + Microsoft Clarity collect
            "connect-src 'self' wss://*.pusher.com https://*.pusher.com https://*.pusherapp.com https://soketi.app https://api.jeko.africa https://api.mapbox.com https://events.mapbox.com https://maps.googleapis.com https://maps.gstatic.com https://*.clarity.ms",
            // Aucun embed/iframe autorisé
            "frame-src 'none'",
            // Bloquer les plugins (Flash, etc.)
            "object-src 'none'",
            // Limiter les URLs de base aux ressources locales
            "base-uri 'self'",
            // Limiter l'envoi de formulaires au domaine courant + passerelle Jeko
            // pay.jeko.africa est nécessaire car Chrome (90+) vérifie toute la chaîne
            // de navigation : le POST arrive sur reziapp.ci puis Laravel fait un
            // redirect()->away('https://pay.jeko.africa/...') — Chrome bloque ce
            // redirect si le domaine n'est pas explicitement autorisé ici.
            "form-action 'self' https://reziapp.ci https://pay.jeko.africa",
            // Workers uniquement locaux
            "worker-src 'self' blob:",
            // Manifeste PWA
            "manifest-src 'self'",
        ];

        if (! $isProduction) {
            // Serveur Vite HMR — on cible uniquement 127.0.0.1 (IPv4).
            // Les adresses IPv6 ([::1]) sont des sources CSP invalides dans
            // Chrome et Safari et sont ignorées ; Vite est configuré dans
            // vite.config.js pour écouter sur 127.0.0.1 et non [::1].
            $vitePort = config('app.vite_port', env('VITE_PORT', 5173));
            $viteHosts = implode(' ', [
                "http://localhost:{$vitePort}",
                "http://127.0.0.1:{$vitePort}",
                // Port alternatif utilisé par certaines configs laravel-vite-plugin
                'http://localhost:4000',
                'http://127.0.0.1:4000',
            ]);

            foreach ($directives as $i => $d) {
                if (str_starts_with($d, 'script-src ')) {
                    $directives[$i] = $d.' '.$viteHosts;
                } elseif (str_starts_with($d, 'style-src ')) {
                    $directives[$i] = $d.' '.$viteHosts;
                } elseif (str_starts_with($d, 'connect-src ')) {
                    // WebSocket HMR Vite (ws://) — uniquement IPv4
                    $wsHosts = implode(' ', [
                        "ws://localhost:{$vitePort}",
                        "ws://127.0.0.1:{$vitePort}",
                        'ws://localhost:4000',
                        'ws://127.0.0.1:4000',
                    ]);
                    $directives[$i] = $d.' '.$viteHosts.' '.$wsHosts;
                }
            }
        }

        if ($isProduction) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isProduction = app()->environment('production');

        // Headers fixes
        foreach ($this->securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // La page selfie nécessite l'accès caméra — on lève la restriction
        if ($request->is('verification/identity/selfie')) {
            $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(self)');
        }

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', $this->buildCsp($isProduction));

        // HSTS (seulement en production avec HTTPS)
        if ($isProduction && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload',
            );
        }

        return $response;
    }
}
