<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SponsoredListingStat extends Model
{
    protected $fillable = [
        'sponsored_listing_id',
        'date',
        'impressions',
        'clicks',
        'contacts',
        'amount_spent',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_spent' => 'decimal:2',
    ];

    public function sponsoredListing(): BelongsTo
    {
        return $this->belongsTo(SponsoredListing::class);
    }

    /**
     * Get or create a stat record for today
     */
    public static function forToday(int $sponsoredListingId): self
    {
        return self::firstOrCreate(
            [
                'sponsored_listing_id' => $sponsoredListingId,
                'date' => now()->toDateString(),
            ],
            [
                'impressions' => 0,
                'clicks' => 0,
                'contacts' => 0,
                'amount_spent' => 0,
            ],
        );
    }
}
