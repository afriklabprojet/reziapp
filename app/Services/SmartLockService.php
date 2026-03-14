<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SmartLock;
use App\Models\SmartLockCode;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SmartLockService
{
    /**
     * Générer un code temporaire pour une réservation
     */
    public function generateCodeForBooking(Booking $booking): ?SmartLockCode
    {
        $lock = SmartLock::where('residence_id', $booking->residence_id)
            ->active()
            ->first();

        if (!$lock) {
            return null;
        }

        // Vérifier si un code existe déjà pour ce booking
        $existing = SmartLockCode::where('smart_lock_id', $lock->id)
            ->where('booking_id', $booking->id)
            ->where('status', SmartLockCode::STATUS_ACTIVE)
            ->first();

        if ($existing) {
            return $existing;
        }

        $code = $this->generateUniqueCode();

        return SmartLockCode::create([
            'smart_lock_id' => $lock->id,
            'booking_id'    => $booking->id,
            'code'          => $code,
            'code_type'     => SmartLockCode::TYPE_TEMPORARY,
            'status'        => SmartLockCode::STATUS_ACTIVE,
            'valid_from'    => Carbon::parse($booking->check_in)->startOfDay(),
            'valid_until'   => Carbon::parse($booking->check_out)->endOfDay(),
            'guest_name'    => $booking->user->name ?? 'Voyageur',
        ]);
    }

    /**
     * Révoquer les codes d'une réservation
     */
    public function revokeCodesForBooking(int $bookingId): int
    {
        return SmartLockCode::where('booking_id', $bookingId)
            ->where('status', SmartLockCode::STATUS_ACTIVE)
            ->update(['status' => SmartLockCode::STATUS_REVOKED]);
    }

    /**
     * Expirer les codes dont la validité a expiré
     */
    public function expireOldCodes(): int
    {
        return SmartLockCode::where('status', SmartLockCode::STATUS_ACTIVE)
            ->where('valid_until', '<', now())
            ->update(['status' => SmartLockCode::STATUS_EXPIRED]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (
            SmartLockCode::where('code', $code)
                ->where('status', SmartLockCode::STATUS_ACTIVE)
                ->exists()
        );

        return $code;
    }
}
