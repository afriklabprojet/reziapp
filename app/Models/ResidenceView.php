<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidenceView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'residence_id',
        'ip_address',
        'user_agent',
        'referer',
        'source',
        'duration_seconds',
        'contacted',
        'favorited',
        'shared',
    ];

    protected $casts = [
        'contacted' => 'boolean',
        'favorited' => 'boolean',
        'shared' => 'boolean',
    ];

    /**
     * Utilisateur qui a visité (peut être null pour visiteurs anonymes)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Résidence visitée
     */
    public function residence(): BelongsTo
    {
        return $this->belongsTo(Residence::class);
    }

    /**
     * Scope pour les visites authentifiées
     */
    public function scopeAuthenticated($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope pour les visites récentes
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope par source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Enregistrer une nouvelle visite
     */
    public static function recordView(int $residenceId, ?int $userId = null, string $source = 'direct', array $extra = []): self
    {
        return static::create(array_merge([
            'residence_id' => $residenceId,
            'user_id' => $userId,
            'source' => $source,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
        ], $extra));
    }

    /**
     * Vérifier si une visite existe déjà récemment (pour éviter les doublons)
     */
    public static function hasRecentView(int $residenceId, ?int $userId = null, int $minutes = 30): bool
    {
        $query = static::where('residence_id', $residenceId)
            ->where('created_at', '>=', now()->subMinutes($minutes));

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', request()->ip());
        }

        return $query->exists();
    }
}
