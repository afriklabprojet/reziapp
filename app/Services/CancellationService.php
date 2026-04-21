<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\CancellationPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancellationService
{
    protected RefundService $refundService;

    public function __construct(RefundService $refundService)
    {
        $this->refundService = $refundService;
    }

    /**
     * Cancel booking by guest
     */
    public function cancelByGuest(Booking $booking, string $reason, ?string $detailedReason = null): Cancellation
    {
        return $this->processCancel($booking, 'user', $booking->user_id, $reason, $detailedReason);
    }

    /**
     * Cancel booking by owner
     */
    public function cancelByOwner(Booking $booking, string $reason, ?string $detailedReason = null): Cancellation
    {
        $ownerId = $booking->residence->owner_id;

        return $this->processCancel($booking, 'owner', $ownerId, $reason, $detailedReason);
    }

    /**
     * Cancel booking by admin
     */
    public function cancelByAdmin(Booking $booking, int $adminId, string $reason, ?string $adminNotes = null): Cancellation
    {
        return $this->processCancel($booking, 'admin', $adminId, $reason, $adminNotes);
    }

    /**
     * Cancel booking by system (automatic)
     */
    public function cancelBySystem(Booking $booking, string $reason): Cancellation
    {
        return $this->processCancel($booking, 'system', null, $reason, null);
    }

    /**
     * Process cancellation
     */
    protected function processCancel(
        Booking $booking,
        string $cancelledBy,
        ?int $cancelledByUserId,
        string $reason,
        ?string $detailedReason,
    ): Cancellation {
        // Verify booking can be cancelled
        if (!$booking->canBeCancelled()) {
            throw new \Exception('Cette réservation ne peut pas être annulée.');
        }

        // Get cancellation policy
        $policy = $booking->getCancellationPolicy() ?? CancellationPolicy::getDefault();

        // Calculate amounts
        $hoursUntilCheckin = $booking->hours_until_checkin;
        $refundAmount = 0;
        $penaltyAmount = 0;

        if ($policy) {
            if ($cancelledBy === 'owner') {
                // Owner cancellation: full refund to guest + penalty to owner
                $refundAmount = $booking->total_amount;
                $penaltyAmount = $policy->calculateOwnerPenalty($booking->total_amount, $hoursUntilCheckin);
            } elseif ($cancelledBy === 'admin') {
                // Admin can decide full or partial refund
                $refundAmount = $booking->total_amount;
            } else {
                // Guest cancellation: based on policy
                $refundAmount = $policy->calculateRefund($booking->total_amount, $hoursUntilCheckin);
            }
        }

        // Create cancellation in transaction
        return DB::transaction(function () use (
            $booking,
            $cancelledBy,
            $cancelledByUserId,
            $reason,
            $detailedReason,
            $policy,
            $refundAmount,
            $penaltyAmount
        ) {
            // Create cancellation record
            $cancellation = Cancellation::create([
                'booking_id' => $booking->id,
                'initiated_by' => $cancelledBy,
                'initiated_by_user_id' => $cancelledByUserId,
                'reason_category' => $reason,
                'reason_details' => $detailedReason,
                'days_before_checkin' => max(0, (int) ceil($booking->hours_until_checkin / 24)),
                'refund_percent_applied' => $policy ? $policy->getRefundPercentage($booking->hours_until_checkin) : 0,
                'original_amount' => $booking->total_amount,
                'refund_amount' => $refundAmount,
                'penalty_amount' => $penaltyAmount,
                'owner_penalty_applied' => $cancelledBy === 'owner' && $penaltyAmount > 0,
                'owner_penalty_amount' => $cancelledBy === 'owner' ? $penaltyAmount : 0,
                'status' => 'approved', // Auto-approve guest/owner cancellations
            ]);

            // Update booking status
            $booking->markCancelled();

            // Process automatic refund if amount > 0
            if ($refundAmount > 0 && $booking->payment_status === 'paid') {
                $this->refundService->createRefund($cancellation, $booking->user_id, $refundAmount);
            }

            // Log the cancellation
            Log::info('Booking cancelled', [
                'booking_id' => $booking->id,
                'cancellation_id' => $cancellation->id,
                'cancelled_by' => $cancelledBy,
                'refund_amount' => $refundAmount,
                'penalty_amount' => $penaltyAmount,
            ]);

            // Notifier le propriétaire (sauf si c'est lui qui annule)
            if ($cancelledBy !== 'owner' && $booking->residence?->owner) {
                $owner = $booking->residence->owner;
                $owner->notify(
                    new \App\Notifications\BookingCancelled(
                        $booking,
                        $booking->residence,
                        $cancelledBy === 'user' ? 'guest' : $cancelledBy,
                    ),
                );

                // Notification in-app
                \App\Models\Notification::send(
                    $owner,
                    'booking',
                    'Réservation annulée',
                    'La réservation pour '.$booking->residence->name.' a été annulée.',
                    route('owner.bookings.show', $booking),
                    ['booking_id' => $booking->id],
                );
            }

            return $cancellation;
        });
    }

    /**
     * Preview cancellation (without executing)
     */
    public function previewCancellation(Booking $booking, string $cancelledBy = 'user'): array
    {
        $policy = $booking->getCancellationPolicy() ?? CancellationPolicy::getDefault();
        $hoursUntilCheckin = $booking->hours_until_checkin;
        $daysUntilCheckin = $booking->days_until_checkin;

        $refundPercentage = 0;
        $refundAmount = 0;
        $penaltyAmount = 0;
        $nonRefundableAmount = 0;

        if ($policy) {
            if ($cancelledBy === 'owner') {
                $refundPercentage = 100;
                $refundAmount = $booking->total_amount;
                $penaltyAmount = $policy->calculateOwnerPenalty($booking->total_amount, $hoursUntilCheckin);
            } else {
                $refundPercentage = $policy->getRefundPercentage($hoursUntilCheckin);
                $refundAmount = $policy->calculateRefund($booking->total_amount, $hoursUntilCheckin);
                $nonRefundableAmount = $booking->total_amount - $refundAmount;
            }
        }

        return [
            'can_cancel' => $booking->canBeCancelled(),
            'booking' => [
                'id' => $booking->id,
                'check_in' => $booking->check_in->format('d/m/Y'),
                'check_out' => $booking->check_out->format('d/m/Y'),
                'total_amount' => $booking->total_amount,
                'currency' => $booking->currency ?? 'FCFA',
            ],
            'policy' => $policy ? [
                'code' => $policy->name,
                'name' => $policy->display_name ?? $policy->name,
                'description' => $policy->description,
                'formatted_description' => $policy->formatted_description,
            ] : null,
            'timing' => [
                'hours_until_checkin' => $hoursUntilCheckin,
                'days_until_checkin' => $daysUntilCheckin,
                'is_free_cancellation' => $policy && $policy->hasFreeCancellation($booking->check_in),
            ],
            'amounts' => [
                'refund_percentage' => $refundPercentage,
                'refund_amount' => $refundAmount,
                'non_refundable_amount' => $nonRefundableAmount,
                'penalty_amount' => $penaltyAmount,
                'formatted_refund' => number_format($refundAmount, 0, ',', ' ').' FCFA',
                'formatted_non_refundable' => number_format($nonRefundableAmount, 0, ',', ' ').' FCFA',
            ],
            'message' => $this->getCancellationMessage($refundPercentage, $daysUntilCheckin, $cancelledBy),
        ];
    }

    /**
     * Get human-readable cancellation message
     */
    protected function getCancellationMessage(int $refundPercentage, int $daysUntilCheckin, string $cancelledBy): string
    {
        if ($cancelledBy === 'owner') {
            return 'Le voyageur recevra un remboursement intégral. Une pénalité peut s\'appliquer selon le délai d\'annulation.';
        }

        if ($refundPercentage === 100) {
            return 'Annulation gratuite ! Vous recevrez un remboursement intégral.';
        }

        if ($refundPercentage >= 50) {
            return "Vous recevrez un remboursement de {$refundPercentage}%.";
        }

        if ($refundPercentage > 0) {
            return "Compte tenu du délai, vous ne recevrez qu'un remboursement partiel de {$refundPercentage}%.";
        }

        return 'Malheureusement, cette réservation n\'est plus remboursable selon la politique d\'annulation.';
    }

    /**
     * Get cancellation statistics for owner
     */
    public function getOwnerStats(int $ownerId): array
    {
        $bookings = Booking::forOwner($ownerId);
        $totalBookings = $bookings->count();

        $cancellations = Cancellation::whereHas('booking', function ($q) use ($ownerId) {
            $q->whereHas('residence', function ($r) use ($ownerId) {
                $r->where('owner_id', $ownerId);
            });
        });

        $ownerCancellations = (clone $cancellations)->byOwner()->count();
        $guestCancellations = (clone $cancellations)->byGuest()->count();

        return [
            'total_bookings' => $totalBookings,
            'total_cancellations' => $ownerCancellations + $guestCancellations,
            'owner_cancellations' => $ownerCancellations,
            'guest_cancellations' => $guestCancellations,
            'owner_cancellation_rate' => $totalBookings > 0
                ? round(($ownerCancellations / $totalBookings) * 100, 2)
                : 0,
            'total_cancellation_rate' => $totalBookings > 0
                ? round((($ownerCancellations + $guestCancellations) / $totalBookings) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get cancellation statistics for guest
     */
    public function getGuestStats(int $userId): array
    {
        $bookings = Booking::forGuest($userId);
        $totalBookings = $bookings->count();

        $cancellations = Cancellation::whereHas('booking', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->byGuest()->count();

        $totalRefunded = Cancellation::whereHas('booking', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->sum('refund_amount');

        return [
            'total_bookings' => $totalBookings,
            'cancellations' => $cancellations,
            'cancellation_rate' => $totalBookings > 0
                ? round(($cancellations / $totalBookings) * 100, 2)
                : 0,
            'total_refunded' => $totalRefunded,
        ];
    }

    /**
     * Check if owner has high cancellation rate (for badge removal)
     */
    public function hasHighCancellationRate(int $ownerId): bool
    {
        $stats = $this->getOwnerStats($ownerId);

        return $stats['owner_cancellation_rate'] > 1; // More than 1%
    }
}
