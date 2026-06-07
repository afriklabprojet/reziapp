<?php

declare(strict_types=1);

namespace App\Services\Bookings;

use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Residence;
use Carbon\Carbon;

class BookingAvailabilityService
{
    public function checkAvailability(int $residenceId, Carbon $checkIn, Carbon $checkOut): array
    {
        $hasBlockedDates = BlockedDate::hasBlockedDatesInRange($residenceId, $checkIn, $checkOut);

        if ($hasBlockedDates) {
            return [
                'available' => false,
                'reason' => 'dates_blocked',
                'blocked_dates' => BlockedDate::getBlockedDatesArray($residenceId, $checkIn, $checkOut),
                'message' => 'Certaines dates sont indisponibles.',
            ];
        }

        $hasExistingBooking = Booking::where('residence_id', $residenceId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut])
                    ->orWhere(function ($nestedQuery) use ($checkIn, $checkOut) {
                        $nestedQuery->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->exists();

        if ($hasExistingBooking) {
            return [
                'available' => false,
                'reason' => 'already_booked',
                'message' => 'Cette résidence est déjà réservée pour ces dates.',
            ];
        }

        $hasPendingRequest = BookingRequest::where('residence_id', $residenceId)
            ->where('status', 'pending')
            ->notExpired()
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut->copy()->subDay()])
                    ->orWhereBetween('check_out', [$checkIn->copy()->addDay(), $checkOut]);
            })
            ->exists();

        return [
            'available' => true,
            'has_pending_request' => $hasPendingRequest,
            'message' => $hasPendingRequest
                ? 'Disponible, mais une demande est en attente pour certaines dates.'
                : 'Disponible pour ces dates.',
        ];
    }

    public function getUnavailableDates(int $residenceId, Carbon $startDate, Carbon $endDate): array
    {
        $dates = BlockedDate::getBlockedDatesArray($residenceId, $startDate, $endDate);

        $bookings = Booking::where('residence_id', $residenceId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<=', $endDate)
            ->where('check_out', '>=', $startDate)
            ->get();

        foreach ($bookings as $booking) {
            $current = Carbon::parse($booking->check_in);
            $end = Carbon::parse($booking->check_out);

            while ($current < $end) {
                $dates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return array_unique($dates);
    }

    public function getAvailabilityCalendar(int $residenceId, int $months = 3): array
    {
        $startDate = today();
        $endDate = today()->addMonths($months);
        $unavailableDates = $this->getUnavailableDates($residenceId, $startDate, $endDate);
        $specialPrices = \App\Models\SpecialPrice::getPricesForDateRange($residenceId, $startDate, $endDate);
        $residence = Residence::find($residenceId);
        $basePricePerNight = $residence->price_per_night;

        $calendar = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $calendar[$dateStr] = [
                'date' => $dateStr,
                'available' => ! in_array($dateStr, $unavailableDates),
                'price' => $specialPrices[$dateStr] ?? $basePricePerNight,
                'is_special_price' => isset($specialPrices[$dateStr]),
                'is_weekend' => $current->isWeekend(),
            ];
            $current->addDay();
        }

        return $calendar;
    }

    public function getOwnerBookingStats(int $ownerId): array
    {
        $residenceIds = Residence::where('owner_id', $ownerId)->pluck('id');

        return [
            'pending_bookings' => Booking::whereIn('residence_id', $residenceIds)
                ->where('status', 'pending')
                ->count(),
            'confirmed_bookings' => Booking::whereIn('residence_id', $residenceIds)
                ->where('status', 'confirmed')
                ->count(),
            'monthly_revenue' => Booking::whereIn('residence_id', $residenceIds)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'pending_requests' => BookingRequest::whereIn('residence_id', $residenceIds)
                ->where('status', 'pending')
                ->count(),
        ];
    }
}
