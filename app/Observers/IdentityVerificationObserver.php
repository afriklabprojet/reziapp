<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\IdentityVerification;

class IdentityVerificationObserver
{
    /**
     * Synchronise identity_verified sur l'utilisateur
     * lorsque le status change (notamment via EditAction Filament).
     */
    public function updated(IdentityVerification $verification): void
    {
        if (! $verification->wasChanged('status')) {
            return;
        }

        $user = $verification->user;

        if ($verification->status === 'approved' && ! $user->identity_verified) {
            $user->update([
                'identity_verified'    => true,
                'identity_verified_at' => $verification->reviewed_at ?? now(),
            ]);
        }

        if (in_array($verification->status, ['rejected', 'expired'], strict: true) && $user->identity_verified) {
            // Vérifier qu'il n'existe pas une autre vérification approuvée
            $hasOtherApproved = $user->identityVerifications()
                ->where('id', '!=', $verification->id)
                ->where('status', 'approved')
                ->exists();

            if (! $hasOtherApproved) {
                $user->update([
                    'identity_verified'    => false,
                    'identity_verified_at' => null,
                ]);
            }
        }
    }
}
