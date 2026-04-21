<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'subject',
        'content',
        'template',
        'audience',
        'audience_filters',
        'excluded_user_ids',
        'scheduled_at',
        'sent_at',
        'status',
        'recipients_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'track_opens',
        'track_clicks',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'audience_filters' => 'array',
        'excluded_user_ids' => 'array',
        'track_opens' => 'boolean',
        'track_clicks' => 'boolean',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    /**
     * Get users excluded from this campaign (virtual relation via excluded_user_ids)
     */
    public function getExcludedUsersAttribute()
    {
        if (empty($this->excluded_user_ids)) {
            return collect();
        }

        return User::whereIn('id', $this->excluded_user_ids)->get();
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    // Helpers
    public function getAudienceQuery()
    {
        $query = User::query();

        switch ($this->audience) {
            case 'owners':
                $query->where('role', 'owner');
                break;
            case 'clients':
                $query->where('role', 'client');
                break;
            case 'inactive_users':
                $query->where('last_login_at', '<', now()->subDays(30));
                break;
            case 'new_users':
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'high_value':
                // Utilisateurs avec plusieurs réservations
                $query->whereHas('contacts', function ($q) {
                    $q->whereIn('status', ['confirmed', 'completed']);
                }, '>=', 3);
                break;
            case 'custom':
                // Appliquer les filtres personnalisés
                if ($this->audience_filters) {
                    if (isset($this->audience_filters['communes'])) {
                        // Pour les propriétaires avec résidences dans ces communes
                    }
                    if (isset($this->audience_filters['min_bookings'])) {
                        $query->whereHas('contacts', function ($q) {
                            $q->whereIn('status', ['confirmed', 'completed']);
                        }, '>=', $this->audience_filters['min_bookings']);
                    }
                }
                break;
        }

        // Exclure certains utilisateurs
        if ($this->excluded_user_ids) {
            $query->whereNotIn('id', $this->excluded_user_ids);
        }

        // Exclure les désabonnés selon le type
        if ($this->type === 'email') {
            $query->where('email_notifications', true);
        }

        return $query;
    }

    public function getRecipientsCount(): int
    {
        return $this->getAudienceQuery()->count();
    }

    public function getOpenRate(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return round(($this->opened_count / $this->delivered_count) * 100, 1);
    }

    public function getClickRate(): float
    {
        if ($this->opened_count === 0) {
            return 0;
        }

        return round(($this->clicked_count / $this->opened_count) * 100, 1);
    }

    public function getBounceRate(): float
    {
        if ($this->recipients_count === 0) {
            return 0;
        }

        return round(($this->bounced_count / $this->recipients_count) * 100, 1);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'scheduled' => 'Programmée',
            'sending' => 'En cours d\'envoi',
            'sent' => 'Envoyée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'scheduled' => 'blue',
            'sending' => 'yellow',
            'sent' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'email' => '📧',
            'sms' => '📱',
            'push' => '🔔',
            'in_app' => '💬',
            default => '📨',
        };
    }

    // Actions
    public function schedule(\DateTime $datetime): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $datetime,
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function markAsSending(): void
    {
        $this->update(['status' => 'sending']);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
