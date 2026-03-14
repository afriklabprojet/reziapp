<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Photo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Optimise une photo de résidence en arrière-plan
 *
 * Redimensionne et compresse l'image après upload pour ne pas
 * bloquer la requête HTTP de l'utilisateur.
 */
class OptimizeResidencePhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives max
     */
    public int $tries = 3;

    /**
     * Délai entre les tentatives (secondes)
     */
    public int $backoff = 30;

    public function __construct(
        protected Photo $photo,
    ) {
    }

    public function handle(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($this->photo->path)) {
            Log::warning("OptimizeResidencePhoto: fichier introuvable — {$this->photo->path}");

            return;
        }

        $maxWidth = (int) config('rezi.photo_processing.max_width', 1920);
        $maxHeight = (int) config('rezi.photo_processing.max_height', 1080);
        $quality = (int) config('rezi.photo_processing.quality', 85);
        $thumbWidth = (int) config('rezi.photo_processing.thumb_width', 400);
        $thumbHeight = (int) config('rezi.photo_processing.thumb_height', 300);

        $contents = $disk->get($this->photo->path);
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            Log::warning("OptimizeResidencePhoto: image illisible — {$this->photo->path}");

            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Redimensionner seulement si nécessaire
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $optimized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($optimized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        } else {
            $optimized = $image;
            $image = null; // éviter double destroy
        }

        // Compresser en JPEG (fichier original)
        ob_start();
        imagejpeg($optimized, null, $quality);
        $jpegOutput = ob_get_clean();
        $disk->put($this->photo->path, $jpegOutput);

        // ── Générer une version WebP ──
        $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $this->photo->path);
        if ($webpPath !== $this->photo->path && function_exists('imagewebp')) {
            ob_start();
            imagewebp($optimized, null, $quality);
            $webpOutput = ob_get_clean();
            $disk->put($webpPath, $webpOutput);
        }

        // ── Générer une miniature (thumbnail) ──
        $thumbPath = preg_replace('/(\.[a-z]+)$/i', '_thumb$1', $this->photo->path);
        $thumbRatio = min($thumbWidth / imagesx($optimized), $thumbHeight / imagesy($optimized));
        $tw = (int) (imagesx($optimized) * $thumbRatio);
        $th = (int) (imagesy($optimized) * $thumbRatio);

        $thumbnail = imagecreatetruecolor($tw, $th);
        imagecopyresampled($thumbnail, $optimized, 0, 0, 0, 0, $tw, $th, imagesx($optimized), imagesy($optimized));

        ob_start();
        imagejpeg($thumbnail, null, 75);
        $thumbOutput = ob_get_clean();
        $disk->put($thumbPath, $thumbOutput);
        imagedestroy($thumbnail);

        // Miniature WebP
        $thumbWebpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $thumbPath);
        if ($thumbWebpPath !== $thumbPath && function_exists('imagewebp')) {
            $thumbForWebp = imagecreatetruecolor($tw, $th);
            imagecopyresampled($thumbForWebp, $optimized, 0, 0, 0, 0, $tw, $th, imagesx($optimized), imagesy($optimized));
            ob_start();
            imagewebp($thumbForWebp, null, 75);
            $thumbWebpOutput = ob_get_clean();
            $disk->put($thumbWebpPath, $thumbWebpOutput);
            imagedestroy($thumbForWebp);
        }

        imagedestroy($optimized);
        if ($image) {
            imagedestroy($image);
        }

        // Marquer comme optimisé
        $this->photo->update(['is_optimized' => true]);

        Log::info("OptimizeResidencePhoto: optimisé — {$this->photo->path} ({$width}x{$height} → quality {$quality}, +WebP, +thumb)");
    }
}
