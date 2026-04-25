<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\BalanceDueReminder;
use App\Notifications\BalanceOverdue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande quotidienne — paiements échelonnés (Sprint 3 item 2).
 *
 *  - J-2 → notifie le client pour qu'il règle le solde via le lien habituel
 *  - J+1 → notifie en retard (relance + alerte)
 *
 * Pas de débit automatique : Jeko/Wave/Orange/MTN n'autorisent pas la
 * tokenisation de carte → le client doit revenir confirmer via OTP.
 */
class ProcessSplitPayments extends Command
{
    protected $signature = 'bookings:process-split-payments';

    protected $description = 'Traite les paiements échelonnés (rappel + retard)';

    public function handle(): int
    {
        $today = now()->toDateString();

        // 1. Rappel — solde dû dans ≤2 jours, pas encore notifié
        $reminders = Booking::query()
            ->with(['user', 'residence'])
            ->where('payment_split', true)
            ->whereNull('balance_paid_at')
            ->whereNull('balance_reminder_sent_at')
            ->whereNotNull('balance_due_at')
            ->whereDate('balance_due_at', '<=', now()->addDays(2)->toDateString())
            ->whereDate('balance_due_at', '>=', $today)
            ->get();

        foreach ($reminders as $booking) {
            try {
                $booking->user?->notify(new BalanceDueReminder($booking));
                $booking->update(['balance_reminder_sent_at' => now()]);
                $this->info("Rappel envoyé booking #{$booking->id}");
            } catch (\Throwable $e) {
                Log::warning('Balance reminder failed', ['id' => $booking->id, 'err' => $e->getMessage()]);
            }
        }

        // 2. Soldes en retard — relance le client + log pour owner
        $overdue = Booking::query()
            ->with(['user', 'residence.owner'])
            ->where('payment_split', true)
            ->whereNull('balance_paid_at')
            ->whereNotNull('balance_due_at')
            ->whereDate('balance_due_at', '<', $today)
            ->get();

        foreach ($overdue as $booking) {
            try {
                $booking->user?->notify(new BalanceOverdue($booking));
                Log::warning('Booking balance overdue', [
                    'id' => $booking->id,
                    'due' => optional($booking->balance_due_at)->toDateString(),
                    'amount' => $booking->balance_amount,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Balance overdue notif failed', ['id' => $booking->id, 'err' => $e->getMessage()]);
            }
        }

        $this->info(sprintf('Traités : %d rappels, %d en retard.', $reminders->count(), $overdue->count()));

        return self::SUCCESS;
    }
}
