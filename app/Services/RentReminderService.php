<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RentReminder;
use App\Models\User;
use App\Notifications\RentReminderNotification;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class RentReminderService
{
    public function getReminders(User $owner, array $filters = []): LengthAwarePaginator
    {
        $query = RentReminder::forOwner($owner->id)
            ->with(['tenant', 'residence', 'leaseContract']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['residence_id'])) {
            $query->where('residence_id', $filters['residence_id']);
        }

        return $query->orderByDesc('due_date')->paginate(20);
    }

    public function create(User $owner, array $data): RentReminder
    {
        $data['owner_id'] = $owner->id;
        $data['status']   = RentReminder::STATUS_PENDING;

        return RentReminder::create($data);
    }

    public function markPaid(RentReminder $reminder): void
    {
        $reminder->markPaid();
    }

    public function sendReminder(RentReminder $reminder): void
    {
        $reminder->update([
            'status'        => RentReminder::STATUS_SENT,
            'last_sent_at'  => now(),
            'send_count'    => $reminder->send_count + 1,
        ]);

        // Send notification to tenant
        if ($reminder->tenant) {
            $reminder->tenant->notify(new RentReminderNotification($reminder));
        }
    }

    /**
     * Process all pending reminders that should be sent
     * Called by the scheduled command
     */
    public function processAutoReminders(): int
    {
        $count = 0;

        // Find overdue reminders
        $overdue = RentReminder::where('status', '!=', RentReminder::STATUS_PAID)
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdue as $reminder) {
            $reminder->update([
                'status'         => RentReminder::STATUS_OVERDUE,
                'reminder_level' => $this->calculateReminderLevel($reminder),
            ]);
            $this->sendReminder($reminder);
            $count++;
        }

        // Find upcoming reminders (5 days before due)
        $upcoming = RentReminder::where('status', RentReminder::STATUS_PENDING)
            ->whereBetween('due_date', [now(), now()->addDays(5)])
            ->whereNull('last_sent_at')
            ->get();

        foreach ($upcoming as $reminder) {
            $daysUntil = now()->diffInDays($reminder->due_date);
            $level = match (true) {
                $daysUntil <= 1 => 'j1',
                $daysUntil <= 3 => 'j3',
                default         => 'j5',
            };
            $reminder->update(['reminder_level' => $level]);
            $this->sendReminder($reminder);
            $count++;
        }

        return $count;
    }

    private function calculateReminderLevel(RentReminder $reminder): string
    {
        $daysOverdue = $reminder->due_date->diffInDays(now());

        return match (true) {
            $daysOverdue > 15 => 'escalated',
            $daysOverdue > 0  => 'overdue',
            default           => 'j1',
        };
    }

    public function getOverdueSummary(User $owner): array
    {
        $overdue = RentReminder::forOwner($owner->id)
            ->where('status', RentReminder::STATUS_OVERDUE)
            ->with(['tenant', 'residence'])
            ->get();

        return [
            'count'        => $overdue->count(),
            'total_amount' => $overdue->sum('amount'),
            'reminders'    => $overdue,
        ];
    }
}
