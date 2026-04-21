<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'active',
    ];

    protected $fillable = [
        'email',
        'name',
        'user_id',
        'status',
        'token',
        'source',
        'ip_address',
        'subscribed_at',
        'unsubscribed_at',
        'verified_at',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // ── Boot ──

    protected static function booted(): void
    {
        static::creating(function (self $subscriber) {
            if (empty($subscriber->token)) {
                $subscriber->token = Str::random(64);
            }
        });
    }

    // ── Relations ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    // ── Methods ──

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function unsubscribe(): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }

    public function resubscribe(): void
    {
        $this->update([
            'status' => 'active',
            'unsubscribed_at' => null,
            'subscribed_at' => now(),
        ]);
    }

    /**
     * Génère l'URL de désabonnement sécurisée
     */
    public function getUnsubscribeUrlAttribute(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->token]);
    }
}
