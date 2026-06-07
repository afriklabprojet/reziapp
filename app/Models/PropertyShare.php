<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PropertyShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'platform',
        'share_token',
        'click_count',
        'booking_count',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'click_count' => 'integer',
        'booking_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function ($share) {
            if (empty($share->share_token)) {
                $share->share_token = Str::random(16);
            }
        });
    }

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
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByResidence($query, int $residenceId)
    {
        return $query->where('residence_id', $residenceId);
    }

    // Methods
    public function getShareUrl(): string
    {
        return route('shared.residence', $this->share_token);
    }

    public function getWhatsAppUrl(): string
    {
        $text = urlencode('Découvrez ce logement sur Rezi App : '.$this->getShareUrl());

        return "https://wa.me/?text={$text}";
    }

    public function getFacebookUrl(): string
    {
        $url = urlencode($this->getShareUrl());

        return "https://www.facebook.com/sharer/sharer.php?u={$url}";
    }

    public function getTwitterUrl(): string
    {
        $text = urlencode('Découvrez ce logement sur Rezi App');
        $url = urlencode($this->getShareUrl());

        return "https://twitter.com/intent/tweet?text={$text}&url={$url}";
    }

    public function getEmailUrl(): string
    {
        $subject = urlencode('Logement à découvrir sur Rezi App');
        $body = urlencode("Bonjour,\n\nJe voulais te partager ce logement que j'ai trouvé sur Rezi App :\n\n".$this->getShareUrl()."\n\nÀ bientôt !");

        return "mailto:?subject={$subject}&body={$body}";
    }

    public function recordClick(): void
    {
        $this->increment('click_count');
    }

    public function recordBooking(): void
    {
        $this->increment('booking_count');
    }

    public static function createShare(int $residenceId, string $platform, ?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return self::create([
            'residence_id' => $residenceId,
            'platform' => $platform,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    public static function getStats(int $residenceId): array
    {
        $shares = self::byResidence($residenceId)->get();

        return [
            'total_shares' => $shares->count(),
            'total_clicks' => $shares->sum('click_count'),
            'total_bookings' => $shares->sum('booking_count'),
            'by_platform' => $shares->groupBy('platform')->map(function ($group) {
                return [
                    'shares' => $group->count(),
                    'clicks' => $group->sum('click_count'),
                    'bookings' => $group->sum('booking_count'),
                ];
            }),
        ];
    }
}
