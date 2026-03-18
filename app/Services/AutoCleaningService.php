<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\CleaningTask;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;

class AutoCleaningService
{
    protected CleaningTaskService $cleaningTaskService;

    public function __construct(CleaningTaskService $cleaningTaskService)
    {
        $this->cleaningTaskService = $cleaningTaskService;
    }

    /**
     * Créer automatiquement une tâche de ménage après confirmation d'une réservation
     */
    public function createForBookingConfirmed(Booking $booking): ?CleaningTask
    {
        $residence = $booking->residence;
        if (!$residence) {
            return null;
        }

        // Vérifier qu'il n'y a pas déjà une tâche pour ce booking
        $existing = CleaningTask::where('booking_id', $booking->id)->first();
        if ($existing) {
            return $existing;
        }

        $scheduledDate = Carbon::parse($booking->check_in)->subDay();
        if ($scheduledDate->isPast()) {
            $scheduledDate = Carbon::parse($booking->check_in);
        }

        return $this->cleaningTaskService->create($residence->owner, [
            'residence_id'   => $residence->id,
            'booking_id'     => $booking->id,
            'scheduled_date' => $scheduledDate->format('Y-m-d'),
            'scheduled_time' => '10:00',
            'notes'          => "Ménage avant arrivée — Réservation #{$booking->reference} (" . ($booking->user->name ?? 'Voyageur') . ")",
        ]);
    }

    /**
     * Créer automatiquement une tâche de ménage après checkout
     */
    public function createForCheckout(Booking $booking): ?CleaningTask
    {
        $residence = $booking->residence;
        if (!$residence) {
            return null;
        }

        // Vérifier qu'il n'y a pas déjà une tâche de checkout pour ce booking
        $existing = CleaningTask::where('booking_id', $booking->id)
            ->where('notes', 'like', '%après départ%')
            ->first();
        if ($existing) {
            return $existing;
        }

        return $this->cleaningTaskService->create($residence->owner, [
            'residence_id'   => $residence->id,
            'booking_id'     => $booking->id,
            'scheduled_date' => Carbon::parse($booking->check_out)->format('Y-m-d'),
            'scheduled_time' => $residence->check_out_time ?? '11:00',
            'notes'          => "Ménage après départ — Réservation #{$booking->reference}",
        ]);
    }

    /**
     * Créer les tâches de ménage pour toutes les réservations confirmées à venir
     */
    public function scheduleUpcomingCleanings(int $daysAhead = 7): int
    {
        $bookings = Booking::where('status', 'confirmed')
            ->whereBetween('check_in', [now(), now()->addDays($daysAhead)])
            ->whereDoesntHave('cleaningTasks')
            ->with('residence.owner')
            ->get();

        $created = 0;
        foreach ($bookings as $booking) {
            if ($this->createForBookingConfirmed($booking)) {
                $created++;
            }
        }

        return $created;
    }
}
