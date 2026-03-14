<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'user_agent',
        'device_name',
    ];

    protected $hidden = [
        'public_key',
        'auth_token',
    ];

    /**
     * L'utilisateur associé
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir les clés pour Web Push
     */
    public function getKeys(): array
    {
        return [
            'p256dh' => $this->public_key,
            'auth' => $this->auth_token,
        ];
    }

    /**
     * Obtenir la configuration complète pour Web Push
     */
    public function getPushSubscription(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => $this->getKeys(),
        ];
    }

    /**
     * Générer un nom de device à partir du user agent
     */
    public static function parseDeviceName(string $userAgent): string
    {
        // Détection mobile
        if (preg_match('/iPhone/', $userAgent)) {
            return 'iPhone';
        }
        if (preg_match('/iPad/', $userAgent)) {
            return 'iPad';
        }
        if (preg_match('/Android/', $userAgent)) {
            if (preg_match('/Mobile/', $userAgent)) {
                return 'Android Mobile';
            }

            return 'Android Tablet';
        }

        // Détection navigateur desktop
        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome Desktop';
        }
        if (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox Desktop';
        }
        if (preg_match('/Safari/', $userAgent)) {
            return 'Safari Desktop';
        }
        if (preg_match('/Edge/', $userAgent)) {
            return 'Edge Desktop';
        }

        return 'Navigateur inconnu';
    }

    /**
     * Scope pour un utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope par endpoint
     */
    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', $endpoint);
    }

    /**
     * Vérifier si l'endpoint existe déjà pour l'utilisateur
     */
    public static function existsForUser(int $userId, string $endpoint): bool
    {
        return self::where('user_id', $userId)
            ->where('endpoint', $endpoint)
            ->exists();
    }

    /**
     * Mettre à jour ou créer une subscription
     */
    public static function updateOrCreateForUser(int $userId, array $data): self
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'endpoint' => $data['endpoint'],
            ],
            [
                'public_key' => $data['keys']['p256dh'] ?? $data['public_key'],
                'auth_token' => $data['keys']['auth'] ?? $data['auth_token'],
                'user_agent' => $data['user_agent'] ?? request()->userAgent(),
                'device_name' => $data['device_name'] ?? self::parseDeviceName(request()->userAgent()),
            ],
        );
    }
}
