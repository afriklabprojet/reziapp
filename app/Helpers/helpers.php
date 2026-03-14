<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_url')) {
    /**
     * Get the URL for a storage path.
     * If the path is already a full URL (http/https), return it as-is.
     * Otherwise, use Storage::url() to generate the URL.
     */
    function storage_url(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::url($path);
    }
}
