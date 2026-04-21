<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class OnboardingService
{
    public const STEPS = [
        'profile_completed'    => ['label' => 'Profil complété', 'description' => 'Photo, bio et coordonnées'],
        'identity_verified'    => ['label' => 'Identité vérifiée', 'description' => 'Document d\'identité et selfie'],
        'first_listing'        => ['label' => 'Première annonce', 'description' => 'Publiez votre première résidence'],
        'pricing_configured'   => ['label' => 'Prix configurés', 'description' => 'Tarifs journaliers ou mensuels'],
        'instant_book_enabled' => ['label' => 'Réservation instantanée', 'description' => 'Activez la réservation sans approbation'],
        'guidebook_created'    => ['label' => 'Guide de bienvenue', 'description' => 'Créez un guide pour vos voyageurs'],
    ];

    /**
     * Obtenir la progression d'onboarding d'un propriétaire
     */
    public function getProgress(User $owner): array
    {
        $steps = $owner->onboarding_steps ?? [];
        $completed = 0;
        $total = count(self::STEPS);

        $result = [];
        foreach (self::STEPS as $key => $meta) {
            $isCompleted = $steps[$key] ?? false;

            // Auto-vérification
            if (!$isCompleted) {
                $isCompleted = $this->checkStep($owner, $key);
                if ($isCompleted) {
                    $steps[$key] = true;
                }
            }

            if ($isCompleted) {
                $completed++;
            }

            $result[] = [
                'key'         => $key,
                'label'       => $meta['label'],
                'description' => $meta['description'],
                'completed'   => $isCompleted,
            ];
        }

        // Sauvegarder la progression
        $owner->update([
            'onboarding_steps'     => $steps,
            'onboarding_completed' => $completed >= $total,
        ]);

        return [
            'steps'      => $result,
            'completed'  => $completed,
            'total'      => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'is_done'    => $completed >= $total,
        ];
    }

    /**
     * Vérifier automatiquement si un step est complété
     */
    private function checkStep(User $owner, string $stepKey): bool
    {
        return match ($stepKey) {
            'profile_completed'    => $this->isProfileCompleted($owner),
            'identity_verified'    => $this->isIdentityVerified($owner),
            'first_listing'        => $this->hasFirstListing($owner),
            'pricing_configured'   => $this->isPricingConfigured($owner),
            'instant_book_enabled' => $this->hasInstantBook($owner),
            'guidebook_created'    => $this->hasGuidebook($owner),
            default                => false,
        };
    }

    private function isProfileCompleted(User $owner): bool
    {
        return $owner->name
            && $owner->email
            && ($owner->profile_photo || $owner->avatar)
            && $owner->phone;
    }

    private function isIdentityVerified(User $owner): bool
    {
        return \App\Models\IdentityVerification::where('user_id', $owner->id)
            ->where('status', 'approved')
            ->exists();
    }

    private function hasFirstListing(User $owner): bool
    {
        return $owner->residences()->exists();
    }

    private function isPricingConfigured(User $owner): bool
    {
        return $owner->residences()
            ->where(function ($q) {
                $q->whereNotNull('price_per_day')
                    ->orWhereNotNull('price_per_month');
            })
            ->exists();
    }

    private function hasInstantBook(User $owner): bool
    {
        return $owner->residences()->where('instant_book', true)->exists();
    }

    private function hasGuidebook(User $owner): bool
    {
        return \App\Models\Guidebook::where('user_id', $owner->id)->exists();
    }

    /**
     * Marquer un step comme complété manuellement
     */
    public function markStepCompleted(User $owner, string $stepKey): void
    {
        $steps = $owner->onboarding_steps ?? [];
        $steps[$stepKey] = true;
        $owner->update(['onboarding_steps' => $steps]);
    }
}
