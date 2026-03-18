<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks processed webhook events to ensure idempotent processing.
 * Prevents double-processing of the same webhook from payment providers.
 */
class WebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Check if an event was already processed, and mark it if not.
     * Returns true if the event is NEW (should be processed).
     * Returns false if the event was ALREADY processed (skip).
     *
     * Uses INSERT IGNORE / unique constraint to handle race conditions.
     */
    public static function acquireLock(string $provider, string $eventId, string $eventType = null, array $payload = []): bool
    {
        try {
            static::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => 'processed',
                'payload' => $payload,
            ]);

            return true; // First time — process this event
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate entry (23000 = integrity constraint violation)
            if (str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() === '23000') {
                return false; // Already processed — skip
            }

            throw $e; // Re-throw unexpected DB errors
        }
    }

    /**
     * Mark a previously acquired event as failed (for retry).
     */
    public static function markFailed(string $provider, string $eventId): void
    {
        static::where('provider', $provider)
            ->where('event_id', $eventId)
            ->update(['status' => 'failed']);
    }

    /**
     * Clean up old events (keep 90 days).
     */
    public static function prune(int $days = 90): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
