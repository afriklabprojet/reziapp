<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

trait HasWithdrawalPin
{
    public function hasWithdrawalPin(): bool
    {
        return ! empty($this->withdrawal_pin);
    }

    public function setWithdrawalPin(string $pin): void
    {
        $this->update([
            'withdrawal_pin'             => Hash::make($pin),
            'withdrawal_pin_set_at'      => now(),
            'withdrawal_pin_attempts'    => 0,
            'withdrawal_pin_locked_until' => null,
        ]);
    }

    public function verifyWithdrawalPin(string $pin): bool
    {
        if ($this->isWithdrawalPinLocked()) {
            return false;
        }

        if (Hash::check($pin, $this->withdrawal_pin)) {
            $this->update([
                'withdrawal_pin_attempts'    => 0,
                'withdrawal_pin_locked_until' => null,
            ]);

            return true;
        }

        $attempts = $this->withdrawal_pin_attempts + 1;
        $data = ['withdrawal_pin_attempts' => $attempts];

        if ($attempts >= 5) {
            $data['withdrawal_pin_locked_until'] = now()->addMinutes(30);
            Log::warning('Withdrawal PIN locked', [
                'user_id'      => $this->id,
                'attempts'     => $attempts,
                'locked_until' => $data['withdrawal_pin_locked_until'],
                'ip'           => request()->ip(),
            ]);
        }

        $this->update($data);

        return false;
    }

    public function isWithdrawalPinLocked(): bool
    {
        if (! $this->withdrawal_pin_locked_until) {
            return false;
        }

        if ($this->withdrawal_pin_locked_until->isPast()) {
            $this->update([
                'withdrawal_pin_attempts'    => 0,
                'withdrawal_pin_locked_until' => null,
            ]);

            return false;
        }

        return true;
    }

    public function withdrawalPinLockRemainingMinutes(): int
    {
        if (! $this->withdrawal_pin_locked_until || $this->withdrawal_pin_locked_until->isPast()) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->withdrawal_pin_locked_until, false);
    }
}
