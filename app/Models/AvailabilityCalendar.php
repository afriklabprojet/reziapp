<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityCalendar extends Model
{
    protected $table = 'availability_calendar';

    protected $fillable = [
        'residence_id',
        'date',
        'status',
        'custom_price',
        'min_nights',
        'note',
        'booking_id',
    ];

    protected $casts = [
        'date' => 'date',
        'custom_price' => 'decimal:2',
        'min_nights' => 'integer',
    ];

    // Statuts
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_BOOKED = 'booked';

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Vérifie si une période est disponible
     */
    public static function isAvailable(int $residenceId, Carbon $checkIn, Carbon $checkOut): bool
    {
        $blockedDates = self::where('residence_id', $residenceId)
            ->whereBetween('date', [$checkIn, $checkOut->subDay()])
            ->whereIn('status', [self::STATUS_BLOCKED, self::STATUS_BOOKED])
            ->exists();

        return !$blockedDates;
    }

    /**
     * Récupère les dates bloquées pour une résidence
     */
    public static function getBlockedDates(int $residenceId, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $query = self::where('residence_id', $residenceId)
            ->whereIn('status', [self::STATUS_BLOCKED, self::STATUS_BOOKED]);

        if ($from) {
            $query->where('date', '>=', $from);
        }
        if ($to) {
            $query->where('date', '<=', $to);
        }

        return $query->pluck('date')->map(fn($d) => $d->format('Y-m-d'));
    }

    /**
     * Bloque une période
     */
    public static function blockDates(int $residenceId, Carbon $startDate, Carbon $endDate, ?string $note = null): int
    {
        $dates = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dates[] = [
                'residence_id' => $residenceId,
                'date' => $current->format('Y-m-d'),
                'status' => self::STATUS_BLOCKED,
                'note' => $note,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $current->addDay();
        }

        return self::upsert($dates, ['residence_id', 'date'], ['status', 'note', 'updated_at']);
    }

    /**
     * Débloque une période
     */
    public static function unblockDates(int $residenceId, Carbon $startDate, Carbon $endDate): int
    {
        return self::where('residence_id', $residenceId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('status', self::STATUS_BLOCKED)
            ->delete();
    }

    /**
     * Marque les dates comme réservées
     */
    public static function markAsBooked(int $residenceId, Carbon $checkIn, Carbon $checkOut, int $bookingId): int
    {
        $dates = [];
        $current = $checkIn->copy();

        while ($current < $checkOut) {
            $dates[] = [
                'residence_id' => $residenceId,
                'date' => $current->format('Y-m-d'),
                'status' => self::STATUS_BOOKED,
                'booking_id' => $bookingId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $current->addDay();
        }

        return self::upsert($dates, ['residence_id', 'date'], ['status', 'booking_id', 'updated_at']);
    }

    /**
     * Libère les dates d'une réservation annulée
     */
    public static function releaseBookingDates(int $bookingId): int
    {
        return self::where('booking_id', $bookingId)->delete();
    }

    /**
     * Récupère le calendrier pour une période
     */
    public static function getCalendar(int $residenceId, Carbon $startDate, Carbon $endDate): Collection
    {
        $existingDates = self::where('residence_id', $residenceId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));

        $calendar = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateKey = $current->format('Y-m-d');
            $existing = $existingDates->get($dateKey);

            $calendar->push([
                'date' => $dateKey,
                'status' => $existing?->status ?? self::STATUS_AVAILABLE,
                'custom_price' => $existing?->custom_price,
                'min_nights' => $existing?->min_nights,
                'note' => $existing?->note,
                'booking_id' => $existing?->booking_id,
            ]);

            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Définit un prix personnalisé pour des dates
     */
    public static function setCustomPrice(int $residenceId, Carbon $startDate, Carbon $endDate, float $price): int
    {
        $dates = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dates[] = [
                'residence_id' => $residenceId,
                'date' => $current->format('Y-m-d'),
                'status' => self::STATUS_AVAILABLE,
                'custom_price' => $price,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $current->addDay();
        }

        return self::upsert($dates, ['residence_id', 'date'], ['custom_price', 'updated_at']);
    }

    /**
     * Calcule le prix total pour une période
     */
    public static function calculateTotalPrice(Residence $residence, Carbon $checkIn, Carbon $checkOut): array
    {
        $nights = $checkIn->diffInDays($checkOut);
        $calendar = self::getCalendar($residence->id, $checkIn, $checkOut->subDay());
        $seasonalPricing = SeasonalPricing::getActiveForPeriod($residence->id, $checkIn, $checkOut);

        $totalPrice = 0;
        $priceBreakdown = [];

        foreach ($calendar as $day) {
            $date = Carbon::parse($day['date']);
            $dayPrice = $day['custom_price'];

            if (!$dayPrice) {
                // Vérifier prix saisonnier
                $seasonal = $seasonalPricing->first(fn($s) => 
                    $date->between($s->start_date, $s->end_date)
                );

                if ($seasonal) {
                    $dayPrice = $seasonal->price_per_day ?? ($residence->price_per_day * $seasonal->price_multiplier);
                } else {
                    $dayPrice = $residence->price_per_day;
                }
            }

            $totalPrice += $dayPrice;
            $priceBreakdown[] = [
                'date' => $day['date'],
                'price' => $dayPrice,
            ];
        }

        return [
            'nights' => $nights,
            'total_price' => $totalPrice,
            'average_per_night' => $nights > 0 ? $totalPrice / $nights : 0,
            'breakdown' => $priceBreakdown,
        ];
    }
}
