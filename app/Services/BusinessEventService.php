<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\SensitiveData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Business Event Tracker — tracks all revenue-critical events.
 *
 * Channels:
 *   - 'business' log channel: structured events for dashboards / BI
 *   - DB table 'business_events': queryable history for admin reports
 *
 * Design: fire-and-forget (never throws). Safe to call from anywhere.
 */
class BusinessEventService
{
    // ──────────── Auth events ────────────

    public static function userRegistered(int $userId, string $channel = 'api', array $meta = []): void
    {
        static::record('user.registered', $userId, array_merge([
            'channel' => $channel,
        ], $meta));
    }

    public static function userLoggedIn(int $userId, string $channel = 'api', array $meta = []): void
    {
        static::record('user.login', $userId, array_merge([
            'channel' => $channel,
        ], $meta));
    }

    public static function userLoginFailed(string $email, string $ip, array $meta = []): void
    {
        static::record('user.login_failed', null, array_merge([
            'email_masked' => SensitiveData::maskEmail($email),
            'email_hash' => SensitiveData::hash($email),
            'ip_masked' => SensitiveData::maskIp($ip),
            'ip_hash' => SensitiveData::hash($ip),
        ], $meta));
    }

    // ──────────── Booking events ────────────

    public static function bookingCreated(int $userId, int $bookingId, float $amount, array $meta = []): void
    {
        static::record('booking.created', $userId, array_merge([
            'booking_id' => $bookingId,
            'amount' => $amount,
        ], $meta));
    }

    public static function bookingConfirmed(int $userId, int $bookingId, float $amount, array $meta = []): void
    {
        static::record('booking.confirmed', $userId, array_merge([
            'booking_id' => $bookingId,
            'amount' => $amount,
        ], $meta));
    }

    public static function bookingCancelled(int $userId, int $bookingId, string $cancelledBy, float $refundAmount = 0, array $meta = []): void
    {
        static::record('booking.cancelled', $userId, array_merge([
            'booking_id' => $bookingId,
            'cancelled_by' => $cancelledBy,
            'refund_amount' => $refundAmount,
        ], $meta));
    }

    // ──────────── Payment events ────────────

    public static function paymentInitiated(int $userId, int $paymentId, float $amount, string $operator, array $meta = []): void
    {
        static::record('payment.initiated', $userId, array_merge([
            'payment_id' => $paymentId,
            'amount' => $amount,
            'operator' => $operator,
        ], $meta));
    }

    public static function paymentCompleted(int $userId, int $paymentId, float $amount, array $meta = []): void
    {
        static::record('payment.completed', $userId, array_merge([
            'payment_id' => $paymentId,
            'amount' => $amount,
        ], $meta));
    }

    public static function paymentFailed(int $userId, int $paymentId, float $amount, string $reason, array $meta = []): void
    {
        static::record('payment.failed', $userId, array_merge([
            'payment_id' => $paymentId,
            'amount' => $amount,
            'reason' => $reason,
        ], $meta));
    }

    // ──────────── Search / view events ────────────

    public static function residenceViewed(int $userId, int $residenceId, array $meta = []): void
    {
        static::record('residence.viewed', $userId, array_merge([
            'residence_id' => $residenceId,
        ], $meta));
    }

    public static function searchPerformed(?int $userId, array $filters, int $resultCount, array $meta = []): void
    {
        static::record('search.performed', $userId, array_merge([
            'filters' => $filters,
            'result_count' => $resultCount,
        ], $meta));
    }

    // ──────────── Revenue events ────────────

    public static function revenueEarned(int $ownerId, float $amount, int $bookingId, float $commission, array $meta = []): void
    {
        static::record('revenue.earned', $ownerId, array_merge([
            'booking_id' => $bookingId,
            'gross_amount' => $amount,
            'commission' => $commission,
            'net_amount' => $amount - $commission,
        ], $meta));
    }

    // ──────────── Core recorder ────────────

    protected static function record(string $event, ?int $userId, array $properties = []): void
    {
        try {
            // Structured log for real-time monitoring / log aggregators
            Log::channel('business')->info($event, array_merge([
                'user_id' => $userId,
                'timestamp' => now()->toIso8601String(),
            ], $properties));

            // Persist to DB for queryable analytics (async-safe)
            DB::table('business_events')->insert([
                'event' => $event,
                'user_id' => $userId,
                'properties' => json_encode($properties, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never crash the main flow — log to critical and move on
            Log::channel('critical')->error('BusinessEventService::record failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ──────────── Query helpers (admin dashboard) ────────────

    /**
     * Get event counts grouped by event name for a date range.
     */
    public static function summary(string $from, string $to): array
    {
        try {
            return DB::table('business_events')
                ->selectRaw('event, COUNT(*) as count, DATE(created_at) as day')
                ->whereBetween('created_at', [$from, $to])
                ->groupBy('event', 'day')
                ->orderBy('day', 'desc')
                ->get()
                ->groupBy('event')
                ->map(fn ($rows) => $rows->pluck('count', 'day'))
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Revenue summary for a date range.
     */
    public static function revenueSummary(string $from, string $to): array
    {
        try {
            $payments = DB::table('business_events')
                ->where('event', 'payment.completed')
                ->whereBetween('created_at', [$from, $to])
                ->get();

            $total = 0;
            foreach ($payments as $row) {
                $props = json_decode($row->properties, true);
                $total += ($props['amount'] ?? 0);
            }

            return [
                'total_revenue' => $total,
                'transaction_count' => $payments->count(),
                'period' => ['from' => $from, 'to' => $to],
            ];
        } catch (\Throwable $e) {
            return ['total_revenue' => 0, 'transaction_count' => 0];
        }
    }
}
