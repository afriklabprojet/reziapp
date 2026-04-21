<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'residence_id',
        'check_in_start',
        'check_in_end',
        'check_out_time',
        'flexible_check_in',
        'early_check_in_fee',
        'late_check_out_fee',
    ];

    protected $casts = [
        'check_in_start' => 'string', // Format H:i
        'check_in_end' => 'string',
        'check_out_time' => 'string',
        'flexible_check_in' => 'boolean',
        'early_check_in_fee' => 'decimal:2',
        'late_check_out_fee' => 'decimal:2',
    ];

    // Relations
    public function residence()
    {
        return $this->belongsTo(Residence::class);
    }

    // Methods
    public static function getForResidence(int $residenceId): ?self
    {
        return self::where('residence_id', $residenceId)->first();
    }

    public function getDefaultCheckInTime(): string
    {
        return $this->check_in_from ?? '14:00';
    }

    public function getDefaultCheckOutTime(): string
    {
        return $this->check_out_until ?? '11:00';
    }

    public function isValidCheckInTime(string $time): bool
    {
        $timeInt = (int) str_replace(':', '', $time);
        $fromInt = (int) str_replace(':', '', $this->check_in_from);
        $untilInt = (int) str_replace(':', '', $this->check_in_until);

        return $timeInt >= $fromInt && $timeInt <= $untilInt;
    }

    public function isEarlyCheckIn(string $time): bool
    {
        $timeInt = (int) str_replace(':', '', $time);
        $fromInt = (int) str_replace(':', '', $this->check_in_from);

        return $timeInt < $fromInt;
    }

    public function isLateCheckOut(string $time): bool
    {
        $timeInt = (int) str_replace(':', '', $time);
        $untilInt = (int) str_replace(':', '', $this->check_out_until);

        return $timeInt > $untilInt;
    }

    public function calculateEarlyCheckInFee(string $requestedTime): float
    {
        if (!$this->early_check_in_fee || !$this->isEarlyCheckIn($requestedTime)) {
            return 0;
        }

        return $this->early_check_in_fee;
    }

    public function calculateLateCheckOutFee(string $requestedTime): float
    {
        if (!$this->late_check_out_fee || !$this->isLateCheckOut($requestedTime)) {
            return 0;
        }

        return $this->late_check_out_fee;
    }

    // Helpers
    public function getCheckInWindow(): string
    {
        return $this->check_in_from.' - '.$this->check_in_until;
    }

    public function getAvailableSlots(): array
    {
        $slots = [];
        $from = (int) substr($this->check_in_from, 0, 2);
        $until = (int) substr($this->check_in_until, 0, 2);

        for ($hour = $from; $hour <= $until; $hour++) {
            $slots[] = sprintf('%02d:00', $hour);
            if ($hour < $until) {
                $slots[] = sprintf('%02d:30', $hour);
            }
        }

        return $slots;
    }
}
