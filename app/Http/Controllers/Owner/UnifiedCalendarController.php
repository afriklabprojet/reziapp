<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CleaningTask;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Models\RentReminder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnifiedCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $residenceIds = $user->residences()->pluck('id');

        return view('owner.calendar.index', compact('user'));
    }

    /**
     * AJAX endpoint for calendar events
     */
    public function events(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $residenceIds = $user->residences()->pluck('id');
        $start = $request->get('start', now()->startOfMonth()->toDateString());
        $end   = $request->get('end', now()->endOfMonth()->toDateString());

        $events = [];

        // Bookings
        $bookings = Booking::whereIn('residence_id', $residenceIds)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('check_in', [$start, $end])
                  ->orWhereBetween('check_out', [$start, $end]);
            })
            ->with(['residence', 'user'])
            ->get();

        foreach ($bookings as $booking) {
            $events[] = [
                'id'    => 'booking-'.$booking->id,
                'title' => $booking->residence?->name.' — '.($booking->user?->name ?? 'Réservation'),
                'start' => $booking->check_in,
                'end'   => $booking->check_out,
                'color' => '#4F46E5', // Indigo
                'type'  => 'booking',
                'url'   => route('owner.bookings.show', $booking),
            ];
        }

        // Cleaning tasks
        $cleanings = CleaningTask::forOwner($user->id)
            ->whereBetween('scheduled_date', [$start, $end])
            ->with('residence')
            ->get();

        foreach ($cleanings as $task) {
            $events[] = [
                'id'    => 'cleaning-'.$task->id,
                'title' => '🧹 '.($task->residence?->name ?? 'Ménage'),
                'start' => $task->scheduled_date->toDateString(),
                'color' => '#10B981', // Emerald
                'type'  => 'cleaning',
            ];
        }

        // Rent reminders
        $reminders = RentReminder::forOwner($user->id)
            ->whereBetween('due_date', [$start, $end])
            ->with('residence')
            ->get();

        foreach ($reminders as $reminder) {
            $events[] = [
                'id'    => 'rent-'.$reminder->id,
                'title' => '💰 Loyer '.($reminder->residence?->name ?? ''),
                'start' => $reminder->due_date->toDateString(),
                'color' => $reminder->isOverdue() ? '#EF4444' : '#F59E0B', // Red or Amber
                'type'  => 'rent_reminder',
            ];
        }

        // Maintenance requests
        $maintenance = MaintenanceRequest::forOwner($user->id)
            ->whereBetween('created_at', [$start, $end])
            ->with('residence')
            ->get();

        foreach ($maintenance as $req) {
            $events[] = [
                'id'    => 'maintenance-'.$req->id,
                'title' => '🔧 '.$req->title,
                'start' => $req->created_at->toDateString(),
                'color' => '#F97316', // Orange
                'type'  => 'maintenance',
            ];
        }

        // Expenses
        $expenses = Expense::forOwner($user->id)
            ->whereBetween('expense_date', [$start, $end])
            ->get();

        foreach ($expenses as $expense) {
            $events[] = [
                'id'    => 'expense-'.$expense->id,
                'title' => '📋 '.number_format((float) $expense->amount, 0, ',', ' ').' F — '.$expense->category_label,
                'start' => $expense->expense_date->toDateString(),
                'color' => '#8B5CF6', // Violet
                'type'  => 'expense',
            ];
        }

        return response()->json($events);
    }
}
