<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CloudinaryService
{
    protected ?string $cloudName;
    protected ?string $apiKey;
    protected ?string $apiSecret;
    protected bool $enabled;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
        $this->enabled = !empty($this->cloudName) && !empty($this->apiKey) && !empty($this->apiSecret);
    }

    /**
     * Check if Cloudinary is configured
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Upload an image to Cloudinary
     */
    public function upload(UploadedFile $file, string $folder = 'residences'): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $timestamp = time();
            $params = [
                'folder' => "rezi/{$folder}",
                'timestamp' => $timestamp,
                'transformation' => 'q_auto,f_auto',
            ];

            $signature = $this->generateSignature($params);

            $response = Http::asMultipart()
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", [
                    'api_key' => $this->apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'folder' => $params['folder'],
                    'transformation' => $params['transformation'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'public_id' => $data['public_id'],
                    'url' => $data['secure_url'],
                    'width' => $data['width'],
                    'height' => $data['height'],
                    'format' => $data['format'],
                    'bytes' => $data['bytes'],
                ];
            }

            Log::error('Cloudinary upload failed', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('Cloudinary upload error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Delete an image from Cloudinary
     */
    public function delete(string $publicId): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $timestamp = time();
            $params = [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
            ];

            $signature = $this->generateSignature($params);

            $response = Http::post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/destroy", [
                'api_key' => $this->apiKey,
                'timestamp' => $timestamp,
                'signature' => $signature,
                'public_id' => $publicId,
            ]);

            return $response->successful() && $response->json('result') === 'ok';

        } catch (\Exception $e) {
            Log::error('Cloudinary delete error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate optimized URL for an image
     */
    public function getOptimizedUrl(string $publicId, array $options = []): string
    {
        if (!$this->enabled) {
            return '';
        }

        $transformations = array_merge([
            'q' => 'auto',      // Auto quality
            'f' => 'auto',      // Auto format (WebP where supported)
            'dpr' => 'auto',    // Auto DPR
        ], $options);

        $transformStr = collect($transformations)
            ->map(fn($value, $key) => "{$key}_{$value}")
            ->implode(',');

        return "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$transformStr}/{$publicId}";
    }

    /**
     * Generate thumbnail URL
     */
    public function getThumbnail(string $publicId, int $width = 300, int $height = 200): string
    {
        return $this->getOptimizedUrl($publicId, [
            'w' => $width,
            'h' => $height,
            'c' => 'fill',
            'g' => 'auto',
        ]);
    }

    /**
     * Generate responsive image srcset
     */
    public function getSrcset(string $publicId, array $widths = [320, 640, 960, 1280, 1920]): string
    {
        if (!$this->enabled) {
            return '';
        }

        return collect($widths)
            ->map(fn($w) => $this->getOptimizedUrl($publicId, ['w' => $w]) . " {$w}w")
            ->implode(', ');
    }

    /**
     * Generate signature for API requests
     */
    protected function generateSignature(array $params): string
    {
        ksort($params);
        $signatureString = collect($params)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode('&');

        return sha1($signatureString . $this->apiSecret);
    }

    /**
     * Migrate local image to Cloudinary
     */
    public function migrateFromLocal(string $localPath, string $folder = 'residences'): ?array
    {
        if (!$this->enabled || !Storage::disk('public')->exists($localPath)) {
            return null;
        }

        try {
            $timestamp = time();
            $fullPath = Storage::disk('public')->path($localPath);
            
            $params = [
                'folder' => "rezi/{$folder}",
                'timestamp' => $timestamp,
            ];

            $signature = $this->generateSignature($params);

            $response = Http::asMultipart()
                ->attach('file', file_get_contents($fullPath), basename($localPath))
                ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/image/upload", [
                    'api_key' => $this->apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'folder' => $params['folder'],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Cloudinary migration error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
