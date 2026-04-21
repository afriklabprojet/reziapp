<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ComparisonList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'residence_ids',
        'share_token',
    ];

    protected $casts = [
        'residence_ids' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($list) {
            if (empty($list->share_token)) {
                $list->share_token = Str::random(24);
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Methods
    public function getResidences()
    {
        return Residence::whereIn('id', $this->residence_ids)->get();
    }

    public function addResidence(int $residenceId): void
    {
        $ids = $this->residence_ids ?? [];
        if (!in_array($residenceId, $ids)) {
            $ids[] = $residenceId;
            $this->update(['residence_ids' => $ids]);
        }
    }

    public function removeResidence(int $residenceId): void
    {
        $ids = $this->residence_ids ?? [];
        $ids = array_values(array_filter($ids, fn ($id) => $id !== $residenceId));
        $this->update(['residence_ids' => $ids]);
    }

    public function hasResidence(int $residenceId): bool
    {
        return in_array($residenceId, $this->residence_ids ?? []);
    }

    public function getCount(): int
    {
        return count($this->residence_ids ?? []);
    }

    public function getShareUrl(): string
    {
        return route('compare.shared', $this->share_token);
    }

    public function regenerateShareToken(): void
    {
        $this->update(['share_token' => Str::random(24)]);
    }

    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            ['name' => 'Ma comparaison', 'residence_ids' => []],
        );
    }
}
