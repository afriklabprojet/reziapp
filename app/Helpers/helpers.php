<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('csp_nonce')) {
    /**
     * Retourne la nonce CSP de la requête courante.
     * Usage Blade : <script nonce="{{ csp_nonce() }}">
     */
    function csp_nonce(): string
    {
        return app()->has('csp-nonce') ? app('csp-nonce') : '';
    }
}

if (!function_exists('storage_url')) {
    /**
     * Get the URL for a storage path.
     * If the path is already a full URL (http/https), return it as-is.
     * Otherwise, use Storage::url() to generate the URL.
     */
    function storage_url(?string $path): string
    {
        static $resolvedUrls = [];

        if (empty($path)) {
            return '';
        }

        if (array_key_exists($path, $resolvedUrls)) {
            return $resolvedUrls[$path];
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $resolvedUrls[$path] = $path;
        }

        $normalizedPath = ltrim($path, '/');

        if (str_starts_with($normalizedPath, 'storage/')) {
            $normalizedPath = substr($normalizedPath, strlen('storage/'));
        }

        if (Storage::disk('public')->exists($normalizedPath)) {
            $publicBaseUrl = rtrim((string) config('filesystems.disks.public.url', asset('storage')), '/');

            return $resolvedUrls[$path] = $publicBaseUrl.'/'.$normalizedPath;
        }

        if (file_exists(public_path($normalizedPath))) {
            return $resolvedUrls[$path] = asset($normalizedPath);
        }

        return $resolvedUrls[$path] = asset('images/placeholder-residence.jpg');
    }
}
