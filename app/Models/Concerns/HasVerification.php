<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasVerification
{
    public function getNextVerificationLevel(): string
    {
        $points = 0;

        if ($this->email_verified_at || $this->email_verified) {
            $points += 10;
        }
        if ($this->phone_verified) {
            $points += 20;
        }
        $points += 40; // +40 pour identité (sera true après approve)

        if ($this->profile_photo || $this->avatar) {
            $points += 5;
        }
        if ($this->created_at && $this->created_at->isBefore(now()->subMonths(6))) {
            $points += 10;
        }
        if ($this->reviews()->where('rating', '>=', 4)->count() >= 3) {
            $points += 15;
        }

        $points = min($points, 100);

        return match (true) {
            $points >= 80 => 'trusted',
            $points >= 60 => 'premium',
            $points >= 40 => 'standard',
            $points >= 20 => 'basic',
            default       => 'none',
        };
    }

    public function isIdentityVerified(): bool
    {
        return (bool) $this->identity_verified;
    }

    public function isPhoneVerified(): bool
    {
        return (bool) $this->phone_verified;
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null || (bool) $this->email_verified;
    }

    public function isFullyVerified(): bool
    {
        return $this->isIdentityVerified()
            && $this->isPhoneVerified()
            && $this->isEmailVerified();
    }

    public function isSuspended(): bool
    {
        if (! $this->is_suspended) {
            return false;
        }

        if ($this->suspended_until && $this->suspended_until->isPast()) {
            $this->update([
                'is_suspended'      => false,
                'suspended_until'   => null,
                'suspension_reason' => null,
            ]);

            return false;
        }

        return true;
    }
}
