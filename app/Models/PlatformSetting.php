<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, $value): void
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            if ($setting->type === 'json' && is_array($value)) {
                $value = json_encode($value);
            }

            $setting->update(['value' => $value]);
            Cache::forget("setting.{$key}");
        }
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => (bool) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($setting) => [
                $setting->key => static::castValue($setting->value, $setting->type),
            ])
            ->toArray();
    }

    /**
     * Get commission rate
     */
    public static function getCommissionRate(): float
    {
        return static::getValue('commission_rate', 10);
    }

    /**
     * Calculate platform commission
     */
    public static function calculateCommission(float $amount): float
    {
        $rate = static::getCommissionRate();
        $min = static::getValue('commission_min', 1000);

        $commission = $amount * ($rate / 100);

        return max($commission, $min);
    }

    /**
     * Get public settings for frontend
     */
    public static function getPublicSettings(): array
    {
        return Cache::remember('public_settings', 3600, function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(fn ($setting) => [
                    $setting->key => static::castValue($setting->value, $setting->type),
                ])
                ->toArray();
        });
    }
}
