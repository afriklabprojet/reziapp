<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\OptimizeResidencePhoto;
use App\Models\Photo;
use App\Models\Residence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service d'upload et gestion des photos
 *
 * Upload immédiat + optimisation en arrière-plan via queue
 */
class PhotoUploadService
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Upload multiple photos pour une résidence
     *
     * @param Residence $residence
     * @param array<UploadedFile> $photos
     * @return array<Photo>
     */
    public function uploadMultiple(Residence $residence, array $photos): array
    {
        $uploadedPhotos = [];
        $order = $residence->photos()->count();

        foreach ($photos as $photo) {
            if ($photo instanceof UploadedFile) {
                $uploadedPhotos[] = $this->upload($residence, $photo, $order++);
            }
        }

        // Si c'est la première photo, la marquer comme primaire
        if ($residence->photos()->count() === count($uploadedPhotos)) {
            $uploadedPhotos[0]->update(['is_primary' => true]);
        }

        return $uploadedPhotos;
    }

    /**
     * Upload une photo
     *
     * @param Residence $residence
     * @param UploadedFile $file
     * @param int $order
     * @return Photo
     * @throws \Exception
     */
    public function upload(Residence $residence, UploadedFile $file, int $order = 0): Photo
    {
        $this->validate($file);

        // Générer un nom unique
        $filename = $this->generateFilename($file);
        $path = "residences/{$residence->id}/{$filename}";

        // Stocker le fichier original immédiatement (rapide)
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        // Créer l'enregistrement en base
        $photo = Photo::create([
            'residence_id' => $residence->id,
            'path' => $path,
            'order' => $order,
            'is_primary' => false,
        ]);

        // Dispatcher l'optimisation en arrière-plan
        OptimizeResidencePhoto::dispatch($photo);

        return $photo;
    }

    /**
     * Définit une photo comme primaire
     *
     * @param Photo $photo
     */
    public function setPrimary(Photo $photo): void
    {
        // Retirer le statut primaire des autres photos
        Photo::where('residence_id', $photo->residence_id)
            ->update(['is_primary' => false]);

        // Définir cette photo comme primaire
        $photo->update(['is_primary' => true]);
    }

    /**
     * Supprime une photo
     *
     * @param Photo $photo
     */
    public function delete(Photo $photo): void
    {
        // Supprimer le fichier physique
        if (Storage::disk('public')->exists($photo->path)) {
            Storage::disk('public')->delete($photo->path);
        }

        // Supprimer l'enregistrement
        $photo->delete();
    }

    /**
     * Réordonne les photos d'une résidence
     *
     * @param Residence $residence
     * @param array $photoIds Tableau d'IDs dans le nouvel ordre
     */
    public function reorder(Residence $residence, array $photoIds): void
    {
        foreach ($photoIds as $order => $photoId) {
            Photo::where('id', $photoId)
                ->where('residence_id', $residence->id)
                ->update(['order' => $order]);
        }
    }

    /**
     * Valide le fichier uploadé
     *
     * @throws \Exception
     */
    private function validate(UploadedFile $file): void
    {
        // Vérifier l'extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            throw new \Exception('Extension non autorisée. Formats acceptés: '.implode(', ', self::ALLOWED_EXTENSIONS));
        }

        // Vérifier que c'est bien une image
        if (!$file->isValid() || !str_starts_with($file->getMimeType(), 'image/')) {
            throw new \Exception('Le fichier doit être une image valide');
        }

        // Vérifier la taille (configurable)
        $maxSizeMb = (int) config('rezi.max_photo_size_mb', 5);
        if ($file->getSize() > $maxSizeMb * 1024 * 1024) {
            throw new \Exception("La taille du fichier ne doit pas dépasser {$maxSizeMb}MB");
        }
    }

    /**
     * Génère un nom de fichier unique
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid().'.'.$extension;
    }
}
