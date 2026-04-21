<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Photo;
use App\Services\PhotoAnalysisService;
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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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

        // ── Analyse Google Cloud Vision (SafeSearch + Labels + Qualité + Hash) ──
        $analysisData = ['is_optimized' => true];

        try {
            $analyzer = app(PhotoAnalysisService::class);

            if ($analyzer->isAvailable()) {
                // Lire l'image optimisée pour l'analyse
                $optimizedContent = $disk->get($this->photo->path);

                if ($optimizedContent) {
                    $analysis = $analyzer->analyzeResidencePhoto($optimizedContent);

                    $analysisData = array_merge($analysisData, [
                        'tags'              => $analysis['tags'] ?: null,
                        'room_type'         => $analysis['room_type'],
                        'moderation_status' => $analysis['moderation']['status'],
                        'moderation_reason' => $analysis['moderation']['reason'],
                        'quality_score'     => $analysis['quality']['score'] ?? null,
                        'quality_issues'    => !empty($analysis['quality']['issues']) ? $analysis['quality']['issues'] : null,
                        'safe_search_data'  => !empty($analysis['safe_search']) ? $analysis['safe_search'] : null,
                        'labels_data'       => !empty($analysis['labels']) ? $analysis['labels'] : null,
                        'image_hash'        => $analysis['image_hash'],
                        'is_property_photo' => $analysis['is_property_photo'],
                    ]);

                    // Chercher les doublons
                    if ($analysis['image_hash']) {
                        $duplicates = $analyzer->findDuplicates(
                            $analysis['image_hash'],
                            $this->photo->id,
                            5,  // threshold : distance Hamming max
                        );

                        if (!empty($duplicates)) {
                            $dupInfo = collect($duplicates)->map(fn ($d) => "photo #{$d['photo_id']} (résidence #{$d['residence_id']}, distance {$d['distance']})")->implode(', ');

                            Log::warning("PhotoAnalysis: Doublons potentiels détectés pour photo #{$this->photo->id}", [
                                'duplicates' => $duplicates,
                            ]);

                            // Si doublon est d'une AUTRE résidence → signaler
                            $crossResidenceDuplicates = array_filter($duplicates, fn ($d) => $d['residence_id'] !== $this->photo->residence_id);

                            if (!empty($crossResidenceDuplicates)) {
                                $analysisData['moderation_status'] = 'review';
                                $analysisData['moderation_reason'] = 'Photo similaire détectée sur une autre annonce ('.$dupInfo.')';
                            }
                        }
                    }

                    // Si contenu rejeté, désactiver la photo comme primaire
                    if ($analysis['moderation']['status'] === 'rejected' && $this->photo->is_primary) {
                        $this->photo->update(['is_primary' => false]);

                        // Promouvoir une autre photo comme primaire
                        $nextPhoto = Photo::where('residence_id', $this->photo->residence_id)
                            ->where('id', '!=', $this->photo->id)
                            ->whereIn('moderation_status', ['approved', 'pending', 'skipped'])
                            ->orderBy('order')
                            ->first();

                        if ($nextPhoto) {
                            $nextPhoto->update(['is_primary' => true]);
                        }
                    }

                    Log::info("PhotoAnalysis: Photo #{$this->photo->id} analysée", [
                        'room_type'  => $analysis['room_type'],
                        'moderation' => $analysis['moderation']['status'],
                        'quality'    => $analysis['quality']['score'] ?? 0,
                        'tags'       => $analysis['tags'],
                        'is_property' => $analysis['is_property_photo'],
                    ]);
                }
            } else {
                $analysisData['moderation_status'] = 'skipped';
            }
        } catch (\Exception $e) {
            Log::error("PhotoAnalysis: Erreur analyse photo #{$this->photo->id}", [
                'error' => $e->getMessage(),
            ]);
            $analysisData['moderation_status'] = 'skipped';
        }

        // Marquer comme optimisé + sauvegarder l'analyse
        $this->photo->update($analysisData);

        Log::info("OptimizeResidencePhoto: optimisé — {$this->photo->path} ({$width}x{$height} → quality {$quality}, +WebP, +thumb)");
    }
}
