<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\Cache;

/**
 * Observer pour invalider le cache home_testimonials
 * quand un avis est approuvé, modifié ou supprimé.
 */
class ReviewObserver
{
    /**
     * Quand un avis est mis à jour (ex: statut → 'approved' via Filament).
     * On invalide uniquement si le statut change pour éviter les
     * invalidations inutiles lors de la mise à jour d'autres champs.
     */
    public function updated(Review $review): void
    {
        if ($review->isDirty('status')) {
            Cache::forget('home_testimonials');
        }
    }

    /**
     * Quand un avis est supprimé (modération, suppression admin).
     */
    public function deleted(Review $review): void
    {
        Cache::forget('home_testimonials');
    }

    /**
     * Quand un avis est restauré (soft delete).
     */
    public function restored(Review $review): void
    {
        Cache::forget('home_testimonials');
    }
}
