<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewHistory extends Model
{
    use HasFactory;

    protected $table = 'view_history';

    protected $fillable = [
        'user_id',
        'residence_id',
        'view_count',
        'total_duration_seconds',
        'first_viewed_at',
        'last_viewed_at',
        'view_sessions',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'total_duration_seconds' => 'integer',
        'first_viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'view_sessions' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // Scopes
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_viewed_at', '>=', now()->subDays($days));
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeMostViewed($query)
    {
        return $query->orderByDesc('view_count');
    }

    // Methods
    public static function recordView(int $userId, int $residenceId, int $durationSeconds = 0): self
    {
        $history = self::firstOrNew([
            'user_id' => $userId,
            'residence_id' => $residenceId,
        ]);

        $now = now();
        $sessions = $history->view_sessions ?? [];

        // Add new session
        $sessions[] = [
            'viewed_at' => $now->toIso8601String(),
            'duration_seconds' => $durationSeconds,
        ];

        // Keep only last 50 sessions
        $sessions = array_slice($sessions, -50);

        if (!$history->exists) {
            $history->first_viewed_at = $now;
        }

        $history->view_count = ($history->view_count ?? 0) + 1;
        $history->total_duration_seconds = ($history->total_duration_seconds ?? 0) + $durationSeconds;
        $history->last_viewed_at = $now;
        $history->view_sessions = $sessions;
        $history->save();

        return $history;
    }

    public function getFormattedDuration(): string
    {
        $hours = floor($this->total_duration_seconds / 3600);
        $minutes = floor(($this->total_duration_seconds % 3600) / 60);
        $seconds = $this->total_duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        } elseif ($minutes > 0) {
            return sprintf('%dm %02ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }

    public function getAverageViewDuration(): int
    {
        if ($this->view_count === 0) {
            return 0;
        }

        return (int) round($this->total_duration_seconds / $this->view_count);
    }
}
