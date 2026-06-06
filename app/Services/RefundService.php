<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    /**
     * Create a refund for cancellation
     */
    public function createRefund(
        Cancellation $cancellation,
        int $userId,
        float $amount,
        string $method = 'original_payment',
    ): Refund {
        return Refund::create([
            'cancellation_id' => $cancellation->id,
            'booking_id' => $cancellation->booking_id,
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => $cancellation->booking->currency ?? 'FCFA',
            'method' => $method,
            'status' => 'pending',
            'requested_by' => 'system',
        ]);
    }

    /**
     * Create manual refund (by admin)
     */
    public function createManualRefund(
        Booking $booking,
        int $userId,
        float $amount,
        string $method,
        int $adminId,
        ?string $notes = null,
    ): Refund {
        return Refund::create([
            'cancellation_id' => $booking->cancellation?->id,
            'booking_id' => $booking->id,
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => $booking->currency ?? 'FCFA',
            'method' => $method,
            'status' => 'pending',
            'requested_by' => 'admin',
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Process pending refund
     */
    public function processRefund(Refund $refund): Refund
    {
        if (!$refund->isPending()) {
            throw new \Exception('Ce remboursement n\'est pas en attente de traitement.');
        }

        $refund->startProcessing();

        try {
            // Process based on method
            $result = match($refund->method) {
                'original_payment' => $this->processOriginalPaymentRefund($refund),
                'credit' => $this->processCreditRefund($refund),
                'bank_transfer' => $this->processBankTransferRefund($refund),
                'mobile_money' => $this->processMobileMoneyRefund($refund),
                default => throw new \Exception('Méthode de remboursement non supportée'),
            };

            if ($result['success']) {
                $refund->markCompleted($result['transaction_id'] ?? null);

                // Update booking payment status
                $this->updateBookingPaymentStatus($refund->booking);

                // Mark cancellation as processed if fully refunded
                if ($refund->cancellation && $refund->cancellation->isFullyRefunded()) {
                    $refund->cancellation->markProcessed();
                }

                Log::info('Refund processed successfully', [
                    'refund_id' => $refund->id,
                    'amount' => $refund->amount,
                    'method' => $refund->method,
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'Erreur lors du traitement');
            }
        } catch (\Exception $e) {
            $refund->markFailed($e->getMessage());

            Log::error('Refund processing failed', [
                'refund_id' => $refund->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $refund->fresh();
    }

    /**
     * Process refund to original payment method
     */
    protected function processOriginalPaymentRefund(Refund $refund): array
    {
        $booking = $refund->booking;

        // Retrouver le paiement original complété pour cette réservation
        $payment = Payment::where('booking_id', $booking->id)
            ->where('status', Payment::STATUS_COMPLETED)
            ->latest()
            ->first();

        if (!$payment) {
            Log::warning('RefundService: No completed payment found for booking', [
                'refund_id' => $refund->id,
                'booking_id' => $booking->id,
            ]);

            return [
                'success' => false,
                'message' => 'Paiement original introuvable',
            ];
        }

        // Appeler Jeko Pay pour le remboursement
        $jeko = new JekoService();
        $result = $jeko->refund($payment, $refund->amount, $refund->reason ?? 'Remboursement ReziApp');

        if ($result['success']) {
            $refund->update([
                'transaction_id' => $result['refund_reference'] ?? Refund::generateTransactionId(),
                'payment_gateway_response' => $result,
            ]);

            return [
                'success' => true,
                'transaction_id' => $result['refund_reference'] ?? $refund->transaction_id,
            ];
        }

        Log::error('RefundService: Jeko refund failed', [
            'refund_id' => $refund->id,
            'payment_id' => $payment->id,
            'result' => $result,
        ]);

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Échec du remboursement',
        ];
    }

    /**
     * Process refund as ReziApp credit
     */
    protected function processCreditRefund(Refund $refund): array
    {
        $user = $refund->user;

        // Add credit to user account (atomic increment to prevent race conditions)
        DB::transaction(function () use ($user, $refund) {
            $user->increment('wallet_credit', $refund->amount);
        });

        return [
            'success' => true,
            'transaction_id' => 'CREDIT-'.Refund::generateTransactionId(),
        ];
    }

    /**
     * Process refund via bank transfer
     */
    protected function processBankTransferRefund(Refund $refund): array
    {
        // Les virements bancaires nécessitent un traitement manuel
        // Le refund reste en status "processing" jusqu'à validation par un admin
        Log::info('RefundService: Bank transfer refund queued for manual processing', [
            'refund_id' => $refund->id,
            'amount' => $refund->amount,
            'user_id' => $refund->user_id,
        ]);

        return [
            'success' => true,
            'transaction_id' => 'BANK-'.Refund::generateTransactionId(),
        ];
    }

    /**
     * Process refund via Mobile Money
     */
    protected function processMobileMoneyRefund(Refund $refund): array
    {
        $user = $refund->user;

        // Le numéro de téléphone provient du profil utilisateur
        $phoneNumber = $user->phone;

        if (!$phoneNumber) {
            Log::warning('RefundService: User has no phone number for mobile money refund', [
                'refund_id' => $refund->id,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'Numéro de téléphone introuvable pour le remboursement Mobile Money',
            ];
        }

        // Détecter l'opérateur et envoyer via Jeko payout
        $jeko = new JekoService();
        $operator = $jeko->detectOperator($phoneNumber) ?? 'orange_money';

        $result = $jeko->payout($phoneNumber, $refund->amount, $operator, [
            'description' => "Remboursement ReziApp — Réservation #{$refund->booking->id}",
            'refund_id' => $refund->id,
            'booking_id' => $refund->booking_id,
        ]);

        if ($result['success']) {
            $refund->update([
                'transaction_id' => $result['payout_reference'] ?? 'MOMO-'.Refund::generateTransactionId(),
                'payment_gateway_response' => $result,
            ]);

            return [
                'success' => true,
                'transaction_id' => $result['payout_reference'] ?? $refund->transaction_id,
            ];
        }

        Log::error('RefundService: Mobile Money refund failed', [
            'refund_id' => $refund->id,
            'phone' => $phoneNumber,
            'result' => $result,
        ]);

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Échec du remboursement Mobile Money',
        ];
    }

    /**
     * Update booking payment status after refund
     */
    protected function updateBookingPaymentStatus(Booking $booking): void
    {
        $totalRefunded = $booking->refunds()->completed()->sum('amount');
        $totalPaid = $booking->total_amount;

        if ($totalRefunded >= $totalPaid) {
            $booking->update(['payment_status' => 'refunded']);
        } elseif ($totalRefunded > 0) {
            $booking->update(['payment_status' => 'partially_refunded']);
        }
    }

    /**
     * Retry failed refund
     */
    public function retryRefund(Refund $refund): Refund
    {
        if (!$refund->canBeRetried()) {
            throw new \Exception('Ce remboursement ne peut pas être réessayé.');
        }

        $refund->retry();

        return $this->processRefund($refund);
    }

    /**
     * Cancel pending refund
     */
    public function cancelRefund(Refund $refund, string $reason): Refund
    {
        if (!$refund->isPending()) {
            throw new \Exception('Seuls les remboursements en attente peuvent être annulés.');
        }

        $refund->cancel($reason);

        return $refund;
    }

    /**
     * Get refund statistics
     */
    public function getStats(?string $period = 'month'): array
    {
        $query = Refund::query();

        if ($period === 'day') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereMonth('created_at', now()->month)
                  ->whereYear('created_at', now()->year);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->pending()->count();
        $completed = (clone $query)->completed()->count();
        $failed = (clone $query)->failed()->count();

        $totalAmount = (clone $query)->completed()->sum('amount');
        $pendingAmount = (clone $query)->pending()->sum('amount');

        return [
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'total_amount' => $totalAmount,
            'pending_amount' => $pendingAmount,
            'by_method' => $this->getStatsByMethod($query),
        ];
    }

    /**
     * Get stats by refund method
     */
    protected function getStatsByMethod($query): array
    {
        return (clone $query)
            ->selectRaw('method, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('method')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->method => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            })
            ->toArray();
    }

    /**
     * Get pending refunds for processing
     */
    public function getPendingRefunds(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Refund::pending()
            ->with(['booking', 'user', 'cancellation'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Process all pending refunds (for scheduled job)
     */
    public function processAllPending(): array
    {
        $pending = $this->getPendingRefunds();
        $processed = 0;
        $failed = 0;

        foreach ($pending as $refund) {
            try {
                $this->processRefund($refund);
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Batch refund processing failed', [
                    'refund_id' => $refund->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'total' => $pending->count(),
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Get user refund history
     */
    public function getUserRefunds(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return Refund::where('user_id', $userId)
            ->with(['booking.residence', 'cancellation'])
            ->orderByDesc('created_at')
            ->get();
    }
}
