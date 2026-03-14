<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnerDocument extends Model
{
    use SoftDeletes;

    const CAT_TITLE_DEED = 'title_deed';
    const CAT_PERMIT     = 'permit';
    const CAT_INSURANCE  = 'insurance';
    const CAT_TAX        = 'tax';
    const CAT_CONTRACT   = 'contract';
    const CAT_OTHER      = 'other';

    const CATEGORIES = [
        self::CAT_TITLE_DEED => 'Titre foncier',
        self::CAT_PERMIT     => 'Permis / Autorisation',
        self::CAT_INSURANCE  => 'Assurance',
        self::CAT_TAX        => 'Document fiscal',
        self::CAT_CONTRACT   => 'Contrat',
        self::CAT_OTHER      => 'Autre',
    ];

    protected $fillable = [
        'owner_id', 'residence_id', 'category', 'name', 'file_path',
        'file_type', 'file_size', 'expiry_date', 'expiry_notified',
        'notes', 'shared_with',
    ];

    protected $casts = [
        'expiry_date'    => 'date',
        'expiry_notified' => 'boolean',
        'shared_with'    => 'array',
        'file_size'      => 'integer',
    ];

    // ===== RELATIONS =====

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    // ===== ACCESSORS =====

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) return '—';
        if ($this->file_size < 1024) return $this->file_size . ' o';
        if ($this->file_size < 1048576) return round($this->file_size / 1024, 1) . ' Ko';
        return round($this->file_size / 1048576, 1) . ' Mo';
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date
            && $this->expiry_date->isFuture()
            && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    // ===== SCOPES =====

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeExpiring($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
                     ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
                     ->where('expiry_date', '<', now());
    }
}
