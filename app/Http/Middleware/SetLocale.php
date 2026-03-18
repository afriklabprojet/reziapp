<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['fr', 'en'];

    /**
     * Default locale
     */
    protected string $defaultLocale = 'fr';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        
        App::setLocale($locale);
        
        // Store in session for future requests
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale from various sources
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check URL parameter (highest priority)
        if ($request->has('lang') && $this->isSupported($request->get('lang'))) {
            return $request->get('lang');
        }

        // 2. Check route prefix (for /en/... or /fr/... URLs)
        $segment = $request->segment(1);
        if ($segment && $this->isSupported($segment)) {
            return $segment;
        }

        // 3. Check session
        if (Session::has('locale') && $this->isSupported(Session::get('locale'))) {
            return Session::get('locale');
        }

        // 4. Check authenticated user preference
        if ($request->user() && $request->user()->preferred_language && $this->isSupported($request->user()->preferred_language)) {
            return $request->user()->preferred_language;
        }

        // 5. Check browser Accept-Language header
        $browserLocale = $this->getPreferredBrowserLocale($request);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 6. Fall back to default
        return $this->defaultLocale;
    }

    /**
     * Check if a locale is supported
     */
    protected function isSupported(?string $locale): bool
    {
        return $locale && in_array($locale, $this->supportedLocales);
    }

    /**
     * Get preferred locale from browser Accept-Language header
     */
    protected function getPreferredBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $locales = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $parts = explode(';', $part);
            $locale = strtolower(trim($parts[0]));
            $quality = isset($parts[1]) ? (float) str_replace('q=', '', $parts[1]) : 1.0;
            
            // Extract primary language tag (e.g., 'fr' from 'fr-FR')
            $primaryLocale = explode('-', $locale)[0];
            
            if ($this->isSupported($primaryLocale)) {
                $locales[$primaryLocale] = $quality;
            }
        }

        if (empty($locales)) {
            return null;
        }

        // Sort by quality and return the best match
        arsort($locales);
        return array_key_first($locales);
    }
}
