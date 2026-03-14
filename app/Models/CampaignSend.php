<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSend extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Actions
    public function markSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $this->campaign->increment('delivered_count');
    }

    public function markOpened(): void
    {
        if ($this->status !== 'opened' && $this->status !== 'clicked') {
            $this->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
            $this->campaign->increment('opened_count');
        }
    }

    public function markClicked(): void
    {
        $wasOpened = in_array($this->status, ['opened', 'clicked']);

        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
            'opened_at' => $this->opened_at ?? now(),
        ]);

        if (!$wasOpened) {
            $this->campaign->increment('opened_count');
        }
        $this->campaign->increment('clicked_count');
    }

    public function markBounced(?string $error = null): void
    {
        $this->update([
            'status' => 'bounced',
            'error_message' => $error,
        ]);
        $this->campaign->increment('bounced_count');
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
