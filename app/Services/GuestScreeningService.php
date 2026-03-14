<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\DamageReport;
use App\Models\GuestScore;
use App\Models\IdentityVerification;
use App\Models\TenantReview;
use App\Models\User;
use Carbon\Carbon;

class GuestScreeningService
{
    /**
     * Calculer ou recalculer le score d'un voyageur
     */
    public function calculateScore(User $user): GuestScore
    {
        $identityScore  = $this->calculateIdentityScore($user);
        $bookingScore   = $this->calculateBookingScore($user);
        $reviewScore    = $this->calculateReviewScore($user);
        $seniorityScore = $this->calculateSeniorityScore($user);
        $totalScore     = $identityScore + $bookingScore + $reviewScore + $seniorityScore;

        $riskLevel = match (true) {
            $totalScore >= 70 => GuestScore::RISK_LOW,
            $totalScore >= 40 => GuestScore::RISK_MEDIUM,
            default           => GuestScore::RISK_HIGH,
        };

        // Statistiques
        $totalBookings     = Booking::where('user_id', $user->id)->count();
        $completedBookings = Booking::where('user_id', $user->id)->where('status', 'completed')->count();
        $cancelledBookings = Booking::where('user_id', $user->id)->where('status', 'cancelled')->count();
        $cancellationRate  = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 2) : 0;
        $damageCount       = DamageReport::where('reported_by', '!=', $user->id)
            ->whereHas('booking', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        $avgOwnerRating = TenantReview::where('tenant_id', $user->id)->avg('overall_rating') ?? 0;

        $flags = $this->detectFlags($user, $cancellationRate, $damageCount);

        return GuestScore::updateOrCreate(
            ['user_id' => $user->id],
            [
                'total_score'          => $totalScore,
                'identity_score'       => $identityScore,
                'booking_score'        => $bookingScore,
                'review_score'         => $reviewScore,
                'seniority_score'      => $seniorityScore,
                'risk_level'           => $riskLevel,
                'total_bookings'       => $totalBookings,
                'completed_bookings'   => $completedBookings,
                'cancelled_bookings'   => $cancelledBookings,
                'cancellation_rate'    => $cancellationRate,
                'average_owner_rating' => round($avgOwnerRating, 2),
                'damage_reports_count' => $damageCount,
                'flags'                => $flags,
                'last_calculated_at'   => now(),
            ]
        );
    }

    private function calculateIdentityScore(User $user): int
    {
        $score = 0;

        // Email vérifié
        if ($user->email_verified_at) {
            $score += 5;
        }

        // Téléphone vérifié
        if ($user->phone_verified_at ?? false) {
            $score += 5;
        }

        // Identité vérifiée (KYC)
        $kyc = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();
        if ($kyc) {
            $score += 10;
        }

        // Photo de profil
        if ($user->profile_photo || $user->avatar) {
            $score += 5;
        }

        return min(25, $score);
    }

    private function calculateBookingScore(User $user): int
    {
        $completed  = Booking::where('user_id', $user->id)->where('status', 'completed')->count();
        $cancelled  = Booking::where('user_id', $user->id)->where('status', 'cancelled')->count();
        $total      = $completed + $cancelled;

        if ($total === 0) {
            return 5; // neutre
        }

        $completionRate = ($completed / $total) * 100;

        $score = match (true) {
            $completionRate >= 95 => 25,
            $completionRate >= 80 => 20,
            $completionRate >= 60 => 15,
            $completionRate >= 40 => 10,
            default               => 5,
        };

        // Bonus pour volume
        if ($completed >= 10) {
            $score = min(25, $score + 5);
        }

        return $score;
    }

    private function calculateReviewScore(User $user): int
    {
        $avgRating = TenantReview::where('tenant_id', $user->id)->avg('overall_rating');

        if (!$avgRating) {
            return 10; // neutre
        }

        return match (true) {
            $avgRating >= 4.5 => 25,
            $avgRating >= 4.0 => 20,
            $avgRating >= 3.5 => 15,
            $avgRating >= 3.0 => 10,
            default           => 5,
        };
    }

    private function calculateSeniorityScore(User $user): int
    {
        $monthsSinceCreated = $user->created_at->diffInMonths(now());

        return match (true) {
            $monthsSinceCreated >= 24 => 25,
            $monthsSinceCreated >= 12 => 20,
            $monthsSinceCreated >= 6  => 15,
            $monthsSinceCreated >= 3  => 10,
            default                   => 5,
        };
    }

    private function detectFlags(User $user, float $cancellationRate, int $damageCount): array
    {
        $flags = [];

        if ($cancellationRate > 30) {
            $flags[] = 'Taux d\'annulation élevé (' . $cancellationRate . '%)';
        }

        if ($damageCount > 0) {
            $flags[] = "$damageCount rapport(s) de dommage(s)";
        }

        if (!$user->email_verified_at) {
            $flags[] = 'Email non vérifié';
        }

        $accountAge = $user->created_at->diffInDays(now());
        if ($accountAge < 7) {
            $flags[] = 'Compte très récent (' . $accountAge . ' jour(s))';
        }

        return $flags;
    }
}
