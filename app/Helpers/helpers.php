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

if (! function_exists('alpine_encode')) {
    /**
     * Encode data as JSON safe for Alpine.js CSP x-data attributes.
     *
     * Uses JSON_UNESCAPED_UNICODE so accented characters (ô, é, etc.) stay as
     * real UTF-8 bytes instead of \uXXXX sequences. Blade's {{ }} will then
     * HTML-encode the quotes (" → &quot;) which is correct for HTML attributes,
     * and Alpine's CSP parser will decode &quot; back to " when it reads the
     * attribute. Accented chars pass through unchanged in both directions.
     */
    function alpine_encode(mixed $data): string
    {
        if ($data instanceof \Illuminate\Contracts\Support\Jsonable) {
            $json = $data->toJson(JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
        } elseif ($data instanceof \Illuminate\Contracts\Support\Arrayable && ! ($data instanceof \JsonSerializable)) {
            $json = json_encode($data->toArray(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
        } else {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
        }

        return $json;
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
